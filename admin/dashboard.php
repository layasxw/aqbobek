<?php
session_start();
require_once '../config/db.php';
require_once '../includes/schedule_widget.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

$total_students = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'")->fetch_assoc()['cnt'];
$total_teachers = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='teacher'")->fetch_assoc()['cnt'];
$total_classes  = $conn->query("SELECT COUNT(*) as cnt FROM classes")->fetch_assoc()['cnt'];
$school_avg     = $conn->query("SELECT ROUND(AVG(score),1) as avg FROM grades")->fetch_assoc()['avg'] ?? 0;

$students = $conn->query("
    SELECT u.id, u.name, c.name as class_name, ROUND(AVG(g.score),1) as avg_score
    FROM users u
    JOIN classes c ON c.id = u.class_id
    LEFT JOIN grades g ON g.student_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id, u.name, c.name
    ORDER BY avg_score ASC
")->fetch_all(MYSQLI_ASSOC);

$total_risk = count(array_filter($students, fn($s) => ($s['avg_score'] ?? 0) < 60));

$subject_avgs = $conn->query("
    SELECT s.name as subject, ROUND(AVG(g.score),1) as avg_score
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

$all_users = $conn->query("
    SELECT u.id, u.name, u.email, u.role, c.name as class_name, u.created_at
    FROM users u
    LEFT JOIN classes c ON c.id = u.class_id
    ORDER BY u.role, u.name
")->fetch_all(MYSQLI_ASSOC);

$achievements = $conn->query("
    SELECT a.title, a.date, u.name as student_name, c.name as class_name
    FROM achievements a
    JOIN users u ON u.id = a.student_id
    JOIN classes c ON c.id = u.class_id
    ORDER BY a.date DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$events = $conn->query("
    SELECT id, title, description, event_date FROM events
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

$news = $conn->query("
    SELECT id, title, body, created_at FROM news
    ORDER BY created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $t = $_POST['event_title'];
    $d = $_POST['event_desc'];
    $dt = $_POST['event_date'];
    $aid = $_SESSION['id'];
    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, author_id) VALUES (?,?,?,?)");
    $stmt->bind_param("sssi", $t, $d, $dt, $aid);
    $stmt->execute();
    header("Location: dashboard.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $t = $_POST['news_title'];
    $b = $_POST['news_body'];
    $aid = $_SESSION['id'];
    $stmt = $conn->prepare("INSERT INTO news (title, body, author_id) VALUES (?,?,?)");
    $stmt->bind_param("ssi", $t, $b, $aid);
    $stmt->execute();
    header("Location: dashboard.php"); exit();
}

if (isset($_GET['delete_news'])) {
    $nid = (int)$_GET['delete_news'];
    $conn->query("DELETE FROM news WHERE id=$nid");
    header("Location: dashboard.php"); exit();
}

if (isset($_GET['delete_event'])) {
    $eid = (int)$_GET['delete_event'];
    $conn->query("DELETE FROM events WHERE id=$eid");
    header("Location: dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Администратор</title>
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
            <a href="#" class="nav-btn" data-target="section-radar">
                <i class="fas fa-satellite-dish"></i> Глобальный радар
            </a>
            <a href="#" class="nav-btn" data-target="section-users">
                <i class="fas fa-users"></i> Пользователи
            </a>
            <a href="#" class="nav-btn" data-target="section-achievements">
                <i class="fas fa-trophy"></i> Достижения
            </a>
            <a href="#" class="nav-btn" data-target="section-events">
                <i class="fas fa-calendar-plus"></i> События
            </a>
            <a href="#" class="nav-btn" data-target="section-news">
                <i class="fas fa-newspaper"></i> Новости
            </a>
            <a href="#" class="nav-btn" data-target="section-schedule">
                <i class="fas fa-calendar-alt"></i> Расписание
            </a>
            <a href="analytics.php" class="nav-btn">
                <i class="fas fa-chart-bar"></i> Аналитика
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
                <p class="role-text">Администратор</p>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <h1 id="pageTitle">Дашборд</h1>
            <div class="date-now"><?= date('d F Y') ?></div>
        </header>

        <section id="section-home" class="content-section active">
            <div class="dashboard-grid">
                <div class="glass-card">
                    <h3>Учеников</h3>
                    <div class="big-number"><?= $total_students ?></div>
                </div>
                <div class="glass-card">
                    <h3>Учителей</h3>
                    <div class="big-number"><?= $total_teachers ?></div>
                </div>
                <div class="glass-card">
                    <h3>Средний балл</h3>
                    <div class="big-number"><?= $school_avg ?></div>
                </div>
                <div class="glass-card">
                    <h3>Зона риска</h3>
                    <div class="big-number" style="-webkit-text-fill-color:#ff5252"><?= $total_risk ?></div>
                </div>
            </div>

            <div class="glass-card full-width" style="margin-top:10px;">
                <h3>📅 Ближайшие события</h3>
                <?php if (empty($events)): ?>
                    <p style="color:var(--text-dim);margin-top:15px;">Событий нет</p>
                <?php else: ?>
                    <?php foreach ($events as $e): ?>
                        <div class="event-item">
                            <div class="event-date"><?= date('d M', strtotime($e['event_date'])) ?></div>
                            <div class="event-info">
                                <strong><?= htmlspecialchars($e['title']) ?></strong>
                                <span><?= date('H:i', strtotime($e['event_date'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="glass-card full-width" style="margin-top:10px;">
                <h3>📅 Расписание школы</h3>
                <?php aq_render_schedule_widget($conn, 'admin'); ?>
            </div>
        </section>

        <section id="section-radar" class="content-section">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-by-subject">По предметам</button>
                <button class="sub-nav-btn" data-sub="sub-by-class">По классам</button>
                <button class="sub-nav-btn" data-sub="sub-risk">Зона риска</button>
            </div>

            <div id="sub-by-subject" class="sub-section active">
                <div class="glass-card full-width">
                    <h3>📊 Успеваемость по предметам</h3>
                    <?php foreach ($subject_avgs as $row):
                        $avg = (float)$row['avg_score'];
                        $bg  = $avg >= 80 ? 'mark-5-bg' : ($avg >= 65 ? 'mark-4-bg' : 'mark-3-bg');
                        $cl  = $avg >= 80 ? 'mark-5'    : ($avg >= 65 ? 'mark-4'    : 'mark-3');
                        $lbl = $avg >= 80 ? '5'          : ($avg >= 65 ? '4'          : '3');
                    ?>
                        <div class="subject-row">
                            <span><?= htmlspecialchars($row['subject']) ?></span>
                            <div class="progress-line">
                                <div class="fill <?= $bg ?>" style="width:<?= $avg ?>%"></div>
                            </div>
                            <span class="mark <?= $cl ?>"><?= $lbl ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="sub-by-class" class="sub-section">
                <div class="glass-card full-width">
                    <h3>🏫 Успеваемость по классам</h3>
                    <table style="width:100%;border-collapse:collapse;margin-top:20px;">
                        <thead>
                            <tr style="color:var(--text-dim);text-align:left;">
                                <th style="padding:12px;">Класс</th>
                                <th style="padding:12px;">Учеников</th>
                                <th style="padding:12px;">Средний балл</th>
                                <th style="padding:12px;">Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($class_avgs as $c):
                                $avg = (float)$c['avg_score'];
                                $color  = $avg >= 80 ? '#00e676' : ($avg >= 65 ? '#ffea00' : '#ff5252');
                                $status = $avg >= 80 ? '✅ Хорошо' : ($avg >= 65 ? '⚠️ Средне' : '🔴 Риск');
                            ?>
                                <tr style="border-top:1px solid rgba(255,255,255,0.05);">
                                    <td style="padding:12px;font-weight:700;"><?= htmlspecialchars($c['class_name']) ?></td>
                                    <td style="padding:12px;"><?= $c['cnt'] ?></td>
                                    <td style="padding:12px;color:<?= $color ?>;font-weight:700;"><?= $avg ?></td>
                                    <td style="padding:12px;"><?= $status ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="sub-risk" class="sub-section">
                <div class="glass-card full-width">
                    <h3>⚠️ Ученики в зоне риска</h3>
                    <?php $risks = array_filter($students, fn($s) => ($s['avg_score'] ?? 0) < 60); ?>
                    <?php if (empty($risks)): ?>
                        <p style="color:var(--text-dim);margin-top:20px;">Все ученики в норме 🎉</p>
                    <?php else: ?>
                        <?php foreach ($risks as $s): ?>
                            <div class="risk-row pulse-red" style="margin-top:15px;">
                                <div class="student-info">
                                    <strong><?= htmlspecialchars($s['name']) ?></strong>
                                    <span><?= htmlspecialchars($s['class_name']) ?></span>
                                </div>
                                <div class="risk-reason">Средний балл: <?= $s['avg_score'] ?? '—' ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="section-users" class="content-section">
            <div class="glass-card full-width">
                <h3>👥 Все пользователи</h3>
                <table style="width:100%;border-collapse:collapse;margin-top:20px;">
                    <thead>
                        <tr style="color:var(--text-dim);text-align:left;">
                            <th style="padding:12px;">Имя</th>
                            <th style="padding:12px;">Email</th>
                            <th style="padding:12px;">Роль</th>
                            <th style="padding:12px;">Класс</th>
                            <th style="padding:12px;">Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $u):
                            $roleColors = ['student'=>'#6c5ce7','teacher'=>'#00e676','parent'=>'#74b9ff','admin'=>'#e84393'];
                            $roleLabels = ['student'=>'Ученик','teacher'=>'Учитель','parent'=>'Родитель','admin'=>'Админ'];
                            $rc = $roleColors[$u['role']] ?? '#fff';
                            $rl = $roleLabels[$u['role']] ?? $u['role'];
                        ?>
                            <tr style="border-top:1px solid rgba(255,255,255,0.05);">
                                <td style="padding:12px;font-weight:600;"><?= htmlspecialchars($u['name']) ?></td>
                                <td style="padding:12px;color:var(--text-dim);font-size:0.9rem;"><?= htmlspecialchars($u['email']) ?></td>
                                <td style="padding:12px;">
                                    <span style="background:<?= $rc ?>22;color:<?= $rc ?>;padding:4px 12px;border-radius:8px;font-size:0.85rem;font-weight:600;">
                                        <?= $rl ?>
                                    </span>
                                </td>
                                <td style="padding:12px;"><?= htmlspecialchars($u['class_name'] ?? '—') ?></td>
                                <td style="padding:12px;color:var(--text-dim);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="section-achievements" class="content-section">
            <div class="glass-card full-width">
                <h3>🏆 Достижения учеников</h3>
                <?php if (empty($achievements)): ?>
                    <p style="color:var(--text-dim);margin-top:15px;">Достижений пока нет</p>
                <?php else: ?>
                    <?php foreach ($achievements as $a): ?>
                        <div class="event-item">
                            <div class="event-date"><?= date('d M', strtotime($a['date'])) ?></div>
                            <div class="event-info">
                                <strong><?= htmlspecialchars($a['title']) ?></strong>
                                <span><?= htmlspecialchars($a['student_name']) ?> — <?= htmlspecialchars($a['class_name']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="section-events" class="content-section">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-events-list">Список событий</button>
                <button class="sub-nav-btn" data-sub="sub-events-add">+ Добавить</button>
            </div>

            <div id="sub-events-list" class="sub-section active">
                <div class="glass-card full-width">
                    <h3>📅 Ближайшие события</h3>
                    <?php if (empty($events)): ?>
                        <p style="color:var(--text-dim);margin-top:15px;">Событий нет</p>
                    <?php else: ?>
                        <?php foreach ($events as $e): ?>
                            <div class="event-item">
                                <div class="event-date"><?= date('d M', strtotime($e['event_date'])) ?></div>
                                <div class="event-info" style="flex:1;">
                                    <strong><?= htmlspecialchars($e['title']) ?></strong>
                                    <span><?= htmlspecialchars($e['description'] ?? '') ?></span>
                                </div>
                                <a href="?delete_event=<?= $e['id'] ?>"
                                   onclick="return confirm('Удалить событие?')"
                                   style="color:#ff5252;font-size:1.1rem;text-decoration:none;">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="sub-events-add" class="sub-section">
                <div class="glass-card full-width">
                    <h3>➕ Новое событие</h3>
                    <form method="POST" style="margin-top:20px;">
                        <input type="hidden" name="add_event" value="1">
                        <div class="form-group">
                            <label>Название</label>
                            <input type="text" name="event_title" required placeholder="Название события">
                        </div>
                        <div class="form-group">
                            <label>Описание</label>
                            <input type="text" name="event_desc" placeholder="Краткое описание">
                        </div>
                        <div class="form-group">
                            <label>Дата и время</label>
                            <input type="datetime-local" name="event_date" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width:auto;padding:12px 30px;">
                            Добавить событие
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section id="section-news" class="content-section">
            <div class="sub-navigation">
                <button class="sub-nav-btn active" data-sub="sub-news-list">Лента новостей</button>
                <button class="sub-nav-btn" data-sub="sub-news-add">+ Опубликовать</button>
            </div>

            <div id="sub-news-list" class="sub-section active">
                <div class="glass-card full-width">
                    <h3>📰 Новости школы</h3>
                    <?php if (empty($news)): ?>
                        <p style="color:var(--text-dim);margin-top:15px;">Новостей пока нет</p>
                    <?php else: ?>
                        <?php foreach ($news as $n): ?>
                            <div class="event-item" style="align-items:flex-start;">
                                <div class="event-date" style="font-size:0.75rem;min-width:55px;text-align:center;">
                                    <?= date('d M', strtotime($n['created_at'])) ?>
                                </div>
                                <div class="event-info" style="flex:1;">
                                    <strong><?= htmlspecialchars($n['title']) ?></strong>
                                    <span style="margin-top:5px;display:block;"><?= htmlspecialchars($n['body']) ?></span>
                                </div>
                                <a href="?delete_news=<?= $n['id'] ?>"
                                   onclick="return confirm('Удалить новость?')"
                                   style="color:#ff5252;font-size:1.1rem;text-decoration:none;margin-left:10px;">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="sub-news-add" class="sub-section">
                <div class="glass-card full-width">
                    <h3>✍️ Новая публикация</h3>
                    <form method="POST" style="margin-top:20px;">
                        <input type="hidden" name="add_news" value="1">
                        <div class="form-group">
                            <label>Заголовок</label>
                            <input type="text" name="news_title" required placeholder="Заголовок новости">
                        </div>
                        <div class="form-group">
                            <label>Текст</label>
                            <input type="text" name="news_body" required placeholder="Текст новости">
                        </div>
                        <button type="submit" class="btn-primary" style="width:auto;padding:12px 30px;">
                            Опубликовать
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section id="section-schedule" class="content-section">
            <div class="glass-card full-width">
                <h3>📅 Автогенерация расписания</h3>
                <div style="margin-top:20px;display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                    <div>
                        <label style="color:var(--text-dim);font-size:0.85rem;">Фильтр по</label>
                        <select id="filter-type" style="margin-left:10px;padding:8px 14px;border-radius:10px;background:var(--glass);border:1px solid var(--glass-border);color:#fff;">
                            <option value="class">Класс</option>
                            <option value="teacher">Учитель</option>
                            <option value="room">Кабинет</option>
                        </select>
                    </div>
                    <div>
                        <select id="filter-value" style="padding:8px 14px;border-radius:10px;background:var(--glass);border:1px solid var(--glass-border);color:#fff;"></select>
                    </div>
                    <button onclick="regenBtn" style="padding:8px 20px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;border:none;cursor:pointer;font-weight:600;">
                        🔄 Перегенерировать
                    </button>
                </div>
                <div id="schedule-container" style="margin-top:20px;overflow-x:auto;"></div>
            </div>
        </section>

    </main>
</div>

<script src="../script.js"></script>
<script>
// === ДАННЫЕ РАСПИСАНИЯ ===
const schedData = <?php
    $jsonPath = __DIR__ . '/../schedule/data.json';
    echo file_exists($jsonPath) ? file_get_contents($jsonPath) : '{}';
?>;

const activities = schedData.activities || [];
const timeslots  = schedData.timeslots  || [];
const rooms      = schedData.rooms      || [];

function canPlace(activity, slot, room, schedule) {
    if (activity.requiredRoomType !== room.type) return false;
    for (let e of schedule) {
        if (e.timeSlotId === slot.id) {
            const a = activities.find(a => a.id === e.activityId);
            if (a.teacher === activity.teacher) return false;
            if (a.classGroup === activity.classGroup) return false;
            if (e.roomId === room.id) return false;
        }
    }
    return true;
}

function scoreSlot(activity, slot) {
    let score = 0;
    if (["Математика","Физика","Химия"].includes(activity.subject) && slot.period < 3) score += 2;
    if (activity.subject === "Физкультура" && slot.period > 3) score += 2;
    if (activity.priority) score *= activity.priority;
    return score;
}

function generateSchedule() {
    const schedule = [];
    for (let activity of activities) {
        let bestSlot = null, bestRoom = null, bestScore = -1;
        for (let slot of timeslots) {
            for (let room of rooms) {
                if (canPlace(activity, slot, room, schedule)) {
                    const score = scoreSlot(activity, slot);
                    if (score > bestScore) { bestScore = score; bestSlot = slot; bestRoom = room; }
                }
            }
        }
        if (bestSlot && bestRoom) {
            schedule.push({ activityId: activity.id, timeSlotId: bestSlot.id, roomId: bestRoom.id });
        }
    }
    return schedule;
}

let currentSchedule = generateSchedule();

const dayNames = { Mon:'Пн', Tue:'Вт', Wed:'Ср', Thu:'Чт', Fri:'Пт' };

function updateFilterValues() {
    const type = document.getElementById('filter-type').value;
    const sel = document.getElementById('filter-value');
    sel.innerHTML = '';
    let options = [];
    if (type === 'class')   options = [...new Set(activities.map(a => a.classGroup))];
    if (type === 'teacher') options = [...new Set(activities.map(a => a.teacher))];
    if (type === 'room')    options = [...new Set(rooms.map(r => r.name))];
    options.forEach(val => {
        const o = document.createElement('option');
        o.value = o.innerText = val;
        sel.appendChild(o);
    });
}

function renderSchedule() {
    const type  = document.getElementById('filter-type').value;
    const value = document.getElementById('filter-value').value;
    const container = document.getElementById('schedule-container');

    const entries = currentSchedule.filter(e => {
        const act  = activities.find(a => a.id === e.activityId);
        const room = rooms.find(r => r.id === e.roomId);
        if (type === 'class')   return act.classGroup === value;
        if (type === 'teacher') return act.teacher === value;
        if (type === 'room')    return room.name === value;
    });

    if (!entries.length) { container.innerHTML = '<p style="color:var(--text-dim);margin-top:10px;">Нет данных</p>'; return; }

    let html = '<table style="width:100%;border-collapse:collapse;">';
    html += '<thead><tr>';
    ['День','Пара','Предмет','Учитель','Класс','Кабинет'].forEach(h => {
        html += `<th style="padding:10px;text-align:left;color:var(--text-dim);font-size:0.85rem;border-bottom:1px solid rgba(255,255,255,0.08);">${h}</th>`;
    });
    html += '</tr></thead><tbody>';

    entries.sort((a,b) => a.timeSlotId - b.timeSlotId).forEach(entry => {
        const act  = activities.find(a => a.id === entry.activityId);
        const slot = timeslots.find(t => t.id === entry.timeSlotId);
        const room = rooms.find(r => r.id === entry.roomId);
        html += `<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
            <td style="padding:10px;font-weight:600;">${dayNames[slot.day] || slot.day}</td>
            <td style="padding:10px;color:var(--text-dim);">${slot.period}</td>
            <td style="padding:10px;">${act.subject}</td>
            <td style="padding:10px;color:var(--text-dim);">${act.teacher}</td>
            <td style="padding:10px;"><span style="background:rgba(108,92,231,0.2);color:#a29bfe;padding:3px 10px;border-radius:8px;">${act.classGroup}</span></td>
            <td style="padding:10px;color:var(--text-dim);">${room.name}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function regenerate() {
    currentSchedule = generateSchedule();
    renderSchedule();
}


document.getElementById('filter-type').addEventListener('change', () => { updateFilterValues(); renderSchedule(); });
document.getElementById('filter-value').addEventListener('change', renderSchedule);
updateFilterValues();
renderSchedule();
document.getElementById('regenBtn').addEventListener('click', regenerate);

</script>
</body>
</html>