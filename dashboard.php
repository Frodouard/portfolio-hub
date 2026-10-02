<?php
require_once __DIR__ . '/auth.php';
require_login('customer');
$user = current_user();
$db = get_db();

$customerRow = $db->prepare("SELECT id, phone, address, created_at FROM customers WHERE user_id = :uid LIMIT 1");
$customerRow->execute(['uid' => $user['id']]);
$profile = $customerRow->fetch();

$myInquiries = $db->prepare("SELECT * FROM inquiries WHERE user_id = :uid OR email = :email ORDER BY created_at DESC");
$myInquiries->execute(['uid' => $user['id'], 'email' => strtolower($user['email'] ?? '')]);
$myInquiries = $myInquiries->fetchAll();

$announcements = $db->query("SELECT a.*, u.name AS author_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC")->fetchAll();
$announcementCount = count($announcements);

/* ---- Real stats for THIS customer only ---- */
$totalMy = count($myInquiries);
$statusCounts = ['pending' => 0, 'read' => 0, 'responded' => 0, 'closed' => 0];
foreach ($myInquiries as $inq) {
    $st = $inq['status'] ?? 'pending';
    if (!isset($statusCounts[$st])) { $st = 'pending'; }
    $statusCounts[$st]++;
}
$pendingMy = $statusCounts['pending'];
$respondedMy = $statusCounts['responded'];

$myServices = array_unique(array_filter(array_column($myInquiries, 'service')));
$servicesUsed = count($myServices);

$myMonthLabels = [];
$myMonthData = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-{$i} months"));
    $myMonthLabels[] = date('M', strtotime("-{$i} months"));
    $cnt = 0;
    foreach ($myInquiries as $inq) {
        if (substr((string) $inq['created_at'], 0, 7) === $key) { $cnt++; }
    }
    $myMonthData[] = $cnt;
}

$greeting = 'Good morning';
$h = (int) date('H');
if ($h >= 12 && $h < 17) $greeting = 'Good afternoon';
elseif ($h >= 17) $greeting = 'Good evening';

$avatarColors = ['#4f6ef7','#25c18d','#f5ad3d','#ef5d71','#7ba3ff','#ff6b9d','#a78bfa','#34d399'];
$avatarColor = $avatarColors[$user['id'] % 8];
$avatarLetter = strtoupper(substr($user['name'] ?? 'C', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Dashboard | Portfolio Hub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-inner">
        <div class="brand-block">
          <img src="assets/images/logo.png" alt="Portfolio Hub" class="brand-mark" />
          <div>
            <p class="brand-name">Portfolio Hub</p>
            <p class="brand-label">Customer portal</p>
          </div>
          <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">&times;</button>
        </div>

        <nav class="side-nav" aria-label="Customer menu">
          <a href="#overview" class="nav-item active" data-section="overview"><span class="nav-icon">&#9633;</span>Overview</a>
          <a href="#services" class="nav-item" data-section="services"><span class="nav-icon">&#9881;</span>Services</a>
          <a href="#inquiries" class="nav-item" data-section="inquiries"><span class="nav-icon">&#9993;</span>My Inquiries</a>
          <a href="#announcements" class="nav-item" data-section="announcements"><span class="nav-icon">&#128227;</span>Announcements</a>
          <a href="#profile" class="nav-item" data-section="profile"><span class="nav-icon">&#9678;</span>My Profile</a>
        </nav>

        <div class="nav-divider"></div>
        <p class="nav-section-label">Quick links</p>
        <nav class="side-nav" aria-label="Quick links">
          <a href="index.php" class="nav-item"><span class="nav-icon">&#8962;</span>Home</a>
          <a href="team.php" class="nav-item"><span class="nav-icon">&#9734;</span>Our Team</a>
          <a href="logout.php" class="nav-item nav-logout"><span class="nav-icon">&#10140;</span>Sign out</a>
        </nav>

        <div class="mini-card">
          <div class="mini-card-header">
            <div class="mini-card-avatar" style="background:<?= $avatarColor ?>"><?= $avatarLetter ?></div>
            <div>
              <p class="mini-card-name"><?= htmlspecialchars($user['name'] ?? 'Customer', ENT_QUOTES, 'UTF-8') ?></p>
              <p class="mini-card-role">Customer</p>
            </div>
          </div>
          <div class="mini-card-status">
            <span class="status-dot"></span>
            <span>Active</span>
          </div>
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
            <p class="eyebrow">Customer dashboard</p>
            <h1><?= $greeting ?>, <?= htmlspecialchars($user['name'] ?? 'Customer', ENT_QUOTES, 'UTF-8') ?></h1>
          </div>
        </div>
        <div class="topbar-right">
          <div class="date-badge" id="todayDate">Loading...</div>
        </div>
      </header>

      <!-- OVERVIEW -->
      <section class="dashboard-section active" id="section-overview">
        <section class="hero-card">
          <div class="hero-content">
            <span class="status-pill">Online</span>
            <h2>Welcome to Portfolio Hub. Here are all the services we offer.</h2>
          </div>
          <div class="hero-stats">
            <div class="hero-stat">
              <span class="hero-stat-value">7</span>
              <span class="hero-stat-label">Services Available</span>
            </div>
            <div class="hero-stat">
              <span class="hero-stat-value">24/7</span>
              <span class="hero-stat-label">Support</span>
            </div>
          </div>
        </section>

        <section class="overview-services-header">
          <div><p class="section-kicker">What we offer</p><h3>All Services</h3></div>
        </section>

        <div class="overview-services-grid">
          <a href="index.php#contact" class="ov-svc-card">
            <div class="ov-svc-icon svc-blue">&#128204;</div>
            <strong>Business Advisory</strong>
            <small>Strategic guidance for growth and planning</small>
          </a>
          <a href="index.php#contact" class="ov-svc-card">
            <div class="ov-svc-icon svc-green">&#128200;</div>
            <strong>Financial Modeling</strong>
            <small>Forecasting, analysis and data-driven decisions</small>
          </a>
          <a href="index.php#contact" class="ov-svc-card">
            <div class="ov-svc-icon svc-purple">&#128203;</div>
            <strong>Project Management</strong>
            <small>End-to-end delivery oversight</small>
          </a>
          <a href="index.php#contact" class="ov-svc-card">
            <div class="ov-svc-icon svc-orange">&#128176;</div>
            <strong>Business Valuation</strong>
            <small>Accurate valuations for M&A and investments</small>
          </a>
          <a href="index.php#contact" class="ov-svc-card">
            <div class="ov-svc-icon svc-cyan">&#128187;</div>
            <strong>IT Support</strong>
            <small>Technology consulting and infrastructure</small>
          </a>
          <a href="index.php#contact" class="ov-svc-card">
            <div class="ov-svc-icon svc-red">&#128220;</div>
            <strong>Tax Services</strong>
            <small>Tax planning, compliance and filing</small>
          </a>
          <a href="index.php#contact" class="ov-svc-card">
            <div class="ov-svc-icon svc-teal">&#128221;</div>
            <strong>Accounting Services</strong>
            <small>Bookkeeping, reporting and audit prep</small>
          </a>
        </div>

        <section class="stats-grid" style="margin-top:28px">
          <article class="stat-card accent">
            <div class="stat-icon-wrap accent"><span class="stat-icon">&#9993;</span></div>
            <div class="stat-body">
              <div class="stat-head"><span>My inquiries</span><span class="trend up">All time</span></div>
              <strong><?= $totalMy ?></strong>
              <small>Total messages sent</small>
            </div>
          </article>
          <article class="stat-card">
            <div class="stat-icon-wrap green"><span class="stat-icon">&#9881;</span></div>
            <div class="stat-body">
              <div class="stat-head"><span>Services requested</span><span class="trend up">Unique</span></div>
              <strong><?= $servicesUsed ?></strong>
              <small>Distinct services</small>
            </div>
          </article>
          <article class="stat-card">
            <div class="stat-icon-wrap blue"><span class="stat-icon">&#9888;</span></div>
            <div class="stat-body">
              <div class="stat-head"><span>Pending</span><span class="trend down">Waiting</span></div>
              <strong><?= $pendingMy ?></strong>
              <small>Awaiting response</small>
            </div>
          </article>
          <article class="stat-card">
            <div class="stat-icon-wrap purple"><span class="stat-icon">&#11088;</span></div>
            <div class="stat-body">
              <div class="stat-head"><span>Answered</span><span class="trend up"><?= $totalMy > 0 ? round(($respondedMy / max($totalMy, 1)) * 100) : 0 ?>%</span></div>
              <strong><?= $respondedMy ?></strong>
              <small>Responded by team</small>
            </div>
          </article>
        </section>

        <section class="content-grid">
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">My activity</p><h3>My inquiries &mdash; last 6 months</h3></div>
              <span class="filter-chip">&#9679; Live</span>
            </div>
            <div class="chart-container"><canvas id="myActivityChart"></canvas></div>
          </article>
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">Breakdown</p><h3>My requests by status</h3></div>
              <span class="filter-chip">&#9679; Live</span>
            </div>
            <div class="chart-container chart-container-sm"><canvas id="myStatusChart"></canvas></div>
          </article>
        </section>

        <section class="content-grid">
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">Latest</p><h3>My recent inquiries</h3></div>
            </div>
            <?php if (empty($myInquiries)): ?>
              <ul class="activity-list"><li class="empty-state">No inquiries yet &mdash; request a service below.</li></ul>
            <?php else: ?>
              <ul class="activity-list">
                <?php foreach (array_slice($myInquiries, 0, 5) as $inq): ?>
                  <li>
                    <span class="dot" style="background:<?= $inq['status']==='responded' ? '#25c18d' : ($inq['status']==='pending' ? '#f5ad3d' : '#06b6d4') ?>"></span>
                    <div style="flex:1">
                      <strong><?= htmlspecialchars($inq['service'], ENT_QUOTES, 'UTF-8') ?></strong>
                      <small><?= htmlspecialchars(mb_substr($inq['message'], 0, 60), ENT_QUOTES, 'UTF-8') ?>...</small>
                      <small class="activity-time"><?= date('M j, Y', strtotime($inq['created_at'])) ?> &middot; <?= ucfirst($inq['status']) ?></small>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </article>
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">Updates</p><h3>Latest announcements</h3></div>
            </div>
            <?php if (empty($announcements)): ?>
              <ul class="activity-list"><li class="empty-state">No announcements yet.</li></ul>
            <?php else: ?>
              <ul class="activity-list">
                <?php foreach (array_slice($announcements, 0, 4) as $ann): ?>
                  <li>
                    <span class="dot blue"></span>
                    <div style="flex:1">
                      <strong><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                      <small><?= htmlspecialchars(mb_substr($ann['content'], 0, 70), ENT_QUOTES, 'UTF-8') ?>...</small>
                      <small class="activity-time"><?= date('M j, Y', strtotime($ann['created_at'])) ?></small>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </article>
        </section>
      </section>

      <!-- SERVICES -->
      <section class="dashboard-section" id="section-services">
        <div class="section-header"><div><p class="section-kicker">What we offer</p><h2>Our Services</h2></div></div>

        <section class="stats-grid stats-grid-3" style="margin-bottom:24px">
          <article class="stat-card accent"><div class="stat-body"><div class="stat-head"><span>Available services</span></div><strong>7</strong><small>Full service offering</small></div></article>
          <article class="stat-card"><div class="stat-body"><div class="stat-head"><span>Requested by you</span></div><strong><?= $servicesUsed ?></strong><small>Services you've inquired about</small></div></article>
          <article class="stat-card"><div class="stat-body"><div class="stat-head"><span>Support</span></div><strong>24/7</strong><small>Always available</small></div></article>
        </section>

        <div class="services-grid">
          <!-- Business Advisory -->
          <div class="service-card svc-active">
            <div class="svc-icon svc-blue">&#128204;</div>
            <div class="svc-body">
              <h3>Business Advisory</h3>
              <p>Strategic guidance for growth, operational efficiency, and long-term business planning.</p>
              <div class="svc-tags"><span class="svc-tag">Strategy</span><span class="svc-tag">Planning</span><span class="svc-tag">Growth</span></div>
            </div>
            <div class="svc-footer">
              <span class="svc-status svc-on">Available</span>
              <a href="index.php#contact" class="svc-btn">Request</a>
            </div>
          </div>

          <!-- Financial Modeling -->
          <div class="service-card svc-active">
            <div class="svc-icon svc-green">&#128200;</div>
            <div class="svc-body">
              <h3>Financial Modeling</h3>
              <p>Advanced financial analysis, forecasting models, and data-driven decision support.</p>
              <div class="svc-tags"><span class="svc-tag">Forecasting</span><span class="svc-tag">Analysis</span></div>
            </div>
            <div class="svc-footer">
              <span class="svc-status svc-on">Available</span>
              <a href="index.php#contact" class="svc-btn">Request</a>
            </div>
          </div>

          <!-- Project Management -->
          <div class="service-card svc-active">
            <div class="svc-icon svc-purple">&#128203;</div>
            <div class="svc-body">
              <h3>Project Management</h3>
              <p>End-to-end project oversight ensuring delivery on time, within scope, and on budget.</p>
              <div class="svc-tags"><span class="svc-tag">Delivery</span><span class="svc-tag">Oversight</span></div>
            </div>
            <div class="svc-footer">
              <span class="svc-status svc-on">Available</span>
              <a href="index.php#contact" class="svc-btn">Request</a>
            </div>
          </div>

          <!-- Business Valuation -->
          <div class="service-card">
            <div class="svc-icon svc-orange">&#128176;</div>
            <div class="svc-body">
              <h3>Business Valuation</h3>
              <p>Accurate company valuations for mergers, acquisitions, investments, and strategic decisions.</p>
              <div class="svc-tags"><span class="svc-tag">Valuation</span><span class="svc-tag">M&amp;A</span></div>
            </div>
            <div class="svc-footer">
              <span class="svc-status svc-on">Available</span>
              <a href="index.php#contact" class="svc-btn">Request</a>
            </div>
          </div>

          <!-- IT Support -->
          <div class="service-card">
            <div class="svc-icon svc-cyan">&#128187;</div>
            <div class="svc-body">
              <h3>IT Support</h3>
              <p>Technology consulting, infrastructure setup, and ongoing technical support for your team.</p>
              <div class="svc-tags"><span class="svc-tag">Technology</span><span class="svc-tag">Infrastructure</span></div>
            </div>
            <div class="svc-footer">
              <span class="svc-status svc-on">Available</span>
              <a href="index.php#contact" class="svc-btn">Request</a>
            </div>
          </div>

          <!-- Tax Services -->
          <div class="service-card">
            <div class="svc-icon svc-red">&#128220;</div>
            <div class="svc-body">
              <h3>Tax Services</h3>
              <p>Tax planning, compliance, filing, and optimization to keep your business compliant and efficient.</p>
              <div class="svc-tags"><span class="svc-tag">Compliance</span><span class="svc-tag">Filing</span></div>
            </div>
            <div class="svc-footer">
              <span class="svc-status svc-on">Available</span>
              <a href="index.php#contact" class="svc-btn">Request</a>
            </div>
          </div>

          <!-- Accounting Services -->
          <div class="service-card">
            <div class="svc-icon svc-teal">&#128221;</div>
            <div class="svc-body">
              <h3>Accounting Services</h3>
              <p>Full-cycle accounting, bookkeeping, financial reporting, and audit preparation.</p>
              <div class="svc-tags"><span class="svc-tag">Bookkeeping</span><span class="svc-tag">Reporting</span></div>
            </div>
            <div class="svc-footer">
              <span class="svc-status svc-on">Available</span>
              <a href="index.php#contact" class="svc-btn">Request</a>
            </div>
          </div>
        </div>

        <div style="margin-top:32px">
          <article class="panel">
            <div class="panel-header">
              <div><p class="section-kicker">Need help?</p><h3>Contact us about any service</h3></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:600px;">
              <div style="padding:16px;background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.15);border-radius:14px;">
                <strong style="display:block;margin-bottom:4px;">&#128231; Email</strong>
                <span style="color:var(--muted);font-size:0.88rem;">portfoliohubwanda@gmail.com</span>
              </div>
              <div style="padding:16px;background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.15);border-radius:14px;">
                <strong style="display:block;margin-bottom:4px;">&#128222; Phone</strong>
                <span style="color:var(--muted);font-size:0.88rem;">+250 787 258 624</span>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- MY INQUIRIES -->
      <section class="dashboard-section" id="section-inquiries">
        <div class="section-header"><div><p class="section-kicker">Communication</p><h2>My Inquiries</h2></div></div>

        <?php if (empty($myInquiries)): ?>
          <article class="panel">
            <div style="text-align:center;padding:40px 20px">
              <p style="color:var(--muted);margin:0 0 12px">You haven't sent any inquiries yet.</p>
              <a href="index.php#contact" class="svc-btn" style="display:inline-block">Send your first inquiry</a>
            </div>
          </article>
        <?php else: ?>
          <?php foreach ($myInquiries as $inq): ?>
            <article class="panel" style="margin-bottom:16px">
              <div class="panel-header">
                <div>
                  <p class="section-kicker"><?= htmlspecialchars($inq['service'], ENT_QUOTES, 'UTF-8') ?></p>
                  <h3 style="font-size:1.05rem"><?= date('F j, Y', strtotime($inq['created_at'])) ?></h3>
                </div>
                <span class="svc-status" style="color:<?= $inq['status']==='responded' ? '#25c18d' : ($inq['status']==='pending' ? '#f5ad3d' : '#5f6f88') ?>"><?= ucfirst($inq['status']) ?></span>
              </div>
              <div style="background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.12);border-radius:12px;padding:14px 16px;margin-bottom:12px">
                <p style="margin:0;color:var(--text);font-size:0.92rem;line-height:1.6"><?= nl2br(htmlspecialchars($inq['message'], ENT_QUOTES, 'UTF-8')) ?></p>
              </div>
              <?php if (!empty($inq['admin_response'])): ?>
                <div style="background:rgba(79,110,247,0.05);border:1px solid rgba(79,110,247,0.15);border-left:3px solid #4f6ef7;border-radius:12px;padding:14px 16px">
                  <p style="margin:0 0 6px;font-size:0.75rem;font-weight:700;color:var(--primary-strong);text-transform:uppercase;letter-spacing:0.08em">Admin Response</p>
                  <p style="margin:0;color:var(--text);font-size:0.92rem;line-height:1.6"><?= nl2br(htmlspecialchars($inq['admin_response'], ENT_QUOTES, 'UTF-8')) ?></p>
                  <?php if (!empty($inq['responded_at'])): ?>
                    <p style="margin:6px 0 0;font-size:0.75rem;color:var(--muted)">Responded <?= date('M j, Y g:i A', strtotime($inq['responded_at'])) ?></p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <!-- ANNOUNCEMENTS -->
      <section class="dashboard-section" id="section-announcements">
        <div class="section-header"><div><p class="section-kicker">Updates</p><h2>Announcements</h2></div></div>

        <?php if (empty($announcements)): ?>
          <article class="panel">
            <div style="text-align:center;padding:40px 20px">
              <p style="color:var(--muted);margin:0">No announcements at this time. Check back later.</p>
            </div>
          </article>
        <?php else: ?>
          <?php foreach ($announcements as $ann): ?>
            <article class="panel" style="margin-bottom:16px">
              <div class="panel-header">
                <div>
                  <p class="section-kicker"><?= htmlspecialchars($ann['author_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></p>
                  <h3 style="font-size:1.05rem"><?= htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                </div>
                <span style="font-size:.75rem;color:var(--muted)"><?= date('M j, Y g:i A', strtotime($ann['created_at'])) ?></span>
              </div>
              <div style="background:rgba(79,110,247,0.05);border:1px solid rgba(79,110,247,0.15);border-left:3px solid #4f6ef7;border-radius:12px;padding:14px 16px">
                <p style="margin:0;color:var(--text);font-size:0.92rem;line-height:1.6"><?= nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8')) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <!-- PROFILE -->
      <section class="dashboard-section" id="section-profile">
        <div class="section-header"><div><p class="section-kicker">Account</p><h2>My Profile</h2></div></div>
        <article class="panel">
          <div style="display:grid;gap:20px;max-width:500px;">
            <div>
              <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Full Name</label>
              <div style="padding:12px 16px;background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.2);border-radius:12px;font-weight:500;"><?= htmlspecialchars($user['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
              <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Email</label>
              <div style="padding:12px 16px;background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.2);border-radius:12px;font-weight:500;"><?= htmlspecialchars($user['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
              <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Phone</label>
              <div style="padding:12px 16px;background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.2);border-radius:12px;font-weight:500;"><?= htmlspecialchars($user['phone'] ?? $profile['phone'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
              <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Current Location</label>
              <div style="padding:12px 16px;background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.2);border-radius:12px;font-weight:500;"><?= htmlspecialchars($user['location'] ?? $profile['address'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
              <label style="display:block;font-size:0.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Member since</label>
              <div style="padding:12px 16px;background:rgba(15,23,42,0.03);border:1px solid rgba(148,163,184,0.2);border-radius:12px;font-weight:500;"><?= date('F j, Y', strtotime($user['created_at'] ?? 'now')) ?></div>
            </div>
          </div>
        </article>
      </section>

    </main>
  </div>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <script src="script.js"></script>
  <script>
  window.__CUSTOMER_DATA__ = {
    monthLabels: <?= json_encode($myMonthLabels) ?>,
    monthData: <?= json_encode($myMonthData) ?>,
    statusLabels: ['Pending', 'Read', 'Responded', 'Closed'],
    statusData: <?= json_encode(array_values($statusCounts)) ?>
  };
  </script>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    if(typeof Chart==='undefined')return;
    var cFont={family:'Inter',size:11,weight:'500'};
    var gridC='rgba(148,163,184,0.1)';
    var D = window.__CUSTOMER_DATA__ || {};

    /* My Inquiry Activity (last 6 months) */
    var actCtx=document.getElementById('myActivityChart');
    if(actCtx){
      new Chart(actCtx.getContext('2d'),{
        type:'bar',
        data:{
          labels:D.monthLabels||[],
          datasets:[{
            label:'My inquiries',
            data:D.monthData||[],
            backgroundColor:function(ctx){
              var chart=ctx.chart;
              var area=chart.chartArea;
              if(!area)return 'rgba(79,110,247,0.7)';
              var gradient=chart.ctx.createLinearGradient(0,area.bottom,0,area.top);
              gradient.addColorStop(0,'rgba(79,110,247,0.3)');
              gradient.addColorStop(1,'rgba(79,110,247,0.85)');
              return gradient;
            },
            borderRadius:8,borderSkipped:false,maxBarThickness:32
          }]
        },
        options:{responsive:true,maintainAspectRatio:false,
          plugins:{legend:{display:false}},
          scales:{x:{grid:{display:false},ticks:{font:cFont,color:'#5f6f88'}},y:{beginAtZero:true,grid:{color:gridC},ticks:{font:cFont,color:'#5f6f88',stepSize:1}}}
        }
      });
    }

    /* My Requests by Status */
    var pieCtx=document.getElementById('myStatusChart');
    if(pieCtx){
      new Chart(pieCtx.getContext('2d'),{
        type:'doughnut',
        data:{
          labels:D.statusLabels||[],
          datasets:[{data:D.statusData||[],backgroundColor:['#f5ad3d','#06b6d4','#25c18d','#9ca3af'],borderWidth:0,spacing:3,borderRadius:6}]
        },
        options:{responsive:true,maintainAspectRatio:false,cutout:'62%',
          plugins:{legend:{display:true,position:'bottom',labels:{font:{family:'Inter',size:10,weight:'500'},color:'#5f6f88',padding:10,usePointStyle:true,pointStyleWidth:8}}}
        }
      });
    }
  });
  </script>
</body>
</html>
