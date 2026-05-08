<?php
// ---------------------------------------------------------------------
// Greenfield Institute — application configuration (EXAMPLE TEMPLATE)
//
// HOW TO USE
//   1. Copy this file in the same folder and rename the copy to:
//          config.php
//   2. Open that new config.php and replace each YOUR_... placeholder
//      below with values that match your environment.
//   3. config.php is .gitignored — your real credentials stay private,
//      while this example template stays in source control so other
//      developers know what fields are required.
//
// Common values:
//
//   Local XAMPP (default — what you'd use during development):
//       DB_HOST = '127.0.0.1'
//       DB_NAME = 'greenfield_db'
//       DB_USER = 'root'
//       DB_PASS = ''               (empty for default XAMPP install)
//
//   InfinityFree / shared-hosting (production):
//       DB_HOST = e.g. 'sql123.infinityfree.com'  (from MySQL panel)
//       DB_NAME = e.g. 'if0_xxxxxxxx_greenfield'
//       DB_USER = e.g. 'if0_xxxxxxxx'
//       DB_PASS = your hosting account / vPanel password
// ---------------------------------------------------------------------

define('DB_HOST', 'YOUR_DB_HOST');
define('DB_NAME', 'YOUR_DB_NAME');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Greenfield Institute Course Registration');

// Session must be started before any output for auth to work.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
