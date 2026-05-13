<?php
// ---------------------------------------------------------------------
// Admin endpoint to manage pre-registered students.
//   GET                 → list all student rows
//   POST action=create  → admit a new student (name + email + reg number)
//   POST action=delete  → remove a student row
// ---------------------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $b      = read_json_body();
    $action = $b['action'] ?? '';

    if ($action === 'create') {
        $name   = trim($b['full_name'] ?? '');
        $email  = strtolower(trim($b['email'] ?? ''));
        $regNum = trim($b['registration_number'] ?? '');

        if ($name === '' || mb_strlen($name) > 120) {
            json_response(['ok' => false, 'error' => 'Full name is required (max 120 characters).'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['ok' => false, 'error' => 'A valid email is required.'], 422);
        }
        if ($regNum === '' || mb_strlen($regNum) > 20) {
            json_response(['ok' => false, 'error' => 'Registration number is required (max 20 characters).'], 422);
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, registration_number, role)
                 VALUES (?, ?, ?, "student")'
            );
            $stmt->execute([$name, $email, $regNum]);
        } catch (PDOException $e) {
            // UNIQUE constraint violation (email or reg number already in use).
            if ($e->getCode() === '23000') {
                json_response(['ok' => false, 'error' => 'That email or registration number is already in use.'], 409);
            }
            throw $e;
        }

        json_response(['ok' => true, 'user_id' => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'delete') {
        $userId = (int)($b['user_id'] ?? 0);
        if ($userId <= 0) {
            json_response(['ok' => false, 'error' => 'user_id is required.'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ? AND role = "student"');
        $stmt->execute([$userId]);
        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'error' => 'unknown_action'], 400);
}

// GET — list all students with activation status.
$stmt = $pdo->query(
    'SELECT user_id, full_name, email, registration_number, created_at,
            (password_hash IS NOT NULL) AS activated
       FROM users
      WHERE role = "student"
      ORDER BY created_at DESC'
);
json_response(['ok' => true, 'students' => $stmt->fetchAll()]);
