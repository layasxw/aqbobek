<?php
session_start();
require_once '../config/db.php';
require_once '../includes/schedule_widget.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../public/login.php");
    exit();
}

$teacher_id = $_SESSION['id'];

// --- КЛАССЫ учителя ---
$stmt = $conn->prepare("
    SELECT c.id as class_id, c.name as class_name
    FROM teacher_classes tc
    JOIN classes c ON c.id = tc.class_id
    WHERE tc.teacher_id = ?
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- ВСЕ УЧЕНИКИ ---
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.class_id, c.name as class_name,
           AVG(g.score) as avg_score
    FROM users u
    JOIN classes c ON c.id = u.class_id
    LEFT JOIN grades g ON g.student_id = u.id
    WHERE u.role = 'student'
      AND u.class_id IN (
          SELECT class_id FROM teacher_classes WHERE teacher_id = ?
      )
    GROUP BY u.id, u.name, u.class_id, c.name
    ORDER BY avg_score ASC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- ЗОНА РИСКА (среднее < 60) ---
$risk_students = array_filter($students, fn($s) => $s['avg_score'] < 60 || $s['avg_score'] === null);

// --- ОБЩАЯ СТАТИСТИКА ---
$total_students = count($students);
$total_risk = count($risk_students);
$school_avg = $total_students > 0
    ? round(array_sum(array_column($students, 'avg_score')) / $total_students, 1)
    : 0;

// --- ВСЕ ОЦЕНКИ для выставления ---
$subjects = $conn->query("SELECT id, name FROM subjects")->fetch_all(MYSQLI_ASSOC);

// --- СОБЫТИЯ ---
$events = $conn->query("
    SELECT title, event_date FROM events
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// --- НОВОСТИ ---
$news = $conn->query("
    SELECT title, body, created_at FROM news
    ORDER BY created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// --- ДОБАВИТЬ ОЦЕНКУ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $score = $_POST['score'];
    $grade_type = $_POST['grade_type'];
    $topic = $_POST['topic'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, subject_id, score, grade_type, topic, recorded_by, date)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiissis", $student_id, $subject_id, $score, $grade_type, $topic, $teacher_id, $date);
    $stmt->execute();
    $success = "Оценка добавлена!";
}

// --- ДОБАВИТЬ ДОСТИЖЕНИЕ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_achievement'])) {
    $student_id = $_POST['student_id'];
    $title = $_POST['achievement_title'];
    $date = $_POST['achievement_date'];

    $stmt = $conn->prepare("
        INSERT INTO achievements (student_id, title, recorded_by, date)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isis", $student_id, $title, $teacher_id, $date);
    $stmt->execute();
    $success = "Достижение добавлено!";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Учитель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
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
            <a href="#" class="nav-btn active" data-target="section-home">
                <i class="fas fa-home"></i> Дашборд
            </a>
            <a href="#" class="nav-btn" data-target="section-class">
                <i class="fas fa-users"></i> Мой класс
            </a>
            <a href="#" class="nav-btn" data-target="section-risk">
                <i class="fas fa-exclamation-triangle"></i> Зона риска
            </a>
            <a href="grades.php" >
                <i class="fas fa-pen"></i> Журнал
            </a>
            <a href="#" class="nav-btn" data-target="section-achievements">
                <i class="fas fa-trophy"></i> Достижения
            </a>
            <a href="#" class="nav-btn" data-target="section-report">
                <i class="fas fa-robot"></i> AI Отчёт
            </a>
            <a href="#" class="nav-btn" data-target="section-schedule">
                <i class="fas fa-calendar"></i> Расписание
            </a>
            <div style="height:1px;background:rgba(255,255,255,0.1);margin:15px 0;"></div>
            <a href="../public/logout.php">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </a>
        </nav>
        <div class="user-block">
            <div class="avatar"></div>
            <div class="user-info">
                <p class="name"><?= htmlspecialchars($_SESSION['name']) ?></p>
                <p class="role-text">Учитель</p>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 id="pageTitle">Дашборд</h1>
            <div class="date-now"><?= date('d F Y') ?></div>
        </header>

        <?php if (isset($success)): ?>
            <div class="error-box" style="background:rgba(0,230,118,0.1); border-color:rgba(0,230,118,0.3); color:#00e676;">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <!-- ДАШБОРД -->
        <section id="section-home" class="content-section active">
            <div class="dashboard-grid">
                <div class="glass-card">
                    <h3>Всего учеников</h3>
                    <div class="big-number"><?= $total_students ?></div>
                </div>
                <div class="glass-card">
                    <h3>Зона риска</h3>
                    <div class="big-number" style="-webkit-text-fill-color:#ff5252">
                        <?= $total_risk ?>
                    </div>
                </div>
                <div class="glass-card">
                    <h3>Средний балл школы</h3>
                    <div class="big-number"><?= $school_avg ?></div>
                </div>
                <div class="glass-card">
                    <h3>Ближайших событий</h3>
                    <div class="big-number"><?= count($events) ?></div>
                </div>
            </div>

            <div class="glass-card full-width" style="margin-top:10px;">
                <h3>📅 Расписание (быстрый просмотр)</h3>
                <?php aq_render_schedule_widget($conn, 'teacher', $_SESSION['name']); ?>
            </div>
        </section>

        <!-- МОЙ КЛАСС -->
        <section id="section-class" class="content-section">
            <div class="glass-card full-width">
                <h3>👥 Все ученики</h3>
                <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                    <thead>
                        <tr style="color:var(--text-dim); text-align:left;">
                            <th style="padding:12px;">Имя</th>
                            <th style="padding:12px;">Класс</th>
                            <th style="padding:12px;">Средний балл</th>
                            <th style="padding:12px;">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s):
                            $avg = round($s['avg_score'] ?? 0, 1);
                            $color = $avg >= 80 ? '#00e676' : ($avg >= 60 ? '#ffea00' : '#ff5252');
                            $status = $avg >= 80 ? '✅ Хорошо' : ($avg >= 60 ? '⚠️ Средне' : '🔴 Риск');
                        ?>
                            <tr style="border-top:1px solid rgba(255,255,255,0.05);">
                                <td style="padding:12px;"><?= htmlspecialchars($s['name']) ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($s['class_name']) ?></td>
                                <td style="padding:12px; color:<?= $color ?>; font-weight:700;"><?= $avg ?></td>
                                <td style="padding:12px;"><?= $status ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ЗОНА РИСКА -->
        <section id="section-risk" class="content-section">
            <div class="glass-card full-width">
                <h3>⚠️ Ученики в зоне риска</h3>
                <?php if (empty($risk_students)): ?>
                    <p style="color:var(--text-dim); margin-top:20px;">Все ученики в норме 🎉</p>
                <?php else: ?>
                    <?php foreach ($risk_students as $s): ?>
                        <div class="risk-row pulse-red" style="margin-top:15px;">
                            <div class="student-info">
                                <strong><?= htmlspecialchars($s['name']) ?></strong>
                                <span><?= htmlspecialchars($s['class_name']) ?></span>
                            </div>
                            <div class="risk-reason">
                                Средний балл: <?= round($s['avg_score'] ?? 0, 1) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        

        <!-- ДОСТИЖЕНИЯ -->
        <section id="section-achievements" class="content-section">
            <div class="glass-card full-width">
                <h3>🏆 Добавить достижение</h3>
                <form method="POST" style="margin-top:20px;">
                    <input type="hidden" name="add_achievement" value="1">
                    <div class="form-group">
                        <label>Ученик</label>
                        <select name="student_id" required>
                            <option value="">— Выбери ученика —</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?> (<?= $s['class_name'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Достижение</label>
                        <input type="text" name="achievement_title" required
                               placeholder="например: 1 место — Олимпиада по математике">
                    </div>
                    <div class="form-group">
                        <label>Дата</label>
                        <input type="date" name="achievement_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="btn-primary" style="width:auto; padding:12px 30px;">
                        Добавить достижение
                    </button>
                </form>
            </div>
        </section>

        <!-- AI ОТЧЁТ -->
        <section id="section-report" class="content-section">
            <div class="glass-card full-width">
                <h3>🤖 AI Отчёт по классу</h3>
                <p style="color:var(--text-dim); margin:15px 0;">
                    Нажми кнопку — AI сгенерирует текстовый отчёт об успеваемости
                </p>
                <button id="generateReport" class="btn-primary" style="width:auto; padding:12px 30px;">
                    Сгенерировать отчёт
                </button>
                <div id="reportResult" style="margin-top:20px; line-height:1.8;"></div>
            </div>
        </section>

        <!-- РАСПИСАНИЕ -->
        <section id="section-schedule" class="content-section">
            <div class="glass-card full-width">
                <h3>📅 Расписание</h3>
                <?php aq_render_schedule_widget($conn, 'teacher', $_SESSION['name']); ?>
            </div>
        </section>

    </main>
</div>

<script src="../script.js"></script>
<script>
// AI отчёт
document.getElementById('generateReport').addEventListener('click', async () => {
    const btn = document.getElementById('generateReport');
    const result = document.getElementById('reportResult');
    btn.disabled = true;
    btn.innerText = 'Генерирую...';
    result.innerHTML = '<p style="color:var(--text-dim)">Подождите...</p>';

    const response = await fetch('../ai/report.php');
    const data = await response.json();

    result.innerHTML = `<div class="glass-card" style="margin-top:10px; line-height:1.8;">
        ${data.report.replace(/\n/g, '<br>')}
    </div>`;
    btn.disabled = false;
    btn.innerText = 'Сгенерировать отчёт';
});
</script>
</body>
</html>