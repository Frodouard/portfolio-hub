<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireLecturer();

$lecturerId = (int)($_SESSION['lecturer_id'] ?? 0);
$fullName = $_SESSION['full_name'] ?? 'Lecturer';
$departmentName = '';

$lecturer = null;
if ($lecturerId) {
    $r = @mysqli_query($conn, "SELECT l.first_name, l.last_name, l.title, l.department_id, d.department_name
        FROM lecturers l LEFT JOIN departments d ON d.department_id = l.department_id WHERE l.lecturer_id = $lecturerId");
    if ($r) { $lecturer = mysqli_fetch_assoc($r); }
    $departmentName = $lecturer['department_name'] ?? 'Department';
}

$courses = [];
if ($lecturer) {
    $fullName = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
    $r = @mysqli_query($conn, "SELECT c.course_id, c.course_code, c.course_name, c.credits, c.semester,
        (SELECT COUNT(*) FROM registrations reg WHERE reg.course_id = c.course_id AND reg.status = 'registered') AS enrolled_count
        FROM courses c WHERE c.lecturer_name = '" . mysqli_real_escape_string($conn, $fullName) . "' ORDER BY c.course_code");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $courses[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses | Lecturer Panel</title>
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
                <a class="dashboard-nav-link active" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="students.php">Students</a>
                <a class="dashboard-nav-link" href="grades.php">Grades</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="profile.php">Profile</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Lecturer</p>
                    <h1>My Courses</h1>
                </div>
                <div class="dashboard-chip"><?= count($courses) ?> courses</div>
            </div>

            <section class="dashboard-card">
                <?php if (!empty($courses)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Credits</th>
                                    <th>Semester</th>
                                    <th>Enrolled Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td><?= e($c['course_code']) ?></td>
                                        <td><?= e($c['course_name']) ?></td>
                                        <td><?= $c['credits'] ?></td>
                                        <td><?= e($c['semester'] ?? 'N/A') ?></td>
                                        <td><?= $c['enrolled_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No courses are currently assigned to you.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
