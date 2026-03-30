<?php
session_start();
require_once '../config/db.php';
require_once '../includes/schedule_widget.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../public/login.php");
    exit();
}

$id = $_SESSION['id'];
$studentClass = $_SESSION['class_name'] ?? '';

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

// --- ОЦЕНКИ по предметам ---
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

// --- ВСЕ оценки ---
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

// --- ОБЩЕЕ среднее ---
$total_avg = count($subject_avgs) > 0
    ? round(array_sum(array_column($subject_avgs, 'avg_score')) / count($subject_avgs), 1)
    : 0;

// --- СОБЫТИЯ ---
$events = $conn->query("
    SELECT title, event_date FROM events
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// --- ДОСТИЖЕНИЯ ---
$stmt3 = $conn->prepare("
    SELECT title, date FROM achievements
    WHERE student_id = ?
    ORDER BY date DESC
");
$stmt3->bind_param("i", $id);
$stmt3->execute();
$achievements = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// --- РЕЙТИНГ класса ---
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
            <a href="#" onclick="toggleKiosk()">
                <i class="fas fa-tv"></i> Стенгазета
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

        <!-- ДАШБОРД -->
        <section id="section-home" class="content-section active">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-stats">📈 Статистика</button>
                <button class="sub-nav-btn" data-sub="sub-events">📅 События</button>
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
                <h3>📅 Расписание (быстрый просмотр)</h3>
                <?php aq_render_schedule_widget($conn, 'class', $studentClass); ?>
            </div>
        </section>

        <!-- ОЦЕНКИ -->
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

        <!-- AI НАСТАВНИК -->
        <section id="section-ai" class="content-section">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-ai-chat">💬 Чат</button>
                <button class="sub-nav-btn" data-sub="sub-ai-predict">📊 Предсказание</button>
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
                    <h3>📊 Предсказание следующего СОЧ</h3>
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

        <!-- ПОРТФОЛИО -->
        <section id="section-portfolio" class="content-section">
            <div class="glass-card full-width">
                <h3>🏆 Мои достижения</h3>
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

        <!-- РЕЙТИНГ -->
        <section id="section-rating" class="content-section">
            <div class="glass-card full-width">
                <h3>👑 Лидерборд класса</h3>
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

        <!-- РАСПИСАНИЕ -->
        <section id="section-schedule" class="content-section">
            <div class="glass-card full-width">
                <h3>📅 Расписание на неделю</h3>
                <?php aq_render_schedule_widget($conn, 'class', $studentClass); ?>
            </div>
        </section>

    </main>
</div>

<!-- КИОСК -->
<div id="kioskOverlay" class="kiosk-overlay">
    <button class="close-kiosk" onclick="toggleKiosk()">&times;</button>
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

<script src="../script.js"></script>
<script>
    const chatHistory = [];

    document.getElementById('sendBtn').addEventListener('click', async () => {
        const input = document.getElementById('aiInput');
        const text = input.value.trim();
        if (!text) return;

        const chatArea = document.getElementById('chatArea');

        // сообщение пользователя
        const userMsg = document.createElement('div');
        userMsg.className = 'msg user';
        userMsg.innerText = text;
        chatArea.appendChild(userMsg);
        chatArea.scrollTop = chatArea.scrollHeight;

        chatHistory.push({ role: 'user', content: text });
        input.value = '';

        // индикатор загрузки
        const loading = document.createElement('div');
        loading.className = 'msg ai';
        loading.innerText = '...';
        chatArea.appendChild(loading);
        chatArea.scrollTop = chatArea.scrollHeight;

        const formData = new FormData();
        formData.append('message', text);
        formData.append('history', JSON.stringify(chatHistory.slice(-6)));

        const response = await fetch('../ai/chat.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        loading.remove();

        const aiMsg = document.createElement('div');
        aiMsg.className = 'msg ai';
        aiMsg.innerText = data.reply ?? 'Ошибка';
        chatArea.appendChild(aiMsg);
        chatArea.scrollTop = chatArea.scrollHeight;

        chatHistory.push({ role: 'assistant', content: data.reply });
    });

    // предсказание
    if (document.getElementById('predictBtn')) {
        document.getElementById('predictBtn').addEventListener('click', async () => {
            const subject = document.getElementById('subjectSelect').value;
            if (!subject) return;
            const result = document.getElementById('predictResult');
            result.innerHTML = '<p style="color:var(--text-dim)">Анализирую...</p>';

            const formData = new FormData();
            formData.append('subject', subject);

            const response = await fetch('../ai/predict.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            result.innerHTML = `<div class="glass-card" style="margin-top:10px; line-height:1.8;">
                ${data.prediction.replace(/\n/g, '<br>')}
            </div>`;
        });
    }
</script>
</body>
</html>