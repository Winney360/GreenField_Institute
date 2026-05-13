<?php
// ---------------------------------------------------------------------
// Profile endpoint — student loads & edits their own profile fields.
//   GET  → returns the profile of the logged-in user
//   POST → updates editable fields for the logged-in user
// ---------------------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT full_name, email, registration_number, year_of_birth,
                gender, department, programme, role
           FROM users WHERE user_id = ?'
    );
    $stmt->execute([$user['user_id']]);
    json_response(['ok' => true, 'profile' => $stmt->fetch() ?: []]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = read_json_body();

    // Registration number and email are NOT editable here — they're set
    // by the admin at admission time and used as account identifiers.
    $name   = trim($b['full_name']  ?? '');
    $yob    = isset($b['year_of_birth']) && $b['year_of_birth'] !== ''
            ? (int)$b['year_of_birth'] : null;
    $gender = $b['gender'] ?? '';
    $dept   = trim($b['department'] ?? '');
    $prog   = trim($b['programme']  ?? '');

    if ($name === '' || mb_strlen($name) > 120) {
        json_response(['ok' => false, 'error' => 'Name is required (max 120 characters).'], 422);
    }
    $thisYear = (int)date('Y');
    if ($yob !== null && ($yob < 1900 || $yob > $thisYear)) {
        json_response(['ok' => false, 'error' => "Year of birth must be between 1900 and $thisYear."], 422);
    }
    $allowedGenders = ['', 'male', 'female', 'other', 'prefer_not_to_say'];
    if (!in_array($gender, $allowedGenders, true)) {
        json_response(['ok' => false, 'error' => 'Invalid gender value.'], 422);
    }
    if (mb_strlen($dept) > 80) {
        json_response(['ok' => false, 'error' => 'Department name too long (max 80).'], 422);
    }
    if (mb_strlen($prog) > 120) {
        json_response(['ok' => false, 'error' => 'Programme name too long (max 120).'], 422);
    }

    $stmt = $pdo->prepare(
        'UPDATE users
            SET full_name = ?,
                year_of_birth = ?,
                gender = ?,
                department = ?,
                programme = ?
          WHERE user_id = ?'
    );
    $stmt->execute([
        $name,
        $yob,
        $gender !== '' ? $gender : null,
        $dept   !== '' ? $dept   : null,
        $prog   !== '' ? $prog   : null,
        $user['user_id'],
    ]);

    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
