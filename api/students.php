<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$db     = getDB();

if ($method === 'GET') {
    $search    = trim($_GET['q']  ?? '');
    $programId = (int)($_GET['p'] ?? 0);
    $withSlots = isset($_GET['with_slots']);

    // Enriched search: returns students with their enrolled groups
    if ($withSlots && $search) {
        $stmt = $db->prepare("
            SELECT s.id AS student_id, s.name, s.membership_status, s.membership_expires,
                   ts.id AS slot_id, ts.day_of_week, ts.start_time, ts.class_name, ts.program_id,
                   p.name AS professor_name, p.color_hex
            FROM students s
            LEFT JOIN enrollments e  ON e.student_id = s.id AND e.status='active' AND e.is_trial=0 AND e.is_award=0
            LEFT JOIN time_slots ts  ON ts.id = e.time_slot_id AND ts.active=1
            LEFT JOIN professors p   ON p.id = ts.professor_id
            WHERE s.active=1 AND s.name LIKE ?
            ORDER BY s.name, ts.day_of_week, ts.start_time
            LIMIT 60
        ");
        $stmt->execute(['%' . $search . '%']);
        $rows = $stmt->fetchAll();
        $out  = [];
        foreach ($rows as $row) {
            $sid = $row['student_id'];
            if (!isset($out[$sid])) {
                $out[$sid] = [
                    'id'                 => $sid,
                    'name'               => $row['name'],
                    'membership_status'  => $row['membership_status'],
                    'membership_expires' => $row['membership_expires'],
                    'slots'              => [],
                ];
            }
            if ($row['slot_id']) {
                $out[$sid]['slots'][] = [
                    'slot_id'        => $row['slot_id'],
                    'day_of_week'    => $row['day_of_week'],
                    'start_time'     => $row['start_time'],
                    'class_name'     => $row['class_name'],
                    'program_id'     => $row['program_id'],
                    'professor_name' => $row['professor_name'],
                    'color_hex'      => $row['color_hex'],
                ];
            }
        }
        jsonResponse(['ok' => true, 'students' => array_values($out)]);
    }

    $where  = ['s.active = 1'];
    $params = [];

    if ($search)    { $where[] = 's.name LIKE ?'; $params[] = '%' . $search . '%'; }
    if ($programId) {
        $where[] = 'EXISTS (
            SELECT 1 FROM enrollments e
            JOIN time_slots ts ON ts.id = e.time_slot_id
            WHERE e.student_id = s.id AND e.status = \'active\' AND ts.program_id = ?
        )';
        $params[] = $programId;
    }

    $sql  = "SELECT * FROM students s WHERE " . implode(' AND ', $where) . " ORDER BY s.name" . ($search ? " LIMIT 40" : "");
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['ok' => true, 'students' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $name = strtoupper(trim($input['name'] ?? ''));
    if (!$name) jsonResponse(['ok' => false, 'error' => 'name es requerido'], 400);

    $stmt = $db->prepare("INSERT INTO students (name, gender, category, phone, membership_status, membership_expires) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
        $name,
        $input['gender']             ?? '',
        $input['category']           ?? '',
        $input['phone']              ?? '',
        $input['membership_status']  ?? 'active',
        $input['membership_expires'] ?: null,
    ]);
    $studentId = (int)$db->lastInsertId();
    logAudit('create_student', 'student', $studentId, "Estudiante creado: {$name}");
    jsonResponse(['ok' => true, 'student_id' => $studentId]);
}

if ($method === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'id requerido'], 400);

    // Quick membership-only update
    if (!empty($input['membership_only'])) {
        $db->prepare("UPDATE students SET membership_status=?, membership_expires=? WHERE id=?")
           ->execute([$input['membership_status'] ?? 'active', $input['membership_expires'] ?: null, $id]);
        logAudit('update_membership', 'student', $id,
            "Membresía actualizada: {$input['membership_status']}");
        jsonResponse(['ok' => true]);
    }

    $name = strtoupper(trim($input['name'] ?? ''));
    if (!$name) jsonResponse(['ok' => false, 'error' => 'name requerido'], 400);

    $stmt = $db->prepare("UPDATE students SET name=?, gender=?, category=?, phone=?, membership_status=?, membership_expires=? WHERE id=?");
    $stmt->execute([
        $name,
        $input['gender']             ?? '',
        $input['category']           ?? '',
        $input['phone']              ?? '',
        $input['membership_status']  ?? 'active',
        $input['membership_expires'] ?: null,
        $id,
    ]);
    logAudit('edit_student', 'student', $id, "Estudiante editado: {$name}");
    jsonResponse(['ok' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonResponse(['ok' => false, 'error' => 'id requerido'], 400);

    $s = $db->prepare("SELECT name FROM students WHERE id = ?");
    $s->execute([$id]);
    $info = $s->fetch();

    $db->prepare("DELETE FROM enrollments WHERE student_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);

    logAudit('delete_student', 'student', $id,
        "Estudiante eliminado permanentemente: " . ($info['name'] ?? $id));
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
