<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit();
}

$student_id = $_SESSION['id'];

// Оценки ученика по темам
$stmt = $conn->prepare("
    SELECT g.topic, g.score, g.subject_id, s.name as subject_name
    FROM grades g
    JOIN subjects s ON s.id = g.subject_id
    WHERE g.student_id = ? AND g.topic IS NOT NULL AND g.topic != ''
    ORDER BY g.subject_id, g.date ASC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Средний балл по каждой теме
$topic_scores = [];
foreach ($grades as $g) {
    $key = $g['subject_id'] . '||' . $g['topic'];
    if (!isset($topic_scores[$key])) {
        $topic_scores[$key] = [
            'topic'        => $g['topic'],
            'subject_id'   => $g['subject_id'],
            'subject_name' => $g['subject_name'],
            'scores'       => [],
        ];
    }
    $topic_scores[$key]['scores'][] = $g['score'];
}

$nodes = [];
foreach ($topic_scores as $key => $data) {
    $avg = round(array_sum($data['scores']) / count($data['scores']), 1);
    $nodes[] = [
        'id'           => $key,
        'topic'        => $data['topic'],
        'subject_id'   => (int)$data['subject_id'],
        'subject_name' => $data['subject_name'],
        'avg'          => $avg,
        'count'        => count($data['scores']),
        'level'        => $avg >= 80 ? 'good' : ($avg >= 60 ? 'ok' : 'weak'),
    ];
}

// Зависимости между темами (хардкод по предметам)
$topic_deps = [
    // Математика (subject_id=1)
    'Квадратные уравнения' => ['Тригонометрия'],
    'Тригонометрия'        => ['Производная'],
    'Производная'          => [],

    // Физика (subject_id=2)
    'Термодинамика'        => ['Идеальный газ'],
    'Идеальный газ'        => ['Молекулярная физика'],
    'Молекулярная физика'  => [],

    // Английский (subject_id=3)
    'Reading'  => ['Writing'],
    'Writing'  => ['Grammar'],
    'Grammar'  => [],

    // Информатика (subject_id=4)
    'Алгоритмы'  => ['Базы данных'],
    'Базы данных' => ['ООП'],
    'ООП'        => [],
];

// Строим рёбра только между существующими узлами
$node_ids = array_column($nodes, 'id', 'topic');
$edges = [];
$seen_edges = [];
foreach ($nodes as $n) {
    $deps = $topic_deps[$n['topic']] ?? [];
    foreach ($deps as $dep_topic) {
        // Ищем зависимость в том же предмете
        $dep_key = $n['subject_id'] . '||' . $dep_topic;
        if (isset($topic_scores[$dep_key])) {
            $edge_key = $n['id'] . '->' . $dep_key;
            if (!isset($seen_edges[$edge_key])) {
                $edges[] = ['from' => $n['id'], 'to' => $dep_key];
                $seen_edges[$edge_key] = true;
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'nodes' => array_values($nodes),
    'edges' => $edges,
]);
?>