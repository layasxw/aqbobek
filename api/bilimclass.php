<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$student_id = $_GET['student_id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$token || $token !== 'mock_token_aqbobek') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid token']);
    exit();
}

$mock_data = [
    '1' => [
        'student_id' => 1,
        'full_name' => 'Алиев Тимур',
        'class' => '10А',
        'grades' => [
            ['subject' => 'Математика', 'score' => 85, 'type' => 'СОЧ', 'topic' => 'Тригонометрия', 'date' => '2025-02-10'],
            ['subject' => 'Математика', 'score' => 72, 'type' => 'СОР', 'topic' => 'Производная', 'date' => '2025-02-20'],
            ['subject' => 'Физика',     'score' => 45, 'type' => 'СОЧ', 'topic' => 'Электромагнетизм', 'date' => '2025-02-12'],
            ['subject' => 'Физика',     'score' => 50, 'type' => 'СОР', 'topic' => 'Оптика', 'date' => '2025-02-25'],
            ['subject' => 'История',    'score' => 90, 'type' => 'СОЧ', 'topic' => 'ВОВ', 'date' => '2025-02-15'],
        ]
    ],
    '2' => [
        'student_id' => 2,
        'full_name' => 'Бекова Айгерим',
        'class' => '10А',
        'grades' => [
            ['subject' => 'Математика', 'score' => 95, 'type' => 'СОЧ', 'topic' => 'Логарифмы', 'date' => '2025-02-10'],
            ['subject' => 'Химия',      'score' => 88, 'type' => 'СОР', 'topic' => 'Реакции', 'date' => '2025-02-18'],
            ['subject' => 'Физика',     'score' => 76, 'type' => 'СОЧ', 'topic' => 'Механика', 'date' => '2025-02-12'],
        ]
    ],
];

if ($student_id) {
    if (isset($mock_data[$student_id])) {
        echo json_encode(['status' => 'ok', 'data' => $mock_data[$student_id]]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found']);
    }
} else {
    echo json_encode(['status' => 'ok', 'data' => array_values($mock_data)]);
}
?>