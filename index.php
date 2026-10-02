 <?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

// Already logged in? Go straight to dashboard.
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';

// Flash messages from redirects
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $errors[] = 'Your session has expired. Please log in again.';
}
if (isset($_GET['registered'])) {
    $success = 'Account created successfully. You can now log in.';
}
if (isset($_GET['reset'])) {
    $success = 'Password reset successfully. You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $identifier = trim($_POST['identifier'] ?? ''); // username or email
        $password   = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $errors[] = 'Please enter both your username/email and password.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'SELECT user_id, username, email, password_hash, role, status
                 FROM users WHERE username = ? OR email = ? LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'ss', $identifier, $identifier);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Incorrect username/email or password.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Your account is not active. Please contact the administrator.';
            } else {
                // Successful login — regenerate session ID to prevent fixation
                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // Pull student display name/id if applicable
                if ($user['role'] === 'student') {
                    $sStmt = mysqli_prepare($conn, 'SELECT student_id, first_name, last_name, profile_picture FROM students WHERE user_id = ?');
                    mysqli_stmt_bind_param($sStmt, 'i', $user['user_id']);
                    mysqli_stmt_execute($sStmt);
                    $sResult = mysqli_stmt_get_result($sStmt);
                    if ($student = mysqli_fetch_assoc($sResult)) {
                        $_SESSION['student_id'] = $student['student_id'];
                        $_SESSION['full_name']  = $student['first_name'] . ' ' . $student['last_name'];
                        $_SESSION['profile_picture'] = $student['profile_picture'];
                    }
                    mysqli_stmt_close($sStmt);
                }

                // Pull lecturer profile data if applicable
                if ($user['role'] === 'lecturer' || $user['role'] === 'hod') {
                    $lStmt = mysqli_prepare($conn, 'SELECT lecturer_id, first_name, last_name, department_id FROM lecturers WHERE user_id = ?');
                    mysqli_stmt_bind_param($lStmt, 'i', $user['user_id']);
                    mysqli_stmt_execute($lStmt);
                    $lResult = mysqli_stmt_get_result($lStmt);
                    if ($lecturer = mysqli_fetch_assoc($lResult)) {
                        $_SESSION['lecturer_id'] = $lecturer['lecturer_id'];
                        $_SESSION['full_name'] = $lecturer['first_name'] . ' ' . $lecturer['last_name'];
                        $_SESSION['department_id'] = $lecturer['department_id'];
                    }
                    mysqli_stmt_close($lStmt);

                    // For HOD, fetch their assigned department
                    if ($user['role'] === 'hod') {
                        $hStmt = mysqli_prepare($conn, 'SELECT ha.department_id, d.department_name FROM hod_assignments ha JOIN departments d ON d.department_id = ha.department_id WHERE ha.user_id = ?');
                        mysqli_stmt_bind_param($hStmt, 'i', $user['user_id']);
                        mysqli_stmt_execute($hStmt);
                        $hResult = mysqli_stmt_get_result($hStmt);
                        if ($hod = mysqli_fetch_assoc($hResult)) {
                            $_SESSION['department_id'] = $hod['department_id'];
                            $_SESSION['department_name'] = $hod['department_name'];
                        }
                        mysqli_stmt_close($hStmt);
                    }
                }

                // Update last login
                $uStmt = mysqli_prepare($conn, 'UPDATE users SET last_login = NOW() WHERE user_id = ?');
                mysqli_stmt_bind_param($uStmt, 'i', $user['user_id']);
                mysqli_stmt_execute($uStmt);
                mysqli_stmt_close($uStmt);

                header('Location: ' . match($user['role']) {
                    'admin' => 'admin/dashboard.php',
                    'hod' => 'hod/dashboard.php',
                    'lecturer' => 'lecturer/dashboard.php',
                    default => 'dashboard.php',
                });
                exit;
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
<title>Login | AUCA Student Portal</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-circle">AUCA</div>
            <h1>Student Portal</h1>
            <p class="auth-subtitle">Adventist University of Central Africa</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><p><?= e($success) ?></p></div>
        <?php endif; ?>

        <form method="POST" action="index.php" class="auth-form" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="identifier">Username or Email</label>
                <input type="text" id="identifier" name="identifier" required autofocus
                       value="<?= e($_POST['identifier'] ?? '') ?>" placeholder="e.g. jdoe or jdoe@auca.ac.rw">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁</button>
                </div>
            </div>

            <div class="form-row-between">
                <label class="checkbox-inline">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="forgot-password.php" class="link-muted">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>

        <p class="auth-footer">Don't have an account? <a href="register.php">Create one</a></p>
    </div>
</div>

<script src="js/login.js"></script>
</body>
</html>