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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid form submission.';
        $msgType = 'error';
    } else {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $sessionDate = $_POST['session_date'] ?? date('Y-m-d');
        $studentIds = $_POST['student_id'] ?? [];
        $statuses = $_POST['status'] ?? [];
        foreach ($studentIds as $idx => $studentId) {
            $studentId = (int)$studentId;
            $status = $statuses[$idx] ?? 'present';
            if ($studentId > 0 && in_array($status, ['present','absent','excused'])) {
                $existing = @mysqli_query($conn, "SELECT attendance_id FROM attendance WHERE student_id = $studentId AND course_id = $courseId AND session_date = '" . mysqli_real_escape_string($conn, $sessionDate) . "'");
                if ($existing && mysqli_num_rows($existing) > 0) {
                    $row = mysqli_fetch_assoc($existing);
                    $stmt = mysqli_prepare($conn, 'UPDATE attendance SET status = ? WHERE attendance_id = ?');
                    mysqli_stmt_bind_param($stmt, 'si', $status, $row['attendance_id']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    $stmt = mysqli_prepare($conn, 'INSERT INTO attendance (student_id, course_id, session_date, status) VALUES (?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'iiss', $studentId, $courseId, $sessionDate, $status);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
        $msg = 'Attendance recorded.';
        $msgType = 'success';
    }
}

$courses = [];
if ($fullName) {
    $r = @mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses WHERE lecturer_name = '" . mysqli_real_escape_string($conn, $fullName) . "' ORDER BY course_code");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courses[] = $row; } }
}

$courseId = (int)($_GET['course_id'] ?? 0);
$courseStudents = [];
if ($courseId > 0) {
    $r = @mysqli_query($conn, "SELECT s.student_id, s.first_name, s.last_name, s.reg_number FROM registrations reg JOIN students s ON s.student_id = reg.student_id WHERE reg.course_id = $courseId AND reg.status = 'registered' ORDER BY s.last_name, s.first_name");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courseStudents[] = $row; } }
}

$recentAttendance = [];
if (!empty($courses)) {
    $ids = implode(',', array_column($courses, 'course_id'));
    $r = @mysqli_query($conn, "SELECT a.status, a.session_date, c.course_code, s.first_name, s.last_name FROM attendance a JOIN courses c ON c.course_id = a.course_id JOIN students s ON s.student_id = a.student_id WHERE a.course_id IN ($ids) ORDER BY a.session_date DESC LIMIT 15");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $recentAttendance[] = $row; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | Lecturer Panel</title>
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
                <a class="dashboard-nav-link active" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="profile.php">Profile</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="dashboard-header">
                <div><p class="eyebrow">Lecturer</p><h1>Attendance</h1></div>
            </div>
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>
            <div class="dashboard-grid">
                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Mark Attendance</p>
                    <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; max-width: 500px; margin-bottom: 16px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Course</label>
                            <select name="course_id" required>
                                <option value="">Select a course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= $c['course_id'] ?>" <?= $courseId == $c['course_id'] ? 'selected' : '' ?>><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Load</button>
                    </form>
                    <?php if ($courseId > 0 && !empty($courseStudents)): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="mark_attendance">
                            <input type="hidden" name="course_id" value="<?= $courseId ?>">
                            <div class="form-group" style="max-width: 200px; margin-bottom: 12px;">
                                <label>Date</label>
                                <input type="date" name="session_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div style="overflow-x: auto;">
                                <table class="student-table">
                                    <thead><tr><th>Student</th><th>Reg Number</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($courseStudents as $s): ?>
                                            <tr>
                                                <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?><input type="hidden" name="student_id[]" value="<?= $s['student_id'] ?>"></td>
                                                <td><?= e($s['reg_number']) ?></td>
                                                <td><select name="status[]" style="padding:4px 8px; border:1px solid var(--color-border); border-radius:4px;"><option value="present">Present</option><option value="absent">Absent</option><option value="excused">Excused</option></select></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-top:12px;">Save Attendance</button>
                        </form>
                    <?php elseif ($courseId > 0): ?>
                        <p class="muted">No students enrolled.</p>
                    <?php endif; ?>
                </section>
                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Recent Records</p>
                    <?php if (!empty($recentAttendance)): ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead><tr><th>Date</th><th>Course</th><th>Student</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recentAttendance as $a): ?>
                                        <tr>
                                            <td><?= e(date('M d, Y', strtotime($a['session_date']))) ?></td>
                                            <td><?= e($a['course_code']) ?></td>
                                            <td><?= e($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                            <td><span style="color: <?= $a['status'] === 'present' ? '#15803d' : ($a['status'] === 'absent' ? '#dc2626' : 'var(--color-warning)') ?>; text-transform: capitalize;"><?= e($a['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="muted">No attendance records yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
