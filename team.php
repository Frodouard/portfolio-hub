<?php
require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Leadership Team | Portfolio Hub</title>
  <meta name="description" content="Meet the leadership team at Portfolio Hub - Chief of Staff and Directors driving business advisory excellence." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="assets/css/style.css" />
  <style>
    .team-hero{background:linear-gradient(135deg,#0a1628 0%,#12233d 50%,#0a1628 100%);padding:100px 0 60px;text-align:center}
    .team-hero h1{margin:0 0 16px;font-size:clamp(2.2rem,5vw,3.6rem);font-weight:900;color:#fff;letter-spacing:-0.04em;line-height:1.15}
    .team-hero h1 span{color:#7ba3ff}
    .team-hero p{margin:0 auto;max-width:620px;color:#b7c2d9;font-size:1.1rem;line-height:1.6}

    .team-section{padding:80px 0;background:#f3f4f7}
    .team-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;max-width:1100px;margin:0 auto;padding:0 20px}

    .team-card{background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04);transition:transform .3s,box-shadow .3s}
    .team-card:hover{transform:translateY(-6px);box-shadow:0 12px 40px rgba(0,0,0,.1)}

    .card-top{position:relative;height:180px;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .card-top::before{content:'';position:absolute;inset:0;z-index:0}
    .card-top.blue::before{background:linear-gradient(135deg,#4f6ef7,#2d4ec7)}
    .card-top.green::before{background:linear-gradient(135deg,#25c18d,#0a7a5b)}
    .card-top.purple::before{background:linear-gradient(135deg,#8b5cf6,#6d28d9)}

    .card-avatar{position:relative;z-index:1;width:110px;height:110px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.8rem;font-weight:900;color:#fff;border:4px solid rgba(255,255,255,.3);box-shadow:0 8px 24px rgba(0,0,0,.2)}
    .card-avatar.blue{background:rgba(79,110,247,.9)}
    .card-avatar.green{background:rgba(37,193,141,.9)}
    .card-avatar.purple{background:rgba(139,92,246,.9)}

    .card-badge{position:absolute;top:16px;right:16px;z-index:2;background:rgba(255,255,255,.2);backdrop-filter:blur(10px);color:#fff;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;padding:5px 12px;border-radius:999px}

    .card-body{padding:28px 24px 32px;text-align:center}
    .card-body h2{margin:0 0 4px;font-size:1.3rem;font-weight:800;color:#111827;letter-spacing:-0.02em}
    .card-role{display:inline-block;margin:0 0 20px;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:5px 14px;border-radius:999px}
    .card-role.blue{background:rgba(79,110,247,.1);color:#4f6ef7}
    .card-role.green{background:rgba(37,193,141,.1);color:#0a7a5b}
    .card-role.purple{background:rgba(139,92,246,.1);color:#7c3aed}

    .card-contact{display:flex;flex-direction:column;gap:10px;margin-top:8px}
    .contact-row{display:flex;align-items:center;justify-content:center;gap:10px;padding:12px 16px;background:#f8f9fc;border-radius:12px;border:1px solid rgba(0,0,0,.04);transition:background .2s}
    .contact-row:hover{background:#eef1f7}
    .contact-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
    .contact-icon.phone{background:rgba(37,193,141,.12);color:#0a7a5b}
    .contact-icon.email{background:rgba(79,110,247,.12);color:#4f6ef7}
    .contact-info{text-align:left}
    .contact-label{display:block;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:2px}
    .contact-value{display:block;font-size:.88rem;font-weight:600;color:#111827}

    /* LIVE CHARTS SECTION */
    .charts-section{background:#0a1628;padding:80px 0}
    .charts-header{text-align:center;margin-bottom:48px}
    .charts-header h2{margin:0 0 12px;font-size:clamp(1.8rem,3vw,2.6rem);font-weight:900;color:#fff;letter-spacing:-0.03em}
    .charts-header h2 span{color:#7ba3ff}
    .charts-header p{margin:0 auto;max-width:520px;color:#b7c2d9;font-size:1rem}

    .charts-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:1100px;margin:0 auto 40px;padding:0 20px}
    .chart-panel{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:24px;backdrop-filter:blur(10px)}
    .chart-panel h3{margin:0 0 4px;color:#fff;font-size:1.1rem;font-weight:700}
    .chart-panel .panel-sub{margin:0 0 20px;color:#7b8ba8;font-size:.82rem}
    .chart-canvas-wrap{position:relative;height:280px}
    .chart-canvas-wrap canvas{width:100%!important;height:100%!important}

    .chart-panel-full{grid-column:1/-1}

    /* LIVE STATS BAR */
    .live-stats-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;max-width:1100px;margin:0 auto;padding:0 20px}
    .live-stat-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:22px 20px;text-align:center;transition:transform .2s,background .2s}
    .live-stat-card:hover{transform:translateY(-3px);background:rgba(255,255,255,.1)}
    .live-stat-icon{font-size:1.6rem;margin-bottom:8px}
    .live-stat-value{display:block;font-size:1.8rem;font-weight:900;color:#fff;letter-spacing:-0.04em}
    .live-stat-label{display:block;margin-top:4px;font-size:.72rem;font-weight:600;color:#7b8ba8;text-transform:uppercase;letter-spacing:.08em}
    .live-stat-change{display:inline-block;margin-top:8px;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:999px}
    .live-stat-change.up{background:rgba(37,193,141,.15);color:#34d399}
    .live-stat-change.down{background:rgba(239,93,113,.15);color:#f87171}

    .live-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(37,193,141,.15);color:#34d399;font-size:.72rem;font-weight:700;padding:5px 12px;border-radius:999px;margin-bottom:12px}
    .live-badge::before{content:'';width:7px;height:7px;border-radius:50%;background:#34d399;animation:pulse 2s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}

    /* Director Performance Cards */
    .dir-perf-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:1100px;margin:40px auto 0;padding:0 20px}
    .dir-perf-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:24px;text-align:center;transition:transform .3s}
    .dir-perf-card:hover{transform:translateY(-4px)}
    .dir-perf-avatar{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:800;color:#fff;margin:0 auto 14px;border:3px solid rgba(255,255,255,.2)}
    .dir-perf-avatar.blue{background:rgba(79,110,247,.8)}
    .dir-perf-avatar.green{background:rgba(37,193,141,.8)}
    .dir-perf-avatar.purple{background:rgba(139,92,246,.8)}
    .dir-perf-card h4{margin:0 0 4px;color:#fff;font-size:1rem;font-weight:700}
    .dir-perf-card .dir-role{margin:0 0 16px;font-size:.72rem;color:#7b8ba8;text-transform:uppercase;letter-spacing:.08em;font-weight:600}
    .dir-metrics{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .dir-metric{background:rgba(255,255,255,.05);border-radius:10px;padding:10px 8px}
    .dir-metric strong{display:block;font-size:1.1rem;color:#fff;font-weight:800}
    .dir-metric span{display:block;font-size:.65rem;color:#7b8ba8;margin-top:2px}

    .team-footer{background:#050910;padding:40px 0;text-align:center;color:#b7c2d9;font-size:.88rem}
    .team-footer a{color:#7ba3ff;text-decoration:none;font-weight:600}
    .team-footer a:hover{text-decoration:underline}

    @media(max-width:900px){
      .team-grid,.dir-perf-grid{grid-template-columns:1fr;max-width:420px}
      .charts-grid{grid-template-columns:1fr}
      .live-stats-bar{grid-template-columns:repeat(2,1fr)}
      .card-top{height:140px}
      .card-avatar{width:90px;height:90px;font-size:2.2rem}
    }
    @media(max-width:480px){
      .live-stats-bar{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <div class="topbar-wrap">
    <header class="site-header container">
      <div class="brand-group">
        <img src="assets/images/logo.png" alt="Portfolio Hub" class="brand-mark" />
        <div class="brand-text">
          <p class="brand-name">Portfolio Hub</p>
          <p class="brand-tag">Beyond compliance, we build trust</p>
        </div>
      </div>
      <nav class="main-nav" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="team.php" style="color:#7ba3ff;">Our Team</a>
        <a href="register.php">Register</a>
        <a href="login.php">Sign in</a>
      </nav>
      <a class="nav-button" href="register.php">Register</a>
    </header>
  </div>

  <!-- HERO -->
  <section class="team-hero">
    <div class="container">
      <h1>Meet Our <span>Leadership</span></h1>
      <p>The experienced directors guiding Portfolio Hub with vision, integrity, and deep expertise in business advisory and financial services.</p>
    </div>
  </section>

  <!-- TEAM CARDS -->
  <section class="team-section">
    <div class="team-grid">
      <article class="team-card">
        <div class="card-top blue"><span class="card-badge">Director</span><div class="card-avatar blue">XN</div></div>
        <div class="card-body">
          <h2>Xavier Nshimiyimana</h2>
          <span class="card-role blue">Chief of Staff / Director</span>
          <div class="card-contact">
            <div class="contact-row"><div class="contact-icon phone">&#128222;</div><div class="contact-info"><span class="contact-label">Phone</span><span class="contact-value">+250 787 258 624</span></div></div>
            <a href="mailto:portfoliohubwanda@gmail.com" class="contact-row" style="text-decoration:none;color:inherit"><div class="contact-icon email">&#9993;</div><div class="contact-info"><span class="contact-label">Email</span><span class="contact-value">portfoliohubwanda@gmail.com</span></div></a>
          </div>
        </div>
      </article>
      <article class="team-card">
        <div class="card-top green"><span class="card-badge">Director</span><div class="card-avatar green">JB</div></div>
        <div class="card-body">
          <h2>Jean Baptiste Nkurunziza</h2>
          <span class="card-role green">Director</span>
          <div class="card-contact">
            <div class="contact-row"><div class="contact-icon phone">&#128222;</div><div class="contact-info"><span class="contact-label">Phone</span><span class="contact-value">+250 789 916 388</span></div></div>
            <a href="mailto:portfoliohubwanda@gmail.com" class="contact-row" style="text-decoration:none;color:inherit"><div class="contact-icon email">&#9993;</div><div class="contact-info"><span class="contact-label">Email</span><span class="contact-value">portfoliohubwanda@gmail.com</span></div></a>
          </div>
        </div>
      </article>
      <article class="team-card">
        <div class="card-top purple"><span class="card-badge">Director</span><div class="card-avatar purple">DN</div></div>
        <div class="card-body">
          <h2>Daniel Niyonyirimirimo</h2>
          <span class="card-role purple">Director</span>
          <div class="card-contact">
            <div class="contact-row"><div class="contact-icon phone">&#128222;</div><div class="contact-info"><span class="contact-label">Phone</span><span class="contact-value">+250 786 491 521</span></div></div>
            <a href="mailto:portfoliohubwanda@gmail.com" class="contact-row" style="text-decoration:none;color:inherit"><div class="contact-icon email">&#9993;</div><div class="contact-info"><span class="contact-label">Email</span><span class="contact-value">portfoliohubwanda@gmail.com</span></div></a>
          </div>
        </div>
      </article>
    </div>
  </section>

  <!-- LIVE CHARTS -->
  <section class="charts-section">
    <div class="charts-header">
      <span class="live-badge">Live Data</span>
      <h2>Chief of Staff <span>Performance</span></h2>
      <p>Real-time leadership metrics, team activity, and department performance tracked across all divisions.</p>
    </div>

    <!-- LIVE STATS BAR -->
    <div class="live-stats-bar">
      <div class="live-stat-card">
        <div class="live-stat-icon">&#128100;</div>
        <span class="live-stat-value" id="ls1">0</span>
        <span class="live-stat-label">Total Clients</span>
        <span class="live-stat-change up">+12% this month</span>
      </div>
      <div class="live-stat-card">
        <div class="live-stat-icon">&#128200;</div>
        <span class="live-stat-value" id="ls2">0</span>
        <span class="live-stat-label">Projects Active</span>
        <span class="live-stat-change up">+8 new</span>
      </div>
      <div class="live-stat-card">
        <div class="live-stat-icon">&#128176;</div>
        <span class="live-stat-value" id="ls3">0</span>
        <span class="live-stat-label">Revenue (RWF M)</span>
        <span class="live-stat-change up">+23% growth</span>
      </div>
      <div class="live-stat-card">
        <div class="live-stat-icon">&#11088;</div>
        <span class="live-stat-value" id="ls4">0</span>
        <span class="live-stat-label">Satisfaction %</span>
        <span class="live-stat-change up">Above target</span>
      </div>
    </div>

    <!-- CHARTS GRID -->
    <div class="charts-grid" style="margin-top:40px">
      <!-- Revenue Trend -->
      <div class="chart-panel">
        <h3>Revenue Trend</h3>
        <p class="panel-sub">Monthly revenue performance (RWF millions)</p>
        <div class="chart-canvas-wrap"><canvas id="revenueTrendChart"></canvas></div>
      </div>
      <!-- Client Growth -->
      <div class="chart-panel">
        <h3>Client Acquisition</h3>
        <p class="panel-sub">New clients onboarded per month</p>
        <div class="chart-canvas-wrap"><canvas id="clientGrowthChart"></canvas></div>
      </div>
      <!-- Department Performance Radar -->
      <div class="chart-panel chart-panel-full">
        <h3>Department Performance Overview</h3>
        <p class="panel-sub">Comparative radar analysis across all leadership departments</p>
        <div class="chart-canvas-wrap" style="height:340px"><canvas id="radarChart"></canvas></div>
      </div>
    </div>

    <!-- DIRECTOR PERFORMANCE CARDS -->
    <div class="dir-perf-grid">
      <div class="dir-perf-card">
        <div class="dir-perf-avatar blue">XN</div>
        <h4>Xavier Nshimiyimana</h4>
        <p class="dir-role">Chief of Staff / Director</p>
        <div class="dir-metrics">
          <div class="dir-metric"><strong id="xm1">0</strong><span>Projects Led</span></div>
          <div class="dir-metric"><strong id="xm2">0%</strong><span>Client Retention</span></div>
          <div class="dir-metric"><strong id="xm3">0</strong><span>Team Members</span></div>
          <div class="dir-metric"><strong id="xm4">0</strong><span>Years Experience</span></div>
        </div>
      </div>
      <div class="dir-perf-card">
        <div class="dir-perf-avatar green">JB</div>
        <h4>Jean Baptiste Nkurunziza</h4>
        <p class="dir-role">Director</p>
        <div class="dir-metrics">
          <div class="dir-metric"><strong id="jbm1">0</strong><span>Projects Led</span></div>
          <div class="dir-metric"><strong id="jbm2">0%</strong><span>Client Retention</span></div>
          <div class="dir-metric"><strong id="jbm3">0</strong><span>Team Members</span></div>
          <div class="dir-metric"><strong id="jbm4">0</strong><span>Years Experience</span></div>
        </div>
      </div>
      <div class="dir-perf-card">
        <div class="dir-perf-avatar purple">DN</div>
        <h4>Daniel Niyonyirimirimo</h4>
        <p class="dir-role">Director</p>
        <div class="dir-metrics">
          <div class="dir-metric"><strong id="dnm1">0</strong><span>Projects Led</span></div>
          <div class="dir-metric"><strong id="dnm2">0%</strong><span>Client Retention</span></div>
          <div class="dir-metric"><strong id="dnm3">0</strong><span>Team Members</span></div>
          <div class="dir-metric"><strong id="dnm4">0</strong><span>Years Experience</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="team-footer">
    <div class="container">
      <p style="margin:0 0 8px">&copy; 2026 Portfolio Hub. All rights reserved.</p>
      <p style="margin:0"><a href="index.php">&larr; Back to home</a> &nbsp;|&nbsp; <a href="register.php">Register</a></p>
    </div>
  </footer>

  <script>
  document.addEventListener('DOMContentLoaded',function(){
    /* -- Animated Counter -- */
    function animateVal(el,end,dur,suffix){
      suffix=suffix||'';
      var start=0,startTime=null;
      function step(ts){
        if(!startTime)startTime=ts;
        var p=Math.min((ts-startTime)/dur,1);
        var eased=1-Math.pow(1-p,3);
        var v=Math.floor(eased*end);
        el.textContent=v.toLocaleString()+suffix;
        if(p<1)requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    /* -- Live Stats -- */
    animateVal(document.getElementById('ls1'),248,1200,'');
    animateVal(document.getElementById('ls2'),36,1200,'');
    animateVal(document.getElementById('ls3'),42,1200,'');
    animateVal(document.getElementById('ls4'),96,1200,'%');

    /* -- Director Metrics -- */
    animateVal(document.getElementById('xm1'),45,1000,'');
    animateVal(document.getElementById('xm2'),97,1000,'%');
    animateVal(document.getElementById('xm3'),18,1000,'');
    animateVal(document.getElementById('xm4'),12,1000,'');

    animateVal(document.getElementById('jbm1'),38,1000,'');
    animateVal(document.getElementById('jbm2'),94,1000,'%');
    animateVal(document.getElementById('jbm3'),14,1000,'');
    animateVal(document.getElementById('jbm4'),10,1000,'');

    animateVal(document.getElementById('dnm1'),32,1000,'');
    animateVal(document.getElementById('dnm2'),95,1000,'%');
    animateVal(document.getElementById('dnm3'),12,1000,'');
    animateVal(document.getElementById('dnm4'),8,1000,'');

    /* -- Chart.js Config -- */
    if(typeof Chart==='undefined')return;
    var cFont={family:'Inter',size:11,weight:'500'};
    var gridColor='rgba(255,255,255,0.06)';
    var months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    /* Revenue Trend Line */
    new Chart(document.getElementById('revenueTrendChart').getContext('2d'),{
      type:'line',
      data:{
        labels:months,
        datasets:[
          {label:'Xavier',data:[2.1,2.4,2.8,3.2,3.5,3.9,4.2,4.8,5.1,5.6,6.0,6.5],borderColor:'#4f6ef7',backgroundColor:'rgba(79,110,247,0.08)',fill:true,tension:0.4,pointRadius:3,pointBackgroundColor:'#4f6ef7',borderWidth:2.5},
          {label:'Jean Baptiste',data:[1.8,2.0,2.2,2.6,2.9,3.1,3.4,3.8,4.0,4.3,4.6,5.0],borderColor:'#25c18d',backgroundColor:'rgba(37,193,141,0.08)',fill:true,tension:0.4,pointRadius:3,pointBackgroundColor:'#25c18d',borderWidth:2.5},
          {label:'Daniel',data:[1.5,1.7,1.9,2.2,2.5,2.7,3.0,3.3,3.5,3.8,4.1,4.4],borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.08)',fill:true,tension:0.4,pointRadius:3,pointBackgroundColor:'#8b5cf6',borderWidth:2.5}
        ]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#9ca3af',usePointStyle:true,pointStyleWidth:10,padding:16}}},
        scales:{
          x:{grid:{color:gridColor},ticks:{font:cFont,color:'#9ca3af'}},
          y:{beginAtZero:true,grid:{color:gridColor},ticks:{font:cFont,color:'#9ca3af',callback:function(v){return v+'M'}}}
        }
      }
    });

    /* Client Growth Bar */
    new Chart(document.getElementById('clientGrowthChart').getContext('2d'),{
      type:'bar',
      data:{
        labels:months,
        datasets:[
          {label:'Xavier',data:[4,6,5,8,7,10,9,12,11,14,13,16],backgroundColor:'rgba(79,110,247,0.75)',borderRadius:6,maxBarThickness:18},
          {label:'Jean Baptiste',data:[3,4,5,6,5,8,7,9,8,10,9,12],backgroundColor:'rgba(37,193,141,0.75)',borderRadius:6,maxBarThickness:18},
          {label:'Daniel',data:[2,3,4,5,4,6,5,7,6,8,7,10],backgroundColor:'rgba(139,92,246,0.75)',borderRadius:6,maxBarThickness:18}
        ]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#9ca3af',usePointStyle:true,pointStyleWidth:10,padding:16}}},
        scales:{
          x:{grid:{display:false},ticks:{font:cFont,color:'#9ca3af'}},
          y:{beginAtZero:true,grid:{color:gridColor},ticks:{font:cFont,color:'#9ca3af',stepSize:4}}
        }
      }
    });

    /* Radar Chart */
    new Chart(document.getElementById('radarChart').getContext('2d'),{
      type:'radar',
      data:{
        labels:['Strategy','Finance','Operations','Client Relations','Team Leadership','Innovation'],
        datasets:[
          {label:'Xavier Nshimiyimana',data:[95,88,90,97,92,85],borderColor:'#4f6ef7',backgroundColor:'rgba(79,110,247,0.15)',pointBackgroundColor:'#4f6ef7',borderWidth:2.5,pointRadius:4},
          {label:'Jean Baptiste Nkurunziza',data:[82,90,85,88,86,80],borderColor:'#25c18d',backgroundColor:'rgba(37,193,141,0.15)',pointBackgroundColor:'#25c18d',borderWidth:2.5,pointRadius:4},
          {label:'Daniel Niyonyirimirimo',data:[78,82,88,85,80,92],borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.15)',pointBackgroundColor:'#8b5cf6',borderWidth:2.5,pointRadius:4}
        ]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:true,position:'bottom',labels:{font:cFont,color:'#9ca3af',usePointStyle:true,pointStyleWidth:10,padding:16}}},
        scales:{
          r:{
            beginAtZero:true,max:100,
            grid:{color:'rgba(255,255,255,0.08)'},
            angleLines:{color:'rgba(255,255,255,0.08)'},
            pointLabels:{font:{family:'Inter',size:12,weight:'600'},color:'#cbd5e1'},
            ticks:{display:false}
          }
        }
      }
    });

    /* -- Live Update Simulation -- */
    setInterval(function(){
      var el=document.getElementById('ls1');
      if(el){
        var v=parseInt(el.textContent.replace(/,/g,''),10);
        if(!isNaN(v)){var nv=v+Math.floor(Math.random()*3);el.textContent=nv.toLocaleString();}
      }
    },5000);
  });
  </script>

</body>
</html>
