<?php
// ---------------------------------------------------------------------
// Change password — logged-in user only. Requires the current password
// so a hijacked session can't silently change it.
// ---------------------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$b = read_json_body();
$current = (string)($b['current_password'] ?? '');
$new     = (string)($b['new_password']     ?? '');

if ($current === '' || $new === '') {
    json_response(['ok' => false, 'error' => 'Both current and new password are required.'], 422);
}
if (strlen($new) < 8) {
    json_response(['ok' => false, 'error' => 'New password must be at least 8 characters.'], 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($current, $row['password_hash'])) {
    json_response(['ok' => false, 'error' => 'Current password is incorrect.'], 401);
}
if (password_verify($new, $row['password_hash'])) {
    json_response(['ok' => false, 'error' => 'New password must be different from your current one.'], 422);
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$stmt    = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
$stmt->execute([$newHash, $user['user_id']]);

json_response(['ok' => true]);
