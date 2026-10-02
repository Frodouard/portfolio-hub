document.addEventListener('DOMContentLoaded', function () {

  /* ============================================================
     NAVBAR — scroll effect & active link tracking
     ============================================================ */
  var nav = document.getElementById('siteNav');
  var navLinks = document.querySelectorAll('.nav-link');
  var sections = document.querySelectorAll('section[id]');

  function onScroll() {
    if (window.scrollY > 40) {
      nav.classList.add('scrolled');
    } else {
      nav.classList.remove('scrolled');
    }

    /* Active link tracking */
    var scrollY = window.scrollY + 120;
    sections.forEach(function (sec) {
      var top = sec.offsetTop;
      var height = sec.offsetHeight;
      var id = sec.getAttribute('id');
      if (scrollY >= top && scrollY < top + height) {
        navLinks.forEach(function (link) {
          link.classList.remove('active');
          if (link.getAttribute('href') === '#' + id) {
            link.classList.add('active');
          }
        });
      }
    });
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ============================================================
     MOBILE MENU
     ============================================================ */
  var toggle = document.getElementById('navToggle');
  var navLinksEl = document.getElementById('navLinks');
  var navActionsEl = document.querySelector('.nav-actions');
  var overlay = document.getElementById('mobileOverlay');

  function openMenu() {
    if (navLinksEl) navLinksEl.classList.add('open');
    if (navActionsEl) navActionsEl.classList.add('open');
    if (overlay) overlay.classList.add('open');
    if (toggle) toggle.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu() {
    if (navLinksEl) navLinksEl.classList.remove('open');
    if (navActionsEl) navActionsEl.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    if (toggle) toggle.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (toggle) toggle.addEventListener('click', function () {
    toggle.classList.contains('open') ? closeMenu() : openMenu();
  });
  if (overlay) overlay.addEventListener('click', closeMenu);

  /* Close mobile menu on nav link click */
  document.querySelectorAll('.nav-link').forEach(function (link) {
    link.addEventListener('click', closeMenu);
  });

  /* ============================================================
     SMOOTH SCROLL (fallback for older browsers)
     ============================================================ */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var targetId = this.getAttribute('href');
      if (targetId === '#') return;
      var target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ============================================================
     SCROLL ANIMATIONS (IntersectionObserver)
     ============================================================ */
  var animateEls = document.querySelectorAll('[data-animate]');
  if ('IntersectionObserver' in window) {
    var animObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var delay = parseInt(entry.target.getAttribute('data-delay') || '0', 10);
          setTimeout(function () {
            entry.target.classList.add('visible');
          }, delay);
          animObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    animateEls.forEach(function (el) { animObserver.observe(el); });
  } else {
    /* Fallback: show everything */
    animateEls.forEach(function (el) { el.classList.add('visible'); });
  }

  /* ============================================================
     ANIMATED COUNTERS
     ============================================================ */
  function animateCounter(el, target, duration) {
    duration = duration || 1200;
    var startTime = null;
    var start = 0;

    function step(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 4);
      el.textContent = Math.floor(start + (target - start) * eased).toLocaleString();
      if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
  }

  var counterEls = document.querySelectorAll('.counter');
  if ('IntersectionObserver' in window && counterEls.length) {
    var counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          var target = parseInt(el.getAttribute('data-target'), 10);
          if (!isNaN(target) && target > 0) {
            animateCounter(el, target);
          }
          counterObserver.unobserve(el);
        }
      });
    }, { threshold: 0.3 });

    counterEls.forEach(function (el) { counterObserver.observe(el); });
  }

  /* ============================================================
     CONTACT FORM (AJAX)
     ============================================================ */
  var form = document.getElementById('contactForm');
  var status = document.querySelector('.form-status');

  if (form && status) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var formData = new FormData(form);
      var payload = new URLSearchParams();
      formData.forEach(function (value, key) {
        payload.append(key, String(value));
      });

      status.textContent = 'Sending your inquiry...';
      status.style.color = 'var(--muted)';

      fetch('contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: payload.toString()
      })
      .then(function (res) { return res.json(); })
      .then(function (result) {
        if (result.success) {
          status.textContent = result.message || 'Inquiry sent successfully!';
          status.style.color = '#34d399';
          form.reset();
        } else {
          status.textContent = result.message || 'Something went wrong.';
          status.style.color = '#ef5d71';
        }
      })
      .catch(function () {
        status.textContent = 'Network error. Please try again.';
        status.style.color = '#ef5d71';
      });
    });
  }

  /* ============================================================
     PARALLAX ORB EFFECT (subtle)
     ============================================================ */
  var orb1 = document.querySelector('.hero-orb-1');
  var orb2 = document.querySelector('.hero-orb-2');

  if (orb1 || orb2) {
    window.addEventListener('mousemove', function (e) {
      var x = (e.clientX / window.innerWidth - 0.5) * 30;
      var y = (e.clientY / window.innerHeight - 0.5) * 30;
      if (orb1) orb1.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
      if (orb2) orb2.style.transform = 'translate(' + (-x) + 'px, ' + (-y) + 'px)';
    }, { passive: true });
  }
});
