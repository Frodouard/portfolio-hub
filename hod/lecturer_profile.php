<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireHod();

$departmentId = (int)($_SESSION['department_id'] ?? 0);
$lecturerId = (int)($_GET['lecturer_id'] ?? 0);
if ($lecturerId <= 0) { header('Location: lecturers.php'); exit; }

$lecturer = null;
$r = @mysqli_query($conn, "SELECT l.*, d.department_name, u.username, u.email, u.status, u.last_login
    FROM lecturers l LEFT JOIN departments d ON d.department_id = l.department_id JOIN users u ON u.user_id = l.user_id
    WHERE l.lecturer_id = $lecturerId AND l.department_id = $departmentId");
if ($r) { $lecturer = mysqli_fetch_assoc($r); }
if (!$lecturer) { header('Location: lecturers.php'); exit; }

$fullName = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
$courses = [];
$r = @mysqli_query($conn, "SELECT c.course_id, c.course_code, c.course_name, c.credits, c.semester,
    (SELECT COUNT(*) FROM registrations reg WHERE reg.course_id = c.course_id AND reg.status = 'registered') AS enrolled_count
    FROM courses c WHERE c.lecturer_name = '" . mysqli_real_escape_string($conn, $fullName) . "' ORDER BY c.course_code");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courses[] = $row; } }

foreach ($courses as &$course) {
    $students = [];
    $sr = @mysqli_query($conn, "SELECT s.first_name, s.last_name, s.reg_number FROM registrations reg JOIN students s ON s.student_id = reg.student_id WHERE reg.course_id = {$course['course_id']} AND reg.status = 'registered' ORDER BY s.last_name");
    if ($sr) { while ($row = mysqli_fetch_assoc($sr)) { $students[] = $row; } }
    $course['students'] = $students;
}
unset($course);

$totalStudents = 0;
foreach ($courses as $c) { $totalStudents += count($c['students']); }

$recentGrades = [];
$r = @mysqli_query($conn, "SELECT res.marks, res.grade, res.published, s.first_name, s.last_name, c.course_code
    FROM results res JOIN students s ON s.student_id = res.student_id JOIN courses c ON c.course_id = res.course_id
    WHERE c.lecturer_name = '" . mysqli_real_escape_string($conn, $fullName) . "'
    ORDER BY res.created_at DESC LIMIT 10");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $recentGrades[] = $row; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullName) ?> | Lecturer Profile</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>HOD Panel</h2><p><?= e($lecturer['department_name'] ?? '') ?></p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="students.php">Students</a>
                <a class="dashboard-nav-link" href="lecturers.php">Lecturers</a>
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
                <div><p class="eyebrow">Lecturer Profile</p><h1><?= e($fullName) ?></h1></div>
                <a href="lecturers.php" class="btn btn-primary">Back to Lecturers</a>
            </div>

            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <p class="eyebrow">Profile Info</p>
                    <div class="info-tiles" style="flex-wrap: wrap;">
                        <div class="info-tile"><div class="label">Username</div><div class="value"><?= e($lecturer['username']) ?></div></div>
                        <div class="info-tile"><div class="label">Email</div><div class="value"><?= e($lecturer['email']) ?></div></div>
                        <div class="info-tile"><div class="label">Title</div><div class="value"><?= e($lecturer['title'] ?? 'N/A') ?></div></div>
                        <div class="info-tile"><div class="label">Phone</div><div class="value"><?= e($lecturer['phone'] ?? 'N/A') ?></div></div>
                        <div class="info-tile"><div class="label">Status</div><div class="value" style="color: <?= $lecturer['status'] === 'active' ? '#15803d' : '#dc2626' ?>;"><?= e(ucfirst($lecturer['status'])) ?></div></div>
                        <div class="info-tile"><div class="label">Courses</div><div class="value"><?= count($courses) ?></div></div>
                        <div class="info-tile"><div class="label">Total Students</div><div class="value"><?= $totalStudents ?></div></div>
                    </div>
                </section>

                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Courses & Students</p>
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $c): ?>
                            <div style="border: 1px solid var(--color-border); border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?= e($c['course_code']) ?> — <?= e($c['course_name']) ?></strong>
                                        <span class="muted" style="margin-left: 8px;"><?= $c['credits'] ?> credits | <?= e($c['semester'] ?? 'N/A') ?></span>
                                    </div>
                                    <span class="eyebrow"><?= count($c['students']) ?> students</span>
                                </div>
                                <?php if (!empty($c['students'])): ?>
                                    <div style="margin-top: 8px;">
                                        <?php foreach ($c['students'] as $s): ?>
                                            <span style="display: inline-block; padding: 3px 8px; margin: 2px; background: var(--color-bg, #f8f9fa); border-radius: 4px; font-size: 12px;"><?= e($s['first_name'] . ' ' . $s['last_name']) ?> <span class="muted">(<?= e($s['reg_number']) ?>)</span></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="muted" style="margin: 8px 0 0;">No students enrolled.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted">No courses assigned.</p>
                    <?php endif; ?>
                </section>

                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Recent Grades Entered</p>
                    <?php if (!empty($recentGrades)): ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead><tr><th>Student</th><th>Course</th><th>Marks</th><th>Grade</th><th>Published</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recentGrades as $g): ?>
                                        <tr>
                                            <td><?= e($g['first_name'] . ' ' . $g['last_name']) ?></td>
                                            <td><?= e($g['course_code']) ?></td>
                                            <td><?= $g['marks'] !== null ? number_format((float)$g['marks'], 1) . '%' : 'N/A' ?></td>
                                            <td><strong><?= e($g['grade'] ?? 'N/A') ?></strong></td>
                                            <td><?= $g['published'] ? '<span style="color:#15803d;">Yes</span>' : '<span style="color:#dc2626;">No</span>' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="muted">No grades entered yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
