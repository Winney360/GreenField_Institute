<?php
// Returns the currently authenticated user — used by the SPA shell
// to decide whether to show the login screen or the dashboard.
require_once __DIR__ . '/../includes/auth.php';

$u = current_user();
if (!$u) json_response(['ok' => false, 'error' => 'not_authenticated'], 401);
json_response(['ok' => true, 'user' => $u]);
