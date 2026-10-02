<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireHod();

$departmentId = (int)($_SESSION['department_id'] ?? 0);
$departmentName = $_SESSION['department_name'] ?? 'Department';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid form submission.';
        $msgType = 'error';
    } elseif ($_POST['action'] === 'add_announcement') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $target = $_POST['target_role'] ?? 'all';
        if ($title !== '' && $message !== '') {
            $stmt = mysqli_prepare($conn, 'INSERT INTO notifications (title, message, target_role, created_by) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sssi', $title, $message, $target, $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = 'Announcement posted.';
            $msgType = 'success';
        }
    } elseif ($_POST['action'] === 'delete_announcement') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($notifId > 0 && $userId > 0) {
            mysqli_query($conn, "DELETE FROM notifications WHERE notification_id = $notifId AND created_by = $userId");
            $msg = 'Announcement deleted.';
            $msgType = 'success';
        }
    }
}

$notifications = [];
$r = @mysqli_query($conn, "SELECT notification_id, title, message, target_role, created_at FROM notifications WHERE target_role IN ('all','hod') ORDER BY created_at DESC");
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
    <title>Announcements | HOD - <?= e($departmentName) ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>HOD Panel</h2><p><?= e($departmentName) ?></p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="students.php">Students</a>
                <a class="dashboard-nav-link" href="lecturers.php">Lecturers</a>
                <a class="dashboard-nav-link" href="courses.php">Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="enrollments.php">Enrollments</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link active" href="announcements.php">Announcements</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow"><?= e($departmentName) ?></p>
                    <h1>Announcements</h1>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <p class="eyebrow">New Announcement</p>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="add_announcement">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" required>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" required rows="4" style="padding:11px 14px; border:1px solid var(--color-border); border-radius: var(--radius-sm); font-size:14px; font-family:inherit; resize: vertical;"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Target</label>
                            <select name="target_role">
                                <option value="all">Everyone</option>
                                <option value="student">Students</option>
                                <option value="lecturer">Lecturers</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Post Announcement</button>
                    </form>
                </section>

                <section class="dashboard-card">
                    <p class="eyebrow">All Announcements</p>
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $n): ?>
                            <div style="border: 1px solid var(--color-border); border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <strong><?= e($n['title']) ?></strong>
                                        <p class="muted" style="margin: 4px 0; font-size: 13px;"><?= e(mb_strimwidth($n['message'], 0, 120, '...')) ?></p>
                                        <span style="font-size: 11px; color: var(--color-text-muted);">Target: <?= e($n['target_role']) ?> | <?= e($n['created_at']) ?></span>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Delete?');" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete_announcement">
                                        <input type="hidden" name="notification_id" value="<?= $n['notification_id'] ?>">
                                        <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px; background: var(--color-danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted">No announcements.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
