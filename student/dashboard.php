<?php
session_start();
require_once '../config/db.php';
require_once '../includes/schedule_widget.php';
require_once '../includes/gamification.php';


if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../public/login.php");
    exit();
}

$id = $_SESSION['id'];
$studentClass = $_SESSION['class_name'] ?? '';


aq_handle_goal_form($conn, $id);  
$gami = aq_get_gamification($conn, $id);

if ($studentClass === '') {
    $stmtClass = $conn->prepare("
        SELECT c.name as class_name
        FROM users u
        LEFT JOIN classes c ON c.id = u.class_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmtClass->bind_param("i", $id);
    $stmtClass->execute();
    $studentClass = $stmtClass->get_result()->fetch_assoc()['class_name'] ?? '';
}

$stmt = $conn->prepare("
    SELECT s.name as subject, AVG(g.score) as avg_score
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    WHERE g.student_id = ?
    GROUP BY s.id, s.name
");
$stmt->bind_param("i", $id);
$stmt->execute();
$subject_avgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$stmt2 = $conn->prepare("
    SELECT g.score, g.grade_type, g.date, g.topic, s.name as subject
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    WHERE g.student_id = ?
    ORDER BY g.date DESC
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$all_grades = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);


$total_avg = count($subject_avgs) > 0
    ? round(array_sum(array_column($subject_avgs, 'avg_score')) / count($subject_avgs), 1)
    : 0;

$events = $conn->query("
    SELECT title, event_date FROM events
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$stmt3 = $conn->prepare("
    SELECT title, date FROM achievements
    WHERE student_id = ?
    ORDER BY date DESC
");
$stmt3->bind_param("i", $id);
$stmt3->execute();
$achievements = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt4 = $conn->prepare("
    SELECT u.name, AVG(g.score) as avg
    FROM users u
    JOIN grades g ON g.student_id = u.id
    WHERE u.class_id = (SELECT class_id FROM users WHERE id = ?)
    GROUP BY u.id, u.name
    ORDER BY avg DESC
");
$stmt4->bind_param("i", $id);
$stmt4->execute();
$leaderboard = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);

$my_rank = 1;
foreach ($leaderboard as $i => $row) {
    if ($row['name'] === $_SESSION['name']) {
        $my_rank = $i + 1;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Ученик</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
    * {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    *::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }
    html, body {
        width: 100% !important;
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
    }
    .app-container {
        height: 90vh !important;
        align-items: stretch !important;
    }
    .sidebar {
        height: 100% !important;
        overflow-y: auto !important;
    }
    .main-content {
        height: 100% !important;
        overflow-y: auto !important;
    }
    </style>
</head>
<body>
<div class="bg-blobs">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<div class="app-container" style="height:90vh; align-items:stretch;">
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
                <i class="fas fa-chart-line"></i> Мои Оценки
            </a>
            <a href="#" class="nav-btn" data-target="section-ai">
                <i class="fas fa-robot"></i> AI Наставник
            </a>
            <a href="#" class="nav-btn" data-target="section-portfolio">
                <i class="fas fa-trophy"></i> Портфолио
            </a>
            <a href="#" class="nav-btn" data-target="section-rating">
                <i class="fas fa-crown"></i> Рейтинг
            </a>
            <a href="#" class="nav-btn" data-target="section-schedule">
                <i class="fas fa-calendar"></i> Расписание
            </a>
            <div style="height:1px;background:rgba(255,255,255,0.1);margin:15px 0;"></div>
            <a href="../public/kiosk.php" >
                <i class="fas fa-tv"></i> Стенгазета
            </a>
            <a href="#" class="nav-btn" data-target="section-gamification">
                <i class="fas fa-gamepad"></i> Геймификация
            </a>
            <a href="#" class="nav-btn" data-target="section-knowledge">
                <i class="fas fa-project-diagram"></i> Граф знаний
            </a>
            <a href="../public/logout.php">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </a>
        </nav>
        <div class="user-block">
            <div class="avatar"></div>
            <div class="user-info">
                <p class="name"><?= htmlspecialchars($_SESSION['name']) ?></p>
                <p class="role-text">Ученик</p>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 id="pageTitle">Дашборд</h1>
            <div class="date-now"><?= date('d F Y') ?></div>
        </header>

        <section id="section-home" class="content-section active">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-stats">Статистика</button>
                <button class="sub-nav-btn" data-sub="sub-events">События</button>
            </div>
            <div id="sub-stats" class="sub-section active">
                <div class="dashboard-grid">
                    <div class="glass-card">
                        <h3>Средний балл</h3>
                        <div class="big-number"><?= $total_avg ?></div>
                    </div>
                    <div class="glass-card">
                        <h3>Место в классе</h3>
                        <div class="big-number">#<?= $my_rank ?></div>
                    </div>
                    <div class="glass-card">
                        <h3>Достижений</h3>
                        <div class="big-number"><?= count($achievements) ?></div>
                    </div>
                    <div class="glass-card">
                        <h3>Предметов</h3>
                        <div class="big-number"><?= count($subject_avgs) ?></div>
                    </div>
                </div>
            </div>
            <div id="sub-events" class="sub-section">
                <div class="glass-card full-width">
                    <?php if (empty($events)): ?>
                        <p style="color:var(--text-dim)">Ближайших событий нет</p>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <div class="event-item">
                                <div class="event-date">
                                    <?= date('d M', strtotime($event['event_date'])) ?>
                                </div>
                                <div class="event-info">
                                    <strong><?= htmlspecialchars($event['title']) ?></strong>
                                    <span><?= date('H:i', strtotime($event['event_date'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-card full-width" style="margin-top:10px;">
                <h3>Расписание (быстрый просмотр)</h3>
                <?php aq_render_schedule_widget($conn, 'class', $studentClass); ?>
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

        <section id="section-ai" class="content-section">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-ai-chat">Чат</button>
                <button class="sub-nav-btn" data-sub="sub-ai-predict">Предсказание</button>
            </div>
            <div id="sub-ai-chat" class="sub-section active">
                <div class="glass-card ai-chat-container">
                    <div class="chat-area" id="chatArea">
                        <div class="msg ai">
                            Привет, <?= htmlspecialchars($_SESSION['name']) ?>! Твой средний балл: <?= $total_avg ?>. Готов помочь!
                        </div>
                    </div>
                    <div class="chat-input-wrapper">
                        <input type="text" id="aiInput" placeholder="Напиши ИИ...">
                        <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
            <div id="sub-ai-predict" class="sub-section">
                <div class="glass-card full-width">
                    <h3>Предсказание следующего СОЧ</h3>
                    <select id="subjectSelect" class="predict-select">
                        <option value="">— Выбери предмет —</option>
                        <?php foreach ($subject_avgs as $row): ?>
                            <option value="<?= htmlspecialchars($row['subject']) ?>">
                                <?= htmlspecialchars($row['subject']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="predictBtn" class="btn-primary" style="width:auto; padding:12px 30px; margin-top:15px;">
                        Анализировать
                    </button>
                    <div id="predictResult" style="margin-top:20px;"></div>
                </div>
            </div>
        </section>

        <section id="section-portfolio" class="content-section">
            <div class="glass-card full-width">
                <h3>Мои достижения</h3>
                <?php if (empty($achievements)): ?>
                    <p style="color:var(--text-dim)">Достижений пока нет</p>
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

        <section id="section-rating" class="content-section">
            <div class="glass-card full-width">
                <h3>Лидерборд класса</h3>
                <?php foreach ($leaderboard as $i => $row):
                    $isMe = $row['name'] === $_SESSION['name'];
                ?>
                    <div class="risk-row <?= $isMe ? 'pulse-red' : '' ?>"
                         style="<?= $isMe ? '' : 'background:rgba(255,255,255,0.03)' ?>">
                        <div class="student-info">
                            <strong><?= $i+1 ?>. <?= htmlspecialchars($row['name']) ?></strong>
                            <?= $isMe ? '<span>— это ты</span>' : '' ?>
                        </div>
                        <div class="risk-reason"><?= round($row['avg'], 1) ?> баллов</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="section-schedule" class="content-section">
            <div class="glass-card full-width">
                <h3>Расписание на неделю</h3>
                <?php aq_render_schedule_widget($conn, 'class', $studentClass); ?>
            </div>
        </section>

        <div id="kioskOverlay" class="kiosk-overlay">
            <button class="close-kiosk" onclick="toggleKiosk()" 
                    style="position:absolute; top:24px; right:32px; background:rgba(255,255,255,0.1);
                        border:1px solid rgba(255,255,255,0.2); color:#fff; font-size:1.4rem;
                        width:48px; height:48px; border-radius:14px; cursor:pointer;
                        display:flex; align-items:center; justify-content:center;
                        transition:0.2s; z-index:10;">
                <i class="fas fa-times"></i>
            </button>
            <h1 class="kiosk-logo">AQBOBEK NEWS</h1>
            <div class="marquee-footer">
                <div class="ticker-content">
                    <?php foreach ($events as $e): ?>
                        📅 <?= htmlspecialchars($e['title']) ?> —
                        <?= date('d M', strtotime($e['event_date'])) ?> •&nbsp;
                    <?php endforeach; ?>
                    🚀 Добро пожаловать в портал Aqbobek!
                </div>
            </div>
        </div>


        
        <section id="section-gamification" class="content-section">
        
            <div class="glass-card full-width" style="margin-bottom:20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:20px;">
                    <div>
                        <div style="font-size:0.85rem; color:var(--text-dim); margin-bottom:6px;">Твой уровень</div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="font-size:2.5rem;"><?= $gami['level_icon'] ?></span>
                            <span style="font-family:'Inter',sans-serif; font-size:2rem; font-weight:800;
                                        background:linear-gradient(var(--primary),var(--secondary));
                                        -webkit-background-clip:text; background-clip:text;
                                        -webkit-text-fill-color:transparent;">
                                <?= $gami['level'] ?>
                            </span>
                        </div>
                    </div>
                    <div style="flex:1; min-width:220px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem;
                                    color:var(--text-dim); margin-bottom:8px;">
                            <span>XP: <strong style="color:#fff"><?= number_format($gami['xp']) ?></strong></span>
                            <?php if ($gami['next_xp']): ?>
                                <span>До следующего: <?= $gami['next_xp'] - $gami['xp'] ?> XP</span>
                            <?php else: ?>
                                <span style="color:#00e676;">Максимальный уровень!</span>
                            <?php endif; ?>
                        </div>
                        <div style="height:12px; background:rgba(255,255,255,0.08); border-radius:10px; overflow:hidden;">
                            <div style="height:100%; width:<?= $gami['progress_pct'] ?>%;
                                        background:linear-gradient(90deg,var(--primary),var(--secondary));
                                        border-radius:10px; transition:width 1s ease;
                                        box-shadow:0 0 10px var(--primary);">
                            </div>
                        </div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:0.75rem; color:var(--text-dim);">Прогресс</div>
                        <div style="font-size:2rem; font-weight:800; color:var(--primary);">
                            <?= $gami['progress_pct'] ?>%
                        </div>
                    </div>
                </div>
        
                <div style="margin-top:20px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.08);">
                    <div style="font-size:0.85rem; color:var(--text-dim); margin-bottom:12px;">Доступные ачивки</div>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <?php
                        $all_achivs_def = [
                            ['icon'=>'💯','name'=>'Первая сотня','desc'=>'Получить 100 баллов'],
                            ['icon'=>'🔥','name'=>'Серия отличника','desc'=>'5 оценок подряд выше 80'],
                            ['icon'=>'📈','name'=>'Отличник','desc'=>'Средний балл выше 85'],
                            ['icon'=>'🎯','name'=>'Цель достигнута','desc'=>'Закрыть учебную цель'],
                        ];
                        // Полученные ачивки
                        $stmt_ach = $conn->prepare("SELECT title FROM achievements WHERE student_id=?");
                        $stmt_ach->bind_param("i", $id);
                        $stmt_ach->execute();
                        $earned_titles = array_column($stmt_ach->get_result()->fetch_all(MYSQLI_ASSOC), 'title');
        
                        foreach ($all_achivs_def as $ad):
                            $earned = false;
                            foreach ($earned_titles as $et) {
                                if (str_contains($et, $ad['name'])) { $earned = true; break; }
                            }
                        ?>
                        <div title="<?= htmlspecialchars($ad['desc']) ?>"
                            style="display:flex; align-items:center; gap:8px;
                                    padding:10px 16px; border-radius:14px;
                                    background:<?= $earned ? 'rgba(108,92,231,0.25)' : 'rgba(255,255,255,0.04)' ?>;
                                    border:1px solid <?= $earned ? 'rgba(108,92,231,0.6)' : 'rgba(255,255,255,0.1)' ?>;
                                    filter:<?= $earned ? 'none' : 'grayscale(1) opacity(0.4)' ?>;">
                            <span style="font-size:1.4rem;"><?= $ad['icon'] ?></span>
                            <span style="font-size:0.85rem; font-weight:600;"><?= $ad['name'] ?></span>
                            <?php if ($earned): ?>
                                <span style="font-size:0.7rem; background:var(--primary); color:#fff;
                                            padding:2px 8px; border-radius:6px;">✓</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-goals-list">Мои цели</button>
                <button class="sub-nav-btn" data-sub="sub-goals-add">+ Новая цель</button>
            </div>
        
            <div id="sub-goals-list" class="sub-section active">
                <div class="glass-card full-width">
                    <?php if (empty($gami['goals'])): ?>
                        <p style="color:var(--text-dim); text-align:center; padding:30px;">
                            Целей пока нет. Поставь первую цель! 
                        </p>
                    <?php else: ?>
                        <?php foreach ($gami['goals'] as $goal):
                            $pct = min(100, round(($goal['current_avg'] ?? 0) / $goal['target_score'] * 100));
                            $status_color = match($goal['status']) {
                                'done'   => '#00e676',
                                'failed' => '#ff5252',
                                default  => 'var(--primary)',
                            };
                            $status_label = match($goal['status']) {
                                'done'   => '✅ Выполнена',
                                'failed' => '❌ Провалена',
                                default  => '⏳ Активна',
                            };
                        ?>
                        <div style="padding:20px; border-radius:18px; margin-bottom:15px;
                                    background:rgba(255,255,255,0.04);
                                    border:1px solid rgba(255,255,255,0.08);">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                                <div>
                                    <div style="font-weight:700; font-size:1rem;">
                                        <?= htmlspecialchars($goal['subject_name']) ?>
                                    </div>
                                    <div style="font-size:0.82rem; color:var(--text-dim); margin-top:4px;">
                                        Цель: <?= $goal['target_score'] ?> баллов
                                        <?php if ($goal['deadline']): ?>
                                            • до <?= date('d M Y', strtotime($goal['deadline'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <span style="color:<?= $status_color ?>; font-size:0.85rem; font-weight:600;">
                                        <?= $status_label ?>
                                    </span>
                                    <?php if ($goal['status'] === 'active'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_goal" value="<?= $goal['id'] ?>">
                                        <button type="submit"
                                                style="background:rgba(255,82,82,0.15); border:none; color:#ff5252;
                                                    padding:6px 12px; border-radius:8px; cursor:pointer; font-size:0.8rem;">
                                            Удалить
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="flex:1; height:8px; background:rgba(255,255,255,0.08);
                                            border-radius:6px; overflow:hidden;">
                                    <div style="height:100%; width:<?= $pct ?>%;
                                                background:<?= $status_color ?>;
                                                border-radius:6px; transition:width 1s ease;">
                                    </div>
                                </div>
                                <span style="font-size:0.85rem; font-weight:700; color:<?= $status_color ?>;
                                            min-width:60px; text-align:right;">
                                    <?= $goal['current_avg'] ?? 0 ?> / <?= $goal['target_score'] ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        
            <div id="sub-goals-add" class="sub-section">
                <div class="glass-card full-width">
                    <h3 style="margin-bottom:20px;">Новая учебная цель</h3>
                    <form method="POST">
                        <input type="hidden" name="add_goal" value="1">
                        <div class="form-group">
                            <label>Предмет</label>
                            <select name="goal_subject" required>
                                <option value="">— Выбери предмет —</option>
                                <?php foreach ($subject_avgs as $sa): ?>
                                    <option value="<?php
                                        $stmt_sid = $conn->prepare("SELECT id FROM subjects WHERE name=?");
                                        $stmt_sid->bind_param("s", $sa['subject']);
                                        $stmt_sid->execute();
                                        $sid_row = $stmt_sid->get_result()->fetch_assoc();
                                        echo $sid_row['id'] ?? '';
                                    ?>">
                                        <?= htmlspecialchars($sa['subject']) ?>
                                        (сейчас: <?= round($sa['avg_score']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Целевой балл</label>
                            <input type="number" name="goal_target" min="1" max="100"
                                placeholder="например: 80" required>
                        </div>
                        <div class="form-group">
                            <label>Дедлайн (необязательно)</label>
                            <input type="date" name="goal_deadline"
                                min="<?= date('Y-m-d') ?>">
                        </div>
                        <button type="submit" class="btn-primary" style="width:auto; padding:12px 30px;">
                            Поставить цель
                        </button>
                    </form>
                </div>
            </div>
        </section>
    
    
    <section id="section-knowledge" class="content-section">
        <div class="glass-card full-width" style="min-height:500px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3>Граф знаний</h3>
                <div style="display:flex; gap:16px; font-size:0.82rem;">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#00e676;display:inline-block;"></span>
                        Освоено (≥80)
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#ffea00;display:inline-block;"></span>
                        В процессе (60–79)
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;">
                        <span style="width:12px;height:12px;border-radius:50%;background:#ff5252;display:inline-block;"></span>
                        Пробел (&lt;60)
                    </span>
                </div>
            </div>
            <div id="subjectTabs" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;"></div>
            <canvas id="knowledgeCanvas" style="width:100%; border-radius:16px;
                    background:rgba(0,0,0,0.2);"></canvas>
            <div id="nodeTooltip" style="position:absolute; display:none; pointer-events:none;
                background:rgba(15,12,41,0.95); border:1px solid rgba(255,255,255,0.2);
                border-radius:12px; padding:12px 18px; font-size:0.85rem; z-index:100;
                min-width:160px; box-shadow:0 8px 30px rgba(0,0,0,0.5);">
            </div>
        </div>
    </section>
    </main>
</div>


 
<script>
(async () => {
    const res  = await fetch('../ai/knowledge_graph.php');
    const data = await res.json();
    if (!data.nodes || data.nodes.length === 0) {
        document.getElementById('knowledgeCanvas').insertAdjacentHTML(
            'afterend',
            '<p style="color:rgba(255,255,255,0.4);text-align:center;padding:40px;">Недостаточно данных для графа</p>'
        );
        return;
    }
 
    const subjects = {};
    data.nodes.forEach(n => {
        if (!subjects[n.subject_name]) subjects[n.subject_name] = [];
        subjects[n.subject_name].push(n);
    });
 
    let activeSubject = Object.keys(subjects)[0];
 
    const tabsEl = document.getElementById('subjectTabs');
    Object.keys(subjects).forEach(subj => {
        const btn = document.createElement('button');
        btn.className = 'sub-nav-btn' + (subj === activeSubject ? ' active' : '');
        btn.textContent = subj;
        btn.onclick = () => {
            activeSubject = subj;
            tabsEl.querySelectorAll('.sub-nav-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            draw();
        };
        tabsEl.appendChild(btn);
    });
 
    const canvas  = document.getElementById('knowledgeCanvas');
    const tooltip = document.getElementById('nodeTooltip');
    const ctx     = canvas.getContext('2d');
 
    const COLORS = {
        good: { fill:'#00e676', glow:'rgba(0,230,118,0.4)', text:'#003d1a' },
        ok:   { fill:'#ffea00', glow:'rgba(255,234,0,0.4)',  text:'#3d3400' },
        weak: { fill:'#ff5252', glow:'rgba(255,82,82,0.4)',  text:'#3d0000' },
    };
 
    let nodePositions = {};
 
    function layout(nodes) {
        const positions = {};
        const n = nodes.length;
        const cx = canvas.width / 2;
        const cy = canvas.height / 2;
        const r  = Math.min(cx, cy) * 0.65;
        nodes.forEach((node, i) => {
            const angle = (2 * Math.PI * i / n) - Math.PI / 2;
            positions[node.id] = {
                x: cx + r * Math.cos(angle),
                y: cy + r * Math.sin(angle),
                node,
            };
        });
        return positions;
    }
 
    function draw() {
        const nodes = subjects[activeSubject] || [];
        const edges = data.edges.filter(e => {
            const fromNode = data.nodes.find(n => n.id === e.from);
            return fromNode && fromNode.subject_name === activeSubject;
        });
 
        canvas.width  = canvas.offsetWidth || 700;
        canvas.height = Math.max(420, canvas.width * 0.55);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
 
        nodePositions = layout(nodes);
 
        // Рёбра
        edges.forEach(e => {
            const from = nodePositions[e.from];
            const to   = nodePositions[e.to];
            if (!from || !to) return;
            ctx.beginPath();
            ctx.moveTo(from.x, from.y);
 
            // Кривая Безье
            const cpx = (from.x + to.x) / 2;
            const cpy = (from.y + to.y) / 2 - 40;
            ctx.quadraticCurveTo(cpx, cpy, to.x, to.y);
 
            ctx.strokeStyle = 'rgba(255,255,255,0.18)';
            ctx.lineWidth   = 2;
            ctx.setLineDash([6, 4]);
            ctx.stroke();
            ctx.setLineDash([]);
 
            // Стрелка
            const angle = Math.atan2(to.y - cpy, to.x - cpx);
            const arrowLen = 12;
            ctx.beginPath();
            ctx.moveTo(to.x, to.y);
            ctx.lineTo(
                to.x - arrowLen * Math.cos(angle - 0.4),
                to.y - arrowLen * Math.sin(angle - 0.4)
            );
            ctx.lineTo(
                to.x - arrowLen * Math.cos(angle + 0.4),
                to.y - arrowLen * Math.sin(angle + 0.4)
            );
            ctx.closePath();
            ctx.fillStyle = 'rgba(255,255,255,0.3)';
            ctx.fill();
        });
 
        // Узлы
        nodes.forEach(node => {
            const pos = nodePositions[node.id];
            const col = COLORS[node.level];
            const R   = 42 + (node.avg / 100) * 14; // размер = балл
 
            // Свечение
            const grd = ctx.createRadialGradient(pos.x, pos.y, 0, pos.x, pos.y, R + 16);
            grd.addColorStop(0, col.glow);
            grd.addColorStop(1, 'transparent');
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, R + 16, 0, Math.PI * 2);
            ctx.fillStyle = grd;
            ctx.fill();
 
            // Круг
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, R, 0, Math.PI * 2);
            ctx.fillStyle = col.fill;
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,0.3)';
            ctx.lineWidth = 2;
            ctx.stroke();
 
            // Балл
            ctx.fillStyle = col.text;
            ctx.font = 'bold 16px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(node.avg, pos.x, pos.y - 6);
 
            // Тема под кругом
            ctx.fillStyle = '#fff';
            ctx.font = '12px Inter, sans-serif';
            ctx.textBaseline = 'top';
            const words = node.topic.split(' ');
            let line = ''; let lineY = pos.y + R + 8;
            words.forEach((w, wi) => {
                const test = line + (line ? ' ' : '') + w;
                if (ctx.measureText(test).width > 120 && wi > 0) {
                    ctx.fillText(line, pos.x, lineY);
                    line = w; lineY += 16;
                } else { line = test; }
            });
            ctx.fillText(line, pos.x, lineY);
        });
    }
 
    canvas.addEventListener('mousemove', e => {
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (canvas.width / rect.width);
        const my = (e.clientY - rect.top)  * (canvas.height / rect.height);
        let hovered = null;
        Object.values(nodePositions).forEach(pos => {
            const dx = mx - pos.x, dy = my - pos.y;
            const R = 42 + (pos.node.avg / 100) * 14;
            if (Math.sqrt(dx*dx + dy*dy) < R) hovered = pos.node;
        });
        if (hovered) {
            const statusText = hovered.level === 'good' ? '✅ Освоено' :
                               hovered.level === 'ok'   ? '⚠️ В процессе' : '🔴 Пробел';
            tooltip.innerHTML = `
                <div style="font-weight:700;margin-bottom:6px;">${hovered.topic}</div>
                <div style="color:rgba(255,255,255,0.6);font-size:0.8rem;">Предмет: ${hovered.subject_name}</div>
                <div style="margin-top:8px;">Средний балл: <strong>${hovered.avg}</strong></div>
                <div style="margin-top:4px;">${statusText}</div>
                <div style="color:rgba(255,255,255,0.5);font-size:0.78rem;margin-top:4px;">
                    Оценок: ${hovered.count}
                </div>`;
            tooltip.style.display = 'block';
            tooltip.style.left = (e.clientX + 16) + 'px';
            tooltip.style.top  = (e.clientY - 20) + 'px';
        } else {
            tooltip.style.display = 'none';
        }
    });
    canvas.addEventListener('mouseleave', () => { tooltip.style.display = 'none'; });
 
    draw();
    window.addEventListener('resize', draw);
 
    document.querySelectorAll('[data-target="section-knowledge"]').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(draw, 100));
    });
})();
</script>

<script>
function toggleKiosk() {
    const kiosk = document.getElementById('kioskOverlay');
    if (!kiosk) return;
    const isOpen = kiosk.style.display === 'flex';
    kiosk.style.display = isOpen ? 'none' : 'flex';
}
</script>

<script src="../script.js"></script>
<script>
    const chatHistory = [];

    async function sendChatMessage() {
        const input = document.getElementById('aiInput');
        const text = input.value.trim();
        if (!text) return;

        const chatArea = document.getElementById('chatArea');

        const userMsg = document.createElement('div');
        userMsg.className = 'msg user';
        userMsg.innerText = text;
        chatArea.appendChild(userMsg);
        chatArea.scrollTop = chatArea.scrollHeight;

        chatHistory.push({ role: 'user', content: text });
        input.value = '';

        const loading = document.createElement('div');
        loading.className = 'msg ai';
        loading.innerText = '...';
        chatArea.appendChild(loading);
        chatArea.scrollTop = chatArea.scrollHeight;

        try {
            const response = await fetch('../ai/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'message=' + encodeURIComponent(text) +
                      '&history=' + encodeURIComponent(JSON.stringify(chatHistory.slice(-6)))
            });

            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch(e) {
                console.error('chat.php вернул не JSON:', raw);
                loading.innerText = 'Ошибка сервера. Проверь консоль.';
                return;
            }

            loading.remove();
            const aiMsg = document.createElement('div');
            aiMsg.className = 'msg ai';
            aiMsg.innerText = data.reply ?? data.error ?? 'Нет ответа';
            chatArea.appendChild(aiMsg);
            chatArea.scrollTop = chatArea.scrollHeight;
            chatHistory.push({ role: 'assistant', content: data.reply ?? '' });

        } catch(err) {
            loading.innerText = 'Сетевая ошибка: ' + err.message;
            console.error(err);
        }
    }

    document.getElementById('sendBtn').addEventListener('click', sendChatMessage);
    document.getElementById('aiInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') sendChatMessage();
    });

    if (document.getElementById('predictBtn')) {
        document.getElementById('predictBtn').addEventListener('click', async () => {
            const subject = document.getElementById('subjectSelect').value;
            if (!subject) return;

            const result = document.getElementById('predictResult');
            const btn = document.getElementById('predictBtn');
            result.innerHTML = '<p style="color:var(--text-dim)">Анализирую...</p>';
            btn.disabled = true;

            try {
                const response = await fetch('../ai/predict.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'subject=' + encodeURIComponent(subject)
                });

                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch(e) {
                    console.error('predict.php вернул не JSON:', raw);
                    result.innerHTML = '<p style="color:#ff5252">Ошибка сервера. Открой консоль (F12).</p>';
                    btn.disabled = false;
                    return;
                }

                if (data.error) {
                    result.innerHTML = `<p style="color:#ff5252">${data.error}</p>`;
                } else {
                    const stats = data.stats ?? {};
                    result.innerHTML = `
                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
                            <div style="background:rgba(255,255,255,0.06); border-radius:14px;
                                        padding:14px 20px; flex:1; min-width:120px; text-align:center;">
                                <div style="font-size:0.75rem; color:rgba(255,255,255,0.5); margin-bottom:4px;">Текущий средний</div>
                                <div style="font-size:1.8rem; font-weight:800; color:#ffea00;">${stats.avg ?? '—'}</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.06); border-radius:14px;
                                        padding:14px 20px; flex:1; min-width:120px; text-align:center;">
                                <div style="font-size:0.75rem; color:rgba(255,255,255,0.5); margin-bottom:4px;">Прогноз СОЧ</div>
                                <div style="font-size:1.8rem; font-weight:800; color:#00e676;">${stats.predicted ?? '—'}</div>
                            </div>
                            <div style="background:rgba(255,255,255,0.06); border-radius:14px;
                                        padding:14px 20px; flex:1; min-width:120px; text-align:center;">
                                <div style="font-size:0.75rem; color:rgba(255,255,255,0.5); margin-bottom:4px;">Риск провала</div>
                                <div style="font-size:1.8rem; font-weight:800;
                                            color:${(stats.fail_prob ?? 0) > 40 ? '#ff5252' : '#00e676'};">
                                    ${stats.fail_prob ?? 0}%
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,0.06); border-radius:14px;
                                        padding:14px 20px; flex:1; min-width:120px; text-align:center;">
                                <div style="font-size:0.75rem; color:rgba(255,255,255,0.5); margin-bottom:4px;">Тренд</div>
                                <div style="font-size:1.1rem; font-weight:700; margin-top:6px;">${stats.trend ?? '—'}</div>
                            </div>
                        </div>
                        <div class="glass-card" style="line-height:1.8;">
                            ${(data.prediction ?? '').replace(/\n/g, '<br>')}
                        </div>`;
                }
            } catch(err) {
                result.innerHTML = '<p style="color:#ff5252">Сетевая ошибка: ' + err.message + '</p>';
                console.error(err);
            }

            btn.disabled = false;
        });
    }
</script>
</body>
</html>