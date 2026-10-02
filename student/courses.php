<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$registeredCourses = [];

if ($studentId) {
    $r = @mysqli_query($conn, "SELECT c.course_code, c.course_name, c.credits, c.semester, c.lecturer_name,
        r.academic_year, r.status
        FROM registrations r
        JOIN courses c ON c.course_id = r.course_id
        WHERE r.student_id = $studentId AND r.status = 'registered'
        ORDER BY c.course_code");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $registeredCourses[] = $row;
        }
    }
}

$totalCredits = array_sum(array_column($registeredCourses, 'credits'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>My courses</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link active" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Current semester</p>
                <h2>My Courses</h2>

                <div style="margin-bottom: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="enroll_course.php" class="btn btn-primary">Register for courses</a>
                    <a href="download_timetable.php" class="btn btn-primary">Download timetable (.doc)</a>
                    <a href="https://urubutopay.rw/pay-now?origin=internal" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="background: var(--color-accent);">Pay tuition with UrubutoPay</a>
                </div>

                <?php if (!empty($registeredCourses)): ?>
                    <div class="info-tiles" style="margin-bottom: 20px;">
                        <div class="info-tile">
                            <div class="label">Courses Enrolled</div>
                            <div class="value"><?= count($registeredCourses) ?></div>
                        </div>
                        <div class="info-tile">
                            <div class="label">Total Credits</div>
                            <div class="value"><?= $totalCredits ?></div>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Lecturer</th>
                                    <th>Semester</th>
                                    <th>Academic Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registeredCourses as $c): ?>
                                    <tr>
                                        <td><?= e($c['course_code']) ?></td>
                                        <td><?= e($c['course_name']) ?></td>
                                        <td><?= e((string)$c['credits']) ?></td>
                                        <td><?= e($c['lecturer_name'] ?? 'TBD') ?></td>
                                        <td><?= e($c['semester'] ?? 'N/A') ?></td>
                                        <td><?= e($c['academic_year']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No courses enrolled yet. <a href="enroll_course.php">Register for courses</a> to get started.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
