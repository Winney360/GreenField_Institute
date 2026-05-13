<?php
// Server-side guard: admin-only. The page is a .php file rather than .html
// so unauthorised users never see the markup at all.
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin · Greenfield Institute</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
    <header class="navbar">
        <h1>Greenfield Admin</h1>
        <nav>
            <a href="dashboard.php" class="active">Overview</a>
            <a href="manage_courses.php">Courses</a>
            <a href="registrations.php">Registrations</a>
            <a href="../api/logout.php" class="logout">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h2>Welcome, <?= htmlspecialchars($admin['full_name']) ?></h2>
        <p style="color:var(--grey-700);">
            Greenfield Institute administrative console. Manage the course
            catalogue, monitor registrations, and import data from XML feeds.
        </p>

        <?php
        // Quick at-a-glance metrics — pulled directly here because the
        // admin landing page is the only place that needs them.
        $pdo  = db();
        $totalStudents = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
        $totalCourses  = (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
        $totalRegs     = (int)$pdo->query('SELECT COUNT(*) FROM registrations WHERE status = "active"')->fetchColumn();
        ?>

        <div class="course-grid">
            <div class="course-card">
                <div class="code">Students</div>
                <h3 style="font-size:2rem"><?= $totalStudents ?></h3>
                <p>Registered student accounts.</p>
            </div>
            <div class="course-card">
                <div class="code">Courses</div>
                <h3 style="font-size:2rem"><?= $totalCourses ?></h3>
                <p>Courses in the catalogue.</p>
                <a href="manage_courses.php" class="btn">Manage</a>
            </div>
            <div class="course-card">
                <div class="code">Active registrations</div>
                <h3 style="font-size:2rem"><?= $totalRegs ?></h3>
                <p>Currently active enrollments.</p>
                <a href="registrations.php" class="btn">View</a>
            </div>
        </div>
    </main>
</body>
</html>
