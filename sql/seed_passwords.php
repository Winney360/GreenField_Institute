<?php
// ----------------------------------------------------------------------
// One-time helper: regenerate the seeded password hashes so all default
// accounts can sign in with `password123`.
//
// Run this AFTER you have imported sql/greenfield.sql:
//
//     php sql/seed_passwords.php
//
// The script uses the same DB credentials defined in includes/config.php.
// ----------------------------------------------------------------------
require_once __DIR__ . '/../includes/db.php';

$hash = password_hash('password123', PASSWORD_DEFAULT);
$stmt = db()->prepare(
    'UPDATE users SET password_hash = ?
       WHERE email IN (
           "admin@greenfield.edu",
           "alice@student.greenfield.edu",
           "brian@student.greenfield.edu"
       )'
);
$stmt->execute([$hash]);

echo "Updated " . $stmt->rowCount() . " seeded accounts. Default password is now 'password123'.\n";
