<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $service === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please complete all fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

require_once __DIR__ . '/db.php';

try {
    $db = get_db();

    $userId = null;
    if (!empty($_SESSION['user']['id'])) {
        $userId = (int) $_SESSION['user']['id'];
    }

    $stmt = $db->prepare("INSERT INTO inquiries (user_id, name, email, phone, service, message, status) VALUES (:uid, :name, :email, :phone, :service, :message, 'pending')");
    $stmt->execute([
        'uid' => $userId,
        'name' => $name,
        'email' => strtolower($email),
        'phone' => $phone,
        'service' => $service,
        'message' => $message,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Your inquiry has been submitted successfully. Our team will respond shortly.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}
