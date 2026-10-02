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

        if ($action === 'add_user') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'student';
            if (!in_array($role, ['student', 'lecturer', 'hod', 'admin'])) {
                $role = 'student';
            }
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $deptId = (int)($_POST['department_id'] ?? 0);
            $regNumber = trim($_POST['reg_number'] ?? '');
            $yearStudy = (int)($_POST['year_of_study'] ?? 1);

            if ($username === '' || $email === '' || $password === '' || $firstName === '' || $lastName === '') {
                $msg = 'All fields are required.';
                $msgType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $uStmt = mysqli_prepare($conn, 'INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, "active")');
                mysqli_stmt_bind_param($uStmt, 'ssss', $username, $email, $hash, $role);
                if (mysqli_stmt_execute($uStmt)) {
                    $userId = mysqli_insert_id($conn);
                    mysqli_stmt_close($uStmt);

                    if ($role === 'student') {
                        if ($deptId > 0) {
                            $sStmt = mysqli_prepare($conn, 'INSERT INTO students (user_id, reg_number, first_name, last_name, department_id, year_of_study) VALUES (?, ?, ?, ?, ?, ?)');
                            mysqli_stmt_bind_param($sStmt, 'isssii', $userId, $regNumber, $firstName, $lastName, $deptId, $yearStudy);
                        } else {
                            $sStmt = mysqli_prepare($conn, 'INSERT INTO students (user_id, reg_number, first_name, last_name, year_of_study) VALUES (?, ?, ?, ?, ?)');
                            mysqli_stmt_bind_param($sStmt, 'isssi', $userId, $regNumber, $firstName, $lastName, $yearStudy);
                        }
                        if (!mysqli_stmt_execute($sStmt)) {
                            $msg = 'Student profile error: ' . mysqli_error($conn);
                            $msgType = 'error';
                        }
                        mysqli_stmt_close($sStmt);
                    } elseif ($role === 'lecturer' || $role === 'hod') {
                        if ($deptId > 0) {
                            $lStmt = mysqli_prepare($conn, 'INSERT INTO lecturers (user_id, first_name, last_name, department_id) VALUES (?, ?, ?, ?)');
                            mysqli_stmt_bind_param($lStmt, 'issi', $userId, $firstName, $lastName, $deptId);
                        } else {
                            $lStmt = mysqli_prepare($conn, 'INSERT INTO lecturers (user_id, first_name, last_name) VALUES (?, ?, ?)');
                            mysqli_stmt_bind_param($lStmt, 'iss', $userId, $firstName, $lastName);
                        }
                        mysqli_stmt_execute($lStmt);
                        mysqli_stmt_close($lStmt);

                        if ($role === 'hod' && $deptId > 0) {
                            $delStmt = mysqli_prepare($conn, "DELETE FROM hod_assignments WHERE department_id = ?");
                            mysqli_stmt_bind_param($delStmt, 'i', $deptId);
                            mysqli_stmt_execute($delStmt);
                            mysqli_stmt_close($delStmt);
                            $hStmt = mysqli_prepare($conn, 'INSERT INTO hod_assignments (user_id, department_id) VALUES (?, ?)');
                            mysqli_stmt_bind_param($hStmt, 'ii', $userId, $deptId);
                            mysqli_stmt_execute($hStmt);
                            mysqli_stmt_close($hStmt);
                        }
                    } elseif ($role === 'admin') {
                        $aStmt = mysqli_prepare($conn, 'INSERT INTO admins (user_id, first_name, last_name, position) VALUES (?, ?, ?, "Staff")');
                        mysqli_stmt_bind_param($aStmt, 'iss', $userId, $firstName, $lastName);
                        mysqli_stmt_execute($aStmt);
                        mysqli_stmt_close($aStmt);
                    }

                    if (empty($msg)) {
                        $msg = 'User created successfully.';
                        $msgType = 'success';
                    }
                } else {
                    $err = mysqli_error($conn);
                    if (strpos($err, 'username') !== false) {
                        $msg = "Username '$username' already exists.";
                    } elseif (strpos($err, 'email') !== false) {
                        $msg = "Email '$email' already exists.";
                    } else {
                        $msg = 'Error: ' . $err;
                    }
                    $msgType = 'error';
                }
            }
        } elseif ($action === 'update_status') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            if ($userId > 0 && in_array($status, ['active', 'inactive', 'suspended'])) {
                $stmt = mysqli_prepare($conn, 'UPDATE users SET status = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $status, $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'User status updated.';
                $msgType = 'success';
            }
        } elseif ($action === 'delete_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId > 0 && $userId !== $_SESSION['user_id']) {
                $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE user_id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = 'User deleted.';
                $msgType = 'success';
            }
        }
    }
}

$allUsers = [];
$r = @mysqli_query($conn, "SELECT u.user_id, u.username, u.email, u.role, u.status, u.last_login, u.created_at,
    s.first_name AS s_first, s.last_name AS s_last, s.student_id,
    l.first_name AS l_first, l.last_name AS l_last, l.lecturer_id
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN lecturers l ON l.user_id = u.user_id
    ORDER BY u.role, u.username");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $allUsers[] = $row;
    }
}

$departments = [];
$r = @mysqli_query($conn, 'SELECT department_id, department_name, department_code FROM departments ORDER BY department_name');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $departments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | AUCA Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle" style="margin:0; width:46px; height:46px;">AUCA</div>
                <div><h2>Admin Panel</h2><p>User management</p></div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="dashboard.php">Overview</a>
                <a class="dashboard-nav-link active" href="users.php">Users</a>
                <a class="dashboard-nav-link" href="departments.php">Departments</a>
                <a class="dashboard-nav-link" href="courses.php">Courses</a>
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
                    <h1>User Management</h1>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>"><p><?= e($msg) ?></p></div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Add User Form -->
                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">Create User</p>
                    <h3 style="margin: 0 0 12px;">Add New User</h3>
                    <form method="POST" class="auth-form" style="max-width: 700px;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="add_user">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" required minlength="8">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" id="addRole">
                                    <option value="student">Student</option>
                                    <option value="lecturer">Lecturer</option>
                                    <option value="hod">HOD</option>
                                    <option value="admin">Admin</option>
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
                            <div class="form-group" id="regField">
                                <label>Reg Number</label>
                                <input type="text" name="reg_number" placeholder="e.g. 2026/SENG/0142">
                            </div>
                        </div>
                        <div class="form-group" id="yearField" style="max-width: 200px;">
                            <label>Year of Study</label>
                            <select name="year_of_study">
                                <?php for ($y = 1; $y <= 5; $y++): ?>
                                    <option value="<?= $y ?>">Year <?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </form>
                </section>

                <!-- Users Table -->
                <section class="dashboard-card" style="grid-column: span 2;">
                    <p class="eyebrow">All Users</p>
                    <h3 style="margin: 0 0 12px;">Registered Users (<?= count($allUsers) ?>)</h3>
                    <div style="overflow-x: auto;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $u): ?>
                                    <tr>
                                        <td><?= $u['user_id'] ?></td>
                                        <td>
                                            <?php if ($u['role'] === 'student' && $u['student_id']): ?>
                                                <a href="student_profile.php?student_id=<?= $u['student_id'] ?>" style="color: var(--color-accent); text-decoration: underline;"><?= e($u['s_first'] . ' ' . $u['s_last']) ?></a>
                                            <?php elseif (($u['role'] === 'lecturer' || $u['role'] === 'hod') && $u['lecturer_id']): ?>
                                                <a href="lecturer_profile.php?lecturer_id=<?= $u['lecturer_id'] ?>" style="color: var(--color-accent); text-decoration: underline;"><?= e($u['l_first'] . ' ' . $u['l_last']) ?></a>
                                            <?php else: ?>
                                                <?= e($u['username']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($u['email']) ?></td>
                                        <td><span style="text-transform: capitalize;"><?= e($u['role']) ?></span></td>
                                        <td><span style="text-transform: capitalize; color: <?= $u['status'] === 'active' ? '#15803d' : '#dc2626' ?>;"><?= e($u['status']) ?></span></td>
                                        <td><?= $u['last_login'] ? date('M d, Y', strtotime($u['last_login'])) : 'Never' ?></td>
                                        <td style="white-space: nowrap;">
                                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                                    <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px; background: <?= $u['status'] === 'active' ? 'var(--color-warning)' : 'var(--color-accent)' ?>;">
                                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this user permanently?');">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px; background: var(--color-danger);">Delete</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">You</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <script>
    document.getElementById('addRole').addEventListener('change', function() {
        const role = this.value;
        document.getElementById('regField').style.display = (role === 'student') ? '' : 'none';
        document.getElementById('yearField').style.display = (role === 'student') ? '' : 'none';
    });
    document.getElementById('addRole').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
