<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$assignments = [];

if ($studentId) {
    $r = @mysqli_query($conn, "SELECT a.assignment_id, a.title, a.description, a.due_date, a.created_at,
        c.course_code, c.course_name
        FROM assignments a
        JOIN courses c ON c.course_id = a.course_id
        JOIN registrations reg ON reg.course_id = a.course_id AND reg.student_id = $studentId
        WHERE reg.status = 'registered'
        ORDER BY a.due_date ASC");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $assignments[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>My assignments</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link active" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Assignments</p>
                <h2>My Assignments</h2>

                <?php if (!empty($assignments)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $a):
                                    $dueDate = strtotime($a['due_date']);
                                    $isPast = $dueDate < time();
                                ?>
                                    <tr>
                                        <td><strong><?= e($a['course_code']) ?></strong></td>
                                        <td><?= e($a['title']) ?></td>
                                        <td><?= e(mb_strimwidth($a['description'] ?? '', 0, 60, '...')) ?></td>
                                        <td><?= e(date('M d, Y', $dueDate)) ?></td>
                                        <td>
                                            <?php if ($isPast): ?>
                                                <span style="color: #dc2626; font-weight: 600;">Past Due</span>
                                            <?php else: ?>
                                                <span style="color: #15803d; font-weight: 600;">Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No assignments posted yet for your courses.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
