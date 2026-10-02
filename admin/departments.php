<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid form submission.';
        $msgType = 'error';
    } else {
        $action = $_POST['action'];

        if ($action === 'add_department') {
            $name = trim($_POST['department_name'] ?? '');
            $code = trim($_POST['department_code'] ?? '');
            if ($name !== '' && $code !== '') {
                $stmt = mysqli_prepare($conn, 'INSERT INTO departments (department_name, department_code) VALUES (?, ?)');
                mysqli_stmt_bind_param($stmt, 'ss', $name, $code);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = 'Department added.';
                    $msgType = 'success';
                } else {
                    $msg = 'Department code already exists.';
                    $msgType = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $msg = 'Name and code are required.';
                $msgType = 'error';
            }
        } elseif ($action === 'edit_department') {
            $deptId = (int)($_POST['department_id'] ?? 0);
            $name = trim($_POST['department_name'] ?? '');
            $code = trim($_POST['department_code'] ?? '');
            if ($deptId > 0 && $name !== '' && $code !== '') {
                $stmt = mysqli_prepare($conn, 'UPDATE departments SET department_name = ?, department_code = ? WHERE department_id = ?');
                mysqli_stmt_bind_param($stmt, 'ssi', $name, $code, $deptId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'Department updated.';
                $msgType = 'success';
            }
        } elseif ($action === 'delete_department') {
            $deptId = (int)($_POST['department_id'] ?? 0);
            if ($deptId > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE students SET department_id = NULL WHERE department_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $deptId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $stmt = mysqli_prepare($conn, "UPDATE lecturers SET department_id = NULL WHERE department_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $deptId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $stmt = mysqli_prepare($conn, "DELETE FROM departments WHERE department_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $deptId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'Department deleted.';
                $msgType = 'success';
            }
        }
    }
}

$departments = [];
$r = @mysqli_query($conn, "SELECT d.department_id, d.department_name, d.department_code,
    (SELECT COUNT(*) FROM students s WHERE s.department_id = d.department_id) AS student_count,
    (SELECT COUNT(*) FROM lecturers l WHERE l.department_id = d.department_id) AS lecturer_count,
    (SELECT COUNT(*) FROM courses c WHERE c.department_id = d.department_id) AS course_count
    FROM departments d ORDER BY d.department_name");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $departments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | AUCA Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Admin Panel</h2><p>Department management</p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="users.php">Users</a>
                <a class="dashboard-nav-link active" href="departments.php">Departments</a>
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
                    <p class="eyebrow">Administration</p>
                    <h1>Department Management</h1>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <section class="dashboard-card">
                    <p class="eyebrow">Add Department</p>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="add_department">
                        <div class="form-group">
                            <label>Department Name</label>
                            <input type="text" name="department_name" required placeholder="e.g. Computer Science">
                        </div>
                        <div class="form-group">
                            <label>Department Code</label>
                            <input type="text" name="department_code" required placeholder="e.g. CS" maxlength="10">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Department</button>
                    </form>
                </section>

                <section class="dashboard-card">
                    <p class="eyebrow">All Departments</p>
                    <h3 style="margin: 0 0 12px;">Departments (<?= count($departments) ?>)</h3>
                    <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $d): ?>
                            <div style="border: 1px solid var(--color-border); border-radius: 12px; padding: 14px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?= e($d['department_name']) ?></strong> (<?= e($d['department_code']) ?>)
                                        <br><span class="muted" style="font-size: 12px;"><?= $d['student_count'] ?> students, <?= $d['lecturer_count'] ?> lecturers, <?= $d['course_count'] ?> courses</span>
                                    </div>
                                    <div style="display: flex; gap: 6px;">
                                        <button onclick="editDept(<?= $d['department_id'] ?>, '<?= e($d['department_name']) ?>', '<?= e($d['department_code']) ?>')" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px;">Edit</button>
                                        <form method="POST" onsubmit="return confirm('Delete this department?');" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="delete_department">
                                            <input type="hidden" name="department_id" value="<?= $d['department_id'] ?>">
                                            <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px; background: var(--color-danger);">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted">No departments yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Edit Modal -->
                <section class="dashboard-card" id="editSection" style="grid-column: span 2; display: none;">
                    <p class="eyebrow">Edit Department</p>
                    <form method="POST" class="auth-form" style="max-width: 400px;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="edit_department">
                        <input type="hidden" name="department_id" id="editDeptId">
                        <div class="form-group">
                            <label>Department Name</label>
                            <input type="text" name="department_name" id="editDeptName" required>
                        </div>
                        <div class="form-group">
                            <label>Department Code</label>
                            <input type="text" name="department_code" id="editDeptCode" required maxlength="10">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-primary" style="background: var(--color-text-muted);" onclick="document.getElementById('editSection').style.display='none'">Cancel</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
    <script>
    function editDept(id, name, code) {
        document.getElementById('editDeptId').value = id;
        document.getElementById('editDeptName').value = name;
        document.getElementById('editDeptCode').value = code;
        document.getElementById('editSection').style.display = '';
        document.getElementById('editSection').scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>
</html>
