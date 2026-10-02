<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$notifications = [];
$r = @mysqli_query($conn, "SELECT n.title, n.message, n.target_role, n.created_at, u.username
    FROM notifications n
    LEFT JOIN users u ON u.user_id = n.created_by
    WHERE n.target_role IN ('all','student')
    ORDER BY n.created_at DESC");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Announcements</p>
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
                <a class="dashboard-nav-link active" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Campus updates</p>
                <h2>Announcements</h2>
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $n): ?>
                        <div style="border: 1px solid var(--color-border); border-radius: 12px; padding: 16px; margin-bottom: 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <strong style="font-size: 15px;"><?= e($n['title']) ?></strong>
                                    <p style="margin: 6px 0; color: var(--color-text); font-size: 14px;"><?= e($n['message']) ?></p>
                                    <span style="font-size: 12px; color: var(--color-text-muted);">
                                        Posted by <?= e($n['username'] ?? 'System') ?> &mdash; <?= e(date('M d, Y H:i', strtotime($n['created_at']))) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted">No announcements at this time. Check back later.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
