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

$courseId = (int)($_GET['course_id'] ?? 0);
$selectedCourse = null;
$courseStudents = [];

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

if ($courseId > 0) {
    $selectedCourse = null;
    foreach ($courses as $c) {
        if ($c['course_id'] == $courseId) { $selectedCourse = $c; break; }
    }
    if (!$selectedCourse) {
        $r = @mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses WHERE course_id = $courseId");
        if ($r) { $selectedCourse = mysqli_fetch_assoc($r); }
    }
    if ($selectedCourse) {
        $r = @mysqli_query($conn, "SELECT s.student_id, s.first_name, s.last_name, s.reg_number, s.year_of_study, u.email
            FROM registrations reg
            JOIN students s ON s.student_id = reg.student_id
            JOIN users u ON u.user_id = s.user_id
            WHERE reg.course_id = $courseId AND reg.status = 'registered'
            ORDER BY s.last_name, s.first_name");
        if ($r) {
            while ($row = mysqli_fetch_assoc($r)) {
                $courseStudents[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | Lecturer Panel</title>
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
                <a class="dashboard-nav-link active" href="students.php">Students</a>
                <a class="dashboard-nav-link" href="grades.php">Grades</a>
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
                    <h1>Enrolled Students</h1>
                </div>
            </div>

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
                    <button type="submit" class="btn btn-primary">View Students</button>
                </form>
            </section>

            <?php if ($selectedCourse): ?>
                <section class="dashboard-card">
                    <p class="eyebrow"><?= e($selectedCourse['course_code']) ?></p>
                    <h3 style="margin: 0 0 12px;"><?= e($selectedCourse['course_name']) ?> — <?= count($courseStudents) ?> students</h3>
                    <?php if (!empty($courseStudents)): ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Reg Number</th>
                                        <th>Year</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courseStudents as $s): ?>
                                        <tr>
                                            <td><?= e($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                            <td><?= e($s['reg_number']) ?></td>
                                            <td>Year <?= $s['year_of_study'] ?></td>
                                            <td><?= e($s['email']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="muted">No students enrolled in this course.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
