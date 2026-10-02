<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

requireLogin();

$studentId = (int)($_SESSION['student_id'] ?? 0);

if ($studentId <= 0) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Student profile not found. Please contact the administrator.']);
    exit;
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid form submission. Please refresh the page and try again.']);
    exit;
}

$action = $_POST['action'] ?? '';
$courseId = (int)($_POST['course_id'] ?? 0);
$academicYear = trim($_POST['academic_year'] ?? date('Y'));
$semester = trim($_POST['semester'] ?? 'Semester 1');

if ($courseId <= 0) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid course.']);
    exit;
}

$cCheck = mysqli_prepare($conn, 'SELECT course_id, course_code FROM courses WHERE course_id = ?');
mysqli_stmt_bind_param($cCheck, 'i', $courseId);
mysqli_stmt_execute($cCheck);
$cResult = mysqli_stmt_get_result($cCheck);
$course = mysqli_fetch_assoc($cResult);
mysqli_stmt_close($cCheck);

if (!$course) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Course not found.']);
    exit;
}

ob_end_clean();
header('Content-Type: application/json');

if ($action === 'enroll') {
    $check = mysqli_prepare($conn, 'SELECT registration_id FROM registrations WHERE student_id = ? AND course_id = ? AND academic_year = ? AND semester = ?');
    mysqli_stmt_bind_param($check, 'iiss', $studentId, $courseId, $academicYear, $semester);
    mysqli_stmt_execute($check);
    $exists = mysqli_stmt_get_result($check)->num_rows > 0;
    mysqli_stmt_close($check);

    if ($exists) {
        echo json_encode(['status' => 'exists', 'message' => 'Already enrolled in this course.']);
        exit;
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO registrations (student_id, course_id, academic_year, semester, status) VALUES (?, ?, ?, ?, "registered")');
    mysqli_stmt_bind_param($stmt, 'iisss', $studentId, $courseId, $academicYear, $semester);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'ok', 'message' => 'Enrolled successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Enrollment failed.']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

if ($action === 'withdraw') {
    $stmt = mysqli_prepare($conn, 'UPDATE registrations SET status = "dropped" WHERE student_id = ? AND course_id = ? AND status = "registered"');
    mysqli_stmt_bind_param($stmt, 'ii', $studentId, $courseId);
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode(['status' => 'ok', 'message' => 'Course withdrawn.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'You are not enrolled in this course.']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
