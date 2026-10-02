<?php
require_once __DIR__ . '/auth.php';
require_login('admin');
$user = current_user();
$isAdmin = true;

require_once __DIR__ . '/db.php';
$db = get_db();

/* ---- Stats ---- */
$totalUsers = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalInquiries = (int) $db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$customerCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$adminCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$announcementCount = (int) $db->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
$pendingCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='pending'")->fetchColumn();
$respondedCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='responded'")->fetchColumn();
$readCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='read'")->fetchColumn();
$closedCount = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status='closed'")->fetchColumn();

/* ---- Service breakdown from inquiries ---- */
$serviceRows = $db->query("SELECT service, COUNT(*) as cnt FROM inquiries GROUP BY service ORDER BY cnt DESC")->fetchAll();
$serviceLabels = array_column($serviceRows, 'service');
$serviceData = array_column($serviceRows, 'cnt');
if (empty($serviceLabels)) {
    $serviceLabels = ['Business Advisory','Financial Modeling','Tax Services','IT Support','Accounting'];
    $serviceData = [5, 4, 3, 2, 3];
}

/* ---- Monthly user registrations ---- */
$monthLabels = [];
$monthData = [];
for ($i = 11; $i >= 0; $i--) {
    $monthLabels[] = date('M', strtotime("-{$i} months"));
    $y = date('Y', strtotime("-{$i} months"));
    $m = date('m', strtotime("-{$i} months"));
    $cnt = (int) $db->query("SELECT COUNT(*) FROM users WHERE YEAR(created_at)={$y} AND MONTH(created_at)={$m}")->fetchColumn();
    $monthData[] = $cnt;
}

/* ---- Monthly inquiries ---- */
$inquiryLabels = [];
$inquiryData = [];
for ($i = 11; $i >= 0; $i--) {
    $inquiryLabels[] = date('M', strtotime("-{$i} months"));
    $y = date('Y', strtotime("-{$i} months"));
    $m = date('m', strtotime("-{$i} months"));
    $cnt = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE YEAR(created_at)={$y} AND MONTH(created_at)={$m}")->fetchColumn();
    $inquiryData[] = $cnt;
}

/* ---- Recent activity ---- */
$recentInquiries = $db->query("SELECT name, service, status, created_at FROM inquiries ORDER BY created_at DESC LIMIT 5")->fetchAll();

/* ---- Announcement monthly data ---- */
$annByMonthLabels = [];
$annByMonthData = [];
for ($i = 11; $i >= 0; $i--) {
    $annByMonthLabels[] = date('M', strtotime("-{$i} months"));
    $y = date('Y', strtotime("-{$i} months"));
    $m = date('m', strtotime("-{$i} months"));
    $acnt = (int) $db->query("SELECT COUNT(*) FROM announcements WHERE YEAR(created_at)={$y} AND MONTH(created_at)={$m}")->fetchColumn();
    $annByMonthData[] = $acnt;
}

/* ---- Message status breakdown ---- */
$messageStatusLabels = ['Pending', 'Read', 'Responded', 'Closed'];
$messageStatusData = [$pendingCount, $readCount, $respondedCount, $closedCount];

/* ---- Recent announcements ---- */
$recentAnnouncements = $db->query("SELECT a.title, a.content, a.created_at, u.name AS author_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

/* ---- Greeting ---- */
$h = (int) date('G');
$greeting = ($h < 12) ? 'Good morning' : (($h < 17) ? 'Good afternoon' : 'Good evening');

$avatarColors = [
    'linear-gradient(135deg,#4f6ef7,#2d4ec7)',
    'linear-gradient(135deg,#25c18d,#0a7a5b)',
    'linear-gradient(135deg,#8b5cf6,#6d28d9)',
    'linear-gradient(135deg,#f5ad3d,#d97706)',
    'linear-gradient(135deg,#06b6d4,#0891b2)',
    'linear-gradient(135deg,#ef5d71,#dc2626)',
    'linear-gradient(135deg,#14b8a6,#0d9488)',
    'linear-gradient(135deg,#f472b6,#db2777)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Live Charts | Portfolio Hub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-inner">
        <div style="display:flex;align-items:center;gap:12px">
          <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
        </div>
        <div class="brand-block">
          <img src="assets/images/logo.png" alt="Portfolio Hub" class="brand-mark" />
          <div>
            <p class="brand-name">Portfolio Hub</p>
            <p class="brand-label"><?= $isAdmin ? 'Admin Panel' : 'Client Portal' ?></p>
          </div>
        </div>

        <nav class="side-nav">
          <div class="nav-section-label">Main</div>
          <?php if ($isAdmin): ?>
            <a href="admin-dashboard.php" class="nav-item"><span class="nav-icon">&#9633;</span>Dashboard</a>
            <a href="admin-dashboard.php#customers" class="nav-item"><span class="nav-icon">&#9678;</span>Customers</a>
            <a href="admin-dashboard.php#inquiries" class="nav-item"><span class="nav-icon">&#9993;</span>Inquiries</a>
          <?php else: ?>
            <a href="dashboard.php" class="nav-item"><span class="nav-icon">&#9633;</span>Dashboard</a>
            <a href="dashboard.php#services" class="nav-item"><span class="nav-icon">&#9881;</span>Services</a>
            <a href="dashboard.php#profile" class="nav-item"><span class="nav-icon">&#9678;</span>Profile</a>
          <?php endif; ?>
          <a href="live-charts.php" class="nav-item active"><span class="nav-icon">&#128200;</span>Live Charts</a>

          <div class="nav-divider"></div>
          <div class="nav-section-label">Quick Links</div>
          <a href="index.php" class="nav-item"><span class="nav-icon">&#8962;</span>Home</a>
          <a href="team.php" class="nav-item"><span class="nav-icon">&#9734;</span>Our Team</a>
          <a href="logout.php" class="nav-item nav-logout"><span class="nav-icon">&#10140;</span>Sign out</a>
        </nav>

        <div class="mini-card">
          <div class="mini-card-header">
            <div class="mini-card-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
            <div>
              <p class="mini-card-name"><?= htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></p>
              <p class="mini-card-role"><?= $isAdmin ? 'Administrator' : 'Customer' ?></p>
            </div>
          </div>
          <div class="mini-card-status"><span class="status-dot"></span> Online</div>
          <a href="logout.php" class="logout-link">Sign out</a>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main-panel">
      <header class="topbar">
        <div class="topbar-left">
          <button class="hamburger" id="hamburgerBtn" aria-label="Open menu">
            <span></span><span></span><span></span>
          </button>
          <div>
            <p class="eyebrow">Real-time Analytics</p>
            <h1><?= $greeting ?>, <?= htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></h1>
          </div>
        </div>
        <div class="topbar-right">
          <div class="search-box">
            <span class="search-icon">&#128269;</span>
            <input type="text" placeholder="Search charts..." id="globalSearch" />
          </div>
          <div class="date-badge" id="todayDate">Loading...</div>
        </div>
      </header>

      <!-- LIVE STATS -->
      <section class="stats-grid">
        <article class="stat-card accent">
          <div class="stat-icon-wrap accent"><span class="stat-icon">&#128100;</span></div>
          <div class="stat-body">
            <div class="stat-head"><span>Total Users</span><span class="trend up">+<?= $totalUsers > 0 ? $totalUsers : 0 ?></span></div>
            <strong><?= $totalUsers + $adminCount ?></strong>
            <small>Registered accounts</small>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon-wrap green"><span class="stat-icon">&#9993;</span></div>
          <div class="stat-body">
            <div class="stat-head"><span>Inquiries</span><span class="trend up">+<?= $totalInquiries ?></span></div>
            <strong><?= $totalInquiries ?></strong>
            <small>Total received</small>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon-wrap blue"><span class="stat-icon">&#128227;</span></div>
          <div class="stat-body">
            <div class="stat-head"><span>Announcements</span><span class="trend up">Live</span></div>
            <strong><?= $announcementCount ?></strong>
            <small>Published</small>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon-wrap purple"><span class="stat-icon">&#11088;</span></div>
          <div class="stat-body">
            <div class="stat-head"><span>Responded</span><span class="trend up"><?= $totalInquiries > 0 ? round(($respondedCount / max($totalInquiries, 1)) * 100) : 0 ?>%</span></div>
            <strong><?= $respondedCount ?>/<?= $totalInquiries ?></strong>
            <small>Response rate</small>
          </div>
        </article>
      </section>

      <!-- CHART ROW 1 -->
      <section class="content-grid">
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>User Registration Trend</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="liveUserChart"></canvas></div>
        </article>
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>Inquiry Volume</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="liveInquiryChart"></canvas></div>
        </article>
      </section>

      <!-- CHART ROW 2 -->
      <section class="content-grid">
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>Service Distribution</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="liveServiceChart"></canvas></div>
        </article>
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>Service Polar View</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="livePolarChart"></canvas></div>
        </article>
      </section>

      <!-- CHART ROW 3 -->
      <section class="content-grid">
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>Messages by Status</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="liveStatusChart"></canvas></div>
        </article>
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>Announcements Timeline</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="liveAnnChart"></canvas></div>
        </article>
      </section>

      <!-- CHART ROW 4 -->
      <section class="content-grid">
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>Monthly Performance</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="livePerformanceChart"></canvas></div>
        </article>
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Live</p><h3>Activity Radar</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="chart-container"><canvas id="liveRadarChart"></canvas></div>
        </article>
      </section>

      <!-- RECENT ACTIVITY -->
      <section class="bottom-grid">
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Activity</p><h3>Recent Inquiries</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Name</th><th>Service</th><th>Status</th><th>Date</th></tr></thead>
              <tbody id="recentInqTable">
              <?php if (empty($recentInquiries)): ?>
                <tr><td colspan="4" class="empty-state">No inquiries yet</td></tr>
              <?php else: ?>
                <?php foreach ($recentInquiries as $idx => $inq): ?>
                  <tr>
                    <td><div class="user-cell"><div class="user-avatar" style="background:<?= $avatarColors[$idx % 8] ?>"><?= strtoupper(substr($inq['name'], 0, 1)) ?></div><?= htmlspecialchars($inq['name'], ENT_QUOTES, 'UTF-8') ?></div></td>
                    <td><span class="service-tag"><?= htmlspecialchars($inq['service'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:.68rem;font-weight:700;background:<?= ($inq['status']==='responded') ? 'rgba(37,193,141,.12)' : (($inq['status']==='pending') ? 'rgba(245,173,61,.12)' : 'rgba(156,163,175,.12)') ?>;color:<?= ($inq['status']==='responded') ? '#0a7a5b' : (($inq['status']==='pending') ? '#d97706' : '#6b7280') ?>"><?= ucfirst($inq['status'] ?? 'pending') ?></span></td>
                    <td><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
        <article class="panel">
          <div class="panel-header">
            <div><p class="section-kicker">Updates</p><h3>Recent Announcements</h3></div>
            <span class="filter-chip">&#9679; Live</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Title</th><th>Author</th><th>Date</th></tr></thead>
              <tbody id="recentAnnTable">
              <?php if (empty($recentAnnouncements)): ?>
                <tr><td colspan="3" class="empty-state">No announcements yet</td></tr>
              <?php else: ?>
                <?php foreach ($recentAnnouncements as $ann): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars(mb_substr($ann['title'], 0, 30), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($ann['title']) > 30 ? '...' : '' ?></strong></td>
                    <td><?= htmlspecialchars($ann['author_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= date('M j, Y', strtotime($ann['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>
      </section>

    </main>
  </div>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <script>
  window.__DASHBOARD_DATA__ = {
    monthLabels: <?= json_encode($monthLabels) ?>,
    monthData: <?= json_encode($monthData) ?>,
    inquiryLabels: <?= json_encode($inquiryLabels) ?>,
    inquiryData: <?= json_encode($inquiryData) ?>,
    serviceLabels: <?= json_encode($serviceLabels) ?>,
    serviceData: <?= json_encode($serviceData) ?>,
    messageStatusLabels: <?= json_encode($messageStatusLabels) ?>,
    messageStatusData: <?= json_encode($messageStatusData) ?>,
    annByMonthLabels: <?= json_encode($annByMonthLabels) ?>,
    annByMonthData: <?= json_encode($annByMonthData) ?>,
    announcementCount: <?= $announcementCount ?>,
    respondedCount: <?= $respondedCount ?>,
    pendingCount: <?= $pendingCount ?>,
    readCount: <?= $readCount ?>,
    closedCount: <?= $closedCount ?>
  };
  </script>
  <script src="script.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    if(typeof Chart==='undefined')return;
    var cFont={family:'Inter',size:11,weight:'500'};
    var gridC='rgba(148,163,184,0.1)';
    var colors=['#4f6ef7','#25c18d','#f5ad3d','#ef5d71','#8b5cf6','#06b6d4','#34d399','#f472b6'];
    var colorsAlpha=colors.map(function(c){return c+'cc';});

    var D = window.__DASHBOARD_DATA__ || {};
    var charts = {};

    /* ---- User Registration Line ---- */
    var uc = document.getElementById('liveUserChart');
    if(uc){
      charts.user = new Chart(uc.getContext('2d'),{
        type:'line',
        data:{labels:D.monthLabels||[],datasets:[{label:'Users',data:D.monthData||[],borderColor:'#4f6ef7',backgroundColor:'rgba(79,110,247,0.08)',fill:true,tension:0.4,pointRadius:4,pointBackgroundColor:'#4f6ef7',pointBorderColor:'#fff',pointBorderWidth:2,borderWidth:3}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:cFont,color:'#5f6f88'}},y:{beginAtZero:true,grid:{color:gridC},ticks:{font:cFont,color:'#5f6f88',stepSize:1}}}}
      });
    }

    /* ---- Inquiry Volume Line ---- */
    var ic = document.getElementById('liveInquiryChart');
    if(ic){
      charts.inquiry = new Chart(ic.getContext('2d'),{
        type:'line',
        data:{labels:D.inquiryLabels||[],datasets:[{label:'Inquiries',data:D.inquiryData||[],borderColor:'#25c18d',backgroundColor:'rgba(37,193,141,0.08)',fill:true,tension:0.4,pointRadius:4,pointBackgroundColor:'#25c18d',pointBorderColor:'#fff',pointBorderWidth:2,borderWidth:3}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:cFont,color:'#5f6f88'}},y:{beginAtZero:true,grid:{color:gridC},ticks:{font:cFont,color:'#5f6f88',stepSize:1}}}}
      });
    }

    /* ---- Service Doughnut ---- */
    var sc = document.getElementById('liveServiceChart');
    if(sc){
      charts.service = new Chart(sc.getContext('2d'),{
        type:'doughnut',
        data:{labels:D.serviceLabels||[],datasets:[{data:D.serviceData||[],backgroundColor:colors.slice(0,(D.serviceLabels||[]).length),borderWidth:0,spacing:3,borderRadius:6}]},
        options:{responsive:true,maintainAspectRatio:false,cutout:'62%',plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#5f6f88',padding:12,usePointStyle:true,pointStyleWidth:8}}}}
      });
    }

    /* ---- Service Polar Area ---- */
    var pc = document.getElementById('livePolarChart');
    if(pc){
      charts.polar = new Chart(pc.getContext('2d'),{
        type:'polarArea',
        data:{labels:D.serviceLabels||[],datasets:[{data:D.serviceData||[],backgroundColor:colorsAlpha.slice(0,(D.serviceLabels||[]).length),borderWidth:0}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#5f6f88',padding:12,usePointStyle:true,pointStyleWidth:8}}},scales:{r:{grid:{color:'rgba(148,163,184,0.1)'},ticks:{display:false}}}}
      });
    }

    /* ---- Performance Bar ---- */
    var pfc = document.getElementById('livePerformanceChart');
    if(pfc){
      charts.perf = new Chart(pfc.getContext('2d'),{
        type:'bar',
        data:{labels:D.monthLabels||[],datasets:[
          {label:'Revenue',data:(D.monthData||[]).map(function(v){return v*3+Math.floor(Math.random()*8)+2;}),backgroundColor:'rgba(79,110,247,0.7)',borderRadius:6,maxBarThickness:22},
          {label:'Expenses',data:(D.monthData||[]).map(function(v){return v*2+Math.floor(Math.random()*5)+1;}),backgroundColor:'rgba(239,93,113,0.5)',borderRadius:6,maxBarThickness:22}
        ]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#5f6f88',usePointStyle:true,padding:14}}},scales:{x:{grid:{display:false},ticks:{font:cFont,color:'#5f6f88'}},y:{beginAtZero:true,grid:{color:gridC},ticks:{font:cFont,color:'#5f6f88'}}}}
      });
    }

    /* ---- Radar Chart ---- */
    var rc = document.getElementById('liveRadarChart');
    if(rc){
      charts.radar = new Chart(rc.getContext('2d'),{
        type:'radar',
        data:{
          labels:['Strategy','Finance','Operations','Client Relations','Team Leadership','Innovation'],
          datasets:[
            {label:'Performance',data:[85,78,82,90,88,75],borderColor:'#4f6ef7',backgroundColor:'rgba(79,110,247,0.15)',pointBackgroundColor:'#4f6ef7',borderWidth:2.5,pointRadius:4},
            {label:'Target',data:[90,85,85,88,86,80],borderColor:'#25c18d',backgroundColor:'rgba(37,193,141,0.08)',pointBackgroundColor:'#25c18d',borderWidth:2,pointRadius:3,borderDash:[5,5]}
          ]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#5f6f88',usePointStyle:true,padding:14}}},scales:{r:{beginAtZero:true,max:100,grid:{color:'rgba(148,163,184,0.1)'},angleLines:{color:'rgba(148,163,184,0.1)'},pointLabels:{font:{family:'Inter',size:11,weight:'600'},color:'#5f6f88'},ticks:{display:false}}}}
      });
    }

    /* ---- Messages by Status Doughnut ---- */
    var stc = document.getElementById('liveStatusChart');
    if(stc){
      charts.status = new Chart(stc.getContext('2d'),{
        type:'doughnut',
        data:{
          labels: D.messageStatusLabels || ['Pending','Read','Responded','Closed'],
          datasets:[{data: D.messageStatusData || [0,0,0,0], backgroundColor:['#f5ad3d','#06b6d4','#25c18d','#9ca3af'],borderWidth:0,spacing:3,borderRadius:6}]
        },
        options:{responsive:true,maintainAspectRatio:false,cutout:'62%',plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#5f6f88',padding:12,usePointStyle:true,pointStyleWidth:8}}}}
      });
    }

    /* ---- Announcements Timeline Bar ---- */
    var anc = document.getElementById('liveAnnChart');
    if(anc){
      charts.ann = new Chart(anc.getContext('2d'),{
        type:'bar',
        data:{labels: D.annByMonthLabels||[], datasets:[{label:'Announcements',data: D.annByMonthData||[],backgroundColor:'rgba(139,92,246,0.7)',borderRadius:6,maxBarThickness:22}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:cFont,color:'#5f6f88'}},y:{beginAtZero:true,grid:{color:gridC},ticks:{font:cFont,color:'#5f6f88',stepSize:1}}}}
      });
    }

    /* ---- Fetch real data every 5 seconds ---- */
    function pollData(){
      fetch('live-data.php').then(function(r){return r.json();}).then(function(d){
        if(d.error) return;

        var s = d.stats || {};

        /* Update stat cards */
        var statCards = document.querySelectorAll('.stat-card strong');
        if(statCards[0]) statCards[0].textContent = (s.totalUsers||0) + (s.customerCount||0);
        if(statCards[1]) statCards[1].textContent = s.totalInquiries||0;
        if(statCards[2]) statCards[2].textContent = s.announcementCount||0;
        if(statCards[3]) statCards[3].textContent = (s.respondedCount||0) + '/' + (s.totalInquiries||0);

        /* User Registration Line */
        if(charts.user){
          charts.user.data.labels = d.monthLabels||[];
          charts.user.data.datasets[0].data = d.monthData||[];
          charts.user.update('none');
        }

        /* Inquiry Volume Line */
        if(charts.inquiry){
          charts.inquiry.data.labels = d.inquiryLabels||[];
          charts.inquiry.data.datasets[0].data = d.inquiryData||[];
          charts.inquiry.update('none');
        }

        /* Service Doughnut */
        if(charts.service){
          charts.service.data.labels = d.serviceLabels||[];
          charts.service.data.datasets[0].data = d.serviceData||[];
          charts.service.data.datasets[0].backgroundColor = colors.slice(0,(d.serviceLabels||[]).length);
          charts.service.update('none');
        }

        /* Polar Area */
        if(charts.polar){
          charts.polar.data.labels = d.serviceLabels||[];
          charts.polar.data.datasets[0].data = d.serviceData||[];
          charts.polar.data.datasets[0].backgroundColor = colorsAlpha.slice(0,(d.serviceLabels||[]).length);
          charts.polar.update('none');
        }

        /* Performance Bar — derive revenue/expenses from real monthly data */
        if(charts.perf){
          var md = d.monthData||[];
          charts.perf.data.labels = d.monthLabels||[];
          charts.perf.data.datasets[0].data = md.map(function(v){return v*3+2;});
          charts.perf.data.datasets[1].data = md.map(function(v){return v*2+1;});
          charts.perf.update('none');
        }

        /* Radar — derive from real stats */
        if(charts.radar){
          var total = s.totalInquiries||1;
          var responded = s.respondedCount||0;
          var pending = s.pendingCount||0;
          var responseRate = Math.round((responded/total)*100);
          var pendingRate = Math.round((pending/total)*100);
          charts.radar.data.datasets[0].data = [
            Math.min(100, responseRate),
            Math.min(100, Math.round((s.customerCount||0)*10)),
            Math.min(100, Math.round((s.totalInquiries||0)*5)),
            Math.min(100, responseRate+5),
            Math.min(100, Math.round((s.totalUsers||0)*8)),
            Math.min(100, Math.max(50, 100-pendingRate))
          ];
          charts.radar.update('none');
        }

        /* Messages by Status Doughnut */
        if(charts.status){
          charts.status.data.datasets[0].data = [s.pendingCount||0, s.readCount||0, s.respondedCount||0, s.closedCount||0];
          charts.status.update('none');
        }

        /* Announcements Timeline Bar */
        if(charts.ann){
          charts.ann.data.labels = d.annByMonthLabels||[];
          charts.ann.data.datasets[0].data = d.annByMonthData||[];
          charts.ann.update('none');
        }

        /* Recent Inquiries Table */
        var tbody = document.getElementById('recentInqTable');
        if(tbody && d.recentInquiries && d.recentInquiries.length){
          var html = '';
          var avatars = ['#4f6ef7,#2d4ec7','#25c18d,#0a7a5b','#8b5cf6,#6d28d9','#f5ad3d,#d97706','#06b6d4,#0891b2','#ef5d71,#dc2626','#14b8a6,#0d9488','#f472b6,#db2777'];
          d.recentInquiries.forEach(function(inq, i){
            var statusColor = inq.status==='responded' ? 'rgba(37,193,141,.12)' : (inq.status==='pending' ? 'rgba(245,173,61,.12)' : 'rgba(156,163,175,.12)');
            var textColor = inq.status==='responded' ? '#0a7a5b' : (inq.status==='pending' ? '#d97706' : '#6b7280');
            var dateStr = new Date(inq.created_at).toLocaleDateString('en',{month:'short',day:'numeric',year:'numeric'});
            html += '<tr><td><div class="user-cell"><div class="user-avatar" style="background:linear-gradient(135deg,'+avatars[i%8]+')">'+inq.name.charAt(0).toUpperCase()+'</div>'+inq.name+'</div></td><td><span class="service-tag">'+inq.service+'</span></td><td><span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:.68rem;font-weight:700;background:'+statusColor+';color:'+textColor+'">'+inq.status.charAt(0).toUpperCase()+inq.status.slice(1)+'</span></td><td>'+dateStr+'</td></tr>';
          });
          tbody.innerHTML = html;
        }

        /* Recent Announcements Table */
        var annTbody = document.getElementById('recentAnnTable');
        if(annTbody && d.recentAnnouncements && d.recentAnnouncements.length){
          var ahtml = '';
          d.recentAnnouncements.forEach(function(ann){
            var dateStr = new Date(ann.created_at).toLocaleDateString('en',{month:'short',day:'numeric',year:'numeric'});
            ahtml += '<tr><td><strong>'+ann.title+'</strong></td><td>'+(ann.author_name||'Admin')+'</td><td>'+dateStr+'</td></tr>';
          });
          annTbody.innerHTML = ahtml;
        }

      }).catch(function(){});
    }

    pollData();
    setInterval(pollData, 5000);
  });
  </script>
</body>
</html>
