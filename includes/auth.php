<?php
// ---------------------------------------------------------------------
// Authentication & authorization helpers (business-logic tier)
// ---------------------------------------------------------------------
require_once __DIR__ . '/db.php';

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT user_id, full_name, email, role FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        if (is_ajax()) {
            json_response(['ok' => false, 'error' => 'not_authenticated'], 401);
        }
        header('Location: ../index.html');
        exit;
    }
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') {
        if (is_ajax()) {
            json_response(['ok' => false, 'error' => 'forbidden'], 403);
        }
        http_response_code(403);
        exit('Forbidden — administrator access required.');
    }
    return $u;
}

function is_ajax(): bool {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return $_POST;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
