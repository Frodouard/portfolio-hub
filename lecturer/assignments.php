<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireLecturer();

$lecturerId = (int)($_SESSION['lecturer_id'] ?? 0);
$fullName = $_SESSION['full_name'] ?? 'Lecturer';
$departmentName = '';

if ($lecturerId) {
    $r = @mysqli_query($conn, "SELECT l.first_name, l.last_name, d.department_name FROM lecturers l LEFT JOIN departments d ON d.department_id = l.department_id WHERE l.lecturer_id = $lecturerId");
    if ($r) { $lecturer = mysqli_fetch_assoc($r); }
    $departmentName = $lecturer['department_name'] ?? 'Department';
    $fullName = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid form submission.';
        $msgType = 'error';
    } elseif ($_POST['action'] === 'add_assignment') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $dueDate = $_POST['due_date'] ?? '';
        if ($courseId > 0 && $title !== '' && $dueDate !== '') {
            $stmt = mysqli_prepare($conn, 'INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'isss', $courseId, $title, $description, $dueDate);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = 'Assignment created.';
            $msgType = 'success';
        }
    } elseif ($_POST['action'] === 'delete_assignment') {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        if ($assignmentId > 0) {
            mysqli_query($conn, "DELETE FROM assignments WHERE assignment_id = $assignmentId");
            $msg = 'Assignment deleted.';
            $msgType = 'success';
        }
    }
}

$courses = [];
if ($fullName) {
    $r = @mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses WHERE lecturer_name = '" . mysqli_real_escape_string($conn, $fullName) . "' ORDER BY course_code");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courses[] = $row; } }
}

$allAssignments = [];
if (!empty($courses)) {
    $ids = implode(',', array_column($courses, 'course_id'));
    $r = @mysqli_query($conn, "SELECT a.assignment_id, a.title, a.description, a.due_date, a.created_at, c.course_code, c.course_name FROM assignments a JOIN courses c ON c.course_id = a.course_id WHERE a.course_id IN ($ids) ORDER BY a.due_date ASC");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $allAssignments[] = $row; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments | Lecturer Panel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Lecturer Panel</h2><p><?= e($departmentName) ?></p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="students.php">Students</a>
                <a class="dashboard-nav-link" href="grades.php">Grades</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link active" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="profile.php">Profile</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="dashboard-header">
                <div><p class="eyebrow">Lecturer</p><h1>Assignments</h1></div>
            </div>
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>
            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <p class="eyebrow">Create Assignment</p>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="add_assignment">
                        <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" required>
                                <option value="">Select course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['course_id'] ?>"><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" required placeholder="Assignment title">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3" style="padding:11px 14px; border:1px solid var(--color-border); border-radius: var(--radius-sm); font-size:14px; font-family:inherit; resize: vertical;"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Due Date</label>
                            <input type="datetime-local" name="due_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Assignment</button>
                    </form>
                </section>
                <section class="dashboard-card">
                    <p class="eyebrow">All Assignments</p>
                    <h3 style="margin: 0 0 12px;">Assignments (<?= count($allAssignments) ?>)</h3>
                    <?php if (!empty($allAssignments)): ?>
                        <?php foreach ($allAssignments as $a): ?>
                            <div style="border: 1px solid var(--color-border); border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <strong><?= e($a['title']) ?></strong>
                                        <span style="color: var(--color-primary); font-size: 13px;"> (<?= e($a['course_code']) ?>)</span>
                                        <p class="muted" style="margin: 4px 0; font-size: 13px;"><?= e(mb_strimwidth($a['description'] ?? '', 0, 100, '...')) ?></p>
                                        <span style="font-size: 11px; color: var(--color-text-muted);">Due: <?= e(date('M d, Y H:i', strtotime($a['due_date']))) ?></span>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Delete?');" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete_assignment">
                                        <input type="hidden" name="assignment_id" value="<?= $a['assignment_id'] ?>">
                                        <button type="submit" class="btn btn-primary" style="padding:4px 10px; font-size:12px; background:var(--color-danger);">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted">No assignments yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
