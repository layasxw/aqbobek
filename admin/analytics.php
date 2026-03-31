<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

$subject_avgs = $conn->query("
    SELECT s.name as subject, ROUND(AVG(g.score),1) as avg_score, COUNT(g.id) as total_grades
    FROM grades g
    JOIN subjects s ON s.id = g.subject_id
    GROUP BY s.id, s.name
    ORDER BY avg_score DESC
")->fetch_all(MYSQLI_ASSOC);

$class_avgs = $conn->query("
    SELECT c.name as class_name, ROUND(AVG(g.score),1) as avg_score, COUNT(DISTINCT u.id) as cnt
    FROM classes c
    JOIN users u ON u.class_id = c.id AND u.role='student'
    LEFT JOIN grades g ON g.student_id = u.id
    GROUP BY c.id, c.name
    HAVING avg_score IS NOT NULL
    ORDER BY c.name ASC
")->fetch_all(MYSQLI_ASSOC);

$monthly = $conn->query("
    SELECT DATE_FORMAT(date, '%Y-%m') as month, ROUND(AVG(score),1) as avg_score
    FROM grades
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month ASC
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

$distribution = $conn->query("
    SELECT
        SUM(CASE WHEN score >= 85 THEN 1 ELSE 0 END) as five,
        SUM(CASE WHEN score >= 70 AND score < 85 THEN 1 ELSE 0 END) as four,
        SUM(CASE WHEN score >= 50 AND score < 70 THEN 1 ELSE 0 END) as three,
        SUM(CASE WHEN score < 50 THEN 1 ELSE 0 END) as two
    FROM grades
")->fetch_assoc();

$top_students = $conn->query("
    SELECT u.name, c.name as class_name, ROUND(AVG(g.score),1) as avg_score
    FROM users u
    JOIN classes c ON c.id = u.class_id
    JOIN grades g ON g.student_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id, u.name, c.name
    ORDER BY avg_score DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);


$falling = $conn->query("
    SELECT name, class_name,
        ROUND(recent_avg, 1) as recent_avg,
        ROUND(prev_avg, 1) as prev_avg
    FROM (
        SELECT u.name, c.name as class_name,
            AVG(CASE WHEN g.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN g.score END) as recent_avg,
            AVG(CASE WHEN g.date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND g.date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) THEN g.score END) as prev_avg
        FROM users u
        JOIN classes c ON c.id = u.class_id
        JOIN grades g ON g.student_id = u.id
        WHERE u.role = 'student'
        GROUP BY u.id, u.name, c.name
    ) sub
    WHERE recent_avg IS NOT NULL AND prev_avg IS NOT NULL AND recent_avg < prev_avg - 5
    ORDER BY (recent_avg - prev_avg) ASC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$stats = $conn->query("
    SELECT
        COUNT(DISTINCT CASE WHEN role='student' THEN id END) as students,
        COUNT(DISTINCT CASE WHEN role='teacher' THEN id END) as teachers
    FROM users
")->fetch_assoc();
$total_grades = $conn->query("SELECT COUNT(*) as cnt FROM grades")->fetch_assoc()['cnt'];
$school_avg = $conn->query("SELECT ROUND(AVG(score),1) as avg FROM grades")->fetch_assoc()['avg'] ?? 0;

$subjectLabels = json_encode(array_column($subject_avgs, 'subject'));
$subjectData   = json_encode(array_column($subject_avgs, 'avg_score'));
$classLabels   = json_encode(array_column($class_avgs, 'class_name'));
$classData     = json_encode(array_column($class_avgs, 'avg_score'));
$monthLabels   = json_encode(array_column($monthly, 'month'));
$monthData     = json_encode(array_column($monthly, 'avg_score'));
$distData      = json_encode([$distribution['five'], $distribution['four'], $distribution['three'], $distribution['two']]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Аналитика</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .analytics-grid .full { grid-column: 1 / -1; }
        .chart-wrap { position: relative; height: 220px; }
        .stat-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .stat-row:last-child { border-bottom: none; }
        .trend-badge {
            padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
        }
        .trend-down { background: rgba(255,82,82,0.15); color: #ff5252; }
        .trend-up   { background: rgba(0,230,118,0.15); color: #00e676; }
        .rank-num {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--glass); border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.9rem; flex-shrink: 0;
        }
        .rank-1 { background: rgba(255,215,0,0.2); color: #ffd700; border-color: #ffd700; }
        .rank-2 { background: rgba(192,192,192,0.2); color: #c0c0c0; border-color: #c0c0c0; }
        .rank-3 { background: rgba(205,127,50,0.2);  color: #cd7f32; border-color: #cd7f32; }
        .kpi-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;
        }
        .kpi-card {
            background: var(--glass); border: 1px solid var(--glass-border);
            border-radius: 18px; padding: 20px; text-align: center;
        }
        .kpi-card .kpi-val {
            font-size: 2rem; font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .kpi-card .kpi-label { color: var(--text-dim); font-size: 0.85rem; margin-top: 4px; }
    </style>
</head>
<body>
<div class="bg-blobs">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<div class="app-container">
    <aside class="sidebar">
        <div class="logo-area">
            <div class="logo-icon"><i class="fas fa-bolt"></i></div>
            <span>AQBOBEK</span>
        </div>
        <nav class="side-nav">
            <a href="dashboard.php" class="nav-btn">
                <i class="fas fa-home"></i> Дашборд
            </a>
            <a href="dashboard.php#section-radar" class="nav-btn">
                <i class="fas fa-satellite-dish"></i> Глобальный радар
            </a>
            <a href="dashboard.php#section-users" class="nav-btn">
                <i class="fas fa-users"></i> Пользователи
            </a>
            <a href="dashboard.php#section-achievements" class="nav-btn">
                <i class="fas fa-trophy"></i> Достижения
            </a>
            <a href="dashboard.php#section-events" class="nav-btn">
                <i class="fas fa-calendar-plus"></i> События
            </a>
            <a href="dashboard.php#section-news" class="nav-btn">
                <i class="fas fa-newspaper"></i> Новости
            </a>
            <a href="dashboard.php#section-schedule" class="nav-btn">
                <i class="fas fa-calendar-alt"></i> Расписание
            </a>
            <a href="analytics.php" class="nav-btn active">
                <i class="fas fa-chart-bar"></i> Аналитика
            </a>
            <div style="height:1px;background:rgba(255,255,255,0.1);margin:15px 0;"></div>
            <a href="../public/logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </a>
        </nav>
        <div class="user-block">
            <div class="avatar"></div>
            <div class="user-info">
                <p class="name"><?= htmlspecialchars($_SESSION['name']) ?></p>
                <p class="role-text">Администратор</p>
            </div>
        </div>
    </aside>

    <main class="main-content" style="overflow-y:auto;">
        <header class="top-bar">
            <h1>📊 Аналитика успеваемости</h1>
            <div class="date-now"><?= date('d F Y') ?></div>
        </header>

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-val"><?= $school_avg ?></div>
                <div class="kpi-label">Средний балл по школе</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val"><?= $stats['students'] ?></div>
                <div class="kpi-label">Учеников</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val"><?= $total_grades ?></div>
                <div class="kpi-label">Оценок в системе</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-val"><?= count($falling) ?></div>
                <div class="kpi-label">Падение успеваемости</div>
            </div>
        </div>

        <div class="analytics-grid">

            <div class="glass-card full">
                <h3>📈 Динамика среднего балла по месяцам</h3>
                <div class="chart-wrap" style="height:200px;margin-top:15px;">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>

            <div class="glass-card">
                <h3>📚 Средний балл по предметам</h3>
                <div class="chart-wrap" style="margin-top:15px;">
                    <canvas id="chartSubjects"></canvas>
                </div>
            </div>

            <div class="glass-card">
                <h3>🏫 Средний балл по классам</h3>
                <div class="chart-wrap" style="margin-top:15px;">
                    <canvas id="chartClasses"></canvas>
                </div>
            </div>

            <div class="glass-card">
                <h3>🎯 Распределение оценок</h3>
                <div class="chart-wrap" style="margin-top:15px;">
                    <canvas id="chartDist"></canvas>
                </div>
            </div>

            <div class="glass-card">
                <h3>🏆 Топ-5 учеников</h3>
                <div style="margin-top:15px;">
                    <?php foreach ($top_students as $i => $s): ?>
                    <div class="stat-row">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div class="rank-num rank-<?= $i+1 ?>"><?= $i+1 ?></div>
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></div>
                                <div style="color:var(--text-dim);font-size:0.82rem;"><?= htmlspecialchars($s['class_name']) ?></div>
                            </div>
                        </div>
                        <div style="font-weight:800;font-size:1.1rem;color:var(--green);"><?= $s['avg_score'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass-card full">
                <h3>⚠️ Аномальное падение успеваемости (последние 30 дней)</h3>
                <?php if (empty($falling)): ?>
                    <p style="color:var(--text-dim);margin-top:15px;">Значительного падения не выявлено 🎉</p>
                <?php else: ?>
                <div style="margin-top:15px;">
                    <?php foreach ($falling as $s): ?>
                    <div class="stat-row">
                        <div>
                            <div style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></div>
                            <div style="color:var(--text-dim);font-size:0.82rem;"><?= htmlspecialchars($s['class_name']) ?></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:15px;">
                            <div style="color:var(--text-dim);font-size:0.85rem;">
                                Было: <strong style="color:#fff;"><?= $s['prev_avg'] ?></strong>
                            </div>
                            <i class="fas fa-arrow-right" style="color:var(--text-dim);font-size:0.8rem;"></i>
                            <div style="color:var(--text-dim);font-size:0.85rem;">
                                Стало: <strong style="color:#ff5252;"><?= $s['recent_avg'] ?></strong>
                            </div>
                            <span class="trend-badge trend-down">
                                <?= round($s['recent_avg'] - $s['prev_avg'], 1) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script>
const chartDefaults = {
    color: 'rgba(255,255,255,0.7)',
    plugins: { legend: { labels: { color: 'rgba(255,255,255,0.7)' } } },
    scales: {
        x: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.05)' } },
        y: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.05)' } }
    }
};

new Chart(document.getElementById('chartMonthly'), {
    type: 'line',
    data: {
        labels: <?= $monthLabels ?>,
        datasets: [{
            label: 'Средний балл',
            data: <?= $monthData ?>,
            borderColor: '#6c5ce7',
            backgroundColor: 'rgba(108,92,231,0.15)',
            tension: 0.4, fill: true, pointBackgroundColor: '#6c5ce7', pointRadius: 5
        }]
    },
    options: { ...chartDefaults, maintainAspectRatio: false }
});

new Chart(document.getElementById('chartSubjects'), {
    type: 'bar',
    data: {
        labels: <?= $subjectLabels ?>,
        datasets: [{
            label: 'Средний балл',
            data: <?= $subjectData ?>,
            backgroundColor: 'rgba(108,92,231,0.7)',
            borderColor: '#6c5ce7',
            borderRadius: 8, borderWidth: 1
        }]
    },
    options: { ...chartDefaults, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chartClasses'), {
    type: 'bar',
    data: {
        labels: <?= $classLabels ?>,
        datasets: [{
            label: 'Средний балл',
            data: <?= $classData ?>,
            backgroundColor: 'rgba(232,67,147,0.7)',
            borderColor: '#e84393',
            borderRadius: 8, borderWidth: 1
        }]
    },
    options: { ...chartDefaults, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('chartDist'), {
    type: 'doughnut',
    data: {
        labels: ['Отлично (85+)', 'Хорошо (70-84)', 'Удовл. (50-69)', 'Неудовл. (<50)'],
        datasets: [{
            data: <?= $distData ?>,
            backgroundColor: ['rgba(0,230,118,0.8)', 'rgba(108,92,231,0.8)', 'rgba(255,234,0,0.8)', 'rgba(255,82,82,0.8)'],
            borderWidth: 0
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right', labels: { color: 'rgba(255,255,255,0.7)', padding: 12, font: { size: 11 } } } }
    }
});
</script>
</body>
</html>