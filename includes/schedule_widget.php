<?php
require_once '../config/db.php';

function aq_load_schedule_data($conn): array {
    $teachers = $conn->query("
        SELECT u.id, u.name, s.name as subject, s.id as subject_id
        FROM users u
        JOIN subjects s ON u.subject_id = s.id
        WHERE u.role = 'teacher'
    ")->fetch_all(MYSQLI_ASSOC);

    $classes = $conn->query("SELECT id, name FROM classes")->fetch_all(MYSQLI_ASSOC);

    $subjects = $conn->query("SELECT id, name FROM subjects")->fetch_all(MYSQLI_ASSOC);

    $rooms = [
        ['id'=>1,'name'=>'Каб.101','type'=>'standard'],
        ['id'=>2,'name'=>'Лаб.201','type'=>'lab'],
        ['id'=>3,'name'=>'Спортзал','type'=>'gym'],
    ];

    $activities = [];
    $i = 1;
    foreach ($teachers as $t) {
        foreach ($classes as $c) {
            $subjectName = $t['subject'];
            $roomType = 'standard';
            if (in_array($subjectName, ['Физика', 'Химия'])) {
                $roomType = 'lab';
            } elseif ($subjectName === 'Физкультура') {
                $roomType = 'gym';
            }

            $activities[] = [
                'id' => $i++,
                'subject' => $t['subject'],
                'teacher' => $t['name'],
                'teacher_id' => $t['id'],
                'classGroup' => $c['name'],
                'class_id' => $c['id'],
                'duration' => 1,
                'requiredRoomType' => $roomType,
                'priority' => 1,
            ];
        }
    }

    $timeslots = [];
    $tid = 1;
    foreach (['Mon','Tue','Wed','Thu','Fri'] as $day) {
        for ($p = 1; $p <= 7; $p++) {
            $timeslots[] = ['id'=>$tid++,'day'=>$day,'period'=>$p];
        }
    }

    return compact('teachers','classes','subjects','rooms','activities','timeslots');
}

function aq_generate_schedule_entries(array $data): array {
    $activities = $data['activities'] ?? [];
    $timeslots = $data['timeslots'] ?? [];
    $rooms = $data['rooms'] ?? [];
    $entries = [];

    foreach ($activities as $activity) {
        $bestScore = -1;
        $bestSlot = null;
        $bestRoom = null;

        foreach ($timeslots as $slot) {
            foreach ($rooms as $room) {
                if (($activity['requiredRoomType'] ?? '') !== ($room['type'] ?? '')) {
                    continue;
                }

                $canPlace = true;
                foreach ($entries as $entry) {
                    $scheduledActivity = $entry['activity'];
                    if (($entry['slot']['id'] ?? null) === ($slot['id'] ?? null) && ($entry['room']['id'] ?? null) === ($room['id'] ?? null)) {
                        $canPlace = false;
                        break;
                    }

                    if (($entry['slot']['id'] ?? null) === ($slot['id'] ?? null)) {
                        if (($scheduledActivity['teacher'] ?? '') === ($activity['teacher'] ?? '')) {
                            $canPlace = false;
                            break;
                        }
                        if (($scheduledActivity['classGroup'] ?? '') === ($activity['classGroup'] ?? '')) {
                            $canPlace = false;
                            break;
                        }
                    }
                }

                if (!$canPlace) {
                    continue;
                }

                $score = 0;
                $subject = $activity['subject'] ?? '';
                $period = (int)($slot['period'] ?? 0);
                if (in_array($subject, ['Math', 'Physics', 'Chemistry'], true) && $period < 3) {
                    $score += 2;
                }
                if ($subject === 'PE' && ($period >= 5 || $period < 3)) {
                    $score += 2;
                }
                $score *= (int)($activity['priority'] ?? 1);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSlot = $slot;
                    $bestRoom = $room;
                }
            }
        }

        if ($bestSlot !== null && $bestRoom !== null) {
            $entries[] = [
                'activity' => $activity,
                'slot' => $bestSlot,
                'room' => $bestRoom
            ];
        }
    }

    return $entries;
}

function aq_render_schedule_widget($conn, string $scopeType, string $scopeValue = ''): void {
    $data = aq_load_schedule_data($conn);
    if (empty($data)) {
        echo '<p style="color:var(--text-dim)">Расписание пока недоступно</p>';
        return;
    }

    $entries = aq_generate_schedule_entries($data);
    $dayOrder = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7];
    $dayLabels = ['Mon' => 'Понедельник', 'Tue' => 'Вторник', 'Wed' => 'Среда', 'Thu' => 'Четверг', 'Fri' => 'Пятница', 'Sat' => 'Суббота', 'Sun' => 'Воскресенье'];

    $filtered = array_values(array_filter($entries, function ($entry) use ($scopeType, $scopeValue) {
        $activity = $entry['activity'];
        if ($scopeType === 'teacher') {
            return ($activity['teacher'] ?? '') === $scopeValue;
        }
        if ($scopeType === 'class') {
            return ($activity['classGroup'] ?? '') === $scopeValue;
        }
        return true;
    }));

    if (empty($filtered)) {
        echo '<p style="color:var(--text-dim)">Для выбранной роли пока нет занятий в расписании</p>';
        return;
    }

    $periods = [];
    $days = [];
    foreach ($data['timeslots'] as $slot) {
        $periods[(int)$slot['period']] = true;
        $days[$slot['day']] = true;
    }
    $periods = array_keys($periods);
    sort($periods);
    $days = array_keys($days);
    usort($days, fn($a, $b) => ($dayOrder[$a] ?? 99) <=> ($dayOrder[$b] ?? 99));

    $matrix = [];
    foreach ($filtered as $entry) {
        $day = $entry['slot']['day'] ?? '';
        $period = (int)($entry['slot']['period'] ?? 0);
        if (!isset($matrix[$day])) {
            $matrix[$day] = [];
        }
        if (!isset($matrix[$day][$period])) {
            $matrix[$day][$period] = [];
        }
        $matrix[$day][$period][] = $entry;
    }

    echo '<div class="schedule-table-wrap"><table class="schedule-table">';
    echo '<thead><tr><th>День</th>';
    foreach ($periods as $period) {
        echo '<th>' . htmlspecialchars('Пара ' . $period) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($days as $day) {
        echo '<tr>';
        echo '<td class="schedule-day-cell">' . htmlspecialchars($dayLabels[$day] ?? $day) . '</td>';
        foreach ($periods as $period) {
            echo '<td>';
            $cellEntries = $matrix[$day][$period] ?? [];
            if (empty($cellEntries)) {
                echo '<div class="schedule-empty">—</div>';
            } else {
                foreach ($cellEntries as $item) {
                    $activity = $item['activity'];
                    $room = $item['room'];
                    echo '<div class="schedule-block">';
                    echo '<div class="schedule-subject">' . htmlspecialchars($activity['subject'] ?? 'Урок') . '</div>';
                    echo '<div class="schedule-meta"><strong>Учитель:</strong> ' . htmlspecialchars($activity['teacher'] ?? '—') . '</div>';
                    echo '<div class="schedule-meta"><strong>Кабинет:</strong> ' . htmlspecialchars($room['name'] ?? '—') . '</div>';
                    echo '<div class="schedule-meta"><strong>Класс:</strong> ' . htmlspecialchars($activity['classGroup'] ?? '—') . '</div>';
                    echo '</div>';
                }
            }
            echo '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}
?>
