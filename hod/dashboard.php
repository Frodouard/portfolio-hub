<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireHod();

$userName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'HOD');
$departmentId = (int)($_SESSION['department_id'] ?? 0);
$departmentName = $_SESSION['department_name'] ?? 'Department';

// Department student count
$studentCount = 0;
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE department_id = $departmentId");
    if ($r) { $studentCount = (int)(mysqli_fetch_assoc($r)['total'] ?? 0); }
}

$lecturerCount = 0;
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM lecturers WHERE department_id = $departmentId");
    if ($r) { $lecturerCount = (int)(mysqli_fetch_assoc($r)['total'] ?? 0); }
}

$courseCount = 0;
$courses = [];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT course_code, course_name, credits, lecturer_name, semester FROM courses WHERE department_id = $departmentId ORDER BY course_code");
    if ($r) {
        $courseCount = mysqli_num_rows($r);
        while ($row = mysqli_fetch_assoc($r)) {
            $courses[] = $row;
        }
    }
}

$students = [];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT s.first_name, s.last_name, s.reg_number, s.year_of_study FROM students s WHERE s.department_id = $departmentId ORDER BY s.last_name, s.first_name LIMIT 20");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $students[] = $row;
        }
    }
}

$lecturers = [];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT first_name, last_name, title, phone FROM lecturers WHERE department_id = $departmentId ORDER BY last_name, first_name");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $lecturers[] = $row;
        }
    }
}

$recentResults = [];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT res.marks, res.grade, c.course_code, c.course_name, s.first_name, s.last_name
        FROM results res
        JOIN students s ON s.student_id = res.student_id
        JOIN courses c ON c.course_id = res.course_id
        WHERE s.department_id = $departmentId
        ORDER BY res.created_at DESC LIMIT 10");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recentResults[] = $row;
        }
    }
}

$avgGpa = 'N/A';
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT AVG(res.grade_points) AS avg_gpa
        FROM results res
        JOIN students s ON s.student_id = res.student_id
        WHERE s.department_id = $departmentId AND res.grade_points IS NOT NULL");
    if ($r) {
        $row = mysqli_fetch_assoc($r);
        if ($row && $row['avg_gpa'] !== null) {
            $avgGpa = number_format((float)$row['avg_gpa'], 2);
        }
    }
}

$announcements = [];
$r = @mysqli_query($conn, "SELECT title, message, created_at FROM notifications WHERE target_role IN ('all','hod') ORDER BY created_at DESC LIMIT 5");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $announcements[] = $row;
    }
}

$pendingAssignments = [];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT a.title, a.due_date, c.course_code
        FROM assignments a
        JOIN courses c ON c.course_id = a.course_id
        WHERE c.department_id = $departmentId AND a.due_date >= NOW()
        ORDER BY a.due_date ASC LIMIT 5");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $pendingAssignments[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div>
                    <h2>HOD Panel</h2>
                    <p><?= e($departmentName) ?></p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link active" href="dashboard.php">Overview</a>
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
                <div>
                    <p class="eyebrow">Head of Department</p>
                    <h1>HOD Dashboard</h1>
                </div>
                <div class="dashboard-chip">Welcome, <?= e($userName) ?></div>
            </div>

            <div class="dashboard-grid">
                <!-- Hero Overview -->
                <section class="dashboard-card hero-card">
                    <div>
                        <p class="eyebrow">Department Overview</p>
                        <h2 style="margin: 0 0 8px;"><?= e($departmentName) ?></h2>
                        <p class="muted">Manage your department's courses, students, and staff.</p>
                    </div>
                    <div class="hero-metrics">
                        <div>
                            <strong><?= $studentCount ?></strong>
                            <span>Students</span>
                        </div>
                        <div>
                            <strong><?= $lecturerCount ?></strong>
                            <span>Lecturers</span>
                        </div>
                        <div>
                            <strong><?= $courseCount ?></strong>
                            <span>Courses</span>
                        </div>
                        <div>
                            <strong><?= e($avgGpa) ?></strong>
                            <span>Avg GPA</span>
                        </div>
                    </div>
                </section>

                <!-- Courses -->
                <section class="dashboard-card" id="courses-section" style="grid-column: span 2;">
                    <p class="eyebrow">Department Courses</p>
                    <h3 style="margin: 0 0 12px;">Courses in <?= e($departmentName) ?></h3>
                    <?php if (!empty($courses)) : ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Course Name</th>
                                        <th>Credits</th>
                                        <th>Lecturer</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $c) : ?>
                                        <tr>
                                            <td><?= e($c['course_code']) ?></td>
                                            <td><?= e($c['course_name']) ?></td>
                                            <td><?= e((string)$c['credits']) ?></td>
                                            <td><?= e($c['lecturer_name'] ?? 'TBD') ?></td>
                                            <td><?= e($c['semester'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="muted">No courses assigned to this department yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Students -->
                <section class="dashboard-card" id="students-section">
                    <p class="eyebrow">Students</p>
                    <h3 style="margin: 0 0 12px;">Department Students (<?= $studentCount ?>)</h3>
                    <?php if (!empty($students)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($students as $s) : ?>
                                <li><?= e($s['first_name'] . ' ' . $s['last_name']) ?> — <?= e($s['reg_number']) ?> (Year <?= e((string)$s['year_of_study']) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No students enrolled in this department yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Lecturers -->
                <section class="dashboard-card" id="lecturers-section">
                    <p class="eyebrow">Lecturers</p>
                    <h3 style="margin: 0 0 12px;">Department Staff (<?= $lecturerCount ?>)</h3>
                    <?php if (!empty($lecturers)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($lecturers as $l) : ?>
                                <li><?= e($l['first_name'] . ' ' . $l['last_name']) ?><?php if (!empty($l['title'])) : ?> — <?= e($l['title']) ?><?php endif; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No lecturers assigned to this department yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Recent Results -->
                <section class="dashboard-card" id="results-section">
                    <p class="eyebrow">Recent Results</p>
                    <h3 style="margin: 0 0 12px;">Department Performance</h3>
                    <?php if (!empty($recentResults)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($recentResults as $rr) : ?>
                                <li><?= e($rr['first_name'] . ' ' . $rr['last_name']) ?> — <?= e($rr['course_code']) ?>: <?= e($rr['grade'] ?? 'N/A') ?> (<?= e($rr['marks'] !== null ? number_format((float)$rr['marks'], 1) . '%' : 'N/A') ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No results published for this department yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Upcoming Assignments -->
                <section class="dashboard-card">
                    <p class="eyebrow">Upcoming Assignments</p>
                    <?php if (!empty($pendingAssignments)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($pendingAssignments as $pa) : ?>
                                <li><?= e($pa['course_code']) ?>: <?= e($pa['title']) ?> — Due <?= e(date('M d, Y', strtotime($pa['due_date']))) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No upcoming assignments.</p>
                    <?php endif; ?>
                </section>

                <!-- Announcements -->
                <section class="dashboard-card">
                    <p class="eyebrow">Announcements</p>
                    <?php if (!empty($announcements)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($announcements as $a) : ?>
                                <li><strong><?= e($a['title']) ?></strong> — <?= e(mb_strimwidth($a['message'], 0, 80, '...')) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No announcements at this time.</p>
                    <?php endif; ?>
                </section>

            </div>
        </main>
    </div>
</body>
</html>
