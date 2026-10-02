<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);
$studentName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Student');
$regNumber = 'N/A';
$department = 'Department pending';
$semester = 'Semester ' . (date('n') <= 6 ? 'II' : 'I');

if ($studentId) {
    $stmt = mysqli_prepare($conn, 'SELECT s.reg_number, d.department_name
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
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Permit | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Exam permit</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="attendance.php">Attendance</a>
                <a class="dashboard-nav-link" href="assignments.php">Assignments</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link active" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Examination access</p>
                <h2>Exam Permit</h2>
                <div class="dashboard-card" style="margin-top: 16px;">
                    <p><strong>Student:</strong> <?= e($studentName) ?></p>
                    <p><strong>Registration number:</strong> <?= e($regNumber) ?></p>
                    <p><strong>Department:</strong> <?= e($department) ?></p>
                    <p><strong>Semester:</strong> <?= e($semester) ?></p>
                    <p style="margin-top: 12px;"><strong>Status:</strong> Approved</p>
                    <div class="verify-actions" style="margin-top: 14px;">
                        <a href="exam_permit_card.php" class="btn btn-primary" id="viewPermitBtn">View Exam Permit Card</a>
                        <a href="download_exam_permit.php" class="btn btn-primary" id="downloadPermitBtn" style="display:none;">Download exam permit</a>
                        <span class="verify-status pending" id="downloadHint">Upload your photo to generate permit card</span>
                    </div>
                </div>

                <div class="verify-panel">
                    <div class="verify-box">
                        <h3>Face verification</h3>
                        <p class="muted">Upload your ID photo and capture a live selfie to verify whether the faces match.</p>

                        <div class="verify-grid">
                            <div>
                                <label for="photoInput"><strong>Upload your photo</strong></label>
                                <input type="file" id="photoInput" accept="image/*" style="margin-top: 8px;">
                                <div class="verify-preview" id="referencePreview">Reference photo preview</div>
                            </div>
                            <div>
                                <label><strong>Live camera capture</strong></label>
                                <div class="verify-preview" id="cameraPreview">
                                    <video id="cameraVideo" autoplay playsinline muted></video>
                                </div>
                                <div class="verify-actions">
                                    <button type="button" class="btn btn-primary" id="startCameraBtn">Open camera</button>
                                    <button type="button" class="btn btn-primary" id="captureBtn">Capture face</button>
                                </div>
                            </div>
                        </div>

                        <div class="verify-actions" style="margin-top: 16px;">
                            <button type="button" class="btn btn-primary" id="verifyBtn">Verify face</button>
                            <span class="verify-status pending" id="resultStatus">Waiting for both photos</span>
                        </div>

                        <canvas id="verificationCanvas" style="display:none"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
<script>
const photoInput = document.getElementById('photoInput');
const referencePreview = document.getElementById('referencePreview');
const cameraPreview = document.getElementById('cameraPreview');
const cameraVideo = document.getElementById('cameraVideo');
const startCameraBtn = document.getElementById('startCameraBtn');
const captureBtn = document.getElementById('captureBtn');
const verifyBtn = document.getElementById('verifyBtn');
const resultStatus = document.getElementById('resultStatus');
const verificationCanvas = document.getElementById('verificationCanvas');
let capturedImage = null;
let referenceImage = null;
let stream = null;

function showPreview(element, dataUrl) {
    element.innerHTML = '';
    const img = document.createElement('img');
    img.src = dataUrl;
    element.appendChild(img);
}

photoInput.addEventListener('change', function (event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        referenceImage = e.target.result;
        showPreview(referencePreview, referenceImage);
        resultStatus.className = 'verify-status pending';
        resultStatus.textContent = 'Waiting for both photos';
    };
    reader.readAsDataURL(file);
});

startCameraBtn.addEventListener('click', async function () {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        cameraVideo.srcObject = stream;
        cameraPreview.innerHTML = '';
        cameraPreview.appendChild(cameraVideo);
    } catch (error) {
        resultStatus.className = 'verify-status error';
        resultStatus.textContent = 'Camera access was denied.';
    }
});

captureBtn.addEventListener('click', function () {
    if (!stream) {
        resultStatus.className = 'verify-status error';
        resultStatus.textContent = 'Open the camera first.';
        return;
    }
    const context = verificationCanvas.getContext('2d');
    verificationCanvas.width = cameraVideo.videoWidth || 320;
    verificationCanvas.height = cameraVideo.videoHeight || 240;
    context.drawImage(cameraVideo, 0, 0, verificationCanvas.width, verificationCanvas.height);
    capturedImage = verificationCanvas.toDataURL('image/png');
    showPreview(cameraPreview, capturedImage);
    resultStatus.className = 'verify-status pending';
    resultStatus.textContent = 'Waiting for both photos';
});

verifyBtn.addEventListener('click', function () {
    if (!referenceImage || !capturedImage) {
        resultStatus.className = 'verify-status error';
        resultStatus.textContent = 'Upload a photo and capture a live face first.';
        return;
    }

    const compareImages = (imgA, imgB) => {
        const canvas = verificationCanvas;
        const context = canvas.getContext('2d');
        const size = 48;
        canvas.width = size;
        canvas.height = size;

        const draw = (src) => {
            const image = new Image();
            image.src = src;
            return new Promise((resolve) => {
                image.onload = () => {
                    context.clearRect(0, 0, size, size);
                    context.drawImage(image, 0, 0, size, size);
                    const imageData = context.getImageData(0, 0, size, size).data;
                    const values = [];
                    for (let i = 0; i < imageData.length; i += 4) {
                        values.push((imageData[i] + imageData[i+1] + imageData[i+2]) / 3);
                    }
                    resolve(values);
                };
            });
        };

        return Promise.all([draw(imgA), draw(imgB)]).then(([a, b]) => {
            let diff = 0;
            for (let i = 0; i < a.length; i++) diff += Math.abs(a[i] - b[i]);
            return 1 - (diff / (a.length * 255));
        });
    };

    compareImages(referenceImage, capturedImage).then((score) => {
        if (score > 0.78) {
            resultStatus.className = 'verify-status success';
            resultStatus.textContent = 'Face verified successfully.';
            document.getElementById('downloadPermitBtn').style.display = 'inline-block';
            document.getElementById('viewPermitBtn').style.display = 'inline-block';
            document.getElementById('downloadHint').textContent = 'Verification passed. You can now download your exam permit.';
            document.getElementById('downloadHint').className = 'verify-status success';
        } else {
            resultStatus.className = 'verify-status error';
            resultStatus.textContent = 'The face does not match the uploaded photo.';
            document.getElementById('downloadPermitBtn').style.display = 'none';
            document.getElementById('downloadHint').textContent = 'Complete face verification to unlock download';
            document.getElementById('downloadHint').className = 'verify-status pending';
        }
    });
});
</script>
</body>
</html>
