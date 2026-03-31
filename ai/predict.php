<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../config.php';


$apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');
$url = 'https:api.groq.com/openai/v1/chat/completions';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit();
}

$student_id = $_SESSION['id'];
$subject_name = trim($_POST['subject'] ?? '');

if (!$subject_name) {
    echo json_encode(['error' => 'Предмет не указан']);
    exit();
}

$stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ?");
$stmt->bind_param("s", $subject_name);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    echo json_encode(['prediction' => 'Предмет не найден.']);
    exit();
}
$subject_id = $subject['id'];

$stmt2 = $conn->prepare("
    SELECT score, grade_type, topic, date
    FROM grades
    WHERE student_id = ? AND subject_id = ?
    ORDER BY date ASC
");
$stmt2->bind_param("ii", $student_id, $subject_id);
$stmt2->execute();
$grades = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

if (count($grades) < 2) {
    echo json_encode(['prediction' => 'Недостаточно данных для анализа. Нужно минимум 2 оценки по этому предмету.']);
    exit();
}


$scores = array_column($grades, 'score');
$n = count($scores);

$avg = array_sum($scores) / $n;

$sum_x = 0; $sum_y = 0; $sum_xy = 0; $sum_x2 = 0;
for ($i = 0; $i < $n; $i++) {
    $sum_x  += $i;
    $sum_y  += $scores[$i];
    $sum_xy += $i * $scores[$i];
    $sum_x2 += $i * $i;
}
$denom = $n * $sum_x2 - $sum_x * $sum_x;
$slope = $denom != 0 ? ($n * $sum_xy - $sum_x * $sum_y) / $denom : 0;

$predicted_raw = $avg + $slope * ($n - ($n - 1) / 2);
$predicted = max(0, min(100, round($predicted_raw)));

if ($slope > 1.5)       $trend_text = 'уверенно растёт';
elseif ($slope > 0.3)   $trend_text = 'слегка растёт';
elseif ($slope < -1.5)  $trend_text = 'заметно падает';
elseif ($slope < -0.3)  $trend_text = 'слегка падает';
else                    $trend_text = 'стабильна';

$variance = 0;
foreach ($scores as $s) {
    $variance += ($s - $avg) ** 2;
}
$std_dev = $n > 1 ? sqrt($variance / ($n - 1)) : 10;

$z = $std_dev > 0 ? ($predicted - 50) / $std_dev : 3;
$fail_prob = round((1 - normalCDF($z)) * 100);
$fail_prob = max(0, min(99, $fail_prob));
function normalCDF(float $z): float {
    $t = 1.0 / (1.0 + 0.2316419 * abs($z));
    $d = 0.3989423 * exp(-$z * $z / 2);
    $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.7814779 + $t * (-1.8212560 + $t * 1.3302744))));
    return $z > 0 ? 1 - $p : $p;
}


$weak_topics = [];
foreach ($grades as $g) {
    if ($g['score'] < 60 && $g['topic']) {
        $weak_topics[] = $g['topic'] . ' (' . $g['score'] . ')';
    }
}

$soch_grades = array_values(array_filter($grades, fn($g) => $g['grade_type'] === 'СОЧ'));
$soch_avg = count($soch_grades) > 0
    ? round(array_sum(array_column($soch_grades, 'score')) / count($soch_grades), 1)
    : null;

$weak_list = empty($weak_topics) ? 'нет явных пробелов' : implode(', ', $weak_topics);
$soch_info = $soch_avg !== null ? "Средний балл на СОЧ: $soch_avg" : "Оценок СОЧ пока нет";
$scores_str = implode(', ', $scores);

$prompt = "Ты AI-наставник школьника. Проанализируй успеваемость по предмету «{$subject_name}».

Данные (НЕ ПОВТОРЯЙ их в ответе):
- Оценки по порядку: [{$scores_str}]
- Средний балл: " . round($avg, 1) . "
- Тренд: {$trend_text} (наклон: " . round($slope, 2) . " баллов за оценку)
- {$soch_info}
- Слабые темы: {$weak_list}
- Алгоритм предсказывает следующий результат: {$predicted} баллов
- Расчётная вероятность провала следующего СОЧ (< 50 баллов): {$fail_prob}%

Напиши ученику:
1. Одно предложение — вывод о ситуации (честно, без воды)
2. Одно предложение — конкретный прогноз на следующий СОЧ с вероятностью
3. 2-3 конкретных шага что делать прямо сейчас (со ссылками на темы)

Тон: честный, как старший товарищ. Максимум 6 предложений. Ответ на русском.";

$body = json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [
        ['role' => 'system', 'content' => 'Ты даёшь краткие, точные и полезные учебные советы школьникам.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.6,
    'max_tokens' => 400
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$prediction = $result['choices'][0]['message']['content'] ?? 'Не удалось получить анализ.';

header('Content-Type: application/json');
echo json_encode([
    'prediction'  => $prediction,
    'stats' => [
        'avg'        => round($avg, 1),
        'predicted'  => $predicted,
        'fail_prob'  => $fail_prob,
        'trend'      => $trend_text,
        'slope'      => round($slope, 2),
    ]
]);
?>