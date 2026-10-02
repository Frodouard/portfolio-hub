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
        if ($_POST['action'] === 'toggle_publish') {
            $resultId = (int)($_POST['result_id'] ?? 0);
            $newState = (int)($_POST['publish_state'] ?? 0);
            if ($resultId > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE results SET published = ? WHERE result_id = ?");
                $pub = $newState;
                mysqli_stmt_bind_param($stmt, 'ii', $pub, $resultId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = $newState ? 'Grade published.' : 'Grade unpublished.';
                $msgType = 'success';
            }
        } elseif ($_POST['action'] === 'publish_all') {
            $courseId = (int)($_POST['course_id'] ?? 0);
            if ($courseId > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE results SET published = 1 WHERE course_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $courseId);
                mysqli_stmt_execute($stmt);
                $count = mysqli_affected_rows($conn);
                mysqli_stmt_close($stmt);
                $msg = "Published $count grades for this course.";
                $msgType = 'success';
            }
        } elseif ($_POST['action'] === 'unpublish_all') {
            $courseId = (int)($_POST['course_id'] ?? 0);
            if ($courseId > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE results SET published = 0 WHERE course_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $courseId);
                mysqli_stmt_execute($stmt);
                $count = mysqli_affected_rows($conn);
                mysqli_stmt_close($stmt);
                $msg = "Unpublished $count grades for this course.";
                $msgType = 'success';
            }
        }
    }
}

$filterCourse = (int)($_GET['course_id'] ?? 0);
$filterStatus = $_GET['status'] ?? '';

$courses = [];
$r = @mysqli_query($conn, "SELECT DISTINCT c.course_id, c.course_code, c.course_name FROM results res JOIN courses c ON c.course_id = res.course_id ORDER BY c.course_code");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $courses[] = $row; } }

$results = [];
$where = '1=1';
$params = [];
$types = '';
if ($filterCourse > 0) { $where .= ' AND res.course_id = ?'; $params[] = $filterCourse; $types .= 'i'; }
if ($filterStatus === 'published') { $where .= ' AND res.published = 1'; }
if ($filterStatus === 'unpublished') { $where .= ' AND res.published = 0'; }

$r = @mysqli_query($conn, "SELECT res.result_id, res.marks, res.grade, res.grade_points, res.academic_year, res.semester, res.published, res.created_at,
    s.first_name, s.last_name, s.reg_number, c.course_code, c.course_name, d.department_name
    FROM results res
    JOIN students s ON s.student_id = res.student_id
    JOIN courses c ON c.course_id = res.course_id
    LEFT JOIN departments d ON d.department_id = s.department_id
    WHERE $where
    ORDER BY c.course_code, s.last_name, s.first_name");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $results[] = $row; } }

$stats = [];
$sr = @mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) AS published_count, SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) AS unpublished_count FROM results");
if ($sr) { $stats = mysqli_fetch_assoc($sr); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | AUCA Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Admin Panel</h2><p>Results</p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="users.php">Users</a>
                <a class="dashboard-nav-link" href="departments.php">Departments</a>
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
                    <p class="eyebrow">Administration</p>
                    <h1>Results Management</h1>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <div class="info-tiles" style="margin-bottom: 20px;">
                <div class="info-tile">
                    <div class="label">Total Grades</div>
                    <div class="value"><?= $stats['total'] ?? 0 ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Published</div>
                    <div class="value" style="color: #15803d;"><?= $stats['published_count'] ?? 0 ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Unpublished</div>
                    <div class="value" style="color: #dc2626;"><?= $stats['unpublished_count'] ?? 0 ?></div>
                </div>
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
                        <label>Status</label>
                        <select name="status">
                            <option value="">All</option>
                            <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="unpublished" <?= $filterStatus === 'unpublished' ? 'selected' : '' ?>>Unpublished</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </section>

            <?php foreach ($courses as $c):
                $courseResults = array_filter($results, fn($r) => $r['course_code'] === $c['course_code']);
                if (empty($courseResults) && $filterCourse > 0) continue;
                if (empty($courseResults)) continue;
                $allPublished = true;
                foreach ($courseResults as $cr) { if (!$cr['published']) { $allPublished = false; break; } }
            ?>
                <section class="dashboard-card" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <p class="eyebrow"><?= e($c['course_code']) ?></p>
                            <h3 style="margin: 0;"><?= e($c['course_name']) ?> (<?= count($courseResults) ?> grades)</h3>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <?php if (!$allPublished): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="publish_all">
                                    <input type="hidden" name="course_id" value="<?= $c['course_id'] ?>">
                                    <button type="submit" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px; background: #15803d;">Publish All</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($allPublished): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="unpublish_all">
                                    <input type="hidden" name="course_id" value="<?= $c['course_id'] ?>">
                                    <button type="submit" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px; background: var(--color-danger);">Unpublish All</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Reg Number</th>
                                    <th>Department</th>
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
                                <?php foreach ($courseResults as $cr): ?>
                                    <tr>
                                        <td><a href="student_profile.php?student_id=<?= $cr['student_id'] ?? 0 ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;"><?= e($cr['first_name'] . ' ' . $cr['last_name']) ?></a></td>
                                        <td><?= e($cr['reg_number']) ?></td>
                                        <td><?= e($cr['department_name'] ?? 'N/A') ?></td>
                                        <td><?= $cr['marks'] !== null ? number_format((float)$cr['marks'], 1) . '%' : 'N/A' ?></td>
                                        <td><strong><?= e($cr['grade'] ?? 'N/A') ?></strong></td>
                                        <td><?= $cr['grade_points'] !== null ? number_format((float)$cr['grade_points'], 2) : 'N/A' ?></td>
                                        <td><?= e($cr['semester']) ?></td>
                                        <td><?= e($cr['academic_year']) ?></td>
                                        <td>
                                            <?php if ($cr['published']): ?>
                                                <span style="color: #15803d; font-weight: 600;">Published</span>
                                            <?php else: ?>
                                                <span style="color: #dc2626; font-weight: 600;">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="toggle_publish">
                                                <input type="hidden" name="result_id" value="<?= $cr['result_id'] ?>">
                                                <input type="hidden" name="publish_state" value="<?= $cr['published'] ? 0 : 1 ?>">
                                                <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 11px; background: <?= $cr['published'] ? 'var(--color-danger)' : '#15803d' ?>;">
                                                    <?= $cr['published'] ? 'Unpublish' : 'Publish' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>

            <?php if (empty($results)): ?>
                <section class="dashboard-card">
                    <p class="muted">No results found.</p>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
