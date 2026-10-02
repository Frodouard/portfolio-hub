<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/timetable_export.php';

requireStudent();

$studentName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');
$regNumber = $_SESSION['student_id'] ?? 'N/A';
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

$filename = 'timetable-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $studentName) . '.doc';

// Get sample timetable
$courses = getSampleTimetable();

// Build RTF with profile picture
$pictureRtf = '';
if (!empty($profilePicture) && file_exists(__DIR__ . '/../' . $profilePicture)) {
    $imageData = file_get_contents(__DIR__ . '/../' . $profilePicture);
    $hexImage = bin2hex($imageData);
    $pictureRtf = "{\\pict\\jpegblip\\picw1500\\pich1500 " . strtoupper($hexImage) . "}\\par\\par";
}

$rtf = "{\\rtf1\\ansi\\deff0"
    . "{\\colortbl;\\red0\\green0\\blue0;}"
    . "{\\fonttbl{\\f0\\fnil\\fcharset0 Arial;}}"
    . "\\viewkind4\\uc1\\f0\\fs28\\b AUCA Student Timetable\\b0\\par"
    . "\\par";

if (!empty($profilePicture) && file_exists(__DIR__ . '/../' . $profilePicture)) {
    try {
        $imageData = file_get_contents(__DIR__ . '/../' . $profilePicture);
        if ($imageData) {
            $hexImage = bin2hex($imageData);
            $rtf .= "{\\pict\\jpegblip\\picw1400\\pich1400 " . strtoupper($hexImage) . "}\\par\\par";
        }
    } catch (Exception $e) {
        // Continue without image
    }
}

$rtf .= "\\fs20 Student Name: " . str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $studentName) . "\\par"
    . "Registration Number: " . str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], (string)$regNumber) . "\\par"
    . "\\par"
    . "\\b Schedule\\b0\\par"
    . "\\trowd\\cellx2000\\cellx5000\\cellx8000\\cellx11000"
    . "\\intbl\\b Day\\cell\\b0\\intbl\\b Course\\cell\\b0\\intbl\\b Time\\cell\\b0\\intbl\\b Room\\cell\\b0\\row";

foreach ($courses as $course) {
    $day = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['day']);
    $course_name = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['course']);
    $time = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['time']);
    $room = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['room']);
    
    $rtf .= "\\trowd\\cellx2000\\cellx5000\\cellx8000\\cellx11000"
        . "\\intbl {$day}\\cell\\intbl {$course_name}\\cell\\intbl {$time}\\cell\\intbl {$room}\\cell\\row";
}

$rtf .= "\\pard\\par}";

header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo $rtf;
exit;

