<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$student = null;
$fullName = $_SESSION['full_name'] ?? 'Student';

if ($studentId) {
    $stmt = mysqli_prepare($conn, 'SELECT s.first_name, s.last_name, s.reg_number, s.year_of_study, s.phone, s.address, s.date_of_birth, s.gender, s.enrollment_date, s.profile_picture, d.department_name, u.email
        FROM students s
        LEFT JOIN departments d ON d.department_id = s.department_id
        JOIN users u ON u.user_id = s.user_id
        WHERE s.student_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $studentId);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($student) {
        $fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
    }
}

// Handle profile picture upload
$uploadMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo' && isset($_FILES['profile_photo'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $uploadMsg = '<div class="alert alert-error"><p>Invalid form submission.</p></div>';
    } else {
        $file = $_FILES['profile_photo'];
    if ($file['error'] === UPLOAD_ERR_OK && $studentId) {
        $uploadDir = __DIR__ . '/../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $fileName = 'student_' . $studentId . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $newPath = 'uploads/profiles/' . $fileName;
                $stmt = mysqli_prepare($conn, 'UPDATE students SET profile_picture = ? WHERE student_id = ?');
                mysqli_stmt_bind_param($stmt, 'si', $newPath, $studentId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $uploadMsg = '<div class="alert alert-success"><p>Photo uploaded successfully!</p></div>';
                $student['profile_picture'] = $newPath;
            }
        }
    }
    }
}

$profilePicture = $student['profile_picture'] ?? 'images/default-avatar.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>My profile</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link active" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
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
                <p class="eyebrow">Student profile</p>
                <h2><?= e($fullName ?: 'Student Profile') ?></h2>

                <?= $uploadMsg ?>

                <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 24px;">
                    <div style="text-align: center;">
                        <?php if (file_exists(__DIR__ . '/../' . $profilePicture)): ?>
                            <img src="../<?= e($profilePicture) ?>" alt="Profile" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--color-primary);">
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 700; border: 3px solid var(--color-primary);">
                                <?= e(mb_strtoupper(mb_substr($student['first_name'] ?? 'S', 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data" style="margin-top: 12px;">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="upload_photo">
                            <input type="file" name="profile_photo" accept="image/*" required style="font-size: 13px;">
                            <button type="submit" class="btn btn-primary" style="margin-top: 6px; padding: 6px 12px; font-size: 12px;">Upload Photo</button>
                        </form>
                    </div>

                    <div style="flex: 1; min-width: 280px;">
                        <div style="display: grid; grid-template-columns: 160px 1fr; gap: 12px; font-size: 14px;">
                            <div><strong>First Name:</strong></div>
                            <div><?= e($student['first_name'] ?? 'N/A') ?></div>

                            <div><strong>Last Name:</strong></div>
                            <div><?= e($student['last_name'] ?? 'N/A') ?></div>

                            <div><strong>Registration Number:</strong></div>
                            <div><?= e($student['reg_number'] ?? 'N/A') ?></div>

                            <div><strong>Email:</strong></div>
                            <div><?= e($student['email'] ?? 'N/A') ?></div>

                            <div><strong>Department:</strong></div>
                            <div><?= e($student['department_name'] ?? 'Pending') ?></div>

                            <div><strong>Year of Study:</strong></div>
                            <div>Year <?= e($student['year_of_study'] ?? '1') ?></div>

                            <div><strong>Phone:</strong></div>
                            <div><?= e($student['phone'] ?? 'N/A') ?></div>

                            <div><strong>Gender:</strong></div>
                            <div><?= e($student['gender'] ?? 'N/A') ?></div>

                            <div><strong>Date of Birth:</strong></div>
                            <div><?= e($student['date_of_birth'] ?? 'N/A') ?></div>

                            <div><strong>Enrollment Date:</strong></div>
                            <div><?= e($student['enrollment_date'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>

                <h3>Quick Links</h3>
                <ul class="dashboard-list">
                    <li><a href="courses.php">View your courses</a></li>
                    <li><a href="results.php">Check your results</a></li>
                    <li><a href="enroll_course.php">Register for courses</a></li>
                    <li><a href="exam_permit.php">View exam permit</a></li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>
