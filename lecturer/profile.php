<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireLecturer();

$lecturerId = (int)($_SESSION['lecturer_id'] ?? 0);
$fullName = $_SESSION['full_name'] ?? 'Lecturer';

$lecturer = null;
if ($lecturerId) {
    $r = mysqli_query($conn, "SELECT l.first_name, l.last_name, l.title, l.phone, l.department_id, d.department_name, u.email
        FROM lecturers l
        JOIN users u ON u.user_id = l.user_id
        LEFT JOIN departments d ON d.department_id = l.department_id
        WHERE l.lecturer_id = $lecturerId");
    $lecturer = mysqli_fetch_assoc($r);
    $fullName = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
}

$departmentName = $lecturer['department_name'] ?? 'Department';

// Count courses
$courseCount = 0;
if ($fullName) {
    $r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM courses WHERE lecturer_name = '" . mysqli_real_escape_string($conn, $fullName) . "'");
    $courseCount = (int)(mysqli_fetch_assoc($r)['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Lecturer Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Lecturer Panel</h2><p><?= e($departmentName) ?></p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="students.php">Students</a>
                <a class="dashboard-nav-link" href="grades.php">Grades</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link active" href="profile.php">Profile</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Lecturer</p>
                    <h1>My Profile</h1>
                </div>
            </div>
            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <p class="eyebrow">Personal Information</p>
                    <h2 style="margin: 0 0 16px;"><?= e($fullName) ?></h2>
                    <div style="display: grid; gap: 12px;">
                        <div><strong>Title:</strong> <?= e($lecturer['title'] ?? 'N/A') ?></div>
                        <div><strong>Department:</strong> <?= e($departmentName) ?></div>
                        <div><strong>Email:</strong> <?= e($lecturer['email'] ?? 'N/A') ?></div>
                        <div><strong>Phone:</strong> <?= e($lecturer['phone'] ?? 'N/A') ?></div>
                        <div><strong>Courses Assigned:</strong> <?= $courseCount ?></div>
                    </div>
                </section>
                <section class="dashboard-card">
                    <p class="eyebrow">Quick Links</p>
                    <ul class="dashboard-list">
                        <li><a href="courses.php">View my courses</a></li>
                        <li><a href="grades.php">Enter grades</a></li>
                        <li><a href="attendance.php">Mark attendance</a></li>
                        <li><a href="assignments.php">Manage assignments</a></li>
                    </ul>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
