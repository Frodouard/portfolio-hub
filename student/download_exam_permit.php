<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$studentName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');
$regNumber = 'N/A';
$department = 'Department pending';
$semester = 'Semester ' . (date('n') <= 6 ? 'II' : 'I');
$profilePicture = 'images/default-avatar.svg';

if ($studentId) {
    $stmt = mysqli_prepare($conn, 'SELECT s.reg_number, s.profile_picture, d.department_name
        FROM students s
        LEFT JOIN departments d ON d.department_id = s.department_id
        WHERE s.student_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $studentId);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($student) {
        $regNumber = $student['reg_number'] ?? 'N/A';
        $department = $student['department_name'] ?? 'Department pending';
        if (!empty($student['profile_picture'])) {
            $profilePicture = $student['profile_picture'];
        }
    }
}

$filename = 'exam-permit-' . preg_replace('/[^a-z0-9]/i', '-', strtolower($studentName)) . '.doc';

require_once __DIR__ . '/timetable_export.php';
$content = buildExamPermitRtf($studentName, $regNumber, $department, $semester, $profilePicture);

header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
echo $content;
