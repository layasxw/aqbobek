<?php
require_once '../config/db.php';

// Топ-3 ученика по среднему баллу
$top_students = $conn->query("
    SELECT u.name, c.name as class_name, ROUND(AVG(g.score),1) as avg_score
    FROM users u
    JOIN classes c ON c.id = u.class_id
    JOIN grades g ON g.student_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id, u.name, c.name
    ORDER BY avg_score DESC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// Ближайшие события
$events = $conn->query("
    SELECT title, event_date, description FROM events
    WHERE event_date >= NOW()
    ORDER BY event_date ASC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Последние новости
$news = $conn->query("
    SELECT title, body FROM news
    ORDER BY created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Последние достижения
$achievements = $conn->query("
    SELECT a.title, u.name as student_name
    FROM achievements a
    JOIN users u ON u.id = a.student_id
    ORDER BY a.date DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Ticker строка
$ticker_parts = [];
foreach ($events as $e) {
    $ticker_parts[] = '📅 ' . $e['title'] . ' — ' . date('d M', strtotime($e['event_date']));
}
foreach ($achievements as $a) {
    $ticker_parts[] = '🏆 ' . $a['student_name'] . ': ' . $a['title'];
}
foreach ($news as $n) {
    $ticker_parts[] = '📰 ' . $n['title'];
}
$ticker_text = implode('   •   ', $ticker_parts) . '   •   ';

$medals = ['🥇', '🥈', '🥉'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="120">
    <title>Aqbobek — Стенгазета</title>
    <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@400;700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6c5ce7;
            --secondary: #e84393;
            --accent: #00e676;
            --yellow: #ffea00;
            --bg: #080618;
            --card: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: #fff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            user-select: none;
            cursor: none;
        }

        /* Animated background */
        .bg-layer {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 30%, rgba(108,92,231,0.25) 0%, transparent 60%),
                radial-gradient(ellipse 60% 70% at 80% 70%, rgba(232,67,147,0.2) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 50% 50%, rgba(0,230,118,0.05) 0%, transparent 60%);
            animation: bgShift 20s ease-in-out infinite alternate;
        }
        @keyframes bgShift {
            0%   { opacity: 0.8; transform: scale(1); }
            100% { opacity: 1;   transform: scale(1.05); }
        }

        /* Grid stars */
        .stars {
            position: fixed; inset: 0; z-index: 0;
            background-image:
                radial-gradient(1px 1px at 20% 15%, rgba(255,255,255,0.4) 0%, transparent 100%),
                radial-gradient(1px 1px at 60% 40%, rgba(255,255,255,0.3) 0%, transparent 100%),
                radial-gradient(1px 1px at 80% 20%, rgba(255,255,255,0.5) 0%, transparent 100%),
                radial-gradient(1px 1px at 35% 70%, rgba(255,255,255,0.3) 0%, transparent 100%),
                radial-gradient(1px 1px at 90% 60%, rgba(255,255,255,0.4) 0%, transparent 100%),
                radial-gradient(1px 1px at 10% 80%, rgba(255,255,255,0.3) 0%, transparent 100%),
                radial-gradient(1px 1px at 50% 90%, rgba(255,255,255,0.2) 0%, transparent 100%);
        }

        .content { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; padding: 0; overflow: hidden; }

        /* HEADER */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 28px 50px 20px;
            border-bottom: 1px solid var(--border);
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(20px);
            flex-shrink: 0;
        }

        .logo {
            font-family: 'Unbounded', sans-serif;
            font-size: 2.2rem;
            font-weight: 900;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
            animation: logoPulse 4s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%, 100% { filter: brightness(1); }
            50%       { filter: brightness(1.3); }
        }

        .header-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }

        .live-badge {
            display: flex; align-items: center; gap: 8px;
            background: rgba(0,230,118,0.15);
            border: 1px solid rgba(0,230,118,0.4);
            border-radius: 20px; padding: 6px 16px;
            font-size: 0.9rem; font-weight: 600; color: var(--accent);
        }
        .live-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--accent);
            animation: blink 1.2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

        #clock {
            font-family: 'Unbounded', sans-serif;
            font-size: 1.4rem; font-weight: 700;
            color: rgba(255,255,255,0.9);
        }
        #date-str { font-size: 0.85rem; color: rgba(255,255,255,0.5); }

        /* MAIN GRID */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            padding: 24px 50px;
            flex: 1;
            overflow: hidden;
        }

        /* CARDS */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            backdrop-filter: blur(10px);
            overflow: hidden;
            position: relative;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            border-radius: 24px 24px 0 0;
        }
        .card-leaders::before { background: linear-gradient(90deg, var(--yellow), #ff9f00); }
        .card-events::before  { background: linear-gradient(90deg, var(--primary), var(--secondary)); }
        .card-achieve::before { background: linear-gradient(90deg, var(--accent), #00b4d8); }

        .card-title {
            font-family: 'Unbounded', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: rgba(255,255,255,0.9);
            letter-spacing: -0.3px;
            display: flex; align-items: center; gap: 10px;
        }
        .card-icon { font-size: 1.3rem; }

        /* LEADERBOARD */
        .leader-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }
        .leader-item:nth-child(1) { background: rgba(255,234,0,0.12); border: 1px solid rgba(255,234,0,0.25); }
        .leader-item:nth-child(2) { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); }
        .leader-item:nth-child(3) { background: rgba(205,127,50,0.1);  border: 1px solid rgba(205,127,50,0.2); }

        .medal { font-size: 2.2rem; flex-shrink: 0; }

        .leader-info { flex: 1; min-width: 0; }
        .leader-name {
            font-family: 'Unbounded', sans-serif;
            font-size: 1.05rem; font-weight: 700;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .leader-class { font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-top: 2px; }

        .leader-score {
            font-family: 'Unbounded', sans-serif;
            font-size: 1.8rem; font-weight: 900;
            flex-shrink: 0;
        }
        .leader-item:nth-child(1) .leader-score { color: var(--yellow); }
        .leader-item:nth-child(2) .leader-score { color: rgba(255,255,255,0.7); }
        .leader-item:nth-child(3) .leader-score { color: #cd7f32; }

        /* Score bar */
        .score-bar {
            position: absolute; bottom: 0; left: 0;
            height: 3px; border-radius: 0 0 16px 16px;
            transition: width 1s ease;
        }
        .leader-item:nth-child(1) .score-bar { background: var(--yellow); }
        .leader-item:nth-child(2) .score-bar { background: rgba(255,255,255,0.4); }
        .leader-item:nth-child(3) .score-bar { background: #cd7f32; }

        /* EVENTS */
        .event-list { display: flex; flex-direction: column; gap: 10px; flex: 1; overflow: hidden; }

        .event-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 14px;
            border-left: 3px solid var(--primary);
            transition: all 0.3s;
            animation: slideIn 0.5s ease both;
        }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to   { transform: translateX(0);     opacity: 1; }
        }
        .event-date-badge {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
            flex-shrink: 0;
            min-width: 58px;
        }
        .event-day   { font-size: 1.4rem; font-weight: 800; font-family: 'Unbounded', sans-serif; line-height: 1; }
        .event-month { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; margin-top: 2px; }
        .event-title { font-size: 0.95rem; font-weight: 600; }
        .event-desc  { font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 2px; }

        /* ACHIEVEMENTS */
        .achieve-list { display: flex; flex-direction: column; gap: 10px; flex: 1; overflow: hidden; }

        .achieve-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 16px;
            background: rgba(0,230,118,0.07);
            border-radius: 14px;
            border-left: 3px solid var(--accent);
            animation: slideIn 0.5s ease both;
        }
        .achieve-trophy {
            width: 42px; height: 42px; border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), #00b4d8);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .achieve-title { font-size: 0.9rem; font-weight: 600; line-height: 1.3; }
        .achieve-name  { font-size: 0.75rem; color: var(--accent); margin-top: 3px; font-weight: 600; }

        /* TICKER */
        .ticker-wrap {
            height: 60px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
            background-size: 200% 100%;
            animation: gradientSlide 6s linear infinite;
            display: flex; align-items: center;
            overflow: hidden;
            flex-shrink: 0;
            border-top: 1px solid rgba(255,255,255,0.15);
        }
        @keyframes gradientSlide {
            0%   { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        .ticker-inner {
            display: flex;
            white-space: nowrap;
            animation: ticker 30s linear infinite;
        }
        .ticker-inner span {
            padding: 0 40px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Unbounded', sans-serif;
            letter-spacing: -0.3px;
        }
        @keyframes ticker {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Scan line effect */
        .scanlines {
            position: fixed; inset: 0; z-index: 2;
            pointer-events: none;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0,0,0,0.03) 2px,
                rgba(0,0,0,0.03) 4px
            );
        }

        /* QR / hint */
        .hint {
            position: fixed; bottom: 70px; right: 30px;
            background: rgba(0,0,0,0.6); border: 1px solid var(--border);
            border-radius: 16px; padding: 12px 20px;
            font-size: 0.8rem; color: rgba(255,255,255,0.5);
            backdrop-filter: blur(10px);
            z-index: 10;
        }
    </style>
</head>
<body>

<div class="bg-layer"></div>
<div class="stars"></div>
<div class="scanlines"></div>

<div class="content">

    <!-- HEADER -->
    <header class="header">
        <div class="logo">⚡ AQBOBEK</div>
        <div style="text-align:center;">
            <div style="font-family:'Unbounded',sans-serif;font-size:1rem;font-weight:700;
                        color:rgba(255,255,255,0.7);letter-spacing:1px;text-transform:uppercase;">
                Aqbobek Lyceum
            </div>
            <div style="font-size:0.8rem;color:rgba(255,255,255,0.35);margin-top:4px;">
                Умный школьный портал
            </div>
        </div>
        <div class="header-right">
            <div class="live-badge">
                <div class="live-dot"></div>
                LIVE
            </div>
            <div id="clock">--:--:--</div>
            <div id="date-str">--</div>
        </div>
    </header>

    <!-- MAIN GRID -->
    <div class="main-grid">

        <!-- ЛИДЕРБОРД -->
        <div class="card card-leaders">
            <div class="card-title">
                <span class="card-icon">👑</span>
                Топ учеников дня
            </div>
            <?php foreach ($top_students as $i => $s): ?>
            <div class="leader-item">
                <div class="medal"><?= $medals[$i] ?></div>
                <div class="leader-info">
                    <div class="leader-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="leader-class"><?= htmlspecialchars($s['class_name']) ?> класс</div>
                </div>
                <div class="leader-score"><?= $s['avg_score'] ?></div>
                <div class="score-bar" style="width:<?= $s['avg_score'] ?>%"></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($top_students)): ?>
                <p style="color:rgba(255,255,255,0.4);text-align:center;padding:20px;">
                    Данных пока нет
                </p>
            <?php endif; ?>
        </div>

        <!-- СОБЫТИЯ -->
        <div class="card card-events">
            <div class="card-title">
                <span class="card-icon">📅</span>
                Ближайшие события
            </div>
            <div class="event-list" id="eventList">
                <?php foreach ($events as $i => $e): ?>
                <div class="event-item" style="animation-delay:<?= $i * 0.1 ?>s">
                    <div class="event-date-badge">
                        <div class="event-day"><?= date('d', strtotime($e['event_date'])) ?></div>
                        <div class="event-month"><?= date('M', strtotime($e['event_date'])) ?></div>
                    </div>
                    <div>
                        <div class="event-title"><?= htmlspecialchars($e['title']) ?></div>
                        <?php if ($e['description']): ?>
                        <div class="event-desc"><?= htmlspecialchars($e['description']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                    <p style="color:rgba(255,255,255,0.4);text-align:center;padding:20px;">
                        Ближайших событий нет
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ДОСТИЖЕНИЯ -->
        <div class="card card-achieve">
            <div class="card-title">
                <span class="card-icon">🏆</span>
                Последние достижения
            </div>
            <div class="achieve-list">
                <?php foreach ($achievements as $i => $a): ?>
                <div class="achieve-item" style="animation-delay:<?= $i * 0.12 ?>s">
                    <div class="achieve-trophy">🏅</div>
                    <div>
                        <div class="achieve-title"><?= htmlspecialchars($a['title']) ?></div>
                        <div class="achieve-name"><?= htmlspecialchars($a['student_name']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($achievements)): ?>
                    <p style="color:rgba(255,255,255,0.4);text-align:center;padding:20px;">
                        Достижений пока нет
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- TICKER -->
    <div class="ticker-wrap">
        <div class="ticker-inner">
            <span><?= htmlspecialchars($ticker_text) ?></span>
            <span><?= htmlspecialchars($ticker_text) ?></span>
        </div>
    </div>

</div>

<div class="hint">🔄 Автообновление каждые 2 мин</div>

<script>
function updateClock() {
    const now = new Date();
    const days = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
    const months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];

    const hh = String(now.getHours()).padStart(2,'0');
    const mm = String(now.getMinutes()).padStart(2,'0');
    const ss = String(now.getSeconds()).padStart(2,'0');
    document.getElementById('clock').textContent = `${hh}:${mm}:${ss}`;
    document.getElementById('date-str').textContent =
        `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}
updateClock();
setInterval(updateClock, 1000);

// Автоскролл событий если их много
const eventList = document.getElementById('eventList');
if (eventList && eventList.children.length > 4) {
    let pos = 0;
    setInterval(() => {
        pos += 1;
        if (pos >= eventList.scrollHeight - eventList.clientHeight) pos = 0;
        eventList.scrollTop = pos;
    }, 50);
}
</script>
</body>
</html>