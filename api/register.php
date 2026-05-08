<?php
// New student account creation.
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$body = read_json_body();
$name     = trim($body['full_name'] ?? '');
$email    = strtolower(trim($body['email'] ?? ''));
$password = (string)($body['password'] ?? '');

// Server-side validation — never trust the client.
if ($name === '' || mb_strlen($name) > 120) {
    json_response(['ok' => false, 'error' => 'Full name is required (max 120 chars).'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Please supply a valid email address.'], 422);
}
if (strlen($password) < 8) {
    json_response(['ok' => false, 'error' => 'Password must be at least 8 characters.'], 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetchColumn()) {
    json_response(['ok' => false, 'error' => 'An account with that email already exists.'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    'INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, "student")'
);
$stmt->execute([$name, $email, $hash]);

$_SESSION['user_id'] = (int)$pdo->lastInsertId();
json_response(['ok' => true, 'redirect' => 'dashboard.html']);
