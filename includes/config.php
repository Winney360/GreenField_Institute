<?php
// ---------------------------------------------------------------------
// Greenfield Institute — application configuration
// Edit these values to match your local MySQL server.
// ---------------------------------------------------------------------

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'greenfield_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Greenfield Institute Course Registration');

// Session must be started before any output for auth to work.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
