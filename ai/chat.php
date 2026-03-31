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
$message = $_POST['message'] ?? '';

if (!$message) {
    echo json_encode(['error' => 'Сообщение пустое']);
    exit();
}

$stmt = $conn->prepare("
    SELECT g.score, g.topic, g.date, g.grade_type, s.name as subject
    FROM grades g
    JOIN subjects s ON s.id = g.subject_id
    WHERE g.student_id = ?
    ORDER BY g.score ASC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$weakTopics = [];
foreach ($grades as $g) {
    if ($g['score'] < 65 && $g['topic']) {
        $weakTopics[] = $g['subject'] . ' — ' . $g['topic'] . ' (' . $g['score'] . ' баллов)';
    }
}

$scores = array_column($grades, 'score');
$avg = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;

$weakList = empty($weakTopics) ? 'слабых тем нет' : implode(', ', $weakTopics);

$systemPrompt = "Ты личный AI-наставник школьника. 
Данные об ученике:
- Средний балл: $avg
- Слабые темы по оценкам: $weakList

Твоя задача — отвечать на вопросы ученика и помогать учиться.
Когда даёшь рекомендации:
1. Назови конкретную тему которую нужно повторить
2. Объясни КАК повторять — конкретные шаги (прочитай параграф X, реши задачи типа Y)
3. Укажи ГДЕ смотреть — YouTube каналы (Математика ЕГЭ, Физика Simply, Khan Academy), учебники (номер параграфа если знаешь), сайты (Яндекс Учебник, Учи.ру, Wolframalpha для математики)
4. Будь конкретным и дружелюбным, как старший друг а не учитель
5. Отвечай на русском, коротко — максимум 5-6 предложений";



$history = json_decode($_POST['history'] ?? '[]', true);

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $h) {
    $messages[] = $h;
}
$messages[] = ['role' => 'user', 'content' => $message];

$body = json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => $messages
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
$reply = $result['choices'][0]['message']['content'] ?? 'Не удалось получить ответ.';

header('Content-Type: application/json');
echo json_encode(['reply' => $reply]);
?>