<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
requireLecturer();

$lecturerId = (int)($_SESSION['lecturer_id'] ?? 0);
$fullName = $_SESSION['full_name'] ?? 'Lecturer';
$departmentName = '';

$lecturer = null;
if ($lecturerId) {
    $r = @mysqli_query($conn, "SELECT l.first_name, l.last_name, d.department_name
        FROM lecturers l LEFT JOIN departments d ON d.department_id = l.department_id WHERE l.lecturer_id = $lecturerId");
    if ($r) { $lecturer = mysqli_fetch_assoc($r); }
    $departmentName = $lecturer['department_name'] ?? 'Department';
    $fullName = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid form submission.';
        $msgType = 'error';
    } elseif ($_POST['action'] === 'submit_grades') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $academicYear = trim($_POST['academic_year'] ?? date('Y'));
        $semester = trim($_POST['semester'] ?? 'Semester 1');
        $studentIds = $_POST['student_id'] ?? [];
        $marks = $_POST['marks'] ?? [];

        foreach ($studentIds as $idx => $studentId) {
            $studentId = (int)$studentId;
            $mark = isset($marks[$idx]) ? (float)$marks[$idx] : null;
            if ($studentId > 0 && $mark !== null) {
                // Determine grade
                $grade = 'F'; $gp = 0.00;
                if ($mark >= 90) { $grade = 'A'; $gp = 4.00; }
                elseif ($mark >= 80) { $grade = 'B+'; $gp = 3.50; }
                elseif ($mark >= 70) { $grade = 'B'; $gp = 3.00; }
                elseif ($mark >= 60) { $grade = 'C+'; $gp = 2.50; }
                elseif ($mark >= 50) { $grade = 'C'; $gp = 2.00; }
                elseif ($mark >= 40) { $grade = 'D'; $gp = 1.00; }

                // Upsert result
                $existing = @mysqli_query($conn, "SELECT result_id FROM results WHERE student_id = $studentId AND course_id = $courseId AND academic_year = '" . mysqli_real_escape_string($conn, $academicYear) . "' AND semester = '" . mysqli_real_escape_string($conn, $semester) . "'");
                if ($existing && mysqli_num_rows($existing) > 0) {
                    $row = mysqli_fetch_assoc($existing);
                    $stmt = mysqli_prepare($conn, 'UPDATE results SET marks = ?, grade = ?, grade_points = ? WHERE result_id = ?');
                    mysqli_stmt_bind_param($stmt, 'dsdi', $mark, $grade, $gp, $row['result_id']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    $stmt = mysqli_prepare($conn, 'INSERT INTO results (student_id, course_id, academic_year, semester, marks, grade, grade_points) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'iissdsd', $studentId, $courseId, $academicYear, $semester, $mark, $grade, $gp);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
        $msg = 'Grades submitted successfully.';
        $msgType = 'success';
    }
}

// Get courses taught by this lecturer
$courses = [];
if ($fullName) {
    $r = @mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses WHERE lecturer_name = '" . mysqli_real_escape_string($conn, $fullName) . "' ORDER BY course_code");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $courses[] = $row;
        }
    }
}

$courseId = (int)($_GET['course_id'] ?? 0);
$courseStudents = [];
if ($courseId > 0) {
    $r = @mysqli_query($conn, "SELECT s.student_id, s.first_name, s.last_name, s.reg_number
        FROM registrations reg
        JOIN students s ON s.student_id = reg.student_id
        WHERE reg.course_id = $courseId AND reg.status = 'registered'
        ORDER BY s.last_name, s.first_name");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            // Check if result already exists
            $existing = null;
            $res = @mysqli_query($conn, "SELECT marks, grade FROM results WHERE student_id = {$row['student_id']} AND course_id = $courseId ORDER BY created_at DESC LIMIT 1");
            if ($res) { $existing = mysqli_fetch_assoc($res); }
            $row['existing_marks'] = $existing['marks'] ?? '';
            $row['existing_grade'] = $existing['grade'] ?? '';
            $courseStudents[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades | Lecturer Panel</title>
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
                <a class="dashboard-nav-link active" href="grades.php">Grades</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="profile.php">Profile</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Lecturer</p>
                    <h1>Enter Grades</h1>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <section class="dashboard-card" style="margin-bottom: 20px;">
                <p class="eyebrow">Select Course</p>
                <form method="GET" class="auth-form" style="display: flex; gap: 12px; align-items: flex-end; max-width: 500px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Course</label>
                        <select name="course_id" required>
                            <option value="">Select a course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['course_id'] ?>" <?= $courseId == $c['course_id'] ? 'selected' : '' ?>><?= e($c['course_code'] . ' - ' . $c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Load Students</button>
                </form>
            </section>

            <?php if ($courseId > 0 && !empty($courseStudents)): ?>
                <section class="dashboard-card">
                    <p class="eyebrow">Grade Entry</p>
                    <h3 style="margin: 0 0 12px;">Submit Marks</h3>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="submit_grades">
                        <input type="hidden" name="course_id" value="<?= $courseId ?>">
                        <div class="form-grid-2" style="max-width: 400px; margin-bottom: 16px;">
                            <div class="form-group">
                                <label>Academic Year</label>
                                <input type="text" name="academic_year" value="<?= date('Y') ?>">
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester">
                                    <option value="Semester 1">Semester 1</option>
                                    <option value="Semester 2">Semester 2</option>
                                </select>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Reg Number</th>
                                        <th>Marks (0-100)</th>
                                        <th>Current Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courseStudents as $s): ?>
                                        <tr>
                                            <td>
                                                <?= e($s['first_name'] . ' ' . $s['last_name']) ?>
                                                <input type="hidden" name="student_id[]" value="<?= $s['student_id'] ?>">
                                            </td>
                                            <td><?= e($s['reg_number']) ?></td>
                                            <td><input type="number" name="marks[]" value="<?= e((string)$s['existing_marks']) ?>" min="0" max="100" step="0.5" style="width: 100px; padding: 6px 8px; border: 1px solid var(--color-border); border-radius: 4px;"></td>
                                            <td><?= e($s['existing_grade'] ?: 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 16px;">Submit All Grades</button>
                    </form>
                </section>
            <?php elseif ($courseId > 0): ?>
                <section class="dashboard-card">
                    <p class="muted">No students enrolled in this course.</p>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
