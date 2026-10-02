<?php
require_once __DIR__ . '/auth.php';
require_login('admin');
$user = current_user();
$db = get_db();

$totalUsers = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$customerCount = $totalUsers;
$adminCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$totalInquiries = (int) $db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$pendingCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='pending'")->fetchColumn();
$respondedCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='responded'")->fetchColumn();
$recentInquiries = $db->query("SELECT id, name, email, service, message, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentUsers = $db->query("SELECT id, name, email, phone, location, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 8")->fetchAll();
$usersByMonth = $db->query("SELECT DATE_FORMAT(created_at, '%b') AS ml, DATE_FORMAT(created_at, '%Y-%m') AS ms, COUNT(*) AS cnt FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY ms, ml ORDER BY ms ASC")->fetchAll();
$inquiriesByMonth = $db->query("SELECT DATE_FORMAT(created_at, '%b') AS ml, DATE_FORMAT(created_at, '%Y-%m') AS ms, COUNT(*) AS cnt FROM inquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY ms, ml ORDER BY ms ASC")->fetchAll();
$inquiriesByService = $db->query("SELECT service, COUNT(*) AS cnt FROM inquiries GROUP BY service ORDER BY cnt DESC LIMIT 6")->fetchAll();
$announcements = $db->query("SELECT a.*, u.name AS author_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC")->fetchAll();
$announcementCount = count($announcements);

$today = date('Y-m-d');
$todayCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = '$today'")->fetchColumn();
$weekCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$inquiryTodayCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at) = '$today'")->fetchColumn();

$monthChartLabels = [];
$monthChartData = [];
foreach ($usersByMonth as $row) { $monthChartLabels[] = $row['ml']; $monthChartData[] = (int) $row['cnt']; }

$inquiryChartLabels = [];
$inquiryChartData = [];
foreach ($inquiriesByMonth as $row) { $inquiryChartLabels[] = $row['ml']; $inquiryChartData[] = (int) $row['cnt']; }

$serviceLabels = [];
$serviceData = [];
foreach ($inquiriesByService as $row) { $serviceLabels[] = $row['service']; $serviceData[] = (int) $row['cnt']; }

$msgDayLabels = [];
$msgDayData = [];
$countMsgStmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE DATE(created_at) = ?");
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $msgDayLabels[] = date('M j', strtotime($day));
    $countMsgStmt->execute([$day]);
    $msgDayData[] = (int) $countMsgStmt->fetchColumn();
}

$greeting = 'Good morning';
$h = (int) date('H');
if ($h >= 12 && $h < 17) $greeting = 'Good afternoon';
elseif ($h >= 17) $greeting = 'Good evening';

$avatarColors = ['#4f6ef7','#25c18d','#f5ad3d','#ef5d71','#7ba3ff','#ff6b9d','#a78bfa','#34d399'];

$statusColors = ['pending'=>'#f5ad3d','read'=>'#06b6d4','responded'=>'#25c18d','closed'=>'#9ca3af'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | Portfolio Hub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="style.css" />
  <style>
    .status-badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
    .status-pending{background:rgba(245,173,61,.12);color:#d97706}
    .status-read{background:rgba(6,182,212,.12);color:#0891b2}
    .status-responded{background:rgba(37,193,141,.12);color:#0a7a5b}
    .status-closed{background:rgba(156,163,175,.12);color:#6b7280}
    .action-btn{padding:6px 12px;border-radius:8px;border:none;font-size:.75rem;font-weight:600;cursor:pointer;transition:all .2s}
    .action-btn:hover{transform:translateY(-1px)}
    .btn-respond{background:rgba(79,110,247,.12);color:#4f6ef7}
    .btn-respond:hover{background:rgba(79,110,247,.22)}
    .btn-view{background:rgba(139,92,246,.12);color:#8b5cf6}
    .btn-view:hover{background:rgba(139,92,246,.22)}
    .btn-danger{background:rgba(239,93,113,.12);color:#ef5d71}
    .btn-danger:hover{background:rgba(239,93,113,.22)}
    .btn-success{background:rgba(37,193,141,.12);color:#25c18d}
    .btn-success:hover{background:rgba(37,193,141,.22)}
    .modal-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);place-items:center}
    .modal-overlay.active{display:grid}
    .modal{background:#111827;border:1px solid rgba(148,163,184,.15);border-radius:20px;padding:28px;max-width:520px;width:90%;max-height:80vh;overflow-y:auto}
    .modal h3{margin:0 0 16px;color:#fff;font-size:1.2rem}
    .modal p{margin:0 0 8px;color:rgba(255,255,255,.6);font-size:.88rem}
    .modal .inq-detail{background:rgba(255,255,255,.05);border-radius:12px;padding:14px;margin-bottom:16px}
    .modal textarea{width:100%;border:1px solid rgba(148,163,184,.2);background:rgba(255,255,255,.05);color:#fff;border-radius:12px;padding:12px;min-height:100px;resize:vertical;font:inherit}
    .modal textarea:focus{outline:none;border-color:#4f6ef7}
    .modal-actions{display:flex;gap:10px;margin-top:16px}
    .modal-close{background:rgba(255,255,255,.08);color:rgba(255,255,255,.6);border:none;padding:10px 18px;border-radius:10px;font-weight:600;cursor:pointer}
    .modal-close:hover{background:rgba(255,255,255,.14)}
    .modal-send{background:linear-gradient(135deg,#4f6ef7,#2d4ec7);color:#fff;border:none;padding:10px 18px;border-radius:10px;font-weight:600;cursor:pointer}
    .modal-send:hover{box-shadow:0 4px 16px rgba(79,110,247,.3)}
    .toast{position:fixed;bottom:24px;right:24px;z-index:99999;padding:14px 20px;border-radius:12px;font-weight:600;font-size:.88rem;transform:translateY(100px);opacity:0;transition:all .3s}
    .toast.show{transform:translateY(0);opacity:1}
    .toast.success{background:#0a7a5b;color:#fff}
    .toast.error{background:#be2f45;color:#fff}
    .user-status-active{color:#25c18d}
    .user-status-suspended{color:#ef5d71}
    .actions-cell{display:flex;gap:6px;flex-wrap:wrap}
  </style>
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-inner">
        <div class="brand-block">
          <img src="assets/images/logo.png" alt="Portfolio Hub" class="brand-mark" />
          <div>
            <p class="brand-name">Portfolio Hub</p>
            <p class="brand-label">Admin panel</p>
          </div>
          <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">&times;</button>
        </div>

        <nav class="side-nav" aria-label="Admin menu">
          <a href="#overview" class="nav-item active" data-section="overview"><span class="nav-icon">&#9633;</span>Overview</a>
          <a href="#customers" class="nav-item" data-section="customers"><span class="nav-icon">&#9678;</span>Customers</a>
          <a href="#inquiries" class="nav-item" data-section="inquiries"><span class="nav-icon">&#9993;</span>Inquiries <?php if($pendingCount>0): ?><span style="background:#ef5d71;color:#fff;font-size:.6rem;padding:2px 7px;border-radius:999px;margin-left:6px"><?= $pendingCount ?></span><?php endif; ?></a>
          <a href="#announcements" class="nav-item" data-section="announcements"><span class="nav-icon">&#128227;</span>Announcements <?php if($announcementCount>0): ?><span style="background:#8b5cf6;color:#fff;font-size:.6rem;padding:2px 7px;border-radius:999px;margin-left:6px"><?= $announcementCount ?></span><?php endif; ?></a>
          <a href="#analytics" class="nav-item" data-section="analytics"><span class="nav-icon">&#9672;</span>Analytics</a>
        </nav>

        <div class="nav-divider"></div>
        <p class="nav-section-label">Quick links</p>
        <nav class="side-nav" aria-label="Quick links">
          <a href="live-charts.php" class="nav-item"><span class="nav-icon">&#128200;</span>Live Charts</a>
          <a href="index.php" class="nav-item"><span class="nav-icon">&#8962;</span>Home</a>
          <a href="team.php" class="nav-item"><span class="nav-icon">&#9734;</span>Our Team</a>
          <a href="logout.php" class="nav-item nav-logout"><span class="nav-icon">&#10140;</span>Sign out</a>
        </nav>

        <div class="mini-card">
          <div class="mini-card-header">
            <div class="mini-card-avatar"><?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?></div>
            <div>
              <p class="mini-card-name"><?= htmlspecialchars($user['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></p>
              <p class="mini-card-role">Administrator</p>
            </div>
          </div>
          <div class="mini-card-status"><span class="status-dot"></span> Online</div>
          <a href="logout.php" class="logout-link">Sign out</a>
        </div>
      </div>
    </aside>

    <main class="main-panel">
      <header class="topbar">
        <div class="topbar-left">
          <button class="hamburger" id="hamburgerBtn" aria-label="Open menu">
            <span></span><span></span><span></span>
          </button>
          <div>
            <p class="eyebrow">Admin dashboard</p>
            <h1><?= $greeting ?>, <?= htmlspecialchars($user['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></h1>
          </div>
        </div>
        <div class="topbar-right">
          <div class="search-box">
            <span class="search-icon">&#128269;</span>
            <input type="text" placeholder="Search..." id="globalSearch" />
          </div>
          <div class="date-badge" id="todayDate">Loading...</div>
        </div>
      </header>

      <!-- OVERVIEW -->
      <section class="dashboard-section active" id="section-overview">
        <section class="hero-card">
          <div class="hero-content">
            <span class="status-pill">Live</span>
            <h2>Business performance is trending above forecast this quarter.</h2>
          </div>
          <div class="hero-stats">
            <div class="hero-stat"><span class="hero-stat-value"><?= $totalUsers + $adminCount ?></span><span class="hero-stat-label">Total Users</span></div>
            <div class="hero-stat"><span class="hero-stat-value"><?= $totalInquiries ?></span><span class="hero-stat-label">Inquiries</span></div>
            <div class="hero-stat"><span class="hero-stat-value"><?= $todayCount ?></span><span class="hero-stat-label">Today</span></div>
          </div>
        </section>

        <section class="stats-grid">
          <article class="stat-card accent">
            <div class="stat-icon-wrap accent"><span class="stat-icon">&#128100;</span></div>
            <div class="stat-body"><div class="stat-head"><span>Total users</span><span class="trend up">+<?= $todayCount ?></span></div><strong><?= $totalUsers + $adminCount ?></strong><small>All registered</small></div>
          </article>
          <article class="stat-card">
            <div class="stat-icon-wrap green"><span class="stat-icon">&#9993;</span></div>
            <div class="stat-body"><div class="stat-head"><span>Inquiries</span><span class="trend up">+<?= $inquiryTodayCount ?> today</span></div><strong><?= $totalInquiries ?></strong><small>Total received</small></div>
          </article>
          <article class="stat-card">
            <div class="stat-icon-wrap blue"><span class="stat-icon">&#128202;</span></div>
            <div class="stat-body"><div class="stat-head"><span>This week</span><span class="trend up">Active</span></div><strong><?= $weekCount ?></strong><small>New users</small></div>
          </article>
          <article class="stat-card">
            <div class="stat-icon-wrap purple"><span class="stat-icon">&#11088;</span></div>
            <div class="stat-body"><div class="stat-head"><span>Responded</span><span class="trend up">Done</span></div><strong><?= $respondedCount ?></strong><small>Inquiries answered</small></div>
          </article>
        </section>

        <section class="content-grid">
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">Growth</p><h3>User registrations</h3></div>
              <span class="filter-chip">&#9679; Live</span>
            </div>
            <div class="chart-container"><canvas id="userGrowthChart"></canvas></div>
          </article>
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">Inquiries</p><h3>By service</h3></div>
              <span class="filter-chip">&#9679; Live</span>
            </div>
            <div class="chart-container chart-container-sm"><canvas id="serviceChart"></canvas></div>
          </article>
        </section>

        <section class="content-grid">
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">Messages</p><h3>Messages &mdash; last 14 days</h3></div>
              <span class="filter-chip">&#9679; Live</span>
            </div>
            <div class="chart-container"><canvas id="messagesChart"></canvas></div>
          </article>
        </section>

        <section class="bottom-grid">
          <article class="panel">
            <div class="panel-header"><div><p class="section-kicker">Users</p><h3>Recent registrations</h3></div></div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($recentUsers)): ?>
                  <tr><td colspan="7" class="empty-state">No users yet</td></tr>
                <?php else: ?>
                  <?php foreach ($recentUsers as $idx => $u): ?>
                    <tr>
                      <td><div class="user-cell"><div class="user-avatar" style="background:<?= $avatarColors[$idx % 8] ?>"><?= strtoupper(substr($u['name'], 0, 1)) ?></div><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div></td>
                      <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars($u['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                      <td><span class="role-badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                      <td><span class="user-status-<?= $u['status'] ?? 'active' ?>"><?= ucfirst($u['status'] ?? 'active') ?></span></td>
                      <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                      <td>
                        <?php if ($u['role'] !== 'admin'): ?>
                          <div class="actions-cell">
                            <button class="action-btn btn-respond" onclick="toggleUser(<?= $u['id'] ?>,'<?= ($u['status'] ?? 'active') === 'active' ? 'suspended' : 'active' ?>')"><?= ($u['status'] ?? 'active') === 'active' ? 'Suspend' : 'Activate' ?></button>
                            <button class="action-btn btn-danger" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
                          </div>
                        <?php else: ?>
                          <span style="color:rgba(255,255,255,.3);font-size:.75rem">Admin</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </article>
          <article class="panel">
            <div class="panel-header"><div><p class="section-kicker">Activity</p><h3>Recent inquiries</h3></div></div>
            <ul class="activity-list">
            <?php if (empty($recentInquiries)): ?>
              <li class="empty-state">No inquiries yet</li>
            <?php else: ?>
              <?php foreach ($recentInquiries as $inq): ?>
                <li>
                  <span class="dot" style="background:<?= $statusColors[$inq['status']] ?? '#4f6ef7' ?>;box-shadow:0 0 0 4px <?= $statusColors[$inq['status']] ?? '#4f6ef7' ?>22"></span>
                  <div style="flex:1">
                    <strong><?= htmlspecialchars($inq['name'], ENT_QUOTES, 'UTF-8') ?> &mdash; <?= htmlspecialchars($inq['service'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars(mb_substr($inq['message'], 0, 50), ENT_QUOTES, 'UTF-8') ?>...</small>
                    <small class="activity-time"><?= date('M j, g:i A', strtotime($inq['created_at'])) ?> &middot; <span class="status-badge status-<?= $inq['status'] ?>"><?= ucfirst($inq['status']) ?></span></small>
                  </div>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
            </ul>
          </article>
        </section>
      </section>

      <!-- CUSTOMERS -->
      <section class="dashboard-section" id="section-customers">
        <div class="section-header"><div><p class="section-kicker">Management</p><h2>All Customers</h2></div></div>
        <article class="panel">
          <div class="table-wrap">
            <table>
              <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
              <tbody>
              <?php
              $allUsers = $db->query("SELECT id, name, email, phone, location, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll();
              if (empty($allUsers)): ?>
                <tr><td colspan="9" class="empty-state">No users found</td></tr>
              <?php else: ?>
                <?php foreach ($allUsers as $i => $u): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><div class="user-cell"><div class="user-avatar" style="background:<?= $avatarColors[$i % 8] ?>"><?= strtoupper(substr($u['name'], 0, 1)) ?></div><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div></td>
                    <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($u['location'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="role-badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td><span class="user-status-<?= $u['status'] ?? 'active' ?>"><?= ucfirst($u['status'] ?? 'active') ?></span></td>
                    <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                      <?php if ($u['role'] !== 'admin'): ?>
                        <div class="actions-cell">
                          <button class="action-btn btn-respond" onclick="toggleUser(<?= $u['id'] ?>,'<?= ($u['status'] ?? 'active') === 'active' ? 'suspended' : 'active' ?>')"><?= ($u['status'] ?? 'active') === 'active' ? 'Suspend' : 'Activate' ?></button>
                          <button class="action-btn btn-danger" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
                        </div>
                      <?php else: ?>
                        <span style="color:rgba(255,255,255,.3);font-size:.75rem">Admin</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>

      <!-- INQUIRIES -->
      <section class="dashboard-section" id="section-inquiries">
        <div class="section-header"><div><p class="section-kicker">Communication</p><h2>All Inquiries</h2></div></div>
        <article class="panel">
          <div class="table-wrap">
            <table>
              <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Service</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody>
              <?php
              $allInquiries = $db->query("SELECT * FROM inquiries ORDER BY created_at DESC")->fetchAll();
              if (empty($allInquiries)): ?>
                <tr><td colspan="9" class="empty-state">No inquiries found</td></tr>
              <?php else: ?>
                <?php foreach ($allInquiries as $i => $inq): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($inq['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($inq['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($inq['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="service-tag"><?= htmlspecialchars($inq['service'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="msg-cell" title="<?= htmlspecialchars($inq['message'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(mb_substr($inq['message'], 0, 50), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($inq['message']) > 50 ? '...' : '' ?></td>
                    <td><span class="status-badge status-<?= $inq['status'] ?>"><?= ucfirst($inq['status']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
                    <td>
                      <div class="actions-cell">
                        <button class="action-btn btn-view" onclick='viewInquiry(<?= json_encode($inq) ?>)'>View</button>
                        <button class="action-btn btn-respond" onclick='respondToInquiry(<?= $inq["id"] ?>,<?= json_encode(htmlspecialchars($inq["name"],ENT_QUOTES,"UTF-8")) ?>)'>Respond</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>

      <!-- ANNOUNCEMENTS -->
      <section class="dashboard-section" id="section-announcements">
        <div class="section-header"><div><p class="section-kicker">Communication</p><h2>Announcements</h2></div></div>

        <article class="panel" style="margin-bottom:20px">
          <div class="panel-header"><div><p class="section-kicker">Create</p><h3>New Announcement</h3></div></div>
          <div style="display:grid;gap:14px;max-width:600px;">
            <input type="text" id="annTitle" placeholder="Announcement title" style="width:100%;border:1px solid rgba(148,163,184,.2);background:rgba(255,255,255,.05);color:#fff;border-radius:12px;padding:12px 14px;font:inherit;font-size:.92rem" />
            <textarea id="annContent" rows="4" placeholder="Write your announcement content here..." style="width:100%;border:1px solid rgba(148,163,184,.2);background:rgba(255,255,255,.05);color:#fff;border-radius:12px;padding:12px 14px;font:inherit;resize:vertical;min-height:80px"></textarea>
            <div><button class="modal-send" onclick="createAnnouncement()">Publish Announcement</button></div>
          </div>
        </article>

        <article class="panel">
          <div class="panel-header"><div><p class="section-kicker">Published</p><h3>All Announcements</h3></div></div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>#</th><th>Title</th><th>Content</th><th>Author</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody>
              <?php if (empty($announcements)): ?>
                <tr><td colspan="6" class="empty-state">No announcements yet</td></tr>
              <?php else: ?>
                <?php foreach ($announcements as $i => $ann): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td class="msg-cell" title="<?= htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(mb_substr($ann['content'], 0, 60), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($ann['content']) > 60 ? '...' : '' ?></td>
                    <td><?= htmlspecialchars($ann['author_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= date('M j, Y', strtotime($ann['created_at'])) ?></td>
                    <td>
                      <button class="action-btn btn-danger" onclick="deleteAnnouncement(<?= $ann['id'] ?>)">Delete</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>

      <!-- ANALYTICS -->
      <section class="dashboard-section" id="section-analytics">
        <div class="section-header"><div><p class="section-kicker">Insights</p><h2>Analytics Overview</h2></div></div>
        <section class="stats-grid stats-grid-3">
          <article class="stat-card accent"><div class="stat-body"><div class="stat-head"><span>Total registrations</span></div><strong><?= $totalUsers + $adminCount ?></strong><small>All time</small></div></article>
          <article class="stat-card"><div class="stat-body"><div class="stat-head"><span>Total inquiries</span></div><strong><?= $totalInquiries ?></strong><small>All time</small></div></article>
          <article class="stat-card"><div class="stat-body"><div class="stat-head"><span>Avg inquiries/user</span></div><strong><?= $totalUsers > 0 ? round($totalInquiries / max($totalUsers, 1), 1) : '0' ?></strong><small>Per customer</small></div></article>
        </section>
        <section class="content-grid">
          <article class="panel">
            <div class="panel-header"><div><p class="section-kicker">Trend</p><h3>Inquiry volume</h3></div><span class="filter-chip">&#9679; Live</span></div>
            <div class="chart-container"><canvas id="inquiryTrendChart"></canvas></div>
          </article>
          <article class="panel">
            <div class="panel-header"><div><p class="section-kicker">Services</p><h3>Service demand</h3></div><span class="filter-chip">&#9679; Live</span></div>
            <div class="chart-container chart-container-sm"><canvas id="servicePieChart"></canvas></div>
          </article>
        </section>
      </section>

    </main>
  </div>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- VIEW MODAL -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal">
      <h3>Inquiry Details</h3>
      <div class="inq-detail" id="viewModalBody"></div>
      <div class="modal-actions">
        <button class="modal-close" onclick="closeModal('viewModal')">Close</button>
      </div>
    </div>
  </div>

  <!-- RESPOND MODAL -->
  <div class="modal-overlay" id="respondModal">
    <div class="modal">
      <h3>Respond to <span id="respondName"></span></h3>
      <textarea id="respondText" placeholder="Type your response here..."></textarea>
      <div class="modal-actions">
        <button class="modal-close" onclick="closeModal('respondModal')">Cancel</button>
        <button class="modal-send" onclick="sendResponse()">Send Response</button>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast"></div>

  <script>
    window.__DASHBOARD_DATA__ = {
      monthLabels: <?= json_encode($monthChartLabels) ?>,
      monthData: <?= json_encode($monthChartData) ?>,
      inquiryLabels: <?= json_encode($inquiryChartLabels) ?>,
      inquiryData: <?= json_encode($inquiryChartData) ?>,
      serviceLabels: <?= json_encode($serviceLabels) ?>,
      serviceData: <?= json_encode($serviceData) ?>,
      msgDayLabels: <?= json_encode($msgDayLabels) ?>,
      msgDayData: <?= json_encode($msgDayData) ?>
    };
  </script>
  <script src="script.js"></script>
  <script>
  var currentRespondId = 0;

  function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast ' + type + ' show';
    setTimeout(function(){ t.className = 'toast'; }, 3000);
  }

  function closeModal(id) {
    document.getElementById(id).classList.remove('active');
  }

  function viewInquiry(inq) {
    var html = '<p><strong>Name:</strong> ' + inq.name + '</p>' +
      '<p><strong>Email:</strong> ' + inq.email + '</p>' +
      '<p><strong>Phone:</strong> ' + (inq.phone || 'N/A') + '</p>' +
      '<p><strong>Service:</strong> ' + inq.service + '</p>' +
      '<p><strong>Date:</strong> ' + inq.created_at + '</p>' +
      '<p><strong>Status:</strong> <span class="status-badge status-' + inq.status + '">' + inq.status.charAt(0).toUpperCase() + inq.status.slice(1) + '</span></p>' +
      '<p style="margin-top:12px"><strong>Message:</strong></p>' +
      '<div style="background:rgba(255,255,255,.05);padding:12px;border-radius:10px;color:rgba(255,255,255,.8);font-size:.88rem;line-height:1.6">' + inq.message + '</div>';
    if (inq.admin_response) {
      html += '<p style="margin-top:14px"><strong>Admin Response:</strong></p>' +
        '<div style="background:rgba(79,110,247,.08);padding:12px;border-radius:10px;color:rgba(255,255,255,.8);font-size:.88rem;line-height:1.6;border-left:3px solid #4f6ef7">' + inq.admin_response + '</div>' +
        '<p style="font-size:.75rem;color:rgba(255,255,255,.4);margin-top:6px">Responded: ' + (inq.responded_at || 'N/A') + '</p>';
    }
    document.getElementById('viewModalBody').innerHTML = html;
    document.getElementById('viewModal').classList.add('active');
  }

  function respondToInquiry(id, name) {
    currentRespondId = id;
    document.getElementById('respondName').textContent = name;
    document.getElementById('respondText').value = '';
    document.getElementById('respondModal').classList.add('active');
  }

  function sendResponse() {
    var text = document.getElementById('respondText').value.trim();
    if (!text) { showToast('Please type a response.', 'error'); return; }

    var fd = new FormData();
    fd.append('action', 'respond');
    fd.append('inquiry_id', currentRespondId);
    fd.append('response', text);

    fetch('admin-action.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success) {
          showToast('Response sent!', 'success');
          closeModal('respondModal');
          setTimeout(function(){ location.reload(); }, 1000);
        } else {
          showToast(d.message || 'Error', 'error');
        }
      })
      .catch(function(){ showToast('Network error.', 'error'); });
  }

  function toggleUser(id, status) {
    var fd = new FormData();
    fd.append('action', 'toggle_user');
    fd.append('user_id', id);
    fd.append('status', status);
    fetch('admin-action.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success) { showToast('User updated!', 'success'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(d.message || 'Error', 'error');
      });
  }

  function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    var fd = new FormData();
    fd.append('action', 'delete_user');
    fd.append('user_id', id);
    fetch('admin-action.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success) { showToast('User deleted.', 'success'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(d.message || 'Error', 'error');
      });
  }

  function createAnnouncement() {
    var title = document.getElementById('annTitle').value.trim();
    var content = document.getElementById('annContent').value.trim();
    if (!title || !content) { showToast('Please fill in both title and content.', 'error'); return; }

    var fd = new FormData();
    fd.append('action', 'create_announcement');
    fd.append('title', title);
    fd.append('content', content);
    fetch('admin-action.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success) { showToast('Announcement published!', 'success'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(d.message || 'Error', 'error');
      })
      .catch(function(){ showToast('Network error.', 'error'); });
  }

  function deleteAnnouncement(id) {
    if (!confirm('Delete this announcement?')) return;
    var fd = new FormData();
    fd.append('action', 'delete_announcement');
    fd.append('announcement_id', id);
    fetch('admin-action.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d.success) { showToast('Announcement deleted.', 'success'); setTimeout(function(){ location.reload(); }, 800); }
        else showToast(d.message || 'Error', 'error');
      });
  }
  </script>
</body>
</html>
