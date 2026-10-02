<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

if (($_SESSION['role'] ?? '') === 'hod') {
    header('Location: hod/dashboard.php');
    exit;
}

if (($_SESSION['role'] ?? '') === 'lecturer') {
    header('Location: lecturer/dashboard.php');
    exit;
}

$stmt = mysqli_prepare($conn, 'SELECT s.first_name, s.last_name, s.reg_number, s.year_of_study, s.department_id, d.department_name
    FROM students s
    LEFT JOIN departments d ON d.department_id = s.department_id
    WHERE s.user_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
if ($fullName === '') {
    $fullName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');
}

$regNumber = $student['reg_number'] ?? 'N/A';
$department = $student['department_name'] ?? 'Department pending';
$year = $student['year_of_study'] ?? '1';
$program = $_SESSION['program'] ?? 'Day';
$credits = $_SESSION['credits'] ?? '0';
$pageTitle = 'Student dashboard';
$activePage = 'dashboard';

$studentId = $_SESSION['student_id'] ?? null;
$registeredCoursesResult = null;
$registeredCourses = [];

if ($studentId) {
    $sid = (int)$studentId;
    $registeredCoursesResult = @mysqli_query($conn, "
        SELECT r.registration_id, c.course_code, c.course_name, c.credits, r.semester, r.academic_year
        FROM registrations r
        JOIN courses c ON c.course_id = r.course_id
        WHERE r.student_id = $sid AND r.status = 'registered'
        ORDER BY c.course_code, c.course_name
    ");
    
    $scheduleDefaults = [
        'CS101'  => ['group' => 'G1', 'room' => 'R101', 'day' => 'Monday',    'hour' => '08:00'],
        'CS201'  => ['group' => 'G1', 'room' => 'R102', 'day' => 'Tuesday',   'hour' => '10:00'],
        'CS301'  => ['group' => 'G1', 'room' => 'R101', 'day' => 'Monday',    'hour' => '09:00'],
        'CS302'  => ['group' => 'G2', 'room' => 'R102', 'day' => 'Tuesday',   'hour' => '11:00'],
        'CS303'  => ['group' => 'G3', 'room' => 'R103', 'day' => 'Wednesday', 'hour' => '13:00'],
        'CS304'  => ['group' => 'G3', 'room' => 'R103', 'day' => 'Wednesday', 'hour' => '14:00'],
        'NET101' => ['group' => 'A', 'room' => 'R201', 'day' => 'Monday',    'hour' => '11:00'],
        'NET201' => ['group' => 'A', 'room' => 'R201', 'day' => 'Tuesday',   'hour' => '13:00'],
        'NET301' => ['group' => 'A', 'room' => 'R202', 'day' => 'Thursday',  'hour' => '09:00'],
        'BADM101'=> ['group' => 'B', 'room' => 'R301', 'day' => 'Friday',    'hour' => '08:00'],
        'BADM201'=> ['group' => 'B', 'room' => 'R301', 'day' => 'Friday',    'hour' => '10:00'],
        'MATH101'=> ['group' => 'C', 'room' => 'R104', 'day' => 'Thursday',  'hour' => '15:00'],
        'MATH102'=> ['group' => 'C', 'room' => 'R104', 'day' => 'Thursday',  'hour' => '16:00'],
        'MAT201' => ['group' => 'G4', 'room' => 'R104', 'day' => 'Thursday', 'hour' => '15:00'],
        'ENG201' => ['group' => 'G5', 'room' => 'R105', 'day' => 'Friday',   'hour' => '08:00'],
    ];
    
    if ($registeredCoursesResult) {
        while ($row = mysqli_fetch_assoc($registeredCoursesResult)) {
            $courseCode = $row['course_code'] ?? '';
            $schedule = $scheduleDefaults[$courseCode] ?? ['group' => 'G1', 'room' => 'R-01', 'day' => 'TBD', 'hour' => 'TBD'];
            $registeredCourses[] = [
                'course_code' => $courseCode,
                'course_name' => $row['course_name'] ?? '',
                'credits' => (int)($row['credits'] ?? 0),
                'group' => $schedule['group'],
                'room' => $schedule['room'],
                'day' => $schedule['day'],
                'hour' => $schedule['hour'],
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | AUCA Student Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Welcome back</p>
                </div>
            </div>

            <nav class="dashboard-nav">
                <a class="dashboard-nav-link active" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="student/enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="student/profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="student/courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="student/results.php">Results</a>
                <a class="dashboard-nav-link" href="student/attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="student/assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="student/announcements.php">Announcements</a>
                <a class="dashboard-nav-link" href="student/exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="student/exam_timetable.php">Exam Timetable</a>
            </nav>

            <a href="logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <p class="eyebrow">AUCA student portal</p>
                    <h1><?= e($pageTitle) ?></h1>
                </div>
                <div class="dashboard-chip">
                    <?= e($fullName) ?>
                </div>
                <div style="display:flex; align-items:center; gap:8px; margin-left:12px;">
                </div>
            </header>
            

            <section class="dashboard-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <article class="dashboard-card hero-card" style="grid-column: 1 / -1;">
                    <div style="width:100%">
                        <div class="info-tiles">
                            <div class="info-tile">
                                <div class="label">Full name</div>
                                <div class="value"><?= e($fullName) ?></div>
                            </div>
                            <div class="info-tile small">
                                <div class="label">Reg. Nr.</div>
                                <div class="value"><?= e($regNumber) ?></div>
                            </div>
                            <div class="info-tile">
                                <div class="label">Faculty</div>
                                <div class="value"><?= e($department) ?></div>
                            </div>
                            <div class="info-tile">
                                <div class="label">Department</div>
                                <div class="value"><?= e($department) ?></div>
                            </div>
                            <div class="info-tile small">
                                <div class="label">Program</div>
                                <div class="value"><?= e($program) ?></div>
                            </div>
                            <div class="info-tile small">
                                <div class="label">Credits</div>
                                <div class="value"><?= e($credits) ?></div>
                            </div>
                        </div>
                    </div>
                </article>

                <div class="dashboard-stack">
                    <article class="dashboard-card">
                        <p class="eyebrow">Quick links</p>
                        <ul class="dashboard-list">
                            <li><a href="student/profile.php">View your profile</a></li>
                            <li><a href="student/courses.php">See your courses</a></li>
                            <li><a href="student/results.php">Check your latest results</a></li>
                            <li><a href="student/enroll_course.php">Enroll a course</a></li>
                            <li><a href="student/exam_permit.php">View your exam permit</a></li>
                            <li><a href="student/exam_timetable.php">View the exam timetable</a></li>
                        </ul>
                        <a href="student/download_timetable.php" class="btn btn-primary" style="margin-top: 12px;">Download timetable (.doc)</a>
                        
                    </article>

                    <article class="dashboard-card">
                        <p class="eyebrow">Upcoming activities</p>
                        <ul class="dashboard-list">
                            <li>Programming lab submission due Friday</li>
                            <li>Student council meeting at 2:00 PM</li>
                            <li>Library orientation for new intake</li>
                        </ul>
                    </article>
                </div>

                <div class="dashboard-stack">
                    <article class="dashboard-card">
                        <p class="eyebrow">Timetable</p>
                        <p class="muted">Download your academic timetable for the current semester.</p>
                        <a href="student/download-timetable.php" class="btn btn-primary">Download timetable</a>
                    </article>

                    <article class="dashboard-card">
                        <p class="eyebrow">Announcements</p>
                        <?php
                        $annResult = @mysqli_query($conn, "SELECT title, message FROM notifications WHERE target_role IN ('all','student') ORDER BY created_at DESC LIMIT 3");
                        $announcements = [];
                        if ($annResult) { while ($ar = mysqli_fetch_assoc($annResult)) { $announcements[] = $ar; } }
                        ?>
                        <?php if (!empty($announcements)) : ?>
                            <?php foreach ($announcements as $ann) : ?>
                                <div style="margin-bottom: 10px;">
                                    <strong style="font-size: 13px;"><?= e($ann['title']) ?></strong>
                                    <p style="margin: 2px 0 0; font-size: 12px; color: var(--color-text-muted);"><?= e(mb_substr($ann['message'], 0, 80)) ?>...</p>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="muted">No announcements yet.</p>
                        <?php endif; ?>
                        <a href="student/announcements.php" class="btn btn-primary">View all</a>
                    </article>

                    <article class="dashboard-card">
                        <p class="eyebrow">Live chat</p>
                        <p class="muted">Need help? Contact the student support team directly.</p>
                        <a href="student/chat.php" class="btn btn-primary">Open chat</a>
                    </article>

                    <article class="dashboard-card">
                        <p class="eyebrow">Registered courses</p>
                        <?php if (!empty($registeredCourses)) : ?>
                            <div style="overflow-x: auto;">
                                <table class="student-table" style="font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course</th>
                                            <th>Credits</th>
                                            <th>Group</th>
                                            <th>Room</th>
                                            <th>Day</th>
                                            <th>Hour</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registeredCourses as $course) : ?>
                                            <tr>
                                                <td><?= e($course['course_code']) ?></td>
                                                <td><?= e($course['course_name']) ?></td>
                                                <td><?= e((string)$course['credits']) ?></td>
                                                <td><?= e($course['group']) ?></td>
                                                <td><?= e($course['room']) ?></td>
                                                <td><?= e($course['day']) ?></td>
                                                <td><?= e($course['hour']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <p class="muted">No registered courses yet.</p>
                        <?php endif; ?>
                    </article>
                </div>
            </section>

        </main>
    </div>
</body>
</html>
