<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();

$studentId = (int)($_SESSION['student_id'] ?? 0);

// Fetch all courses from DB
$allCourses = [];
$r = @mysqli_query($conn, 'SELECT course_id, course_code, course_name, credits, department_id, semester FROM courses ORDER BY course_code');
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $allCourses[] = $row;
    }
}

// Fetch currently registered courses
$registeredIds = [];
$registeredCourses = [];
if ($studentId) {
    $r = @mysqli_query($conn, "SELECT r.course_id, r.registration_id, c.course_code, c.course_name, c.credits, c.semester
        FROM registrations r
        JOIN courses c ON c.course_id = r.course_id
        WHERE r.student_id = $studentId AND r.status = 'registered'
        ORDER BY c.course_code");
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $registeredIds[] = (int)$row['course_id'];
            $registeredCourses[] = $row;
        }
    }
}

$totalCredits = array_sum(array_column($registeredCourses, 'credits'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Course | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Enroll course</p>
                </div>
            </div>
            <nav class="dashboard-nav">
                <a class="dashboard-nav-link" href="../dashboard.php">Overview</a>
                <a class="dashboard-nav-link active" href="enroll_course.php">Registration</a>
                <a class="dashboard-nav-link" href="profile.php">My Profile</a>
                <a class="dashboard-nav-link" href="courses.php">My Courses</a>
                <a class="dashboard-nav-link" href="results.php">Results</a>
                <a class="dashboard-nav-link" href="announcements.php">Announcements</a>
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Course registration</p>
                <h2>Enroll a Course</h2>
                <p class="muted">Select the courses you want to register for this semester.</p>

                <div id="message" class="alert" style="display: none;"></div>

                <div style="background: #f8fbff; border: 1px solid var(--color-border); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <h3 style="margin-top: 0;">Your Enrolled Courses</h3>
                    <div id="enrolledSummary" style="font-size: 14px; color: var(--color-text-muted); margin-bottom: 8px;">
                        <?= count($registeredCourses) ?> courses &middot; <?= $totalCredits ?> credits
                    </div>
                    <div id="enrolledList">
                        <?php if (!empty($registeredCourses)): ?>
                            <?php foreach ($registeredCourses as $rc): ?>
                                <div class="enrolled-item" data-course-id="<?= $rc['course_id'] ?>" style="padding: 10px 12px; background: #fff; margin-bottom: 8px; border-left: 4px solid var(--color-primary); display: flex; justify-content: space-between; align-items: center; border-radius: 4px;">
                                    <div>
                                        <strong><?= e($rc['course_code']) ?></strong> — <?= e($rc['course_name']) ?> (<?= e((string)$rc['credits']) ?> credits)
                                    </div>
                                    <button class="btn btn-primary withdraw-btn" data-course-id="<?= $rc['course_id'] ?>" style="padding: 5px 10px; font-size: 12px; background: var(--color-danger);">Withdraw</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p id="noCourses" class="muted">No courses enrolled yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <h3>Available Courses (<?= count($allCourses) ?>)</h3>
                <div style="overflow-x: auto;">
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCourses as $course): ?>
                                <tr id="course-row-<?= $course['course_id'] ?>">
                                    <td><?= e($course['course_code']) ?></td>
                                    <td><?= e($course['course_name']) ?></td>
                                    <td><?= e((string)$course['credits']) ?></td>
                                    <td><?= e($course['semester'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (in_array((int)$course['course_id'], $registeredIds)): ?>
                                            <button class="btn btn-primary" disabled style="padding: 5px 12px; font-size: 12px; background: #6c757d; cursor: not-allowed;">Enrolled ✓</button>
                                        <?php else: ?>
                                            <button class="btn btn-primary enroll-btn" data-course-id="<?= $course['course_id'] ?>" data-code="<?= e($course['course_code']) ?>" data-name="<?= e($course['course_name']) ?>" data-credits="<?= $course['credits'] ?>" style="padding: 5px 12px; font-size: 12px;">Enroll</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    const csrfToken = '<?= e(csrfToken()) ?>';
    const academicYear = '<?= date('Y') ?>';
    const semester = 'Semester 1';

    function showMessage(text, type) {
        const msgEl = document.getElementById('message');
        msgEl.textContent = text;
        msgEl.className = 'alert alert-' + type;
        msgEl.style.display = 'block';
        setTimeout(() => { msgEl.style.display = 'none'; }, 5000);
    }

    document.querySelectorAll('.enroll-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (this.disabled) return;
            this.disabled = true;
            this.textContent = 'Enrolling...';

            const courseId = this.getAttribute('data-course-id');
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            const credits = parseInt(this.getAttribute('data-credits'));

            try {
                const response = await fetch('register_course.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${encodeURIComponent(csrfToken)}&action=enroll&course_id=${courseId}&academic_year=${encodeURIComponent(academicYear)}&semester=${encodeURIComponent(semester)}`
                });
                const data = await response.json();

                if (data.status === 'ok') {
                    // Add to enrolled list
                    const enrolledList = document.getElementById('enrolledList');
                    const noCourses = document.getElementById('noCourses');
                    if (noCourses) noCourses.remove();

                    const item = document.createElement('div');
                    item.className = 'enrolled-item';
                    item.setAttribute('data-course-id', courseId);
                    item.style.cssText = 'padding: 10px 12px; background: #fff; margin-bottom: 8px; border-left: 4px solid var(--color-primary); display: flex; justify-content: space-between; align-items: center; border-radius: 4px;';
                    item.innerHTML = `<div><strong>${code}</strong> — ${name} (${credits} credits)</div><button class="btn btn-primary withdraw-btn" data-course-id="${courseId}" style="padding: 5px 10px; font-size: 12px; background: var(--color-danger);">Withdraw</button>`;
                    enrolledList.appendChild(item);

                    // Attach withdraw handler
                    item.querySelector('.withdraw-btn').addEventListener('click', handleWithdraw);

                    // Update button state
                    this.textContent = 'Enrolled ✓';
                    this.style.background = '#6c757d';
                    this.style.cursor = 'not-allowed';

                    showMessage(`✓ Successfully enrolled in ${code}`, 'success');
                } else if (data.status === 'exists') {
                    showMessage('You are already enrolled in this course.', 'error');
                    this.textContent = 'Enrolled ✓';
                    this.style.background = '#6c757d';
                } else {
                    showMessage(data.message || 'Enrollment failed', 'error');
                    this.disabled = false;
                    this.textContent = 'Enroll';
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
                this.disabled = false;
                this.textContent = 'Enroll';
            }
        });
    });

    async function handleWithdraw(e) {
        const btn = e.target;
        const courseId = btn.getAttribute('data-course-id');
        if (!confirm('Are you sure you want to withdraw from this course?')) return;

        btn.disabled = true;
        btn.textContent = 'Withdrawing...';

        try {
            const response = await fetch('register_course.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${encodeURIComponent(csrfToken)}&action=withdraw&course_id=${courseId}`
            });
            const data = await response.json();

            if (data.status === 'ok') {
                // Remove from enrolled list
                const item = document.querySelector(`.enrolled-item[data-course-id="${courseId}"]`);
                if (item) item.remove();

                // Re-enable enroll button in table
                const enrollBtn = document.querySelector(`.enroll-btn[data-course-id="${courseId}"]`);
                if (enrollBtn) {
                    enrollBtn.disabled = false;
                    enrollBtn.textContent = 'Enroll';
                    enrollBtn.style.background = '';
                    enrollBtn.style.cursor = '';
                }

                showMessage('Course withdrawn.', 'success');

                // Show "no courses" message if list is empty
                if (document.querySelectorAll('.enrolled-item').length === 0) {
                    document.getElementById('enrolledList').innerHTML = '<p id="noCourses" class="muted">No courses enrolled yet.</p>';
                }
            } else {
                showMessage(data.message || 'Withdrawal failed', 'error');
                btn.disabled = false;
                btn.textContent = 'Withdraw';
            }
        } catch (error) {
            showMessage('Network error: ' + error.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Withdraw';
        }
    }

    document.querySelectorAll('.withdraw-btn').forEach(btn => {
        btn.addEventListener('click', handleWithdraw);
    });
    </script>
</body>
</html>
