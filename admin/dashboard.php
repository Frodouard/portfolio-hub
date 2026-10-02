<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$userName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Admin');

// Handle HOD assignment form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        if ($_POST['action'] === 'assign_hod') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $deptId = (int)($_POST['department_id'] ?? 0);
            if ($userId > 0 && $deptId > 0) {
                // Remove existing HOD for this department
                $stmt = mysqli_prepare($conn, "DELETE FROM hod_assignments WHERE department_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $deptId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                // Remove existing HOD role from previous assignment for this user
                $stmt = mysqli_prepare($conn, "UPDATE users SET role = 'lecturer' WHERE user_id = ? AND role = 'hod'");
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                // Assign new HOD
                $stmt = mysqli_prepare($conn, "INSERT INTO hod_assignments (user_id, department_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, 'ii', $userId, $deptId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $stmt = mysqli_prepare($conn, "UPDATE users SET role = 'hod' WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success = 'HOD assigned successfully.';
            }
        } elseif ($_POST['action'] === 'add_lecturer') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $deptId = (int)($_POST['department_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            if ($userId > 0 && $firstName !== '' && $lastName !== '') {
                $stmt = mysqli_prepare($conn, "UPDATE users SET role = 'lecturer' WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $d = $deptId > 0 ? $deptId : null;
                $stmt = mysqli_prepare($conn, "INSERT INTO lecturers (user_id, first_name, last_name, department_id, title) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'issis', $userId, $firstName, $lastName, $d, $title);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success = 'Lecturer added successfully.';
            }
        }
    }
}

// Stats
$stats = [];
$r = @mysqli_query($conn, "SELECT role, COUNT(*) AS total FROM users GROUP BY role");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $stats[$row['role']] = (int)$row['total'];
    }
}
$totalUsers = array_sum($stats);

$totalStudents = $stats['student'] ?? 0;
$totalAdmins = $stats['admin'] ?? 0;
$totalLecturers = $stats['lecturer'] ?? 0;
$totalHods = $stats['hod'] ?? 0;

$totalCourses = 0;
$r5 = @mysqli_query($conn, 'SELECT COUNT(*) AS total FROM courses');
if ($r5) { $totalCourses = (int)(mysqli_fetch_assoc($r5)['total'] ?? 0); }

$totalDepts = 0;
$r6 = @mysqli_query($conn, 'SELECT COUNT(*) AS total FROM departments');
if ($r6) { $totalDepts = (int)(mysqli_fetch_assoc($r6)['total'] ?? 0); }

$totalRegistrations = 0;
$r7 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM registrations WHERE status = 'registered'");
if ($r7) { $totalRegistrations = (int)(mysqli_fetch_assoc($r7)['total'] ?? 0); }

$totalResults = 0;
$publishedResults = 0;
$unpublishedResults = 0;
$r8 = @mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) AS pub, SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) AS unp FROM results");
if ($r8) { $row = mysqli_fetch_assoc($r8); $totalResults = (int)($row['total'] ?? 0); $publishedResults = (int)($row['pub'] ?? 0); $unpublishedResults = (int)($row['unp'] ?? 0); }

$totalAttendance = 0;
$r9 = @mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance");
if ($r9) { $totalAttendance = (int)(mysqli_fetch_assoc($r9)['total'] ?? 0); }

// All users
$allUsers = [];
$r = @mysqli_query($conn, "SELECT u.user_id, u.username, u.email, u.role, u.status, u.last_login, u.created_at FROM users u ORDER BY u.role, u.username");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $allUsers[] = $row;
    }
}

// All lecturers
$allLecturers = [];
$r = @mysqli_query($conn, "SELECT l.first_name, l.last_name, l.title, d.department_name
    FROM lecturers l
    LEFT JOIN departments d ON d.department_id = l.department_id
    ORDER BY l.last_name, l.first_name");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $allLecturers[] = $row;
    }
}

// HOD assignments
$hodAssignments = [];
$r = @mysqli_query($conn, "SELECT u.username, l.first_name, l.last_name, d.department_name
    FROM hod_assignments ha
    JOIN users u ON u.user_id = ha.user_id
    JOIN lecturers l ON l.user_id = ha.user_id
    JOIN departments d ON d.department_id = ha.department_id
    ORDER BY d.department_name");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $hodAssignments[] = $row;
    }
}

// Departments
$departments = [];
$r = @mysqli_query($conn, "SELECT department_id, department_name, department_code FROM departments ORDER BY department_name");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $departments[] = $row;
    }
}

// Registered courses
$registeredCoursesResult = @mysqli_query($conn, "
    SELECT r.registration_id, c.course_code, c.course_name, c.credits, r.semester, r.academic_year
    FROM registrations r
    JOIN courses c ON c.course_id = r.course_id
    WHERE r.status = 'registered'
    ORDER BY c.course_code, c.course_name
");
$registeredCourses = [];
if ($registeredCoursesResult) {
    while ($row = mysqli_fetch_assoc($registeredCoursesResult)) {
        $registeredCourses[] = $row;
    }
}

// Staff members
$staffMembers = [];
$r = @mysqli_query($conn, 'SELECT first_name, last_name, position FROM admins ORDER BY first_name, last_name LIMIT 6');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $staffMembers[] = $row;
    }
}

// Notifications (admin-created)
$notifications = [];
$r = @mysqli_query($conn, "SELECT title, message, target_role, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div>
                    <h2>Admin Panel</h2>
                    <p>University portal management</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link active" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="users.php">Users</a>
                <a class="dashboard-nav-link" href="departments.php">Departments</a>
                <a class="dashboard-nav-link" href="courses.php">Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="enrollments.php">Enrollments</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
            </nav>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">AUCA administration</p>
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="dashboard-chip">Welcome, <?= e($userName) ?></div>
            </div>

            <?php if (!empty($error)) : ?>
                <div class="alert alert-error"><p><?= e($error) ?></p></div>
            <?php endif; ?>
            <?php if (!empty($success)) : ?>
                <div class="alert alert-success"><p><?= e($success) ?></p></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Hero Overview -->
                <section class="dashboard-card hero-card">
                    <div>
                        <p class="eyebrow">Overview</p>
                        <h2 style="margin: 0 0 8px;">Manage the AUCA student portal</h2>
                        <p class="muted">Monitor the platform, manage users, and keep the campus community informed.</p>
                    </div>
                    <div class="hero-metrics">
                        <div>
                            <strong><?= $totalUsers ?></strong>
                            <span>Total Users</span>
                        </div>
                        <div>
                            <strong><?= $totalStudents ?></strong>
                            <span>Students</span>
                        </div>
                        <div>
                            <strong><?= $totalLecturers ?></strong>
                            <span>Lecturers</span>
                        </div>
                        <div>
                            <strong><?= $totalCourses ?></strong>
                            <span>Courses</span>
                        </div>
                        <div>
                            <strong><?= $totalRegistrations ?></strong>
                            <span>Enrollments</span>
                        </div>
                        <div>
                            <strong><?= $totalResults ?></strong>
                            <span>Grades</span>
                        </div>
                        <div>
                            <strong><?= $totalAttendance ?></strong>
                            <span>Attendance Records</span>
                        </div>
                    </div>
                </section>

                <!-- All Users -->
                <section class="dashboard-card" id="users-section" style="grid-column: span 2;">
                    <p class="eyebrow">User Management</p>
                    <h3 style="margin: 0 0 12px;">All Users (<?= $totalUsers ?>)</h3>
                    <?php if (!empty($allUsers)) : ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allUsers as $u) : ?>
                                        <tr>
                                            <td><?= e($u['username']) ?></td>
                                            <td><?= e($u['email']) ?></td>
                                            <td><span style="text-transform: capitalize;"><?= e($u['role']) ?></span></td>
                                            <td><span style="text-transform: capitalize; color: <?= $u['status'] === 'active' ? '#15803d' : '#dc2626' ?>;"><?= e($u['status']) ?></span></td>
                                            <td><?= e($u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never') ?></td>
                                            <td><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="muted">No users found.</p>
                    <?php endif; ?>
                </section>

                <!-- Lecturers -->
                <section class="dashboard-card" id="lecturers-section">
                    <p class="eyebrow">Lecturers</p>
                    <h3 style="margin: 0 0 12px;">All Lecturers (<?= count($allLecturers) ?>)</h3>
                    <?php if (!empty($allLecturers)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($allLecturers as $l) : ?>
                                <li><?= e($l['first_name'] . ' ' . $l['last_name']) ?><?php if (!empty($l['title'])) : ?> — <?= e($l['title']) ?><?php endif; ?><?php if (!empty($l['department_name'])) : ?> (<?= e($l['department_name']) ?>)<?php endif; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No lecturers registered yet.</p>
                    <?php endif; ?>
                </section>

                <!-- HOD Assignments -->
                <section class="dashboard-card" id="hod-section">
                    <p class="eyebrow">Head of Department</p>
                    <h3 style="margin: 0 0 12px;">HOD Assignments</h3>
                    <?php if (!empty($hodAssignments)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($hodAssignments as $h) : ?>
                                <li><strong><?= e($h['first_name'] . ' ' . $h['last_name']) ?></strong> — <?= e($h['department_name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No HODs assigned yet.</p>
                    <?php endif; ?>

                    <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-border);">
                        <p class="eyebrow" style="margin-bottom: 8px;">Assign HOD</p>
                        <form method="POST" class="auth-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="assign_hod">
                            <div class="form-group">
                                <label for="hod_user_id">Lecturer (User ID)</label>
                                <input type="number" id="hod_user_id" name="user_id" required placeholder="Enter user ID">
                            </div>
                            <div class="form-group">
                                <label for="hod_dept_id">Department</label>
                                <select id="hod_dept_id" name="department_id" required>
                                    <option value="">Select department</option>
                                    <?php foreach ($departments as $d) : ?>
                                        <option value="<?= $d['department_id'] ?>"><?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Assign HOD</button>
                        </form>
                    </div>
                </section>

                <!-- Staff Directory -->
                <section class="dashboard-card">
                    <p class="eyebrow">Admin Staff</p>
                    <h3 style="margin: 0 0 8px;">Administrators</h3>
                    <?php if (!empty($staffMembers)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($staffMembers as $staff) : ?>
                                <?php $staffName = trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')); ?>
                                <li><?= e($staffName) ?><?php if (!empty($staff['position'])) : ?> — <?= e($staff['position']) ?><?php endif; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No staff records found.</p>
                    <?php endif; ?>
                </section>

                <!-- Registered Courses -->
                <section class="dashboard-card" id="courses-section" style="grid-column: span 2;">
                    <p class="eyebrow">Registered Courses</p>
                    <h3 style="margin: 0 0 12px;">All Registrations (<?= count($registeredCourses) ?>)</h3>
                    <?php if (!empty($registeredCourses)) : ?>
                        <div style="overflow-x: auto;">
                            <table class="student-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Course</th>
                                        <th>Credits</th>
                                        <th>Semester</th>
                                        <th>Year</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registeredCourses as $course) : ?>
                                        <tr>
                                            <td><?= e($course['course_code']) ?></td>
                                            <td><?= e($course['course_name']) ?></td>
                                            <td><?= e((string)$course['credits']) ?></td>
                                            <td><?= e($course['semester']) ?></td>
                                            <td><?= e($course['academic_year']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p class="muted">No registered courses found yet.</p>
                    <?php endif; ?>
                </section>

                <!-- Departments -->
                <section class="dashboard-card" id="departments-section">
                    <p class="eyebrow">Departments</p>
                    <h3 style="margin: 0 0 12px;">All Departments</h3>
                    <?php if (!empty($departments)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($departments as $d) : ?>
                                <li><?= e($d['department_name']) ?> (<?= e($d['department_code']) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No departments found.</p>
                    <?php endif; ?>
                </section>

                <!-- Notifications -->
                <section class="dashboard-card">
                    <p class="eyebrow">Recent Notifications</p>
                    <?php if (!empty($notifications)) : ?>
                        <ul class="dashboard-list">
                            <?php foreach ($notifications as $n) : ?>
                                <li><strong><?= e($n['title']) ?></strong> — <?= e(mb_strimwidth($n['message'], 0, 60, '...')) ?> (<?= e($n['target_role']) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="muted">No notifications yet.</p>
                    <?php endif; ?>
                </section>

            </div>
        </main>
    </div>
</body>
</html>
