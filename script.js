document.addEventListener('DOMContentLoaded', function () {
  /* -- Date Display -- */
  var dateEl = document.getElementById('todayDate');
  if (dateEl) {
    var now = new Date();
    dateEl.textContent = now.toLocaleDateString('en-US', {
      weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
    });
  }

  /* -- Section Navigation (only items with data-section) -- */
  var navItems = document.querySelectorAll('.nav-item[data-section]');
  var sections = document.querySelectorAll('.dashboard-section');

  navItems.forEach(function (item) {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      var target = item.getAttribute('data-section');

      navItems.forEach(function (n) { n.classList.remove('active'); });
      item.classList.add('active');

      sections.forEach(function (s) { s.classList.remove('active'); });
      var targetSection = document.getElementById('section-' + target);
      if (targetSection) {
        targetSection.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }

      closeSidebar();
    });
  });

  /* -- Mobile Sidebar -- */
  var hamburger = document.getElementById('hamburgerBtn');
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');
  var closeBtn = document.getElementById('sidebarClose');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (hamburger) hamburger.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);

  /* -- Global Search Filter -- */
  var searchInput = document.getElementById('globalSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      var query = this.value.toLowerCase();
      var tables = document.querySelectorAll('.dashboard-section.active table tbody');
      tables.forEach(function (tbody) {
        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function (row) {
          var text = row.textContent.toLowerCase();
          row.style.display = text.indexOf(query) !== -1 || query === '' ? '' : 'none';
        });
      });
    });
  }

  /* -- Chart.js Initialization -- */
  var data = window.__DASHBOARD_DATA__ || {};

  function initCharts() {
    if (typeof Chart === 'undefined') return;

    var chartDefaults = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      }
    };

    /* User Growth Bar Chart */
    var ugCanvas = document.getElementById('userGrowthChart');
    if (ugCanvas && data.monthLabels) {
      new Chart(ugCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: data.monthLabels,
          datasets: [{
            label: 'Users',
            data: data.monthData,
            backgroundColor: function(ctx) {
              var chart = ctx.chart;
              var area = chart.chartArea;
              if (!area) return 'rgba(79, 110, 247, 0.7)';
              var gradient = chart.ctx.createLinearGradient(0, area.bottom, 0, area.top);
              gradient.addColorStop(0, 'rgba(79, 110, 247, 0.3)');
              gradient.addColorStop(1, 'rgba(79, 110, 247, 0.85)');
              return gradient;
            },
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 40
          }]
        },
        options: Object.assign({}, chartDefaults, {
          scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 12, weight: '500' }, color: '#5f6f88' } },
            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { font: { family: 'Inter', size: 12 }, color: '#5f6f88', stepSize: 1 } }
          }
        })
      });
    }

    /* Service Donut Chart */
    var svcCanvas = document.getElementById('serviceChart');
    if (svcCanvas && data.serviceLabels) {
      new Chart(svcCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: data.serviceLabels,
          datasets: [{
            data: data.serviceData,
            backgroundColor: ['#4f6ef7', '#25c18d', '#f5ad3d', '#ef5d71', '#8b5cf6', '#06b6d4'],
            borderWidth: 0,
            spacing: 3,
            borderRadius: 6
          }]
        },
        options: Object.assign({}, chartDefaults, {
          cutout: '65%',
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: { font: { family: 'Inter', size: 11, weight: '500' }, color: '#5f6f88', padding: 16, usePointStyle: true, pointStyleWidth: 10 }
            }
          }
        })
      });
    }

    /* Messages Per Day Bar Chart (last 14 days) */
    var msgCanvas = document.getElementById('messagesChart');
    if (msgCanvas && data.msgDayLabels) {
      new Chart(msgCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: data.msgDayLabels,
          datasets: [{
            label: 'Messages',
            data: data.msgDayData,
            backgroundColor: function(ctx) {
              var chart = ctx.chart;
              var area = chart.chartArea;
              if (!area) return 'rgba(37, 193, 141, 0.7)';
              var gradient = chart.ctx.createLinearGradient(0, area.bottom, 0, area.top);
              gradient.addColorStop(0, 'rgba(37, 193, 141, 0.3)');
              gradient.addColorStop(1, 'rgba(37, 193, 141, 0.85)');
              return gradient;
            },
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 28
          }]
        },
        options: Object.assign({}, chartDefaults, {
          scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 12, weight: '500' }, color: '#5f6f88' } },
            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { font: { family: 'Inter', size: 12 }, color: '#5f6f88', stepSize: 1 } }
          }
        })
      });
    }

    /* Inquiry Trend Line Chart */
    var inqCanvas = document.getElementById('inquiryTrendChart');
    if (inqCanvas && data.inquiryLabels) {
      new Chart(inqCanvas.getContext('2d'), {
        type: 'line',
        data: {
          labels: data.inquiryLabels,
          datasets: [{
            label: 'Inquiries',
            data: data.inquiryData,
            borderColor: '#4f6ef7',
            backgroundColor: 'rgba(79, 110, 247, 0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#4f6ef7',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 7,
            borderWidth: 3
          }]
        },
        options: Object.assign({}, chartDefaults, {
          scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 12, weight: '500' }, color: '#5f6f88' } },
            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { font: { family: 'Inter', size: 12 }, color: '#5f6f88', stepSize: 1 } }
          }
        })
      });
    }

    /* Customer Usage Line Chart */
    var usageCanvas = document.getElementById('usageChart');
    if (usageCanvas) {
      new Chart(usageCanvas.getContext('2d'), {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
          datasets: [{
            label: 'Usage',
            data: [46, 58, 62, 72, 68, 88],
            borderColor: '#25c18d',
            backgroundColor: 'rgba(37, 193, 141, 0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#25c18d',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 7,
            borderWidth: 3
          }]
        },
        options: Object.assign({}, chartDefaults, {
          scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 12, weight: '500' }, color: '#5f6f88' } },
            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { font: { family: 'Inter', size: 12 }, color: '#5f6f88', callback: function(v) { return v + '%'; } } }
          }
        })
      });
    }

    /* Revenue Chart (index.html) */
    var revCanvas = document.getElementById('revenueChart');
    if (revCanvas) {
      new Chart(revCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: data.monthLabels || ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
          datasets: [{
            label: 'Revenue',
            data: data.monthData || [8,12,10,15,18,22,20,28,25,32,29,35],
            backgroundColor: function(ctx) {
              var chart = ctx.chart;
              var area = chart.chartArea;
              if (!area) return 'rgba(79, 110, 247, 0.7)';
              var gradient = chart.ctx.createLinearGradient(0, area.bottom, 0, area.top);
              gradient.addColorStop(0, 'rgba(79, 110, 247, 0.3)');
              gradient.addColorStop(1, 'rgba(79, 110, 247, 0.85)');
              return gradient;
            },
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 40
          }]
        },
        options: Object.assign({}, chartDefaults, {
          scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11, weight: '500' }, color: '#5f6f88' } },
            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#5f6f88' } }
          }
        })
      });
    }

    /* Analytics Revenue Chart */
    var anRevCanvas = document.getElementById('analyticsRevenueChart');
    if (anRevCanvas) {
      new Chart(anRevCanvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: data.monthLabels || ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
          datasets: [
            { label: 'Revenue', data: data.monthData || [8,12,10,15,18,22,20,28,25,32,29,35], backgroundColor: 'rgba(79,110,247,0.7)', borderRadius: 6, maxBarThickness: 30 },
            { label: 'Expenses', data: [5,8,7,10,11,14,13,17,15,20,18,22], backgroundColor: 'rgba(239,93,113,0.5)', borderRadius: 6, maxBarThickness: 30 }
          ]
        },
        options: Object.assign({}, chartDefaults, {
          plugins: { legend: { display: true, position: 'top', labels: { font: { family: 'Inter', size: 11 }, color: '#5f6f88', usePointStyle: true } } },
          scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#5f6f88' } },
            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#5f6f88' } }
          }
        })
      });
    }

    /* Analytics Service Chart */
    var anSvcCanvas = document.getElementById('analyticsServiceChart');
    if (anSvcCanvas && data.serviceLabels) {
      new Chart(anSvcCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: data.serviceLabels,
          datasets: [{
            data: data.serviceData,
            backgroundColor: ['#4f6ef7', '#25c18d', '#f5ad3d', '#ef5d71', '#8b5cf6', '#06b6d4'],
            borderWidth: 0,
            spacing: 3,
            borderRadius: 6
          }]
        },
        options: Object.assign({}, chartDefaults, {
          cutout: '60%',
          plugins: {
            legend: { display: true, position: 'bottom', labels: { font: { family: 'Inter', size: 11, weight: '500' }, color: '#5f6f88', padding: 14, usePointStyle: true } }
          }
        })
      });
    }

    /* Service Polar Area Chart */
    var polarCanvas = document.getElementById('servicePieChart');
    if (polarCanvas && data.serviceLabels) {
      new Chart(polarCanvas.getContext('2d'), {
        type: 'polarArea',
        data: {
          labels: data.serviceLabels,
          datasets: [{
            data: data.serviceData,
            backgroundColor: ['rgba(79,110,247,0.7)', 'rgba(37,193,141,0.7)', 'rgba(245,173,61,0.7)', 'rgba(239,93,113,0.7)', 'rgba(139,92,246,0.7)', 'rgba(6,182,212,0.7)'],
            borderWidth: 0
          }]
        },
        options: Object.assign({}, chartDefaults, {
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: { font: { family: 'Inter', size: 11, weight: '500' }, color: '#5f6f88', padding: 16, usePointStyle: true, pointStyleWidth: 10 }
            }
          },
          scales: {
            r: { grid: { color: 'rgba(148,163,184,0.1)' }, ticks: { display: false } }
          }
        })
      });
    }
  }

  /* Load Chart.js from CDN if not already loaded */
  if (typeof Chart === 'undefined') {
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
    script.onload = initCharts;
    document.head.appendChild(script);
  } else {
    initCharts();
  }

  /* -- Animate stat numbers on scroll -- */
  function animateValue(el, start, end, duration) {
    var range = end - start;
    var startTime = null;
    function step(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(start + range * eased).toLocaleString();
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var el = entry.target;
        var val = parseInt(el.textContent.replace(/,/g, ''), 10);
        if (!isNaN(val) && val > 0) {
          el.textContent = '0';
          animateValue(el, 0, val, 800);
        }
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.3 });

  document.querySelectorAll('.stat-card strong, .hero-stat-value').forEach(function (el) {
    observer.observe(el);
  });
});
