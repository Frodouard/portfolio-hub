<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only.']);
    exit;
}

$user = $_SESSION['user'] ?? null;
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

require_once __DIR__ . '/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'respond') {
    $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
    $response = trim($_POST['response'] ?? '');

    if ($inquiryId <= 0 || $response === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Inquiry ID and response are required.']);
        exit;
    }

    $db = get_db();
    $stmt = $db->prepare("UPDATE inquiries SET admin_response = :resp, status = 'responded', responded_at = NOW() WHERE id = :id");
    $stmt->execute(['resp' => $response, 'id' => $inquiryId]);

    echo json_encode(['success' => true, 'message' => 'Response sent successfully.']);
    exit;
}

if ($action === 'update_status') {
    $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'pending';

    if (!in_array($newStatus, ['pending', 'read', 'responded', 'closed'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }

    $db = get_db();
    $stmt = $db->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $newStatus, 'id' => $inquiryId]);

    echo json_encode(['success' => true, 'message' => 'Status updated.']);
    exit;
}

if ($action === 'toggle_user') {
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'active';

    if (!in_array($newStatus, ['active', 'suspended'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }

    $db = get_db();
    $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id AND role != 'admin'");
    $stmt->execute(['status' => $newStatus, 'id' => $targetId]);

    echo json_encode(['success' => true, 'message' => 'User status updated.']);
    exit;
}

if ($action === 'delete_user') {
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $db = get_db();
    $db->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'")->execute(['id' => $targetId]);
    echo json_encode(['success' => true, 'message' => 'User deleted.']);
    exit;
}

if ($action === 'create_announcement') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title and content are required.']);
        exit;
    }

    $db = get_db();
    $stmt = $db->prepare("INSERT INTO announcements (title, content, created_by) VALUES (:title, :content, :uid)");
    $stmt->execute(['title' => $title, 'content' => $content, 'uid' => $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Announcement published.']);
    exit;
}

if ($action === 'delete_announcement') {
    $announcementId = (int) ($_POST['announcement_id'] ?? 0);
    if ($announcementId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid announcement ID.']);
        exit;
    }
    $db = get_db();
    $db->prepare("DELETE FROM announcements WHERE id = :id")->execute(['id' => $announcementId]);
    echo json_encode(['success' => true, 'message' => 'Announcement deleted.']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
