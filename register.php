<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old = [
    'first_name' => '', 'last_name' => '', 'reg_number' => '',
    'email' => '', 'username' => '', 'department_id' => '', 'year_of_study' => '1'
];

// Departments for the dropdown
$deptResult = mysqli_query($conn, 'SELECT department_id, department_name FROM departments ORDER BY department_name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        foreach ($old as $key => $default) {
            $old[$key] = trim($_POST[$key] ?? $default);
        }
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // ---------- Validation ----------
        if ($old['first_name'] === '' || $old['last_name'] === '') {
            $errors[] = 'First and last name are required.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $old['username'])) {
            $errors[] = 'Username must be 3-30 characters (letters, numbers, underscore only).';
        }
        if ($old['reg_number'] === '') {
            $errors[] = 'Registration number is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        // ---------- Uniqueness checks ----------
        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE username = ? OR email = ?');
            mysqli_stmt_bind_param($stmt, 'ss', $old['username'], $old['email']);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
                $errors[] = 'That username or email is already registered.';
            }
            mysqli_stmt_close($stmt);

            $stmt2 = mysqli_prepare($conn, 'SELECT student_id FROM students WHERE reg_number = ?');
            mysqli_stmt_bind_param($stmt2, 's', $old['reg_number']);
            mysqli_stmt_execute($stmt2);
            if (mysqli_stmt_get_result($stmt2)->num_rows > 0) {
                $errors[] = 'That registration number is already in use.';
            }
            mysqli_stmt_close($stmt2);
        }

        // ---------- Insert ----------
        if (empty($errors)) {
            mysqli_begin_transaction($conn);
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                $uStmt = mysqli_prepare($conn,
                    'INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, "student", "active")'
                );
                mysqli_stmt_bind_param($uStmt, 'sss', $old['username'], $old['email'], $hash);
                mysqli_stmt_execute($uStmt);
                $userId = mysqli_insert_id($conn);
                mysqli_stmt_close($uStmt);

                $deptId = $old['department_id'] !== '' ? (int)$old['department_id'] : null;
                $year   = (int)$old['year_of_study'];

                $sStmt = mysqli_prepare($conn,
                    'INSERT INTO students (user_id, reg_number, first_name, last_name, department_id, year_of_study, enrollment_date)
                     VALUES (?, ?, ?, ?, ?, ?, CURDATE())'
                );
                mysqli_stmt_bind_param($sStmt, 'isssii', $userId, $old['reg_number'], $old['first_name'], $old['last_name'], $deptId, $year);
                mysqli_stmt_execute($sStmt);
                mysqli_stmt_close($sStmt);

                mysqli_commit($conn);
                header('Location: index.php?registered=1');
                exit;
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = 'Registration failed. Please try again.';
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
<title>Create Account | AUCA Student Portal</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <div class="auth-logo">
            <div class="auth-logo-circle">AUCA</div>
            <h1>Create Student Account</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required value="<?= e($old['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required value="<?= e($old['last_name']) ?>">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="reg_number">Registration Number</label>
                    <input type="text" id="reg_number" name="reg_number" required value="<?= e($old['reg_number']) ?>" placeholder="e.g. 2026/SENG/0142">
                </div>
                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id">
                        <option value="">-- Select Department --</option>
                        <?php while ($d = mysqli_fetch_assoc($deptResult)): ?>
                            <option value="<?= (int)$d['department_id'] ?>" <?= ((string)$d['department_id'] === $old['department_id']) ? 'selected' : '' ?>>
                                <?= e($d['department_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?= e($old['username']) ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= e($old['email']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="year_of_study">Year of Study</label>
                <select id="year_of_study" name="year_of_study">
                    <?php for ($y = 1; $y <= 5; $y++): ?>
                        <option value="<?= $y ?>" <?= ($old['year_of_study'] == $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <p class="auth-footer">Already have an account? <a href="index.php">Log in</a></p>
    </div>
</div>

</body>
</html>