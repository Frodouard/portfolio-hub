<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$registeredCourses = [];

if ($studentId) {
    $r = @mysqli_query($conn, "SELECT c.course_code, c.course_name
        FROM registrations reg
        JOIN courses c ON c.course_id = reg.course_id
        WHERE reg.student_id = $studentId AND reg.status = 'registered'
        ORDER BY c.course_code");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $registeredCourses[] = $row;
        }
    }
}

$examSchedule = [
    'Database Systems' => ['date' => '10 Aug 2026', 'time' => '09:00 - 11:00', 'room' => 'Lab 2'],
    'Web Programming' => ['date' => '12 Aug 2026', 'time' => '09:00 - 11:00', 'room' => 'R-12'],
    'Software Engineering' => ['date' => '14 Aug 2026', 'time' => '09:00 - 11:00', 'room' => 'R-08'],
    'Object-Oriented Programming' => ['date' => '16 Aug 2026', 'time' => '14:00 - 16:00', 'room' => 'Lab 1'],
    'Data Structures & Algorithms' => ['date' => '18 Aug 2026', 'time' => '09:00 - 11:00', 'room' => 'R-10'],
    'Discrete Mathematics' => ['date' => '20 Aug 2026', 'time' => '14:00 - 16:00', 'room' => 'R-05'],
    'Linear Algebra' => ['date' => '22 Aug 2026', 'time' => '09:00 - 11:00', 'room' => 'R-06'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Timetable | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Exam timetable</p>
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
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link active" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Examination schedule</p>
                <h2>Exam Timetable</h2>

                <?php if (!empty($registeredCourses)): ?>
                    <div class="info-tiles" style="margin-bottom: 20px;">
                        <div class="info-tile">
                            <div class="label">Exams Scheduled</div>
                            <div class="value"><?= count($registeredCourses) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="overflow-x: auto;">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($registeredCourses)): ?>
                                <?php foreach ($registeredCourses as $c): ?>
                                    <?php
                                    $schedule = $examSchedule[$c['course_name']] ?? ['date' => 'TBD', 'time' => 'TBD', 'room' => 'TBD'];
                                    ?>
                                    <tr>
                                        <td><?= e($c['course_code']) ?></td>
                                        <td><?= e($c['course_name']) ?></td>
                                        <td><?= e($schedule['date']) ?></td>
                                        <td><?= e($schedule['time']) ?></td>
                                        <td><?= e($schedule['room']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="muted">No registered courses. Exam timetable will appear once you register for courses.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
