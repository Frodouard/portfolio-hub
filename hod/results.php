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
    } elseif ($_POST['action'] === 'publish_result') {
        $resultId = (int)($_POST['result_id'] ?? 0);
        if ($resultId > 0) {
            $stmt = mysqli_prepare($conn, 'UPDATE results SET published = 1 WHERE result_id = ? AND student_id IN (SELECT student_id FROM students WHERE department_id = ?)');
            mysqli_stmt_bind_param($stmt, 'ii', $resultId, $departmentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = 'Result published.';
            $msgType = 'success';
        }
    } elseif ($_POST['action'] === 'unpublish_result') {
        $resultId = (int)($_POST['result_id'] ?? 0);
        if ($resultId > 0) {
            $stmt = mysqli_prepare($conn, 'UPDATE results SET published = 0 WHERE result_id = ? AND student_id IN (SELECT student_id FROM students WHERE department_id = ?)');
            mysqli_stmt_bind_param($stmt, 'ii', $resultId, $departmentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = 'Result unpublished.';
            $msgType = 'success';
        }
    } elseif ($_POST['action'] === 'bulk_publish') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $semester = trim($_POST['semester'] ?? '');
        $year = trim($_POST['academic_year'] ?? '');
        $where = "s.department_id = $departmentId AND res.published = 0";
        $params = [];
        $types = '';
        if ($courseId > 0) { $where .= ' AND res.course_id = ?'; $params[] = $courseId; $types .= 'i'; }
        if ($semester !== '') { $where .= ' AND res.semester = ?'; $params[] = $semester; $types .= 's'; }
        if ($year !== '') { $where .= ' AND res.academic_year = ?'; $params[] = $year; $types .= 's'; }
        $sql = "UPDATE results res JOIN students s ON s.student_id = res.student_id SET res.published = 1 WHERE $where";
        $stmt = mysqli_prepare($conn, $sql);
        if ($types) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $msg = "Bulk published $affected result(s).";
        $msgType = 'success';
    } elseif ($_POST['action'] === 'bulk_unpublish') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $semester = trim($_POST['semester'] ?? '');
        $year = trim($_POST['academic_year'] ?? '');
        $where = "s.department_id = $departmentId AND res.published = 1";
        $params = [];
        $types = '';
        if ($courseId > 0) { $where .= ' AND res.course_id = ?'; $params[] = $courseId; $types .= 'i'; }
        if ($semester !== '') { $where .= ' AND res.semester = ?'; $params[] = $semester; $types .= 's'; }
        if ($year !== '') { $where .= ' AND res.academic_year = ?'; $params[] = $year; $types .= 's'; }
        $sql = "UPDATE results res JOIN students s ON s.student_id = res.student_id SET res.published = 0 WHERE $where";
        $stmt = mysqli_prepare($conn, $sql);
        if ($types) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        $msg = "Bulk unpublished $affected result(s).";
        $msgType = 'success';
    }
}

$filterCourse = (int)($_GET['course_id'] ?? 0);
$filterSemester = $_GET['semester'] ?? '';
$filterYear = $_GET['academic_year'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$deptCourses = [];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses WHERE department_id = $departmentId ORDER BY course_code");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $deptCourses[] = $row; } }
}

$allResults = [];
if ($departmentId) {
    $where = "s.department_id = $departmentId";
    $params = [];
    $types = '';
    if ($filterCourse > 0) { $where .= ' AND res.course_id = ?'; $params[] = $filterCourse; $types .= 'i'; }
    if ($filterSemester !== '') { $where .= ' AND res.semester = ?'; $params[] = $filterSemester; $types .= 's'; }
    if ($filterYear !== '') { $where .= ' AND res.academic_year = ?'; $params[] = $filterYear; $types .= 's'; }
    if ($filterStatus === 'published') { $where .= ' AND res.published = 1'; }
    elseif ($filterStatus === 'draft') { $where .= ' AND res.published = 0'; }
    if ($search !== '') { $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.reg_number LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; $types .= 'sss'; }

    $sql = "SELECT res.result_id, res.marks, res.grade, res.grade_points, res.published, res.academic_year, res.semester,
        c.course_code, c.course_name, s.student_id, s.first_name, s.last_name, s.reg_number
        FROM results res
        JOIN students s ON s.student_id = res.student_id
        JOIN courses c ON c.course_id = res.course_id
        WHERE $where
        ORDER BY s.last_name, c.course_code";
    $r = @mysqli_query($conn, $sql);
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $allResults[] = $row; } }
}

$stats = ['avg' => 'N/A', 'pass' => 'N/A', 'published' => 0, 'draft' => 0];
if ($departmentId) {
    $r = @mysqli_query($conn, "SELECT AVG(res.grade_points) AS avg_gpa,
        SUM(CASE WHEN res.marks >= 50 THEN 1 ELSE 0 END) AS passed,
        COUNT(*) AS total,
        SUM(CASE WHEN res.published = 1 THEN 1 ELSE 0 END) AS published,
        SUM(CASE WHEN res.published = 0 THEN 1 ELSE 0 END) AS draft
        FROM results res JOIN students s ON s.student_id = res.student_id
        WHERE s.department_id = $departmentId AND res.marks IS NOT NULL");
    if ($r) {
        $row = mysqli_fetch_assoc($r);
        if ($row && $row['total'] > 0) {
            $stats['avg'] = number_format((float)$row['avg_gpa'], 2);
            $stats['pass'] = number_format(($row['passed'] / $row['total']) * 100, 1) . '%';
            $stats['published'] = (int)$row['published'];
            $stats['draft'] = (int)$row['draft'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | HOD - <?= e($departmentName) ?></title>
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
                <a class="dashboard-nav-link active" href="results.php">Results</a>
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
                    <h1>Department Results</h1>
                </div>
                <div class="dashboard-chip">Avg GPA: <?= e($stats['avg']) ?> | Pass: <?= e($stats['pass']) ?></div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <div class="info-tiles" style="margin-bottom: 20px;">
                <div class="info-tile"><div class="label">Total Results</div><div class="value"><?= count($allResults) ?></div></div>
                <div class="info-tile"><div class="label">Published</div><div class="value" style="color: #15803d;"><?= $stats['published'] ?></div></div>
                <div class="info-tile"><div class="label">Draft</div><div class="value" style="color: var(--color-warning);"><?= $stats['draft'] ?></div></div>
            </div>

            <section class="dashboard-card" style="margin-bottom: 20px;">
                <p class="eyebrow">Filter</p>
                <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">All courses</option>
                            <?php foreach ($deptCourses as $c): ?>
                                <option value="<?= $c['course_id'] ?>" <?= $filterCourse == $c['course_id'] ? 'selected' : '' ?>><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester">
                            <option value="">All</option>
                            <option value="1" <?= $filterSemester === '1' ? 'selected' : '' ?>>Semester 1</option>
                            <option value="2" <?= $filterSemester === '2' ? 'selected' : '' ?>>Semester 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Academic Year</label>
                        <input type="text" name="academic_year" value="<?= e($filterYear) ?>" placeholder="e.g. 2024-2025">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All</option>
                            <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Name or reg number">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </section>

            <section class="dashboard-card" style="margin-bottom: 20px;">
                <p class="eyebrow">Bulk Actions</p>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <form method="POST" style="display: flex; gap: 6px; align-items: flex-end;" onsubmit="return confirm('Publish all filtered draft results?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="bulk_publish">
                        <input type="hidden" name="course_id" value="<?= $filterCourse ?>">
                        <input type="hidden" name="semester" value="<?= e($filterSemester) ?>">
                        <input type="hidden" name="academic_year" value="<?= e($filterYear) ?>">
                        <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px; background: #15803d;">Bulk Publish (filtered)</button>
                    </form>
                    <form method="POST" style="display: flex; gap: 6px; align-items: flex-end;" onsubmit="return confirm('Unpublish all filtered published results?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="bulk_unpublish">
                        <input type="hidden" name="course_id" value="<?= $filterCourse ?>">
                        <input type="hidden" name="semester" value="<?= e($filterSemester) ?>">
                        <input type="hidden" name="academic_year" value="<?= e($filterYear) ?>">
                        <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 12px; background: var(--color-warning); color: #000;">Bulk Unpublish (filtered)</button>
                    </form>
                </div>
            </section>

            <section class="dashboard-card">
                <p class="eyebrow">Results</p>
                <h3 style="margin: 0 0 12px;">Found: <?= count($allResults) ?></h3>
                <?php if (!empty($allResults)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Reg No</th>
                                    <th>Course</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>GPA</th>
                                    <th>Semester</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allResults as $res): ?>
                                    <tr>
                                        <td><a href="student_profile.php?student_id=<?= $res['student_id'] ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;"><?= e($res['first_name'] . ' ' . $res['last_name']) ?></a></td>
                                        <td><?= e($res['reg_number']) ?></td>
                                        <td><?= e($res['course_code'] . ' - ' . $res['course_name']) ?></td>
                                        <td><?= $res['marks'] !== null ? number_format((float)$res['marks'], 1) . '%' : 'N/A' ?></td>
                                        <td><?= e($res['grade'] ?? 'N/A') ?></td>
                                        <td><?= $res['grade_points'] !== null ? number_format((float)$res['grade_points'], 2) : 'N/A' ?></td>
                                        <td><?= e($res['semester']) ?></td>
                                        <td><?= e($res['academic_year']) ?></td>
                                        <td>
                                            <span style="color: <?= $res['published'] ? '#15803d' : 'var(--color-warning)' ?>;">
                                                <?= $res['published'] ? 'Published' : 'Draft' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="result_id" value="<?= $res['result_id'] ?>">
                                                <?php if ($res['published']): ?>
                                                    <input type="hidden" name="action" value="unpublish_result">
                                                    <button type="submit" class="btn btn-primary" style="padding: 3px 8px; font-size: 11px; background: var(--color-warning);">Unpublish</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="action" value="publish_result">
                                                    <button type="submit" class="btn btn-primary" style="padding: 3px 8px; font-size: 11px;">Publish</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No results found for this filter.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
