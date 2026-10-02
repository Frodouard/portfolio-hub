<?php
session_start();
header('Content-Type: application/json');

$user = $_SESSION['user'] ?? null;
if (!$user) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/db.php';
$db = get_db();

$totalUsers = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$customerCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalInquiries = (int) $db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$respondedCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='responded'")->fetchColumn();
$pendingCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='pending'")->fetchColumn();
$readCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='read'")->fetchColumn();
$closedCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='closed'")->fetchColumn();

$announcementCount = (int) $db->query("SELECT COUNT(*) FROM announcements")->fetchColumn();

$serviceRows = $db->query("SELECT service, COUNT(*) as cnt FROM inquiries GROUP BY service ORDER BY cnt DESC")->fetchAll();
$serviceLabels = array_column($serviceRows, 'service');
$serviceData = array_map('intval', array_column($serviceRows, 'cnt'));
if (empty($serviceLabels)) {
    $serviceLabels = ['Business Advisory','Financial Modeling','Tax Services','IT Support','Accounting'];
    $serviceData = [5, 4, 3, 2, 3];
}

$monthLabels = [];
$monthData = [];
$inquiryLabels = [];
$inquiryData = [];
for ($i = 11; $i >= 0; $i--) {
    $monthLabels[] = date('M', strtotime("-{$i} months"));
    $y = date('Y', strtotime("-{$i} months"));
    $m = date('m', strtotime("-{$i} months"));
    $ucnt = (int) $db->query("SELECT COUNT(*) FROM users WHERE YEAR(created_at)={$y} AND MONTH(created_at)={$m}")->fetchColumn();
    $monthData[] = $ucnt;
    $inquiryLabels[] = date('M', strtotime("-{$i} months"));
    $icnt = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE YEAR(created_at)={$y} AND MONTH(created_at)={$m}")->fetchColumn();
    $inquiryData[] = $icnt;
}

$recentInquiries = $db->query("SELECT id, name, service, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 5")->fetchAll();

$annByMonthLabels = [];
$annByMonthData = [];
for ($i = 11; $i >= 0; $i--) {
    $annByMonthLabels[] = date('M', strtotime("-{$i} months"));
    $y = date('Y', strtotime("-{$i} months"));
    $m = date('m', strtotime("-{$i} months"));
    $acnt = (int) $db->query("SELECT COUNT(*) FROM announcements WHERE YEAR(created_at)={$y} AND MONTH(created_at)={$m}")->fetchColumn();
    $annByMonthData[] = $acnt;
}

$recentAnnouncements = $db->query("SELECT a.title, a.content, a.created_at, u.name AS author_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

echo json_encode([
    'stats' => [
        'totalUsers' => $totalUsers,
        'customerCount' => $customerCount,
        'totalInquiries' => $totalInquiries,
        'respondedCount' => $respondedCount,
        'pendingCount' => $pendingCount,
        'readCount' => $readCount,
        'closedCount' => $closedCount,
        'announcementCount' => $announcementCount,
    ],
    'monthLabels' => $monthLabels,
    'monthData' => $monthData,
    'inquiryLabels' => $inquiryLabels,
    'inquiryData' => $inquiryData,
    'serviceLabels' => $serviceLabels,
    'serviceData' => $serviceData,
    'recentInquiries' => $recentInquiries,
    'annByMonthLabels' => $annByMonthLabels,
    'annByMonthData' => $annByMonthData,
    'recentAnnouncements' => $recentAnnouncements,
]);
