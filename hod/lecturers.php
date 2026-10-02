<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireHod();

$departmentId = (int)($_SESSION['department_id'] ?? 0);
$departmentName = $_SESSION['department_name'] ?? 'Department';

$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';

$lecturers = [];
if ($departmentId) {
    $where = "l.department_id = $departmentId";
    $params = [];
    $types = '';
    if ($search !== '') {
        $where .= " AND (l.first_name LIKE ? OR l.last_name LIKE ? OR l.title LIKE ?)";
        $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss';
    }
    if ($filterStatus !== '') {
        $where .= ' AND u.status = ?'; $params[] = $filterStatus; $types .= 's';
    }
    $sql = "SELECT l.lecturer_id, l.first_name, l.last_name, l.title, l.phone, u.email, u.status
        FROM lecturers l JOIN users u ON u.user_id = l.user_id
        WHERE $where ORDER BY l.last_name, l.first_name";
    $r = @mysqli_query($conn, $sql);
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $lecturers[] = $row; } }
}

$totalCount = 0;
if ($departmentId) {
    $cr = @mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM lecturers l WHERE l.department_id = $departmentId");
    if ($cr) { $row = mysqli_fetch_assoc($cr); $totalCount = $row['cnt']; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturers | HOD - <?= e($departmentName) ?></title>
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
                <a class="dashboard-nav-link active" href="lecturers.php">Lecturers</a>
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
                    <p class="eyebrow"><?= e($departmentName) ?></p>
                    <h1>Department Lecturers</h1>
                </div>
                <div class="dashboard-chip"><?= $totalCount ?> total | <?= count($lecturers) ?> shown</div>
            </div>

            <section class="dashboard-card" style="margin-bottom: 20px;">
                <p class="eyebrow">Filter</p>
                <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or title">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All</option>
                            <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </section>

            <section class="dashboard-card">
                <?php if (!empty($lecturers)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Title</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lecturers as $l): ?>
                                    <tr>
                                        <td><a href="lecturer_profile.php?lecturer_id=<?= $l['lecturer_id'] ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;"><?= e($l['first_name'] . ' ' . $l['last_name']) ?></a></td>
                                        <td><?= e($l['title'] ?? 'N/A') ?></td>
                                        <td><?= e($l['email']) ?></td>
                                        <td><?= e($l['phone'] ?? 'N/A') ?></td>
                                        <td><span style="color: <?= $l['status'] === 'active' ? '#15803d' : '#dc2626' ?>; text-transform: capitalize;"><?= e($l['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No lecturers found for this filter.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
