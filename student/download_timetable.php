<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/timetable_export.php';

requireStudent();

$studentName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');
$department = 'Computer Science';
$studentId = $_SESSION['student_id'] ?? null;
$profilePicture = 'images/default-avatar.svg';

// Fetch student profile picture
if ($studentId) {
    $stmt = mysqli_prepare($conn, 'SELECT profile_picture FROM students WHERE student_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $studentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($result && !empty($result['profile_picture'])) {
        $profilePicture = $result['profile_picture'];
    }
    mysqli_stmt_close($stmt);
}

$courses = getSampleTimetable();

$rtf = buildTimetableRtf($studentName, $department, $courses, $profilePicture);

header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="student_timetable.doc"');
header('Pragma: no-cache');
header('Expires: 0');
echo $rtf;
