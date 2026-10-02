<?php
function getSampleTimetable(): array {
    return [
        ['day' => 'Monday', 'time' => '08:00 - 10:00', 'course' => 'Database Systems', 'room' => 'Lab 2'],
        ['day' => 'Tuesday', 'time' => '10:00 - 12:00', 'course' => 'Web Programming', 'room' => 'R-12'],
        ['day' => 'Wednesday', 'time' => '13:00 - 15:00', 'course' => 'Software Engineering', 'room' => 'R-08'],
        ['day' => 'Thursday', 'time' => '09:00 - 11:00', 'course' => 'Discrete Mathematics', 'room' => 'R-05'],
        ['day' => 'Friday', 'time' => '14:00 - 16:00', 'course' => 'Communication Skills', 'room' => 'R-10'],
    ];
}

function buildTimetableRtf(string $studentName, string $department, array $courses, string $profilePicture = ''): string {
    $rows = [];
    $rows[] = "\\trowd\\cellx2000\\cellx5000\\cellx8000\\cellx11000";
    $rows[] = "\\intbl\\b Day\\cell\\b0\\intbl\\b Time\\cell\\b0\\intbl\\b Course\\cell\\b0\\intbl\\b Room\\cell\\b0\\row";

    foreach ($courses as $course) {
        $day = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['day']);
        $time = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['time']);
        $courseName = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['course']);
        $room = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $course['room']);

        $rows[] = "\\trowd\\cellx2000\\cellx5000\\cellx8000\\cellx11000";
        $rows[] = "\\intbl{$day}\\cell\\intbl{$time}\\cell\\intbl{$courseName}\\cell\\intbl{$room}\\cell\\row";
    }

    $studentName = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $studentName);
    $department = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $department);

    $pictureRtf = '';
    if (!empty($profilePicture) && file_exists(__DIR__ . '/../' . $profilePicture)) {
        try {
            $imageData = file_get_contents(__DIR__ . '/../' . $profilePicture);
            if ($imageData) {
                $hexImage = bin2hex($imageData);
                $pictureRtf = "{\\pict\\jpegblip\\picw1400\\pich1400 " . strtoupper($hexImage) . "}\\par\\par";
            }
        } catch (Exception $e) {
            $pictureRtf = '';
        }
    }

    return "{\\rtf1\\ansi\\deff0"
        . "{\\colortbl;\\red0\\green0\\blue0;}"
        . "{\\fonttbl{\\f0\\fnil\\fcharset0 Arial;}}"
        . "\\viewkind4\\uc1\\f0\\fs28\\b AUCA Student Timetable\\b0\\par"
        . "\\par"
        . $pictureRtf
        . "\\fs20 Student Name: {$studentName}\\par"
        . "Department: {$department}\\par"
        . "\\par"
        . "\\b Schedule\\b0\\par"
        . implode("", $rows)
        . "\\pard\\par"
        . "}";
}

function buildExamPermitRtf(string $studentName, string $regNumber, string $department, string $semester, string $profilePicture = ''): string {
    $studentName = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $studentName);
    $regNumber = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $regNumber);
    $department = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $department);
    $semester = str_replace(["\\", "{" , "}"], ["\\\\", "\\{", "\\}"], $semester);

    $pictureRtf = '';
    if (!empty($profilePicture) && file_exists(__DIR__ . '/../' . $profilePicture)) {
        try {
            $imageData = file_get_contents(__DIR__ . '/../' . $profilePicture);
            if ($imageData) {
                $hexImage = bin2hex($imageData);
                $pictureRtf = "{\\pict\\jpegblip\\picw1400\\pich1400 " . strtoupper($hexImage) . "}\\par\\par";
            }
        } catch (Exception $e) {
            // If image fails, continue without it
            $pictureRtf = '';
        }
    }

    return "{\\rtf1\\ansi\\deff0"
        . "{\\colortbl;\\red0\\green0\\blue0;}"
        . "{\\fonttbl{\\f0\\fnil\\fcharset0 Arial;}}"
        . "\\viewkind4\\uc1\\pard\\f0\\fs28\\b AUCA EXAM PERMIT\\b0\\par"
        . "\\par"
        . $pictureRtf
        . "\\fs20\\par"
        . "\\b Student Information\\b0\\par"
        . "\\fs18 Name: {$studentName}\\par"
        . "Registration Number: {$regNumber}\\par"
        . "Department: {$department}\\par"
        . "Semester: {$semester}\\par"
        . "\\par"
        . "\\b Status\\b0\\par"
        . "\\fs18 Exam Status: APPROVED\\par"
        . "\\par"
        . "}";
}
?>
