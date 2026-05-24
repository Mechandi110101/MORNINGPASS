<?php
require_once __DIR__ . '/db.php';

$DAY_NAMES = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];

function getProfessors(): array {
    $db = getDB();
    return $db->query("SELECT * FROM professors WHERE active = 1 ORDER BY name")->fetchAll();
}

function getStudents(): array {
    $db = getDB();
    return $db->query("SELECT * FROM students WHERE active = 1 ORDER BY name")->fetchAll();
}

function getWeekStart(\DateTime $date = null): string {
    if (!$date) $date = new \DateTime();
    $clone = clone $date;
    $dayOfWeek = (int)$clone->format('N'); // 1=Mon ... 7=Sun
    $clone->modify('-' . ($dayOfWeek - 1) . ' days');
    return $clone->format('Y-m-d');
}

function getScheduleForWeek(string $weekStart): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            ts.id AS slot_id,
            ts.day_of_week,
            ts.start_time,
            ts.end_time,
            ts.class_name,
            ts.class_type,
            ts.max_students,
            ts.notes AS slot_notes,
            p.id   AS professor_id,
            p.name AS professor_name,
            p.color_hex,
            b.id   AS booking_id,
            b.student_id,
            b.status AS booking_status,
            b.notes AS booking_notes,
            s.name AS student_name
        FROM time_slots ts
        JOIN professors p ON p.id = ts.professor_id
        LEFT JOIN bookings b ON b.time_slot_id = ts.id AND b.week_start = :week_start AND b.status != 'cancelled'
        LEFT JOIN students s ON s.id = b.student_id
        WHERE ts.active = 1 AND p.active = 1
        ORDER BY ts.day_of_week, ts.start_time, p.name, s.name
    ");
    $stmt->execute([':week_start' => $weekStart]);
    $rows = $stmt->fetchAll();

    // Group: [day][slot_id] => { slot info, bookings[] }
    $schedule = [];
    foreach ($rows as $row) {
        $day  = $row['day_of_week'];
        $sid  = $row['slot_id'];

        if (!isset($schedule[$day][$sid])) {
            $schedule[$day][$sid] = [
                'slot_id'        => $sid,
                'day_of_week'    => $day,
                'start_time'     => $row['start_time'],
                'end_time'       => $row['end_time'],
                'class_name'     => $row['class_name'],
                'class_type'     => $row['class_type'],
                'max_students'   => $row['max_students'],
                'slot_notes'     => $row['slot_notes'],
                'professor_id'   => $row['professor_id'],
                'professor_name' => $row['professor_name'],
                'color_hex'      => $row['color_hex'],
                'bookings'       => [],
            ];
        }

        if ($row['booking_id']) {
            $schedule[$day][$sid]['bookings'][] = [
                'booking_id'   => $row['booking_id'],
                'student_id'   => $row['student_id'],
                'student_name' => $row['student_name'],
                'status'       => $row['booking_status'],
                'notes'        => $row['booking_notes'],
            ];
        }
    }
    return $schedule;
}

function getBookingsForProfessor(int $professorId, string $weekStart): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            ts.id AS slot_id,
            ts.day_of_week,
            ts.start_time,
            ts.end_time,
            ts.class_name,
            ts.class_type,
            ts.max_students,
            b.id AS booking_id,
            b.student_id,
            b.status,
            b.notes AS booking_notes,
            s.name AS student_name
        FROM time_slots ts
        LEFT JOIN bookings b ON b.time_slot_id = ts.id AND b.week_start = :week_start AND b.status != 'cancelled'
        LEFT JOIN students s ON s.id = b.student_id
        WHERE ts.professor_id = :prof_id AND ts.active = 1
        ORDER BY ts.day_of_week, ts.start_time, s.name
    ");
    $stmt->execute([':prof_id' => $professorId, ':week_start' => $weekStart]);
    $rows = $stmt->fetchAll();

    $schedule = [];
    foreach ($rows as $row) {
        $key = $row['slot_id'];
        if (!isset($schedule[$key])) {
            $schedule[$key] = [
                'slot_id'      => $row['slot_id'],
                'day_of_week'  => $row['day_of_week'],
                'start_time'   => $row['start_time'],
                'end_time'     => $row['end_time'],
                'class_name'   => $row['class_name'],
                'class_type'   => $row['class_type'],
                'max_students' => $row['max_students'],
                'bookings'     => [],
            ];
        }
        if ($row['booking_id']) {
            $schedule[$key]['bookings'][] = [
                'booking_id'   => $row['booking_id'],
                'student_id'   => $row['student_id'],
                'student_name' => $row['student_name'],
                'status'       => $row['status'],
                'notes'        => $row['booking_notes'],
            ];
        }
    }
    return $schedule;
}

function formatTime(string $time): string {
    return date('g:i A', strtotime($time));
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
