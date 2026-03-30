<?php
session_start();
require_once '../config/db.php';
require_once __DIR__ . '/../config.php';

$apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit();
}

$teacher_id = $_SESSION['id'];

// 1. Предмет учителя
$stmt = $conn->prepare("
    SELECT s.id, s.name 
    FROM users u 
    JOIN subjects s ON s.id = u.subject_id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();

if (!$subject) {
    echo json_encode(['report' => 'Предмет не найден для этого учителя.']);
    exit();
}

// 2. Все оценки студентов из классов этого учителя
$stmt2 = $conn->prepare("
    SELECT u.name as student_name, g.score, g.date
    FROM grades g
    JOIN users u ON u.id = g.student_id
    WHERE g.subject_id = ?
      AND u.class_id IN (SELECT class_id FROM teacher_classes WHERE teacher_id = ?)
    ORDER BY g.date ASC
");
$stmt2->bind_param("ii", $subject['id'], $teacher_id);
$stmt2->execute();
$grades = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($grades)) {
    echo json_encode(['report' => 'Оценок пока нет.']);
    exit();
}

// --- СВОЯ АНАЛИТИКА ---

// Средний балл
$scores = array_column($grades, 'score');
$avg = round(array_sum($scores) / count($scores), 1);

// Зона риска — студенты со средним < 60
$studentScores = [];
foreach ($grades as $g) {
    $studentScores[$g['student_name']][] = $g['score'];
}
$riskStudents = [];
foreach ($studentScores as $name => $s) {
    $studentAvg = array_sum($s) / count($s);
    if ($studentAvg < 60) {
        $riskStudents[] = $name . ' (' . round($studentAvg, 1) . ')';
    }
}
$riskCount = count($riskStudents);

// Тренд — сравниваем первую половину оценок со второй
$half = (int)(count($scores) / 2);
$firstHalf = $half > 0 ? round(array_sum(array_slice($scores, 0, $half)) / $half, 1) : $avg;
$secondHalf = $half > 0 ? round(array_sum(array_slice($scores, $half)) / ($half ?: 1), 1) : $avg;
$trend = $secondHalf - $firstHalf;
$trendText = $trend > 2 ? 'растёт' : ($trend < -2 ? 'падает' : 'стабильна');

// --- ПРОМПТ ДЛЯ GEMINI ---
$riskList = empty($riskStudents) ? 'нет' : implode(', ', $riskStudents);
$prompt = "Ты школьный аналитик. Напиши короткий отчёт об успеваемости класса по предмету «{$subject['name']}».
Данные:
- Средний балл класса: $avg
- Успеваемость: $trendText (изменение: $trend пунктов)
- Учеников в зоне риска (средний < 60): $riskCount
- Кто в зоне риска: $riskList

Отчёт должен быть на русском, 3-4 предложения, конкретный, без воды. В конце дай 1-2 рекомендации учителю.";

// --- ЗАПРОС К GROQ ---


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