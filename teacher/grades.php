<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../public/login.php");
    exit();
}

// создать новую колонку = добавить пустую запись с датой
if (isset($_GET['new_col_type']) && isset($_GET['new_col_date'])) {
    // просто редиректим обратно, колонка появится когда добавят первую оценку
    header("Location: grades.php");
    exit();
}

$teacher_id = $_SESSION['id'];

// получаем subject_id учителя
$stmt = $conn->prepare("SELECT subject_id FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$subject_id = $teacher['subject_id'];

// сохранить оценку
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
    $grade_id = $_POST['grade_id'] ?? null;
    $student_id = $_POST['student_id'];
    $score = $_POST['score'];
    $grade_type = $_POST['grade_type'];
    $topic = $_POST['topic'];
    $date = $_POST['date'];

    if ($grade_id) {
        // обновить существующую
        $stmt = $conn->prepare("
            UPDATE grades SET score=?, topic=?, date=?
            WHERE id=? AND recorded_by=?
        ");
        $stmt->bind_param("issis", $score, $topic, $date, $grade_id, $teacher_id);
        $stmt->execute();
    } else {
        // добавить новую
        $stmt = $conn->prepare("
            INSERT INTO grades (student_id, subject_id, score, grade_type, topic, recorded_by, date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiissis", $student_id, $subject_id, $score, $grade_type, $topic, $teacher_id, $date);
        $stmt->execute();
    }
    header("Location: grades.php");
    exit();
}

// удалить оценку
if (isset($_GET['delete'])) {
    $grade_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM grades WHERE id=? AND recorded_by=?");
    $stmt->bind_param("ii", $grade_id, $teacher_id);
    $stmt->execute();
    header("Location: grades.php");
    exit();
}

// все ученики
$students = $conn->query("
    SELECT u.id, u.name, c.name as class_name
    FROM users u
    JOIN classes c ON c.id = u.class_id
    WHERE u.role = 'student'
    ORDER BY c.name, u.name
")->fetch_all(MYSQLI_ASSOC);

// все оценки по предмету учителя
$grades_raw = $conn->query("
    SELECT g.id, g.student_id, g.score, g.grade_type, g.topic, g.date
    FROM grades g
    WHERE g.subject_id = $subject_id AND g.recorded_by = $teacher_id
    ORDER BY g.date ASC
")->fetch_all(MYSQLI_ASSOC);

// типы работ
$grade_types = [];
foreach ($grades_raw as $g) {
    $key = $g['grade_type'] . ' ' . $g['date'];
    if (!in_array($key, $grade_types)) {
        $grade_types[] = $key;
    }
}

// оценки в формате [student_id][type_date] = grade
$grades_map = [];
foreach ($grades_raw as $g) {
    $key = $g['grade_type'] . ' ' . $g['date'];
    $grades_map[$g['student_id']][$key] = $g;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqbobek | Журнал</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .journal-table { width:100%; border-collapse:collapse; }
        .journal-table th { 
            padding:12px; text-align:center; 
            color:var(--text-dim); font-size:0.85rem;
            border-bottom:1px solid rgba(255,255,255,0.1);
            white-space:nowrap;
        }
        .journal-table td { 
            padding:10px; text-align:center;
            border-bottom:1px solid rgba(255,255,255,0.05);
        }
        .journal-table td:first-child { text-align:left; }
        .score-cell { 
            font-weight:700; font-size:1.1rem; cursor:pointer;
            padding:8px 12px; border-radius:8px;
            transition:background 0.2s;
        }
        .score-cell:hover { background:rgba(255,255,255,0.1); }
        .score-5 { color:#00e676; }
        .score-4 { color:#ffea00; }
        .score-3 { color:#ff5252; }
        .empty-cell { 
            color:rgba(255,255,255,0.2); cursor:pointer;
            padding:8px 12px; border-radius:8px;
            transition:background 0.2s;
        }
        .empty-cell:hover { background:rgba(255,255,255,0.1); }

        /* модальное окно */
        .modal { 
            display:none; position:fixed; top:0; left:0;
            width:100vw; height:100vh; z-index:9999;
            background:rgba(0,0,0,0.7);
            justify-content:center; align-items:center;
        }
        .modal.open { display:flex; }
        .modal-card { 
            background:#1a1a40; border:1px solid rgba(255,255,255,0.15);
            border-radius:24px; padding:30px; width:400px;
        }
        .modal-card h3 { margin-bottom:20px; }
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
            <a href="dashboard.php"><i class="fas fa-home"></i> Дашборд</a>
            <a href="grades.php" class="active"><i class="fas fa-pen"></i> Журнал</a>
            <a href="../public/logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
        </nav>
        <div class="user-block">
            <div class="avatar"></div>
            <div class="user-info">
                <p class="name"><?= htmlspecialchars($_SESSION['name']) ?></p>
                <p class="role-text">Учитель</p>
            </div>
        </div>
    </aside>

    <main class="main-content" style="overflow-y:auto;">
        <header class="top-bar">
            <h1>Журнал оценок</h1>
            <button onclick="openAddColumn()" class="btn-primary" style="width:auto; padding:10px 20px;">
                + Добавить работу
            </button>
        </header>

        <div class="glass-card full-width" style="overflow-x:auto;">
            <table class="journal-table">
                <thead>
                    <tr>
                        <th style="text-align:left; min-width:180px;">Ученик</th>
                        <th>Класс</th>
                        <?php foreach ($grade_types as $type): ?>
                            <th>
                                <?= htmlspecialchars($type) ?>
                                <a href="?delete_col=<?= urlencode($type) ?>" 
                                   style="color:#ff5252; margin-left:5px; font-size:0.7rem;">✕</a>
                            </th>
                        <?php endforeach; ?>
                        <th>Среднее</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): 
                        $student_grades = $grades_map[$s['id']] ?? [];
                        $scores = array_column($student_grades, 'score');
                        $avg = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null;
                        $avg_color = $avg >= 80 ? '#00e676' : ($avg >= 60 ? '#ffea00' : '#ff5252');
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td style="color:var(--text-dim)"><?= htmlspecialchars($s['class_name']) ?></td>
                            <?php foreach ($grade_types as $type): 
                                $grade = $student_grades[$type] ?? null;
                                $score_class = $grade ? ($grade['score'] >= 80 ? 'score-5' : ($grade['score'] >= 60 ? 'score-4' : 'score-3')) : '';
                            ?>
                                <td>
                                    <?php if ($grade): ?>
                                        <span class="score-cell <?= $score_class ?>"
                                              onclick="openEdit(<?= $grade['id'] ?>, <?= $s['id'] ?>, '<?= $grade['score'] ?>', '<?= $grade['grade_type'] ?>', '<?= htmlspecialchars($grade['topic']) ?>', '<?= $grade['date'] ?>')">
                                            <?= $grade['score'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="empty-cell"
                                              onclick="openAdd(<?= $s['id'] ?>, '<?= explode(' ', $type)[0] ?>', '<?= explode(' ', $type)[1] ?>')">
                                            —
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td style="font-weight:700; color:<?= $avg ? $avg_color : 'var(--text-dim)' ?>">
                                <?= $avg ?? '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- МОДАЛКА: редактировать оценку -->
<div class="modal" id="editModal">
    <div class="modal-card">
        <h3>✏️ Редактировать оценку</h3>
        <form method="POST">
            <input type="hidden" name="save_grade" value="1">
            <input type="hidden" name="grade_id" id="edit_grade_id">
            <input type="hidden" name="student_id" id="edit_student_id">
            <input type="hidden" name="grade_type" id="edit_grade_type">
            <div class="form-group">
                <label>Тема</label>
                <input type="text" name="topic" id="edit_topic">
            </div>
            <div class="form-group">
                <label>Балл</label>
                <input type="number" name="score" id="edit_score" min="0" max="100" required>
            </div>
            <div class="form-group">
                <label>Дата</label>
                <input type="date" name="date" id="edit_date" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn-primary" style="width:auto; padding:12px 25px;">
                    Сохранить
                </button>
                <button type="button" onclick="closeModal()" 
                        style="background:rgba(255,255,255,0.1); border:none; color:white;
                               padding:12px 25px; border-radius:14px; cursor:pointer;">
                    Отмена
                </button>
                <a id="deleteLink" href="#"
                   style="background:rgba(255,82,82,0.2); border:none; color:#ff5252;
                          padding:12px 25px; border-radius:14px; cursor:pointer; text-decoration:none;">
                    Удалить
                </a>
            </div>
        </form>
    </div>
</div>

<!-- МОДАЛКА: добавить оценку -->
<div class="modal" id="addModal">
    <div class="modal-card">
        <h3>➕ Добавить оценку</h3>
        <form method="POST">
            <input type="hidden" name="save_grade" value="1">
            <input type="hidden" name="grade_id" value="">
            <input type="hidden" name="student_id" id="add_student_id">
            <input type="hidden" name="grade_type" id="add_grade_type">
            <input type="hidden" name="date" id="add_date">
            <div class="form-group">
                <label>Тема</label>
                <input type="text" name="topic" placeholder="например: Термодинамика">
            </div>
            <div class="form-group">
                <label>Балл</label>
                <input type="number" name="score" min="0" max="100" required placeholder="85">
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn-primary" style="width:auto; padding:12px 25px;">
                    Добавить
                </button>
                <button type="button" onclick="closeModal()"
                        style="background:rgba(255,255,255,0.1); border:none; color:white;
                               padding:12px 25px; border-radius:14px; cursor:pointer;">
                    Отмена
                </button>
            </div>
        </form>
    </div>
</div>

<!-- МОДАЛКА: добавить колонку (новая работа) -->
<div class="modal" id="addColumnModal">
    <div class="modal-card">
        <h3>📋 Новая работа</h3>
        <p style="color:var(--text-dim); margin-bottom:20px;">
            Добавь колонку — потом заполни оценки для каждого ученика
        </p>
        <div class="form-group">
            <label>Тип работы</label>
            <select id="col_type">
                <option value="СОР">СОР</option>
                <option value="СОЧ">СОЧ</option>
                <option value="homework">Домашняя работа</option>
            </select>
        </div>
        <div class="form-group">
            <label>Дата</label>
            <input type="date" id="col_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div style="display:flex; gap:10px; margin-top:20px;">
            <button onclick="addColumn()" class="btn-primary" style="width:auto; padding:12px 25px;">
                Добавить колонку
            </button>
            <button type="button" onclick="closeModal()"
                    style="background:rgba(255,255,255,0.1); border:none; color:white;
                           padding:12px 25px; border-radius:14px; cursor:pointer;">
                Отмена
            </button>
        </div>
    </div>
</div>

<script src="../script.js"></script>
<script>
function openEdit(gradeId, studentId, score, type, topic, date) {
    document.getElementById('edit_grade_id').value = gradeId;
    document.getElementById('edit_student_id').value = studentId;
    document.getElementById('edit_score').value = score;
    document.getElementById('edit_grade_type').value = type;
    document.getElementById('edit_topic').value = topic;
    document.getElementById('edit_date').value = date;
    document.getElementById('deleteLink').href = '?delete=' + gradeId;
    document.getElementById('editModal').classList.add('open');
}

function openAdd(studentId, type, date) {
    document.getElementById('add_student_id').value = studentId;
    document.getElementById('add_grade_type').value = type;
    document.getElementById('add_date').value = date;
    document.getElementById('addModal').classList.add('open');
}

function openAddColumn() {
    document.getElementById('addColumnModal').classList.add('open');
}

function addColumn() {
    const type = document.getElementById('col_type').value;
    const date = document.getElementById('col_date').value;
    // редиректим с параметрами чтобы PHP создал колонку
    window.location.href = `?new_col_type=${type}&new_col_date=${date}`;
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('open'));
}

// закрыть по клику вне модалки
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
});
</script>
</body>
</html>