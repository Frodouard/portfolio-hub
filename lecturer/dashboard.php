<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireLecturer();

$userName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Lecturer');
$lecturerId = (int)($_SESSION['lecturer_id'] ?? 0);

// Fetch lecturer info
$lecturer = null;
if ($lecturerId) {
    $r = @mysqli_query($conn, "SELECT l.first_name, l.last_name, l.title, l.department_id, d.department_name
        FROM lecturers l
        LEFT JOIN departments d ON d.department_id = l.department_id
        WHERE l.lecturer_id = $lecturerId");
    if ($r) { $lecturer = mysqli_fetch_assoc($r); }
}

$departmentName = $lecturer['department_name'] ?? 'Department';

// Courses assigned to this lecturer
$courses = [];
$totalStudentsInMyCourses = 0;
if ($lecturerId) {
    $r = @mysqli_query($conn, "SELECT c.course_id, c.course_code, c.course_name, c.credits, c.semester
        FROM courses c
        WHERE c.lecturer_name = CONCAT('" . mysqli_real_escape_string($conn, $lecturer['first_name'] ?? '') . "', ' ', '" . mysqli_real_escape_string($conn, $lecturer['last_name'] ?? '') . "')
        ORDER BY c.course_code");
    
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $courses[] = $row;
        }
    }
}

// Enrolled students per course
$courseStudents = [];
foreach ($courses as &$course) {
    $r = @mysqli_query($conn, "SELECT s.first_name, s.last_name, s.reg_number
        FROM registrations reg
        JOIN students s ON s.student_id = reg.student_id
        WHERE reg.course_id = {$course['course_id']} AND reg.status = 'registered'
        ORDER BY s.last_name, s.first_name");
    $students = [];
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $students[] = $row;
        }
    }
    $course['student_count'] = count($students);
    $totalStudentsInMyCourses += count($students);
    $courseStudents[$course['course_id']] = $students;
}
unset($course);

// Recent attendance
$recentAttendance = [];
if ($lecturerId && !empty($courses)) {
    $courseIds = array_column($courses, 'course_id');
    $ids = implode(',', array_map('intval', $courseIds));
    $r = @mysqli_query($conn, "SELECT a.status, a.session_date, c.course_code, s.first_name, s.last_name
        FROM attendance a
        JOIN courses c ON c.course_id = a.course_id
        JOIN students s ON s.student_id = a.student_id
        WHERE a.course_id IN ($ids)
        ORDER BY a.session_date DESC LIMIT 10");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $recentAttendance[] = $row;
        }
    }
}

// Pending assignments
$pendingAssignments = [];
if (!empty($courses)) {
    $courseIds = array_column($courses, 'course_id');
    $ids = implode(',', array_map('intval', $courseIds));
    $r = @mysqli_query($conn, "SELECT a.title, a.due_date, a.course_id, c.course_code
        FROM assignments a
        JOIN courses c ON c.course_id = a.course_id
        WHERE a.course_id IN ($ids)
        ORDER BY a.due_date ASC LIMIT 10");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $pendingAssignments[] = $row;
        }
    }
}

// Announcements
$announcements = [];
$r = @mysqli_query($conn, "SELECT title, message, created_at FROM notifications WHERE target_role IN ('all','lecturer') ORDER BY created_at DESC LIMIT 5");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $announcements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div>
                    <h2>Lecturer Panel</h2>
                    <p><?= e($departmentName) ?></p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link active" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
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
                    <h1>Lecturer Dashboard</h1>
                </div>
                <div class="dashboard-chip">Welcome, <?= e($userName) ?></div>
            </div>

            <div class="dashboard-grid">
                <!-- Hero Overview -->
                <section class="dashboard-card hero-card">
                    <div>
                        <p class="eyebrow">My Overview</p>
                        <h2 style="margin: 0 0 8px;">Lecturer Dashboard</h2>
                        <p class="muted">Manage your courses, track student progress, and update grades.</p>
                    </div>
                    <div class="hero-metrics">
                        <div>
                            <strong><?= count($courses) ?></strong>
                            <span>My Courses</span>
                        </div>
                        <div>
                            <strong><?= $totalStudentsInMyCourses ?></strong>
                            <span>Total Students</span>
                        </div>
                        <div>
                            <strong><?= count($pendingAssignments) ?></strong>
                            <span>Assignments</span>
                        </div>
                    </div>
                </section>

                <!-- My Courses -->
                <section class="dashboard-card" id="courses-section" style="grid-column: span 2;">
                    <p class="eyebrow">My Courses</p>
                    <h3 style="margin: 0 0 12px;">Courses Assigned</h3>
                    <?php if (!empty($courses)) : ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Course Name</th>
                                        <th>Credits</th>
                                        <th>Semester</th>
                                        <th>Enrolled Students</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $c) : ?>
                                        <tr>
                                            <td><?= e($c['course_code']) ?></td>
                                            <td><?= e($c['course_name']) ?></td>
                                            <td><?= e((string)$c['credits']) ?></td>
                                            <td><?= e($c['semester'] ?? 'N/A') ?></td>
                                            <td><?= $c['student_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="muted">No courses are currently assigned to you.</p>
                    <?php endif; ?>
                </section>

                <!-- Students per course -->
                <section class="dashboard-card" id="students-section" style="grid-column: span 2;">
                    <p class="eyebrow">Enrolled Students</p>
                    <h3 style="margin: 0 0 12px;">Students by Course</h3>
                    <?php if (!empty($courses)) : ?>
                        <?php foreach ($courses as $c) : ?>
                            <div style="margin-bottom: 16px;">
                                <h4 style="margin: 0 0 6px;"><?= e($c['course_code']) ?> — <?= e($c['course_name']) ?></h4>
                                <?php if (!empty($courseStudents[$c['course_id']])) : ?>
                                    <ul class="dashboard-list">
                                        <?php foreach ($courseStudents[$c['course_id']] as $s) : ?>
                                            <li><?= e($s['first_name'] . ' ' . $s['last_name']) ?> — <?= e($s['reg_number']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <p class="muted">No students enrolled.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="muted">No courses to display.</p>
                    <?php endif; ?>
                </section>

                <!-- Recent Attendance -->
                <section class="dashboard-card" id="attendance-section">
                    <p class="eyebrow">Recent Attendance</p>
                    <h3 style="margin: 0 0 12px;">Attendance Records</h3>
                    <?php if (!empty($recentAttendance)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($recentAttendance as $a) : ?>
                                <li><?= e($a['first_name'] . ' ' . $a['last_name']) ?> — <?= e($a['course_code']) ?>: <?= e(ucfirst($a['status'])) ?> (<?= e(date('M d', strtotime($a['session_date']))) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No attendance records yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Assignments -->
                <section class="dashboard-card" id="assignments-section">
                    <p class="eyebrow">Assignments</p>
                    <h3 style="margin: 0 0 12px;">Upcoming Assignments</h3>
                    <?php if (!empty($pendingAssignments)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($pendingAssignments as $a) : ?>
                                <li><?= e($a['course_code']) ?>: <?= e($a['title']) ?> — Due <?= e(date('M d, Y', strtotime($a['due_date']))) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No assignments created yet.</p>
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
