<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$studentName = $_SESSION['full_name'] ?? 'Student';
$profilePicture = 'images/default-avatar.svg';

// Fetch student data
if ($studentId) {
    $stmt = mysqli_prepare($conn, 'SELECT s.reg_number, s.profile_picture, d.department_name FROM students s LEFT JOIN departments d ON s.department_id = d.department_id WHERE s.student_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $studentId);
    mysqli_stmt_execute($stmt);
    $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($student) {
        if (!empty($student['profile_picture'])) {
            $profilePicture = $student['profile_picture'];
        }
        $regNumber = $student['reg_number'] ?? 'N/A';
        $department = $student['department_name'] ?? 'Information Technology';
    }
    mysqli_stmt_close($stmt);
}

// Fetch registered courses
$registeredCourses = [];
if ($studentId) {
    $coursesResult = mysqli_query($conn, "
        SELECT c.course_code, c.course_name, c.credits
        FROM registrations r
        JOIN courses c ON c.course_id = r.course_id
        WHERE r.student_id = $studentId AND r.status = 'registered'
        LIMIT 6
    ");
    
    while ($row = mysqli_fetch_assoc($coursesResult)) {
        $registeredCourses[] = $row;
    }
}

$totalCredits = array_sum(array_column($registeredCourses, 'credits'));
$totalCourses = count($registeredCourses);

// Handle photo upload
$uploadMessage = '';
if ($_POST['action'] ?? '' === 'upload_photo' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $uploadDir = __DIR__ . '/../uploads/profiles/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = 'student_' . $studentId . '_' . time() . '.jpg';
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $newPath = 'uploads/profiles/' . $fileName;
        mysqli_query($conn, "UPDATE students SET profile_picture = '$newPath' WHERE student_id = $studentId");
        $profilePicture = $newPath;
        $uploadMessage = '<div style="color: green; margin-bottom: 10px;">Photo uploaded successfully!</div>';
    } else {
        $uploadMessage = '<div style="color: red; margin-bottom: 10px;">Failed to upload photo.</div>';
    }
}

// Generate QR code data (student info)
$qrData = $regNumber . '|' . $studentName . '|' . $department;
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrData);

// Handle download
if ($_GET['download'] ?? '' === '1') {
    require_once __DIR__ . '/timetable_export.php';
    
    // Get image data
    $imageHex = '';
    if (file_exists(__DIR__ . '/../' . $profilePicture)) {
        $imageData = file_get_contents(__DIR__ . '/../' . $profilePicture);
        if ($imageData) {
            $imageHex = bin2hex($imageData);
        }
    }
    
    $pictureRtf = '';
    if (!empty($imageHex)) {
        $pictureRtf = "{\\pict\\jpegblip\\picw1400\\pich1400 " . strtoupper($imageHex) . "}\\par";
    }
    
    // Get QR code image data
    $qrImageHex = '';
    try {
        $qrImageData = @file_get_contents($qrCodeUrl);
        if ($qrImageData && strlen($qrImageData) > 100) {
            $qrImageHex = bin2hex($qrImageData);
        }
    } catch (Exception $e) {
        // QR code generation failed, continue without it
    }
    
    // Build RTF document
    $rtf = "{\\rtf1\\ansi\\ansicpg1252\\deff0\\deflang1033"
        . "{\\colortbl;\\red0\\green0\\blue0;}"
        . "{\\fonttbl{\\f0\\fnil\\fcharset0 Arial;}}"
        . "\\viewkind4\\uc1\\pard\\f0\\fs24"
        . "\\b ADVENTIST UNIVERSITY OF CENTRAL AFRICA\\b0\\par"
        . "P.O. Box 2461 Kigali, Rwanda | www.auca.ac.rw | info@auca.ac.rw\\par"
        . "\\par"
        . "\\trowd\\trrh400\\cellx10000\\intbl\\qc\\b EXAMINATION PERMIT CARD\\b0\\cell\\row"
        . "\\trowd\\trrh200\\cellx10000\\intbl\\qc Semester 2025/3 | Generated " . date('d M Y') . "\\cell\\row"
        . "\\par\\par"
        . "\\trowd\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
        . "\\cellx2000\\cellx4000\\cellx6000\\cellx8000\\cellx10000"
        . "\\intbl\\b Reg No\\b0\\cell\\intbl " . str_replace(["\\", "{", "}"], ["\\\\", "\\{", "\\}"], $regNumber) . "\\cell"
        . "\\intbl\\cell\\intbl\\cell\\intbl\\cell\\row"
        . "\\trowd\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
        . "\\cellx2000\\cellx4000\\cellx6000\\cellx8000\\cellx10000"
        . "\\intbl\\b Name\\b0\\cell\\intbl " . str_replace(["\\", "{", "}"], ["\\\\", "\\{", "\\}"], $studentName) . "\\cell"
        . "\\intbl\\cell\\intbl\\cell\\intbl\\cell\\row"
        . "\\trowd\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
        . "\\cellx2000\\cellx4000\\cellx6000\\cellx8000\\cellx10000"
        . "\\intbl\\b Faculty\\b0\\cell\\intbl Information Technology\\cell"
        . "\\intbl\\cell\\intbl\\cell\\intbl\\cell\\row"
        . "\\trowd\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
        . "\\cellx2000\\cellx4000\\cellx6000\\cellx8000\\cellx10000"
        . "\\intbl\\b Department\\b0\\cell\\intbl " . str_replace(["\\", "{", "}"], ["\\\\", "\\{", "\\}"], $department) . "\\cell"
        . "\\intbl\\cell\\intbl\\cell\\intbl\\cell\\row"
        . "\\trowd\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
        . "\\cellx2000\\cellx4000\\cellx6000\\cellx8000\\cellx10000"
        . "\\intbl\\b Programme\\b0\\cell\\intbl Day\\cell"
        . "\\intbl\\b Photo\\b0\\cell\\intbl " . $pictureRtf . "\\cell\\intbl\\cell\\row"
        . "\\par"
        . "\\trowd\\trrh150\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
        . "\\cellx1500\\cellx4500\\cellx8000\\cellx10000"
        . "\\intbl\\b\\qc CODE\\b0\\cell\\intbl\\b COURSE TITLE\\b0\\cell\\intbl\\b CR\\b0\\cell\\row";
    
    // Add courses
    foreach ($registeredCourses as $course) {
        $code = str_replace(["\\", "{", "}"], ["\\\\", "\\{", "\\}"], $course['course_code']);
        $title = str_replace(["\\", "{", "}"], ["\\\\", "\\{", "\\}"], $course['course_name']);
        $credits = $course['credits'];
        
        $rtf .= "\\trowd\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
            . "\\cellx1500\\cellx4500\\cellx8000\\cellx10000"
            . "\\intbl " . $code . "\\cell\\intbl " . $title . "\\cell\\intbl\\qc " . $credits . "\\cell\\row";
    }
    
    $rtf .= "\\trowd\\trbrdrb\\brdrs\\brdrw10\\trbrdrh\\brdrs\\brdrw10\\trbrdrv\\brdrs\\brdrw10"
        . "\\cellx1500\\cellx4500\\cellx8000\\cellx10000"
        . "\\intbl\\b Courses\\b0\\cell\\intbl\\qc " . $totalCourses . "\\cell\\intbl\\b Credits\\b0\\cell\\intbl\\qc " . $totalCredits . "\\cell\\row"
        . "\\par\\par";
    
    // Add QR code to RTF if available
    if (!empty($qrImageHex)) {
        $rtf .= "\\qc {\\pict\\jpegblip\\picw1400\\pich1400 " . strtoupper($qrImageHex) . "}\\par";
        $rtf .= "\\qc\\fs18 Scan to Verify\\par";
    }
    
    $rtf .= "\\par\\b Note:\\b0 Valid only for this exam session. Alteration invalidates this permit.\\par"
        . "Phones, smart watches, bags, and unauthorized notes are prohibited.\\par"
        . "}";
    
    $filename = 'exam_permit_' . preg_replace('/[^a-z0-9]/i', '_', strtolower($studentName)) . '.doc';
    
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $rtf;
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Permit Card | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .permit-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .permit-card {
            background: white;
            border: 2px solid #333;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            page-break-inside: avoid;
        }
        
        .permit-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0f4c81;
            padding-bottom: 15px;
        }
        
        .permit-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 10px;
        }
        
        .permit-header h1 {
            margin: 5px 0;
            font-size: 24px;
            color: #0f4c81;
        }
        
        .permit-header p {
            margin: 2px 0;
            font-size: 12px;
            color: #666;
        }
        
        .permit-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .permit-subtitle {
            text-align: center;
            font-size: 12px;
            margin-bottom: 20px;
            color: #666;
        }
        
        .permit-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .permit-info {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 10px;
            align-content: start;
        }
        
        .permit-info-label {
            font-weight: bold;
            background: #f0f0f0;
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .permit-info-value {
            background: #fff;
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .photo-box {
            border: 2px solid #333;
            width: 280px;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
        }
        
        .photo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .qr-section {
            border: 2px solid #333;
            padding: 10px;
            margin-top: 15px;
            text-align: center;
            background: white;
        }
        
        .qr-section img {
            width: 150px;
            height: 150px;
        }
        
        .qr-label {
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
            color: #333;
        }
        
        .courses-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .courses-table th {
            background: #0f4c81;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #333;
        }
        
        .courses-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }
        
        .courses-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .course-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        
        .summary-box {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        
        .summary-box label {
            font-weight: bold;
            display: block;
            font-size: 12px;
        }
        
        .summary-box value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #0f4c81;
            margin-top: 5px;
        }
        
        .permit-footer {
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 20px;
            font-size: 11px;
            color: #666;
        }
        
        .footer-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-top: 20px;
            gap: 40px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        
        .controls {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }
        
        .upload-section {
            margin-bottom: 15px;
        }
        
        .upload-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .upload-section input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-primary {
            background: #0f4c81;
            color: white;
        }
        
        .btn-success {
            background: #16a34a;
            color: white;
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Exam Permit</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="exam_permit.php">Back</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>

        <main class="dashboard-main">
            <div class="permit-container">
                <div class="controls">
                    <h3>Upload Your Photo</h3>
                    <?= $uploadMessage ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-section">
                            <label for="photo">Select photo (JPG, PNG):</label>
                            <input type="file" id="photo" name="profile_photo" accept="image/jpeg,image/png" required>
                        </div>
                        <div class="btn-group">
                            <button type="submit" name="action" value="upload_photo" class="btn btn-primary">Upload Photo</button>
                            <a href="?download=1" class="btn btn-success" style="text-decoration: none; display: inline-block;">Download Exam Permit</a>
                        </div>
                    </form>
                </div>

                <div class="permit-card">
                    <div class="permit-header">
                        <img src="../images/auca-logo.png" alt="AUCA Logo" onerror="this.style.display='none'">
                        <h1>Adventist University of Central Africa</h1>
                        <p>P.O. Box 2461 Kigali, Rwanda | www.auca.ac.rw | info@auca.ac.rw</p>
                    </div>

                    <div class="permit-title">EXAMINATION PERMIT CARD</div>
                    <div class="permit-subtitle">Semester 2025/3 | Generated <?= date('d M Y') ?></div>

                    <div class="permit-content">
                        <div class="permit-info">
                            <div class="permit-info-label">Reg No</div>
                            <div class="permit-info-value"><?= e($regNumber) ?></div>
                            
                            <div class="permit-info-label">Name</div>
                            <div class="permit-info-value"><?= e($studentName) ?></div>
                            
                            <div class="permit-info-label">Faculty</div>
                            <div class="permit-info-value">Information Technology</div>
                            
                            <div class="permit-info-label">Department</div>
                            <div class="permit-info-value"><?= e($department) ?></div>
                            
                            <div class="permit-info-label">Programme</div>
                            <div class="permit-info-value">Day</div>
                        </div>

                        <div>
                            <div class="photo-box">
                                <?php if (file_exists(__DIR__ . '/../' . $profilePicture)) : ?>
                                    <img src="../<?= e($profilePicture) ?>" alt="Student Photo">
                                <?php else : ?>
                                    <div style="text-align: center; color: #999;">
                                        <p>No Photo</p>
                                        <p style="font-size: 12px;">Upload a photo to display</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="qr-section">
                                <img src="<?= e($qrCodeUrl) ?>" alt="QR Code" title="Scan to verify student information">
                                <div class="qr-label">Scan to Verify</div>
                            </div>
                        </div>
                    </div>

                    <table class="courses-table">
                        <thead>
                            <tr>
                                <th>CODE</th>
                                <th>COURSE TITLE</th>
                                <th>CR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registeredCourses as $course) : ?>
                                <tr>
                                    <td><?= e($course['course_code']) ?></td>
                                    <td><?= e($course['course_name']) ?></td>
                                    <td><?= e((string)$course['credits']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="course-summary">
                        <div class="summary-box">
                            <label>Courses</label>
                            <value><?= $totalCourses ?></value>
                        </div>
                        <div class="summary-box">
                            <label>Credits</label>
                            <value><?= $totalCredits ?></value>
                        </div>
                    </div>

                    <div class="permit-footer">
                        <p><strong>Valid only for this exam session. Alteration invalidates this permit. Phones, smart watches, bags, and unauthorized notes are prohibited.</strong></p>
                        
                        <div class="footer-row">
                            <div>
                                <div style="margin-bottom: 40px;">Student Signature</div>
                                <div class="signature-line"></div>
                            </div>
                            <div>
                                <div style="margin-bottom: 40px;">Authorized Stamp / Signature</div>
                                <div class="signature-line"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
