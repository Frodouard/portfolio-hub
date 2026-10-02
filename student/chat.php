<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

requireStudent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | AUCA Student Portal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                <div class="auth-logo-circle">AUCA</div>
                <div>
                    <h2>Student Portal</h2>
                    <p>Support chat</p>
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
                <a class="dashboard-nav-link" href="exam_permit.php">Exam Permit</a>
                <a class="dashboard-nav-link" href="exam_timetable.php">Exam Timetable</a>
            </nav>
            <a href="../logout.php" class="btn btn-primary btn-block">Log out</a>
        </aside>
        <main class="dashboard-main">
            <div class="student-page">
                <p class="eyebrow">Student support</p>
                <h2>Chat with the support team</h2>
                <div class="chat-shell">
                    <div class="chat-sidebar">
                        <h3 style="margin-top: 0;">Available contacts</h3>
                        <div class="chat-list">
                            <div class="chat-item"><strong>Academic Office</strong><br><span style="font-size: 12px; color: var(--color-text-muted);">Mon-Fri, 8:00-17:00</span></div>
                            <div class="chat-item"><strong>Finance Desk</strong><br><span style="font-size: 12px; color: var(--color-text-muted);">Tuition and payments</span></div>
                            <div class="chat-item"><strong>IT Support</strong><br><span style="font-size: 12px; color: var(--color-text-muted);">Portal and login issues</span></div>
                        </div>
                    </div>
                    <div class="chat-main">
                        <div id="chatMessages" style="margin-bottom: 12px; max-height: 400px; overflow-y: auto;">
                            <div class="chat-bubble support">Hello! How can we help you today?</div>
                        </div>
                        <form class="chat-input" id="chatForm">
                            <input type="text" id="chatInput" placeholder="Type your message..." autocomplete="off">
                            <button type="submit" class="btn btn-primary">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;

        const messages = document.getElementById('chatMessages');
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble student';
        bubble.textContent = text;
        messages.appendChild(bubble);
        input.value = '';
        messages.scrollTop = messages.scrollHeight;

        // Auto-reply
        setTimeout(() => {
            const reply = document.createElement('div');
            reply.className = 'chat-bubble support';
            reply.textContent = 'Thank you for your message. A support team member will respond shortly. For urgent matters, visit the administration office.';
            messages.appendChild(reply);
            messages.scrollTop = messages.scrollHeight;
        }, 1000);
    });
    </script>
</body>
</html>
