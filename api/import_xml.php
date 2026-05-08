<?php
// Admin-only: read /data/courses_sample.xml (or any uploaded file) and
// merge its <course> nodes into the courses table.
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$path = __DIR__ . '/../data/courses_sample.xml';
if (!empty($_FILES['xml']['tmp_name'])) {
    $path = $_FILES['xml']['tmp_name'];
}
if (!is_readable($path)) {
    json_response(['ok' => false, 'error' => 'XML file not readable.'], 400);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($path);
if ($xml === false) {
    json_response(['ok' => false, 'error' => 'Invalid XML document.'], 400);
}

$pdo = db();
$stmt = $pdo->prepare(
    'INSERT INTO courses (course_code, title, description, instructor, credits, capacity, department)
     VALUES (:code, :title, :desc, :inst, :cred, :cap, :dept)
     ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description),
                              instructor=VALUES(instructor), credits=VALUES(credits),
                              capacity=VALUES(capacity), department=VALUES(department)'
);

$inserted = 0;
foreach ($xml->course as $c) {
    $stmt->execute([
        ':code'  => (string)$c['code'],
        ':title' => (string)$c->title,
        ':desc'  => (string)$c->description,
        ':inst'  => (string)$c->instructor,
        ':cred'  => (int)$c->credits,
        ':cap'   => (int)$c->capacity,
        ':dept'  => (string)$c->department,
    ]);
    $inserted++;
}
json_response(['ok' => true, 'imported' => $inserted]);
