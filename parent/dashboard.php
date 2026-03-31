<?php
session_start();
require_once '../config/db.php';
require_once '../includes/schedule_widget.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../public/login.php");
    exit();
}

$parent_id = $_SESSION['id'];

$stmt = $conn->prepare("
    SELECT u.id, u.name, c.name as class_name
    FROM users u
    JOIN parent_student ps ON ps.student_id = u.id
    JOIN classes c ON c.id = u.class_id
    WHERE ps.parent_id = ?
");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$child = $stmt->get_result()->fetch_assoc();

if (!$child) {
    die("Ребёнок не привязан к аккаунту. Обратитесь к администратору.");
}

$child_id = $child['id'];

$stmt2 = $conn->prepare("
    SELECT s.name as subject, AVG(g.score) as avg_score
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    WHERE g.student_id = ?
    GROUP BY s.id, s.name
");
$stmt2->bind_param("i", $child_id);
$stmt2->execute();
$subject_avgs = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt3 = $conn->prepare("
    SELECT g.score, g.grade_type, g.date, g.topic, s.name as subject
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    WHERE g.student_id = ?
    ORDER BY g.date DESC
");
$stmt3->bind_param("i", $child_id);
$stmt3->execute();
$all_grades = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

$total_avg = count($subject_avgs) > 0
    ? round(array_sum(array_column($subject_avgs, 'avg_score')) / count($subject_avgs), 1)
    : 0;

 
$stmt4 = $conn->prepare("
    SELECT title, date FROM achievements
    WHERE student_id = ?
    ORDER BY date DESC
");
$stmt4->bind_param("i", $child_id);
$stmt4->execute();
$achievements = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);

 
$events = $conn->query("
    SELECT title, event_date FROM events
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

 
$news = $conn->query("
    SELECT title, body, created_at FROM news
    ORDER BY created_at DESC LIMIT 3
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Родитель</title>
    <link rel="stylesheet" href="https:cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https:fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
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
            <a href="#" class="nav-btn" data-target="section-grades">
                <i class="fas fa-chart-line"></i> Оценки
            </a>
            <a href="#" class="nav-btn" data-target="section-achievements">
                <i class="fas fa-trophy"></i> Достижения
            </a>
            <a href="#" class="nav-btn" data-target="section-events">
                <i class="fas fa-calendar"></i> События
            </a>
            <a href="#" class="nav-btn" data-target="section-ai">
                <i class="fas fa-robot"></i> AI Выжимка
            </a>
            <a href="#" class="nav-btn" data-target="section-schedule">
                <i class="fas fa-calendar-alt"></i> Расписание
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
                <p class="role-text">Родитель</p>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 id="pageTitle">Дашборд</h1>
            <div class="date-now"><?= date('d F Y') ?></div>
        </header>

        <section id="section-home" class="content-section active">
            <div class="glass-card full-width" style="margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:20px;">
                    <div class="avatar" style="width:60px; height:60px; border-radius:50%; 
                                               background:linear-gradient(135deg, var(--primary), var(--secondary));">
                    </div>
                    <div>
                        <h2 style="margin:0"><?= htmlspecialchars($child['name']) ?></h2>
                        <p style="color:var(--text-dim); margin:5px 0 0">
                            <?= htmlspecialchars($child['class_name']) ?> класс
                        </p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="glass-card">
                    <h3>Средний балл</h3>
                    <div class="big-number"><?= $total_avg ?></div>
                </div>
                <div class="glass-card">
                    <h3>Предметов</h3>
                    <div class="big-number"><?= count($subject_avgs) ?></div>
                </div>
                <div class="glass-card">
                    <h3>Достижений</h3>
                    <div class="big-number"><?= count($achievements) ?></div>
                </div>
                <div class="glass-card">
                    <h3>Событий</h3>
                    <div class="big-number"><?= count($events) ?></div>
                </div>
            </div>

            <div class="glass-card full-width" style="margin-top:20px;">
                <h3>📰 Новости школы</h3>
                <?php foreach ($news as $n): ?>
                    <div class="event-item" style="flex-direction:column; align-items:flex-start;">
                        <strong><?= htmlspecialchars($n['title']) ?></strong>
                        <p style="color:var(--text-dim); margin:5px 0 0; font-size:0.9rem;">
                            <?= htmlspecialchars($n['body']) ?>
                        </p>
                        <span style="color:var(--text-dim); font-size:0.8rem; margin-top:5px;">
                            <?= date('d M Y', strtotime($n['created_at'])) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="glass-card full-width" style="margin-top:10px;">
                <h3>📅 Расписание (быстрый просмотр)</h3>
                <?php aq_render_schedule_widget($conn, 'class', $child['class_name']); ?>
            </div>
        </section>

        <section id="section-grades" class="content-section">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-grades-avg">По предметам</button>
                <button class="sub-nav-btn" data-sub="sub-grades-all">Все оценки</button>
            </div>
            <div id="sub-grades-avg" class="sub-section active">
                <div class="glass-card full-width">
                    <?php foreach ($subject_avgs as $row):
                        $avg = round($row['avg_score']);
                        $bg = $avg >= 80 ? 'mark-5-bg' : ($avg >= 65 ? 'mark-4-bg' : 'mark-3-bg');
                        $cl = $avg >= 80 ? 'mark-5' : ($avg >= 65 ? 'mark-4' : 'mark-3');
                        $label = $avg >= 80 ? '5' : ($avg >= 65 ? '4' : '3');
                    ?>
                        <div class="subject-row">
                            <span><?= htmlspecialchars($row['subject']) ?></span>
                            <div class="progress-line">
                                <div class="fill <?= $bg ?>" style="width:<?= $avg ?>%"></div>
                            </div>
                            <span class="mark <?= $cl ?>"><?= $label ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="sub-grades-all" class="sub-section">
                <div class="glass-card full-width">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="color:var(--text-dim); text-align:left;">
                                <th style="padding:12px;">Предмет</th>
                                <th style="padding:12px;">Тема</th>
                                <th style="padding:12px;">Тип</th>
                                <th style="padding:12px;">Балл</th>
                                <th style="padding:12px;">Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_grades as $g):
                                $color = $g['score'] >= 80 ? '#00e676' : ($g['score'] >= 65 ? '#ffea00' : '#ff5252');
                            ?>
                                <tr style="border-top:1px solid rgba(255,255,255,0.05);">
                                    <td style="padding:12px;"><?= htmlspecialchars($g['subject']) ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars($g['topic'] ?? '—') ?></td>
                                    <td style="padding:12px;"><?= htmlspecialchars($g['grade_type'] ?? '—') ?></td>
                                    <td style="padding:12px; color:<?= $color ?>; font-weight:700;">
                                        <?= $g['score'] ?>
                                    </td>
                                    <td style="padding:12px;"><?= $g['date'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="section-achievements" class="content-section">
            <div class="glass-card full-width">
                <h3>🏆 Достижения <?= htmlspecialchars($child['name']) ?></h3>
                <?php if (empty($achievements)): ?>
                    <p style="color:var(--text-dim); margin-top:15px;">Достижений пока нет</p>
                <?php else: ?>
                    <?php foreach ($achievements as $a): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <?= date('d M', strtotime($a['date'])) ?>
                            </div>
                            <div class="event-info">
                                <strong><?= htmlspecialchars($a['title']) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="section-events" class="content-section">
            <div class="glass-card full-width">
                <h3>📅 Ближайшие события</h3>
                <?php if (empty($events)): ?>
                    <p style="color:var(--text-dim); margin-top:15px;">Событий нет</p>
                <?php else: ?>
                    <?php foreach ($events as $e): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <?= date('d M', strtotime($e['event_date'])) ?>
                            </div>
                            <div class="event-info">
                                <strong><?= htmlspecialchars($e['title']) ?></strong>
                                <span><?= date('H:i', strtotime($e['event_date'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="section-ai" class="content-section">
            <div class="glass-card full-width">
                <h3>🤖 AI Выжимка за неделю</h3>
                <p style="color:var(--text-dim); margin:15px 0;">
                    AI проанализирует успеваемость ребёнка и даст краткий отчёт
                </p>
                <button id="getAdvice" class="btn-primary" style="width:auto; padding:12px 30px;">
                    Получить выжимку
                </button>
                <div id="adviceResult" style="margin-top:20px; line-height:1.8;"></div>
            </div>
        </section>

        <section id="section-schedule" class="content-section">
            <div class="glass-card full-width">
                <h3>📅 Расписание класса <?= htmlspecialchars($child['class_name']) ?></h3>
                <?php aq_render_schedule_widget($conn, 'class', $child['class_name']); ?>
            </div>
        </section>

    </main>
</div>

<script src="../script.js"></script>
<script>
document.getElementById('getAdvice').addEventListener('click', async () => {
    const btn = document.getElementById('getAdvice');
    const result = document.getElementById('adviceResult');
    btn.disabled = true;
    btn.innerText = 'Анализирую...';
    result.innerHTML = '<p style="color:var(--text-dim)">Подождите...</p>';

    const response = await fetch('../ai/parent_advice.php?child_id=<?= $child_id ?>');
    const data = await response.json();

    result.innerHTML = `<div class="glass-card" style="margin-top:10px; line-height:1.8;">
        ${data.report.replace(/\n/g, '<br>')}
    </div>`;
    btn.disabled = false;
    btn.innerText = 'Получить выжимку';
});
</script>
</body>
</html>