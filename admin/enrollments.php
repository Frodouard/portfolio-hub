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
        if ($_POST['action'] === 'enroll_student') {
            $studentId = (int)($_POST['student_id'] ?? 0);
            $courseId = (int)($_POST['course_id'] ?? 0);
            $semester = trim($_POST['semester'] ?? 'Semester 1');
            $academicYear = trim($_POST['academic_year'] ?? date('Y'));
            if ($studentId > 0 && $courseId > 0) {
                $check = mysqli_prepare($conn, "SELECT registration_id FROM registrations WHERE student_id = ? AND course_id = ? AND academic_year = ? AND semester = ?");
                mysqli_stmt_bind_param($check, 'iiss', $studentId, $courseId, $academicYear, $semester);
                mysqli_stmt_execute($check);
                $exists = mysqli_stmt_get_result($check)->num_rows > 0;
                mysqli_stmt_close($check);
                if ($exists) {
                    $msg = 'Student already enrolled in this course.';
                    $msgType = 'error';
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO registrations (student_id, course_id, academic_year, semester, status) VALUES (?, ?, ?, ?, 'registered')");
                    mysqli_stmt_bind_param($stmt, 'iiss', $studentId, $courseId, $academicYear, $semester);
                    if (mysqli_stmt_execute($stmt)) {
                        $msg = 'Student enrolled successfully.';
                        $msgType = 'success';
                    } else {
                        $msg = 'Enrollment failed.';
                        $msgType = 'error';
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        } elseif ($_POST['action'] === 'drop_student') {
            $regId = (int)($_POST['registration_id'] ?? 0);
            if ($regId > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE registrations SET status = 'dropped' WHERE registration_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $regId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'Enrollment dropped.';
                $msgType = 'success';
            }
        } elseif ($_POST['action'] === 'reactivate') {
            $regId = (int)($_POST['registration_id'] ?? 0);
            if ($regId > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE registrations SET status = 'registered' WHERE registration_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $regId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'Enrollment reactivated.';
                $msgType = 'success';
            }
        }
    }
}

$filterCourse = (int)($_GET['course_id'] ?? 0);
$filterStatus = $_GET['status'] ?? 'registered';
$search = trim($_GET['q'] ?? '');

$allCourses = [];
$r = @mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $allCourses[] = $row; } }

$allStudents = [];
$r = @mysqli_query($conn, "SELECT s.student_id, s.first_name, s.last_name, s.reg_number FROM students s ORDER BY s.last_name");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $allStudents[] = $row; } }

$enrollments = [];
$where = '1=1';
$params = [];
$types = '';
if ($filterCourse > 0) { $where .= ' AND reg.course_id = ?'; $params[] = $filterCourse; $types .= 'i'; }
if ($filterStatus !== '') { $where .= ' AND reg.status = ?'; $params[] = $filterStatus; $types .= 's'; }
if ($search !== '') { $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.reg_number LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

$r = @mysqli_query($conn, "SELECT reg.registration_id, reg.academic_year, reg.semester, reg.status, reg.registered_at,
    s.student_id, s.first_name, s.last_name, s.reg_number, d.department_name,
    c.course_id, c.course_code, c.course_name
    FROM registrations reg
    JOIN students s ON s.student_id = reg.student_id
    JOIN courses c ON c.course_id = reg.course_id
    LEFT JOIN departments d ON d.department_id = s.department_id
    WHERE $where
    ORDER BY c.course_code, s.last_name, s.first_name");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $enrollments[] = $row; } }

$counts = [];
$cr = @mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM registrations GROUP BY status");
if ($cr) { while ($row = mysqli_fetch_assoc($cr)) { $counts[$row['status']] = $row['cnt']; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollments | AUCA Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Admin Panel</h2><p>Enrollments</p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="users.php">Users</a>
                <a class="dashboard-nav-link" href="departments.php">Departments</a>
                <a class="dashboard-nav-link" href="courses.php">Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link active" href="enrollments.php">Enrollments</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Administration</p>
                    <h1>Enrollment Management</h1>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <div class="info-tiles" style="margin-bottom: 20px;">
                <div class="info-tile">
                    <div class="label">Registered</div>
                    <div class="value" style="color: #15803d;"><?= $counts['registered'] ?? 0 ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Dropped</div>
                    <div class="value" style="color: #dc2626;"><?= $counts['dropped'] ?? 0 ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Completed</div>
                    <div class="value"><?= $counts['completed'] ?? 0 ?></div>
                </div>
            </div>

            <div class="dashboard-grid">
                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Enroll Student</p>
                    <form method="POST" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="enroll_student">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" required>
                                <option value="">Select student</option>
                                <?php foreach ($allStudents as $s): ?>
                                    <option value="<?= $s['student_id'] ?>"><?= e($s['last_name'] . ', ' . $s['first_name'] . ' (' . $s['reg_number'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" required>
                                <option value="">Select course</option>
                                <?php foreach ($allCourses as $c): ?>
                                    <option value="<?= $c['course_id'] ?>"><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Semester</label>
                            <select name="semester">
                                <option value="Semester 1">Semester 1</option>
                                <option value="Semester 2">Semester 2</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Year</label>
                            <input type="text" name="academic_year" value="<?= date('Y') ?>" style="width: 80px;">
                        </div>
                        <button type="submit" class="btn btn-primary">Enroll</button>
                    </form>
                </section>
            </div>

            <section class="dashboard-card" style="margin-top: 20px;">
                <p class="eyebrow">Filter</p>
                <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">All courses</option>
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?= $c['course_id'] ?>" <?= $filterCourse == $c['course_id'] ? 'selected' : '' ?>><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="" <?= $filterStatus === '' ? 'selected' : '' ?>>All</option>
                            <option value="registered" <?= $filterStatus === 'registered' ? 'selected' : '' ?>>Registered</option>
                            <option value="dropped" <?= $filterStatus === 'dropped' ? 'selected' : '' ?>>Dropped</option>
                            <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or reg number">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </section>

            <section class="dashboard-card" style="margin-top: 20px;">
                <p class="eyebrow">Enrollments</p>
                <h3 style="margin: 0 0 12px;">Found: <?= count($enrollments) ?></h3>
                <?php if (!empty($enrollments)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Reg Number</th>
                                    <th>Department</th>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $e): ?>
                                    <tr>
                                        <td><a href="student_profile.php?student_id=<?= $e['student_id'] ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></a></td>
                                        <td><?= e($e['reg_number']) ?></td>
                                        <td><?= e($e['department_name'] ?? 'N/A') ?></td>
                                        <td><?= e($e['course_code'] . ' - ' . $e['course_name']) ?></td>
                                        <td><?= e($e['semester']) ?></td>
                                        <td><?= e($e['academic_year']) ?></td>
                                        <td>
                                            <?php if ($e['status'] === 'registered'): ?>
                                                <span style="color: #15803d; font-weight: 600;">Registered</span>
                                            <?php elseif ($e['status'] === 'dropped'): ?>
                                                <span style="color: #dc2626; font-weight: 600;">Dropped</span>
                                            <?php else: ?>
                                                <span style="font-weight: 600;"><?= e(ucfirst($e['status'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($e['status'] === 'registered'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Drop this enrollment?');">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="drop_student">
                                                    <input type="hidden" name="registration_id" value="<?= $e['registration_id'] ?>">
                                                    <button type="submit" class="btn btn-primary" style="padding: 3px 8px; font-size: 11px; background: var(--color-danger);">Drop</button>
                                                </form>
                                            <?php elseif ($e['status'] === 'dropped'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="reactivate">
                                                    <input type="hidden" name="registration_id" value="<?= $e['registration_id'] ?>">
                                                    <button type="submit" class="btn btn-primary" style="padding: 3px 8px; font-size: 11px; background: #15803d;">Reactivate</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No enrollments found.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
