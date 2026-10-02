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
    } elseif ($_POST['action'] === 'assign_lecturer') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $lecturerName = trim($_POST['lecturer_name'] ?? '');
        if ($courseId > 0 && $lecturerName !== '') {
            $stmt = mysqli_prepare($conn, 'UPDATE courses SET lecturer_name = ? WHERE course_id = ? AND department_id = ?');
            mysqli_stmt_bind_param($stmt, 'sii', $lecturerName, $courseId, $departmentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = 'Lecturer assigned to course.';
            $msgType = 'success';
        }
    }
}

$search = trim($_GET['q'] ?? '');
$filterSemester = $_GET['semester'] ?? '';
$expandCourse = (int)($_GET['expand'] ?? 0);

$courses = [];
if ($departmentId) {
    $where = "c.department_id = $departmentId";
    $params = [];
    $types = '';
    if ($search !== '') {
        $where .= " AND (c.course_code LIKE ? OR c.course_name LIKE ? OR c.lecturer_name LIKE ?)";
        $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss';
    }
    if ($filterSemester !== '') {
        $where .= ' AND c.semester = ?'; $params[] = $filterSemester; $types .= 's';
    }
    $sql = "SELECT c.course_id, c.course_code, c.course_name, c.credits, c.semester, c.lecturer_name,
        (SELECT COUNT(*) FROM registrations r WHERE r.course_id = c.course_id AND r.status = 'registered') AS enrolled_count
        FROM courses c WHERE $where ORDER BY c.course_code";
    $r = @mysqli_query($conn, $sql);
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courses[] = $row; } }
}

$courseStudents = [];
if ($expandCourse > 0) {
    $r = @mysqli_query($conn, "SELECT s.student_id, s.first_name, s.last_name, s.reg_number, s.year_of_study, reg.status
        FROM registrations reg
        JOIN students s ON s.student_id = reg.student_id
        WHERE reg.course_id = $expandCourse AND reg.status = 'registered'
        ORDER BY s.last_name, s.first_name");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courseStudents[] = $row; } }
}

$lecturers = [];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM lecturers WHERE department_id = $departmentId ORDER BY last_name");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $lecturers[] = $row['full_name']; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses | HOD - <?= e($departmentName) ?></title>
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
                <a class="dashboard-nav-link active" href="courses.php">Courses</a>
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
                    <h1>Department Courses</h1>
                </div>
                <div class="dashboard-chip"><?= count($courses) ?> courses</div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <section class="dashboard-card" style="margin-bottom: 20px;">
                <p class="eyebrow">Filter</p>
                <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Code, name, or lecturer">
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester">
                            <option value="">All</option>
                            <option value="1" <?= $filterSemester === '1' ? 'selected' : '' ?>>Semester 1</option>
                            <option value="2" <?= $filterSemester === '2' ? 'selected' : '' ?>>Semester 2</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </section>

            <section class="dashboard-card">
                <?php if (!empty($courses)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Credits</th>
                                    <th>Lecturer</th>
                                    <th>Semester</th>
                                    <th>Enrolled</th>
                                    <th>Students</th>
                                    <th>Assign Lecturer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td><?= e($c['course_code']) ?></td>
                                        <td><?= e($c['course_name']) ?></td>
                                        <td><?= $c['credits'] ?></td>
                                        <td><?= e($c['lecturer_name'] ?? 'TBD') ?></td>
                                        <td><?= e($c['semester'] ?? 'N/A') ?></td>
                                        <td><?= $c['enrolled_count'] ?></td>
                                        <td>
                                            <?php if ($c['enrolled_count'] > 0): ?>
                                                <a href="?expand=<?= $c['course_id'] ?>&q=<?= urlencode($search) ?>&semester=<?= urlencode($filterSemester) ?>" style="color: var(--color-primary); text-decoration: none; font-size: 12px; font-weight: 600;"><?= $expandCourse == $c['course_id'] ? 'Hide' : 'View' ?></a>
                                            <?php else: ?>
                                                <span class="muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: flex; gap: 4px;">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="assign_lecturer">
                                                <input type="hidden" name="course_id" value="<?= $c['course_id'] ?>">
                                                <input type="text" name="lecturer_name" list="lecList<?= $c['course_id'] ?>" value="<?= e($c['lecturer_name'] ?? '') ?>" style="padding: 4px 8px; border: 1px solid var(--color-border); border-radius: 4px; font-size: 12px; width: 140px;">
                                                <datalist id="lecList<?= $c['course_id'] ?>">
                                                    <?php foreach ($lecturers as $l): ?>
                                                        <option value="<?= e($l) ?>">
                                                    <?php endforeach; ?>
                                                </datalist>
                                                <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($expandCourse > 0 && !empty($courseStudents)): ?>
                        <div style="margin-top: 16px; padding: 12px; background: var(--color-bg); border: 1px solid var(--color-border); border-radius: 8px;">
                            <?php
                            $courseInfo = null;
                            foreach ($courses as $c) { if ($c['course_id'] == $expandCourse) { $courseInfo = $c; break; } }
                            ?>
                            <p class="eyebrow">Enrolled Students — <?= e(($courseInfo['course_code'] ?? '') . ' - ' . ($courseInfo['course_name'] ?? '')) ?></p>
                            <?php if (!empty($courseStudents)): ?>
                                <table class="student-table" style="margin-top: 8px;">
                                    <thead><tr><th>Name</th><th>Reg Number</th><th>Year</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($courseStudents as $cs): ?>
                                            <tr>
                                                <td><a href="student_profile.php?student_id=<?= $cs['student_id'] ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;"><?= e($cs['first_name'] . ' ' . $cs['last_name']) ?></a></td>
                                                <td><?= e($cs['reg_number']) ?></td>
                                                <td>Year <?= $cs['year_of_study'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="muted">No enrolled students.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="muted">No courses found for this filter.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
