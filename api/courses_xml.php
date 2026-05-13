<?php
// XML export of the course catalogue. Demonstrates structured-data
// exchange — the same business-tier code that produces JSON can equally
// produce XML for partner systems, RSS-style feeds, or offline backups.
require_once __DIR__ . '/../includes/db.php';

$stmt = db()->query('
    SELECT course_id, course_code, title, description, instructor,
           capacity, department
      FROM courses
     ORDER BY department, course_code
');
$rows = $stmt->fetchAll();

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

$root = $dom->createElement('courses');
$root->setAttribute('institution', 'Greenfield Institute');
$root->setAttribute('generated_at', date('c'));
$dom->appendChild($root);

foreach ($rows as $r) {
    $node = $dom->createElement('course');
    $node->setAttribute('id',   (string)$r['course_id']);
    $node->setAttribute('code', $r['course_code']);

    foreach (['title','description','instructor','department'] as $field) {
        $el = $dom->createElement($field);
        $el->appendChild($dom->createTextNode((string)$r[$field]));
        $node->appendChild($el);
    }
    $node->appendChild($dom->createElement('capacity', (string)$r['capacity']));

    $root->appendChild($node);
}

header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: inline; filename="greenfield-courses.xml"');
echo $dom->saveXML();
