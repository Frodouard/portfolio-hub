<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$filterCourse = (int)($_GET['course_id'] ?? 0);
$filterDate = $_GET['date'] ?? '';
$filterStudent = trim($_GET['student'] ?? '');

$courses = [];
$r = @mysqli_query($conn, "SELECT DISTINCT c.course_id, c.course_code, c.course_name FROM attendance a JOIN courses c ON c.course_id = a.course_id ORDER BY c.course_code");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courses[] = $row; } }

$attendance = [];
$where = '1=1';
$params = [];
$types = '';
if ($filterCourse > 0) { $where .= ' AND a.course_id = ?'; $params[] = $filterCourse; $types .= 'i'; }
if ($filterDate !== '') { $where .= ' AND a.session_date = ?'; $params[] = $filterDate; $types .= 's'; }
if ($filterStudent !== '') { $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.reg_number LIKE ?)"; $s = "%$filterStudent%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

$r = @mysqli_query($conn, "SELECT a.attendance_id, a.session_date, a.status,
    s.first_name, s.last_name, s.reg_number,
    c.course_code, c.course_name, c.lecturer_name
    FROM attendance a
    JOIN students s ON s.student_id = a.student_id
    JOIN courses c ON c.course_id = a.course_id
    WHERE $where
    ORDER BY a.session_date DESC, c.course_code, s.last_name
    LIMIT 200");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $attendance[] = $row; } }

$stats = [];
$sr = @mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM attendance GROUP BY status");
if ($sr) { while ($row = mysqli_fetch_assoc($sr)) { $stats[$row['status']] = $row['cnt']; } }
$total = array_sum($stats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | AUCA Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Admin Panel</h2><p>Attendance</p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="users.php">Users</a>
                <a class="dashboard-nav-link" href="departments.php">Departments</a>
                <a class="dashboard-nav-link" href="courses.php">Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="enrollments.php">Enrollments</a>
                <a class="dashboard-nav-link active" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Administration</p>
                    <h1>Attendance Records</h1>
                </div>
            </div>

            <div class="info-tiles" style="margin-bottom: 20px;">
                <div class="info-tile"><div class="label">Total Records</div><div class="value"><?= $total ?></div></div>
                <div class="info-tile"><div class="label">Present</div><div class="value" style="color: #15803d;"><?= $stats['present'] ?? 0 ?></div></div>
                <div class="info-tile"><div class="label">Absent</div><div class="value" style="color: #dc2626;"><?= $stats['absent'] ?? 0 ?></div></div>
                <div class="info-tile"><div class="label">Excused</div><div class="value" style="color: #f59e0b;"><?= $stats['excused'] ?? 0 ?></div></div>
            </div>

            <section class="dashboard-card" style="margin-bottom: 20px;">
                <p class="eyebrow">Filter</p>
                <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">All courses</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['course_id'] ?>" <?= $filterCourse == $c['course_id'] ? 'selected' : '' ?>><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date" value="<?= e($filterDate) ?>">
                    </div>
                    <div class="form-group">
                        <label>Student</label>
                        <input type="text" name="student" value="<?= e($filterStudent) ?>" placeholder="Name or reg number">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </section>

            <section class="dashboard-card">
                <p class="eyebrow">Records</p>
                <h3 style="margin: 0 0 12px;">Found: <?= count($attendance) ?></h3>
                <?php if (!empty($attendance)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr><th>Date</th><th>Course</th><th>Student</th><th>Reg Number</th><th>Lecturer</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $a): ?>
                                    <tr>
                                        <td><?= e($a['session_date']) ?></td>
                                        <td><?= e($a['course_code']) ?></td>
                                        <td><?= e($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                        <td><?= e($a['reg_number']) ?></td>
                                        <td><?= e($a['lecturer_name'] ?? 'N/A') ?></td>
                                        <td><span style="color: <?= $a['status'] === 'present' ? '#15803d' : ($a['status'] === 'absent' ? '#dc2626' : '#f59e0b') ?>; font-weight: 600; text-transform: capitalize;"><?= e($a['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No attendance records found.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
