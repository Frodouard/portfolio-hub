<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$studentId = (int)($_GET['student_id'] ?? 0);
if ($studentId <= 0) {
    header('Location: users.php');
    exit;
}

$student = null;
$r = @mysqli_query($conn, "SELECT s.*, d.department_name, u.username, u.email, u.status, u.last_login, u.created_at
    FROM students s
    LEFT JOIN departments d ON d.department_id = s.department_id
    JOIN users u ON u.user_id = s.user_id
    WHERE s.student_id = $studentId");
if ($r) { $student = mysqli_fetch_assoc($r); }
if (!$student) { header('Location: users.php'); exit; }

$courses = [];
$r = @mysqli_query($conn, "SELECT c.course_code, c.course_name, c.credits, c.lecturer_name, reg.status, reg.academic_year, reg.semester
    FROM registrations reg JOIN courses c ON c.course_id = reg.course_id
    WHERE reg.student_id = $studentId ORDER BY c.course_code");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courses[] = $row; } }

$results = [];
$r = @mysqli_query($conn, "SELECT res.marks, res.grade, res.grade_points, res.published, res.academic_year, res.semester, c.course_code, c.course_name, c.credits
    FROM results res JOIN courses c ON c.course_id = res.course_id
    WHERE res.student_id = $studentId ORDER BY res.academic_year DESC, c.course_code");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $results[] = $row; } }

$gpa = 'N/A';
$r2 = @mysqli_query($conn, "SELECT SUM(res.grade_points * c.credits) / SUM(c.credits) AS avg_gpa
    FROM results res JOIN courses c ON c.course_id = res.course_id
    WHERE res.student_id = $studentId AND res.published = 1 AND res.grade_points IS NOT NULL");
if ($r2) { $gpaRow = mysqli_fetch_assoc($r2); if ($gpaRow && $gpaRow['avg_gpa'] !== null) { $gpa = number_format((float)$gpaRow['avg_gpa'], 2); } }

$attendance = [];
$r = @mysqli_query($conn, "SELECT a.status, a.session_date, c.course_code
    FROM attendance a JOIN courses c ON c.course_id = a.course_id
    WHERE a.student_id = $studentId ORDER BY a.session_date DESC LIMIT 20");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $attendance[] = $row; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($student['first_name'] . ' ' . $student['last_name']) ?> | Student Profile</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Admin Panel</h2><p>Student Profile</p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="users.php">Users</a>
                <a class="dashboard-nav-link" href="departments.php">Departments</a>
                <a class="dashboard-nav-link" href="courses.php">Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="enrollments.php">Enrollments</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Student Profile</p>
                    <h1><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
                </div>
                <a href="users.php" class="btn btn-primary">Back to Users</a>
            </div>

            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <p class="eyebrow">Personal Info</p>
                    <div class="info-tiles" style="flex-wrap: wrap;">
                        <div class="info-tile"><div class="label">Reg Number</div><div class="value"><?= e($student['reg_number']) ?></div></div>
                        <div class="info-tile"><div class="label">Email</div><div class="value"><?= e($student['email']) ?></div></div>
                        <div class="info-tile"><div class="label">Department</div><div class="value"><?= e($student['department_name'] ?? 'N/A') ?></div></div>
                        <div class="info-tile"><div class="label">Year</div><div class="value"><?= $student['year_of_study'] ?></div></div>
                        <div class="info-tile"><div class="label">Phone</div><div class="value"><?= e($student['phone'] ?? 'N/A') ?></div></div>
                        <div class="info-tile"><div class="label">Gender</div><div class="value"><?= e($student['gender'] ?? 'N/A') ?></div></div>
                        <div class="info-tile"><div class="label">DOB</div><div class="value"><?= e($student['date_of_birth'] ?? 'N/A') ?></div></div>
                        <div class="info-tile"><div class="label">Account Status</div><div class="value" style="color: <?= $student['status'] === 'active' ? '#15803d' : '#dc2626' ?>;"><?= e(ucfirst($student['status'])) ?></div></div>
                        <div class="info-tile"><div class="label">GPA</div><div class="value"><?= $gpa ?></div></div>
                        <div class="info-tile"><div class="label">Enrolled Since</div><div class="value"><?= e($student['enrollment_date'] ?? 'N/A') ?></div></div>
                    </div>
                </section>

                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Registered Courses</p>
                    <h3 style="margin: 0 0 12px;"><?= count($courses) ?> courses</h3>
                    <?php if (!empty($courses)): ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead><tr><th>Code</th><th>Course</th><th>Credits</th><th>Lecturer</th><th>Status</th><th>Year</th></tr></thead>
                                <tbody>
                                    <?php foreach ($courses as $c): ?>
                                        <tr>
                                            <td><?= e($c['course_code']) ?></td>
                                            <td><?= e($c['course_name']) ?></td>
                                            <td><?= $c['credits'] ?></td>
                                            <td><?= e($c['lecturer_name'] ?? 'TBD') ?></td>
                                            <td><span style="color: <?= $c['status'] === 'registered' ? '#15803d' : ($c['status'] === 'dropped' ? '#dc2626' : '#f59e0b') ?>; font-weight: 600;"><?= e(ucfirst($c['status'])) ?></span></td>
                                            <td><?= e($c['academic_year']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="muted">No courses enrolled.</p>
                    <?php endif; ?>
                </section>

                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Academic Results</p>
                    <?php if (!empty($results)): ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead><tr><th>Code</th><th>Course</th><th>Credits</th><th>Marks</th><th>Grade</th><th>GPA</th><th>Published</th><th>Semester</th></tr></thead>
                                <tbody>
                                    <?php foreach ($results as $res): ?>
                                        <tr>
                                            <td><?= e($res['course_code']) ?></td>
                                            <td><?= e($res['course_name']) ?></td>
                                            <td><?= $res['credits'] ?></td>
                                            <td><?= $res['marks'] !== null ? number_format((float)$res['marks'], 1) . '%' : 'N/A' ?></td>
                                            <td><strong><?= e($res['grade'] ?? 'N/A') ?></strong></td>
                                            <td><?= $res['grade_points'] !== null ? number_format((float)$res['grade_points'], 2) : 'N/A' ?></td>
                                            <td><?= $res['published'] ? '<span style="color:#15803d;">Yes</span>' : '<span style="color:#dc2626;">No</span>' ?></td>
                                            <td><?= e($res['semester']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="muted">No results recorded.</p>
                    <?php endif; ?>
                </section>

                <section class="dashboard-card">
                    <p class="eyebrow">Recent Attendance</p>
                    <?php if (!empty($attendance)): ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead><tr><th>Date</th><th>Course</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($attendance as $a): ?>
                                        <tr>
                                            <td><?= e($a['session_date']) ?></td>
                                            <td><?= e($a['course_code']) ?></td>
                                            <td><span style="color: <?= $a['status'] === 'present' ? '#15803d' : ($a['status'] === 'absent' ? '#dc2626' : '#f59e0b') ?>; font-weight: 600; text-transform: capitalize;"><?= e($a['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="muted">No attendance records.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
