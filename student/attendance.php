<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$fullName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');

$attendanceRecords = [];
if ($studentId) {
    $r = @mysqli_query($conn, "SELECT a.status, a.session_date, c.course_code, c.course_name
        FROM attendance a
        JOIN courses c ON c.course_id = a.course_id
        JOIN registrations reg ON reg.course_id = a.course_id AND reg.student_id = a.student_id
        WHERE a.student_id = $studentId AND reg.status = 'registered'
        ORDER BY a.session_date DESC");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $attendanceRecords[] = $row;
        }
    }
}

$courseStats = [];
if ($studentId) {
    $r = @mysqli_query($conn, "SELECT c.course_code, c.course_name,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) AS excused_count,
        COUNT(a.attendance_id) AS total_sessions
        FROM attendance a
        JOIN courses c ON c.course_id = a.course_id
        JOIN registrations reg ON reg.course_id = a.course_id AND reg.student_id = a.student_id
        WHERE a.student_id = $studentId AND reg.status = 'registered'
        GROUP BY c.course_id, c.course_code, c.course_name
        ORDER BY c.course_code");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $courseStats[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>My attendance</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link active" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Attendance</p>
                <h2>My Attendance</h2>

                <?php if (!empty($courseStats)): ?>
                    <div style="overflow-x: auto; margin-bottom: 24px;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Total Sessions</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Excused</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courseStats as $s): ?>
                                    <?php $rate = $s['total_sessions'] > 0 ? round(($s['present_count'] / $s['total_sessions']) * 100) : 0; ?>
                                    <tr>
                                        <td><strong><?= e($s['course_code']) ?></strong> — <?= e($s['course_name']) ?></td>
                                        <td><?= $s['total_sessions'] ?></td>
                                        <td style="color: #15803d;"><?= $s['present_count'] ?></td>
                                        <td style="color: #dc2626;"><?= $s['absent_count'] ?></td>
                                        <td style="color: var(--color-warning, #f59e0b);"><?= $s['excused_count'] ?></td>
                                        <td>
                                            <strong style="color: <?= $rate >= 75 ? '#15803d' : ($rate >= 50 ? '#f59e0b' : '#dc2626') ?>;"><?= $rate ?>%</strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No attendance records yet.</p>
                <?php endif; ?>

                <?php if (!empty($attendanceRecords)): ?>
                    <h3>Detailed Records</h3>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceRecords as $a): ?>
                                    <tr>
                                        <td><?= e(date('M d, Y', strtotime($a['session_date']))) ?></td>
                                        <td><strong><?= e($a['course_code']) ?></strong> — <?= e($a['course_name']) ?></td>
                                        <td>
                                            <span style="color: <?= $a['status'] === 'present' ? '#15803d' : ($a['status'] === 'absent' ? '#dc2626' : '#f59e0b') ?>; text-transform: capitalize;">
                                                <?= e(ucfirst($a['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
