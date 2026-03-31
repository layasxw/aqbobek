<?php
require_once __DIR__ . '/../config/db.php';


function aq_get_gamification(mysqli $conn, int $student_id): array {

    $stmt = $conn->prepare("SELECT COALESCE(SUM(score),0) as xp FROM grades WHERE student_id=?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $xp = (int)$stmt->get_result()->fetch_assoc()['xp'];

    if      ($xp >= 2000) { $level = 'Мастер';    $level_icon = '🔥'; $next_xp = null;  }
    elseif  ($xp >= 1200) { $level = 'Про';       $level_icon = '⚡'; $next_xp = 2000;  }
    elseif  ($xp >= 600)  { $level = 'Ученик';    $level_icon = '📚'; $next_xp = 1200;  }
    else                  { $level = 'Новичок';   $level_icon = '🌱'; $next_xp = 600;   }

    $progress_pct = 0;
    if ($next_xp !== null) {
        $prev_xp = match($level) {
            'Ученик' => 600, 'Про' => 1200, default => 0
        };
        $progress_pct = min(100, round(($xp - $prev_xp) / ($next_xp - $prev_xp) * 100));
    } else {
        $progress_pct = 100;
    }

    $stmt2 = $conn->prepare("
        SELECT g.id, g.subject_id, g.target_score, g.deadline, g.status, s.name as subject_name,
               ROUND(AVG(gr.score),1) as current_avg
        FROM goals g
        JOIN subjects s ON s.id = g.subject_id
        LEFT JOIN grades gr ON gr.student_id = g.student_id AND gr.subject_id = g.subject_id
        WHERE g.student_id = ?
        GROUP BY g.id, g.subject_id, g.target_score, g.deadline, g.status, s.name
        ORDER BY g.status ASC, g.deadline ASC
    ");
    $stmt2->bind_param("i", $student_id);
    $stmt2->execute();
    $goals = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($goals as &$goal) {
        if ($goal['status'] === 'active' && $goal['current_avg'] >= $goal['target_score']) {
            $upd = $conn->prepare("UPDATE goals SET status='done' WHERE id=?");
            $upd->bind_param("i", $goal['id']);
            $upd->execute();
            $goal['status'] = 'done';

            $chk = $conn->prepare("
                SELECT id FROM achievements
                WHERE student_id=? AND title=?
            ");
            $ach_title = '🎯 Цель достигнута: ' . $goal['subject_name'] . ' — ' . $goal['target_score'] . ' баллов';
            $chk->bind_param("is", $student_id, $ach_title);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows === 0) {
                $ins = $conn->prepare("
                    INSERT INTO achievements (student_id, title, date)
                    VALUES (?, ?, CURDATE())
                ");
                $ins->bind_param("is", $student_id, $ach_title);
                $ins->execute();
            }
        }

        if ($goal['status'] === 'active' && $goal['deadline'] && $goal['deadline'] < date('Y-m-d')) {
            $upd = $conn->prepare("UPDATE goals SET status='failed' WHERE id=?");
            $upd->bind_param("i", $goal['id']);
            $upd->execute();
            $goal['status'] = 'failed';
        }
    }
    unset($goal);

    $auto_achivs = [
        ['title' => '💯 Первая сотня', 'condition' => fn($scores) => in_array(100, $scores)],
        ['title' => '🔥 5 оценок подряд выше 80', 'condition' => function($scores) {
            $streak = 0;
            foreach ($scores as $s) { if ($s >= 80) { $streak++; if ($streak >= 5) return true; } else $streak = 0; }
            return false;
        }],
        ['title' => '📈 Средний балл выше 85', 'condition' => fn($scores) =>
            count($scores) >= 3 && array_sum($scores)/count($scores) >= 85
        ],
    ];

    $stmt3 = $conn->prepare("SELECT score FROM grades WHERE student_id=? ORDER BY date ASC");
    $stmt3->bind_param("i", $student_id);
    $stmt3->execute();
    $all_scores = array_column($stmt3->get_result()->fetch_all(MYSQLI_ASSOC), 'score');

    foreach ($auto_achivs as $achiv) {
        if (($achiv['condition'])($all_scores)) {
            $chk = $conn->prepare("SELECT id FROM achievements WHERE student_id=? AND title=?");
            $chk->bind_param("is", $student_id, $achiv['title']);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO achievements (student_id, title, date) VALUES (?,?,CURDATE())");
                $ins->bind_param("is", $student_id, $achiv['title']);
                $ins->execute();
            }
        }
    }

    return compact('xp', 'level', 'level_icon', 'next_xp', 'progress_pct', 'goals');
}

function aq_handle_goal_form(mysqli $conn, int $student_id): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    if (isset($_POST['add_goal'])) {
        $subject_id   = (int)$_POST['goal_subject'];
        $target_score = min(100, max(1, (int)$_POST['goal_target']));
        $deadline     = $_POST['goal_deadline'] ?? null;

        $chk = $conn->prepare("
            SELECT id FROM goals WHERE student_id=? AND subject_id=? AND status='active'
        ");
        $chk->bind_param("ii", $student_id, $subject_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $ins = $conn->prepare("
                INSERT INTO goals (student_id, subject_id, target_score, deadline, status)
                VALUES (?,?,?,?,'active')
            ");
            $ins->bind_param("iiis", $student_id, $subject_id, $target_score, $deadline);
            $ins->execute();
        }
        header("Location: dashboard.php#section-gamification");
        exit();
    }

    if (isset($_POST['delete_goal'])) {
        $goal_id = (int)$_POST['delete_goal'];
        $del = $conn->prepare("DELETE FROM goals WHERE id=? AND student_id=?");
        $del->bind_param("ii", $goal_id, $student_id);
        $del->execute();
        header("Location: dashboard.php#section-gamification");
        exit();
    }
}
?>