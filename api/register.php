<?php
// ---------------------------------------------------------------------
// Account ACTIVATION (formerly "registration").
//
// Students no longer self-register. An admin pre-registers an admitted
// student (name + school email + registration number) via the admin
// console; the student then comes here to set their password.
//
// Match logic:
//   • email + registration_number must both match a student row
//   • that row's password_hash must still be NULL (not yet activated)
//   • if matched and not activated → hash the password, save it, log them in
//   • if matched but already has a password → tell them to sign in instead
//   • otherwise → generic "doesn't match our records" error (doesn't leak
//     which of the two fields was wrong)
// ---------------------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$body     = read_json_body();
$email    = strtolower(trim($body['email'] ?? ''));
$regNum   = trim($body['registration_number'] ?? '');
$password = (string)($body['password'] ?? '');

// --- Server-side validation ------------------------------------------
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Please supply a valid email address.'], 422);
}
if ($regNum === '' || mb_strlen($regNum) > 20) {
    json_response(['ok' => false, 'error' => 'Registration number is required.'], 422);
}
if (strlen($password) < 8) {
    json_response(['ok' => false, 'error' => 'Password must be at least 8 characters.'], 422);
}

// --- Match the pre-registered student row ----------------------------
$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT user_id, password_hash
       FROM users
      WHERE email = ? AND registration_number = ? AND role = "student"'
);
$stmt->execute([$email, $regNum]);
$row = $stmt->fetch();

if (!$row) {
    // Don't reveal which field was wrong — protects against enumeration.
    json_response(['ok' => false, 'error' => "The email and registration number don't match an admitted student record."], 401);
}
if (!empty($row['password_hash'])) {
    json_response(['ok' => false, 'error' => 'That account is already signed up. Please log in instead.'], 409);
}

// --- Activate: set the password, start their session -----------------
$hash = password_hash($password, PASSWORD_DEFAULT);
$upd  = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
$upd->execute([$hash, $row['user_id']]);

$_SESSION['user_id'] = (int)$row['user_id'];
json_response(['ok' => true, 'redirect' => 'dashboard.html']);
