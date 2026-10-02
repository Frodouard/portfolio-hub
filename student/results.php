<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$results = [];
$gpa = 'N/A';
$totalCredits = 0;

if ($studentId) {
    $r = @mysqli_query($conn, "SELECT res.marks, res.grade, res.grade_points, res.academic_year, res.semester,
        c.course_code, c.course_name, c.credits
        FROM results res
        JOIN courses c ON c.course_id = res.course_id
        WHERE res.student_id = $studentId AND res.published = 1
        ORDER BY res.academic_year DESC, res.semester, c.course_code");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $results[] = $row;
        }
    }

    $gpaResult = @mysqli_query($conn, "SELECT SUM(res.grade_points * c.credits) / SUM(c.credits) AS avg_gpa, SUM(c.credits) AS total_credits
        FROM results res
        JOIN courses c ON c.course_id = res.course_id
        WHERE res.student_id = $studentId AND res.published = 1 AND res.grade_points IS NOT NULL");
    if ($gpaResult) {
        $gpaRow = mysqli_fetch_assoc($gpaResult);
        if ($gpaRow && $gpaRow['avg_gpa'] !== null) {
            $gpa = number_format((float)$gpaRow['avg_gpa'], 2);
            $totalCredits = (int)($gpaRow['total_credits'] ?? 0);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Results</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link active" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Academic progress</p>
                <h2>Latest Results</h2>

                <?php if ($gpa !== 'N/A'): ?>
                    <div class="info-tiles" style="margin-bottom: 20px;">
                        <div class="info-tile">
                            <div class="label">Cumulative GPA</div>
                            <div class="value"><?= e($gpa) ?></div>
                        </div>
                        <div class="info-tile">
                            <div class="label">Total Credits</div>
                            <div class="value"><?= $totalCredits ?></div>
                        </div>
                        <div class="info-tile">
                            <div class="label">Courses Completed</div>
                            <div class="value"><?= count($results) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($results)): ?>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>GPA</th>
                                    <th>Semester</th>
                                    <th>Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $r): ?>
                                    <tr>
                                        <td><?= e($r['course_code']) ?></td>
                                        <td><?= e($r['course_name']) ?></td>
                                        <td><?= e((string)$r['credits']) ?></td>
                                        <td><?= $r['marks'] !== null ? number_format((float)$r['marks'], 1) . '%' : 'N/A' ?></td>
                                        <td><strong><?= e($r['grade'] ?? 'N/A') ?></strong></td>
                                        <td><?= $r['grade_points'] !== null ? number_format((float)$r['grade_points'], 2) : 'N/A' ?></td>
                                        <td><?= e($r['semester']) ?></td>
                                        <td><?= e($r['academic_year']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No published results available yet. Results will appear here once your lecturers submit grades and your HOD publishes them.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
