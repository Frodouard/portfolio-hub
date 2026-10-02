<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$studentName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');
$regNumber = 'N/A';
$department = 'Department pending';
$semester = 'Semester ' . (date('n') <= 6 ? 'II' : 'I');

if ($studentId) {
    $stmt = mysqli_prepare($conn, 'SELECT s.reg_number, d.department_name
        FROM students s
        LEFT JOIN departments d ON d.department_id = s.department_id
        WHERE s.student_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $studentId);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($student) {
        $regNumber = $student['reg_number'] ?? 'N/A';
        $department = $student['department_name'] ?? 'Department pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Exam Permit | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>View permit</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link active" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Exam permit preview</p>
                <h2>Exam Permit</h2>
                <div class="dashboard-card" style="margin-top: 16px;">
                    <p><strong>Student:</strong> <?= e($studentName) ?></p>
                    <p><strong>Registration number:</strong> <?= e($regNumber) ?></p>
                    <p><strong>Department:</strong> <?= e($department) ?></p>
                    <p><strong>Semester:</strong> <?= e($semester) ?></p>
                    <p style="margin-top: 12px;"><strong>Status:</strong> Approved</p>
                    <div class="verify-actions" style="margin-top: 14px;">
                        <a href="download_exam_permit.php" class="btn btn-primary">Download exam permit</a>
                        <a href="exam_permit.php" class="btn btn-primary" style="background: var(--color-text-muted);">Back to verification</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
