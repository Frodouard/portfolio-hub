<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireHod();

$departmentId = (int)($_SESSION['department_id'] ?? 0);
$departmentName = $_SESSION['department_name'] ?? 'Department';

$search = trim($_GET['q'] ?? '');
$filterYear = $_GET['year'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$students = [];
if ($departmentId) {
    $where = "s.department_id = $departmentId";
    $params = [];
    $types = '';
    if ($search !== '') {
        $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.reg_number LIKE ?)";
        $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss';
    }
    if ($filterYear !== '') {
        $where .= ' AND s.year_of_study = ?'; $params[] = $filterYear; $types .= 'i';
    }
    if ($filterStatus !== '') {
        $where .= ' AND u.status = ?'; $params[] = $filterStatus; $types .= 's';
    }
    $sql = "SELECT s.student_id, s.first_name, s.last_name, s.reg_number, s.year_of_study, s.phone, u.email, u.status
        FROM students s JOIN users u ON u.user_id = s.user_id
        WHERE $where ORDER BY s.last_name, s.first_name";
    $r = @mysqli_query($conn, $sql);
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $students[] = $row; } }
}

$totalCount = 0;
if ($departmentId) {
    $cr = @mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM students s WHERE s.department_id = $departmentId");
    if ($cr) { $row = mysqli_fetch_assoc($cr); $totalCount = $row['cnt']; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | HOD - <?= e($departmentName) ?></title>
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
                <a class="dashboard-nav-link active" href="students.php">Students</a>
                <a class="dashboard-nav-link" href="lecturers.php">Lecturers</a>
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
                    <h1>Department Students</h1>
                </div>
                <div class="dashboard-chip"><?= $totalCount ?> total | <?= count($students) ?> shown</div>
            </div>

            <section class="dashboard-card" style="margin-bottom: 20px;">
                <p class="eyebrow">Filter</p>
                <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or reg number">
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <select name="year">
                            <option value="">All years</option>
                            <option value="1" <?= $filterYear === '1' ? 'selected' : '' ?>>Year 1</option>
                            <option value="2" <?= $filterYear === '2' ? 'selected' : '' ?>>Year 2</option>
                            <option value="3" <?= $filterYear === '3' ? 'selected' : '' ?>>Year 3</option>
                            <option value="4" <?= $filterYear === '4' ? 'selected' : '' ?>>Year 4</option>
                            <option value="5" <?= $filterYear === '5' ? 'selected' : '' ?>>Year 5</option>
                        </select>
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
                <?php if (!empty($students)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Reg Number</th>
                                    <th>Year</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><a href="student_profile.php?student_id=<?= $s['student_id'] ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></a></td>
                                        <td><?= e($s['reg_number']) ?></td>
                                        <td>Year <?= $s['year_of_study'] ?></td>
                                        <td><?= e($s['email']) ?></td>
                                        <td><?= e($s['phone'] ?? 'N/A') ?></td>
                                        <td><span style="color: <?= $s['status'] === 'active' ? '#15803d' : '#dc2626' ?>; text-transform: capitalize;"><?= e($s['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No students found for this filter.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
