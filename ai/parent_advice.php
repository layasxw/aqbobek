<?php
session_start();

require_once '../config/db.php';
require_once __DIR__ . '/../config.php';

$apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit();
}

$parent_id = $_SESSION['id'];
$child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;

if (!$child_id) {
    http_response_code(400);
    echo json_encode(['error' => 'child_id не указан']);
    exit();
}

$stmt = $conn->prepare("
    SELECT student_id FROM parent_student
    WHERE parent_id = ? AND student_id = ?
");
$stmt->bind_param("ii", $parent_id, $child_id);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows == 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit();
}

$stmt2 = $conn->prepare("
    SELECT g.score, g.date, s.name as subject_name 
    FROM grades g 
    JOIN subjects s ON s.id = g.subject_id
    WHERE g.student_id = ?
");
$stmt2->bind_param("i", $child_id);
$stmt2->execute();
$grades = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$scores = array_column($grades, 'score');
$avg = round(array_sum($scores) / count($scores), 1); 

foreach ($grades as $g) {
    $subjectScores[$g['subject_name']][] = $g['score'];
}

foreach ($subjectScores as $name => $s) {
    $subAvg = array_sum($s) / count($s);
    if ($subAvg < 60) $riskSubjects[] = $name . ' (' . round($subAvg, 1) . ')';
}
$riskCount = count($riskSubjects ?? []);
$half = (int)(count($scores) / 2);
$firstHalf  = round(array_sum(array_slice($scores, 0, $half)) / $half, 1);
$secondHalf = round(array_sum(array_slice($scores, $half)) / $half, 1);
$trend = $secondHalf - $firstHalf;
$trendText = $trend > 2 ? 'растёт' : ($trend < -2 ? 'падает' : 'стабильна');

$riskList = empty($riskSubjects) ? 'нет' : implode(', ', $riskSubjects);
$prompt = "Ты школьный психолог и аналитик. Напиши короткую выжимку для родителя об успеваемости его ребёнка за последнее время.

- Средний балл класса: $avg
- Успеваемость: $trendText (изменение: $trend пунктов)
- Предметов в зоне риска (средний < 60): $riskCount
- СЛабые предметы: $riskList

Пиши простым языком, без сложных терминов, как будто разговариваешь с родителем лично.
Не используй сухие цифры в тексте — интерпретируй их человечно.
Объём: 4 предложения максимум.";



$body = json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [
        ['role' => 'user', 'content' => $prompt]
    ]
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
$report = $result['choices'][0]['message']['content'] ?? 'Не удалось получить отчёт.';

error_log(print_r($result, true));

header('Content-Type: application/json');



$result = json_decode($response, true);

$report = $result['choices'][0]['message']['content'];
echo json_encode(['report' => $report]);

?>