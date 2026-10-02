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

        if ($action === 'add_course') {
            $code = trim($_POST['course_code'] ?? '');
            $name = trim($_POST['course_name'] ?? '');
            $credits = (int)($_POST['credits'] ?? 3);
            $deptId = (int)($_POST['department_id'] ?? 0);
            $semester = trim($_POST['semester'] ?? '');
            $lecturer = trim($_POST['lecturer_name'] ?? '');
            if ($code !== '' && $name !== '') {
                $stmt = mysqli_prepare($conn, 'INSERT INTO courses (course_code, course_name, credits, department_id, semester, lecturer_name) VALUES (?, ?, ?, ?, ?, ?)');
                $d = $deptId > 0 ? $deptId : null;
                $sem = $semester !== '' ? $semester : null;
                $lec = $lecturer !== '' ? $lecturer : null;
                mysqli_stmt_bind_param($stmt, 'ssiiss', $code, $name, $credits, $d, $sem, $lec);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = 'Course added.';
                    $msgType = 'success';
                } else {
                    $msg = 'Course code already exists.';
                    $msgType = 'error';
                }
                mysqli_stmt_close($stmt);
            }
        } elseif ($action === 'edit_course') {
            $courseId = (int)($_POST['course_id'] ?? 0);
            $name = trim($_POST['course_name'] ?? '');
            $credits = (int)($_POST['credits'] ?? 3);
            $deptId = (int)($_POST['department_id'] ?? 0);
            $semester = trim($_POST['semester'] ?? '');
            $lecturer = trim($_POST['lecturer_name'] ?? '');
            if ($courseId > 0 && $name !== '') {
                $stmt = mysqli_prepare($conn, 'UPDATE courses SET course_name = ?, credits = ?, department_id = ?, semester = ?, lecturer_name = ? WHERE course_id = ?');
                $d = $deptId > 0 ? $deptId : null;
                $sem = $semester !== '' ? $semester : null;
                $lec = $lecturer !== '' ? $lecturer : null;
                mysqli_stmt_bind_param($stmt, 'siissi', $name, $credits, $d, $sem, $lec, $courseId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'Course updated.';
                $msgType = 'success';
            }
        } elseif ($action === 'delete_course') {
            $courseId = (int)($_POST['course_id'] ?? 0);
            if ($courseId > 0) {
                $stmt = mysqli_prepare($conn, "DELETE FROM courses WHERE course_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $courseId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'Course deleted.';
                $msgType = 'success';
            }
        }
    }
}

$courses = [];
$r = @mysqli_query($conn, "SELECT c.course_id, c.course_code, c.course_name, c.credits, c.semester, c.lecturer_name, d.department_name,
    (SELECT COUNT(*) FROM registrations r WHERE r.course_id = c.course_id AND r.status = 'registered') AS enrolled_count
    FROM courses c LEFT JOIN departments d ON d.department_id = c.department_id ORDER BY c.course_code");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $courses[] = $row;
    }
}

$departments = [];
$r = @mysqli_query($conn, 'SELECT department_id, department_name FROM departments ORDER BY department_name');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $departments[] = $row;
    }
}

$lecturers = [];
$r = @mysqli_query($conn, 'SELECT first_name, last_name FROM lecturers ORDER BY last_name');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $lecturers[] = $row['first_name'] . ' ' . $row['last_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses | AUCA Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Admin Panel</h2><p>Course management</p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="users.php">Users</a>
                <a class="dashboard-nav-link" href="departments.php">Departments</a>
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
                    <p class="eyebrow">Administration</p>
                    <h1>Course Management</h1>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Add Course</p>
                    <form method="POST" class="auth-form" style="max-width: 700px;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="add_course">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Course Code</label>
                                <input type="text" name="course_code" required placeholder="e.g. CS301">
                            </div>
                            <div class="form-group">
                                <label>Course Name</label>
                                <input type="text" name="course_name" required>
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Credits</label>
                                <input type="number" name="credits" value="3" min="1" max="12">
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester">
                                    <option value="">N/A</option>
                                    <option value="Semester 1">Semester 1</option>
                                    <option value="Semester 2">Semester 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id">
                                    <option value="">None</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?= $d['department_id'] ?>"><?= e($d['department_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lecturer Name</label>
                                <input type="text" name="lecturer_name" list="lecturerList" placeholder="e.g. John Doe">
                                <datalist id="lecturerList">
                                    <?php foreach ($lecturers as $l): ?>
                                        <option value="<?= e($l) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Course</button>
                    </form>
                </section>

                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">All Courses</p>
                    <h3 style="margin: 0 0 12px;">Courses (<?= count($courses) ?>)</h3>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Credits</th>
                                    <th>Department</th>
                                    <th>Lecturer</th>
                                    <th>Semester</th>
                                    <th>Enrolled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td><?= e($c['course_code']) ?></td>
                                        <td><?= e($c['course_name']) ?></td>
                                        <td><?= $c['credits'] ?></td>
                                        <td><?= e($c['department_name'] ?? 'N/A') ?></td>
                                        <td><?= e($c['lecturer_name'] ?? 'TBD') ?></td>
                                        <td><?= e($c['semester'] ?? 'N/A') ?></td>
                                        <td><?= $c['enrolled_count'] ?></td>
                                        <td style="white-space: nowrap;">
                                            <button onclick="editCourse(<?= $c['course_id'] ?>, '<?= e($c['course_name']) ?>', <?= $c['credits'] ?>, '<?= e($c['semester'] ?? '') ?>', '<?= e($c['lecturer_name'] ?? '') ?>')" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px;">Edit</button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this course?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete_course">
                                                <input type="hidden" name="course_id" value="<?= $c['course_id'] ?>">
                                                <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px; background: var(--color-danger);">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Edit Course Modal -->
                <section class="dashboard-card" id="editCourseSection" style="grid-column: span 2; display: none;">
                    <p class="eyebrow">Edit Course</p>
                    <form method="POST" class="auth-form" style="max-width: 700px;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="edit_course">
                        <input type="hidden" name="course_id" id="editCourseId">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Course Name</label>
                                <input type="text" name="course_name" id="editCourseName" required>
                            </div>
                            <div class="form-group">
                                <label>Credits</label>
                                <input type="number" name="credits" id="editCourseCredits" min="1" max="12">
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id" id="editCourseDept">
                                    <option value="">None</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?= $d['department_id'] ?>"><?= e($d['department_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lecturer Name</label>
                                <input type="text" name="lecturer_name" id="editCourseLecturer">
                            </div>
                        </div>
                        <div class="form-group" style="max-width: 200px;">
                            <label>Semester</label>
                            <select name="semester" id="editCourseSemester">
                                <option value="">N/A</option>
                                <option value="Semester 1">Semester 1</option>
                                <option value="Semester 2">Semester 2</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-primary" style="background: var(--color-text-muted);" onclick="document.getElementById('editCourseSection').style.display='none'">Cancel</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
    <script>
    function editCourse(id, name, credits, semester, lecturer) {
        document.getElementById('editCourseId').value = id;
        document.getElementById('editCourseName').value = name;
        document.getElementById('editCourseCredits').value = credits;
        document.getElementById('editCourseLecturer').value = lecturer;
        document.getElementById('editCourseSemester').value = semester;
        document.getElementById('editCourseSection').style.display = '';
        document.getElementById('editCourseSection').scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>
</html>
