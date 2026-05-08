<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$body = read_json_body();
$email    = strtolower(trim($body['email'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['ok' => false, 'error' => 'Email and password are required.'], 422);
}

$stmt = db()->prepare('SELECT user_id, password_hash, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row || !password_verify($password, $row['password_hash'])) {
    // Same generic message for both branches — avoids user-enumeration leaks.
    json_response(['ok' => false, 'error' => 'Invalid credentials.'], 401);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$row['user_id'];

$redirect = $row['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.html';
json_response(['ok' => true, 'role' => $row['role'], 'redirect' => $redirect]);
