<?php
require_once __DIR__ . '/db.php';

$DAY_NAMES = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'];

function getProfessors(): array {
    $db = getDB();
    return $db->query("SELECT * FROM professors WHERE active = 1 ORDER BY name")->fetchAll();
}

function getStudents(int $programId = 0): array {
    $db = getDB();
    if ($programId > 0) {
        // Students enrolled in at least one slot of this program
        $stmt = $db->prepare("
            SELECT DISTINCT s.*
            FROM students s
            JOIN enrollments e  ON e.student_id   = s.id  AND e.status = 'active'
            JOIN time_slots ts  ON ts.id           = e.time_slot_id AND ts.active = 1
            WHERE s.active = 1 AND ts.program_id = ?
            ORDER BY s.name
        ");
        $stmt->execute([$programId]);
    } else {
        $stmt = $db->query("SELECT * FROM students WHERE active = 1 ORDER BY name");
    }
    return $stmt->fetchAll();
}

function getPrograms(): array {
    return getDB()->query("SELECT * FROM programs WHERE active = 1 ORDER BY id")->fetchAll();
}

function getWeekStart(\DateTime $date = null): string {
    if (!$date) $date = new \DateTime();
    $clone   = clone $date;
    $dayOfWeek = (int)$clone->format('N');
    $clone->modify('-' . ($dayOfWeek - 1) . ' days');
    return $clone->format('Y-m-d');
}

// Returns weekly schedule using ENROLLMENTS (permanent/recurring model)
function getScheduleForWeek(string $weekStart, int $programId = 1): array {
    $db      = getDB();
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

    $stmt = $db->prepare("
        SELECT
            ts.id          AS slot_id,
            ts.day_of_week,
            ts.start_time,
            ts.end_time,
            ts.class_name,
            ts.class_type,
            ts.max_students,
            ts.notes       AS slot_notes,
            ts.start_date,
            ts.end_date,
            p.id           AS professor_id,
            p.name         AS professor_name,
            p.color_hex,
            e.id           AS booking_id,
            e.student_id,
            e.status       AS booking_status,
            e.notes        AS booking_notes,
            s.name         AS student_name
        FROM time_slots ts
        JOIN professors p ON p.id = ts.professor_id AND p.active = 1
        LEFT JOIN enrollments e ON e.time_slot_id = ts.id AND e.status = 'active'
        LEFT JOIN students   s ON s.id = e.student_id   AND s.active = 1
        WHERE ts.active     = 1
          AND ts.program_id = :program_id
          AND (ts.start_date IS NULL OR ts.start_date <= :week_end)
          AND (ts.end_date   IS NULL OR ts.end_date   >= :week_start)
        ORDER BY ts.day_of_week, ts.start_time, p.name, s.name
    ");
    $stmt->execute([
        ':program_id' => $programId,
        ':week_start' => $weekStart,
        ':week_end'   => $weekEnd,
    ]);
    $rows = $stmt->fetchAll();

    $schedule = [];
    foreach ($rows as $row) {
        $day = $row['day_of_week'];
        $sid = $row['slot_id'];

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

function getEnrollmentsForProfessor(int $professorId, int $programId = 1): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT
            ts.id          AS slot_id,
            ts.day_of_week,
            ts.start_time,
            ts.end_time,
            ts.class_name,
            ts.class_type,
            ts.max_students,
            e.id           AS booking_id,
            e.student_id,
            e.status,
            e.notes        AS booking_notes,
            s.name         AS student_name
        FROM time_slots ts
        LEFT JOIN enrollments e ON e.time_slot_id = ts.id AND e.status = 'active'
        LEFT JOIN students    s ON s.id = e.student_id   AND s.active = 1
        WHERE ts.professor_id = :prof_id
          AND ts.program_id   = :program_id
          AND ts.active       = 1
        ORDER BY ts.day_of_week, ts.start_time, s.name
    ");
    $stmt->execute([':prof_id' => $professorId, ':program_id' => $programId]);
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
