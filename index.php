<?php
require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Portfolio Hub | Business Advisory & Financial Services</title>
  <meta name="description" content="Professional business advisory, financial modeling, tax support, valuation and IT services from Portfolio Hub. Trusted by 500+ businesses across Rwanda." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

  <!-- ===================== NAVBAR ===================== -->
  <nav class="site-nav" id="siteNav">
    <div class="nav-container">
      <a href="index.php" class="nav-brand">
        <img src="assets/images/logo.png" alt="Portfolio Hub" class="nav-logo" />
        <div class="nav-brand-text">
          <span class="nav-brand-name">Portfolio Hub</span>
          <span class="nav-brand-tag">Beyond compliance, we build trust</span>
        </div>
      </a>

      <ul class="nav-links" id="navLinks">
        <li><a href="#home" class="nav-link active">Home</a></li>
        <li><a href="#about" class="nav-link">About</a></li>
        <li><a href="#services" class="nav-link">Services</a></li>
        <li><a href="team.php" class="nav-link">Our Team</a></li>

        <li><a href="#contact" class="nav-link">Contact</a></li>
      </ul>

      <div class="nav-actions">
        <?php if ($user): ?>
          <a href="<?= ($user['role'] ?? 'customer') === 'admin' ? 'admin-dashboard.php' : 'dashboard.php' ?>" class="nav-btn nav-btn-ghost">Dashboard</a>
        <?php else: ?>
          <a href="login.php" class="nav-btn nav-btn-ghost">Sign in</a>
          <a href="register.php" class="nav-btn nav-btn-primary">Register</a>
        <?php endif; ?>
      </div>

      <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- Mobile menu overlay -->
  <div class="mobile-overlay" id="mobileOverlay"></div>

  <!-- ===================== HERO ===================== -->
  <section class="hero" id="home">
    <div class="hero-bg">
      <div class="hero-orb hero-orb-1"></div>
      <div class="hero-orb hero-orb-2"></div>
      <div class="hero-orb hero-orb-3"></div>
      <div class="hero-grid-lines"></div>
    </div>
    <div class="container hero-inner">
      <div class="hero-content">
        <div class="hero-badge" data-animate="fade-up">
          <span class="hero-badge-dot"></span>
          Trusted by 500+ businesses across Rwanda
        </div>
        <h1 data-animate="fade-up" data-delay="100">
          Strategic financial guidance that turns complexity into <span class="text-gradient">confidence.</span>
        </h1>
        <p class="hero-desc" data-animate="fade-up" data-delay="200">
          Portfolio Hub helps businesses, founders, and teams improve performance, strengthen compliance, and make better decisions with dependable expert advice.
        </p>
        <div class="hero-actions" data-animate="fade-up" data-delay="300">
          <a href="register.php" class="btn btn-primary btn-lg">Start a project</a>
          <a href="#services" class="btn btn-outline btn-lg">Explore services</a>
        </div>
        <div class="hero-stats" data-animate="fade-up" data-delay="400">
          <div class="hero-stat">
            <strong class="counter" data-target="10">0</strong><span>+</span>
            <small>Years of expertise</small>
          </div>
          <div class="hero-stat">
            <strong class="counter" data-target="500">0</strong><span>+</span>
            <small>Projects delivered</small>
          </div>
          <div class="hero-stat">
            <strong>24/7</strong>
            <small>Responsive support</small>
          </div>
          <div class="hero-stat">
            <strong class="counter" data-target="95">0</strong><span>%</span>
            <small>Client retention</small>
          </div>
        </div>
      </div>
      <div class="hero-visual" data-animate="fade-left" data-delay="200">
        <div class="hero-card-stack">
          <div class="hero-float-card hero-float-main">
            <div class="hfc-icon hfc-icon-green">&#10003;</div>
            <div>
              <strong>Strategy Session</strong>
              <small>Completed today at 14:30</small>
            </div>
          </div>
          <div class="hero-float-card hero-float-mid">
            <div class="hfc-icon hfc-icon-blue">&#128200;</div>
            <div>
              <strong>Revenue Growth</strong>
              <small>+23% this quarter</small>
            </div>
          </div>
          <div class="hero-float-card hero-float-top">
            <div class="hfc-icon hfc-icon-purple">&#11088;</div>
            <div>
              <strong>Client Satisfaction</strong>
              <small>4.9/5 average rating</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== ABOUT ===================== -->
  <section class="about-section" id="about">
    <div class="container">
      <div class="about-grid">
        <div class="about-content" data-animate="fade-right">
          <span class="section-tag">About Us</span>
          <h2>Built around clarity, accountability, and long-term value.</h2>
          <p>Portfolio Hub is a Rwanda-based business advisory firm helping organizations navigate financial complexity, optimize operations, and achieve sustainable growth. Our team brings deep expertise across accounting, tax, valuation, project management, and IT.</p>
          <div class="about-features">
            <div class="about-feature">
              <div class="about-feature-icon">&#128737;</div>
              <div>
                <strong>Reliable Guidance</strong>
                <small>Clear strategies and measurable direction for every financial decision.</small>
              </div>
            </div>
            <div class="about-feature">
              <div class="about-feature-icon">&#127912;</div>
              <div>
                <strong>Tailored Solutions</strong>
                <small>Every engagement shaped around your business model and goals.</small>
              </div>
            </div>
            <div class="about-feature">
              <div class="about-feature-icon">&#129309;</div>
              <div>
                <strong>Trust-Based Partnership</strong>
                <small>Transparent, dependable execution beyond simple compliance.</small>
              </div>
            </div>
          </div>
        </div>
        <div class="about-visual" data-animate="fade-left">
          <div class="about-image-grid">
            <div class="about-img-card about-img-1">
              <div class="aic-content">
                <strong>500+</strong>
                <small>Projects Completed</small>
              </div>
            </div>
            <div class="about-img-card about-img-2">
              <div class="aic-content">
                <strong>98%</strong>
                <small>Success Rate</small>
              </div>
            </div>
            <div class="about-img-card about-img-3">
              <div class="aic-content">
                <strong>7</strong>
                <small>Core Services</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== SERVICES ===================== -->
  <section class="services-section" id="services">
    <div class="container">
      <div class="section-header" data-animate="fade-up">
        <span class="section-tag">What We Offer</span>
        <h2>Comprehensive services to power your business growth.</h2>
        <p>From strategic advisory to hands-on financial management, we provide the full spectrum of business support services.</p>
      </div>

      <div class="services-grid">
        <div class="svc-card" data-animate="fade-up" data-delay="0">
          <div class="svc-card-icon svc-card-blue">&#128204;</div>
          <h3>Business Advisory</h3>
          <p>Strategic guidance for growth, market entry, operational efficiency, and organizational transformation.</p>
          <div class="svc-card-tags">
            <span>Strategy</span><span>Growth</span><span>Operations</span>
          </div>
          <a href="#contact" class="svc-card-link">Request this service &rarr;</a>
        </div>

        <div class="svc-card" data-animate="fade-up" data-delay="80">
          <div class="svc-card-icon svc-card-green">&#128176;</div>
          <h3>Financial Modeling</h3>
          <p>Custom financial models, forecasts, scenario analysis, and valuation support for decision-making.</p>
          <div class="svc-card-tags">
            <span>Forecasting</span><span>Analysis</span><span>Valuation</span>
          </div>
          <a href="#contact" class="svc-card-link">Request this service &rarr;</a>
        </div>

        <div class="svc-card" data-animate="fade-up" data-delay="160">
          <div class="svc-card-icon svc-card-purple">&#128196;</div>
          <h3>Project Management</h3>
          <p>End-to-end project delivery, planning, execution, and monitoring to ensure on-time, on-budget results.</p>
          <div class="svc-card-tags">
            <span>Planning</span><span>Execution</span><span>Delivery</span>
          </div>
          <a href="#contact" class="svc-card-link">Request this service &rarr;</a>
        </div>

        <div class="svc-card" data-animate="fade-up" data-delay="240">
          <div class="svc-card-icon svc-card-orange">&#128200;</div>
          <h3>Business Valuation</h3>
          <p>Comprehensive business and asset valuations for mergers, acquisitions, investment, and compliance.</p>
          <div class="svc-card-tags">
            <span>M&A</span><span>Investment</span><span>Compliance</span>
          </div>
          <a href="#contact" class="svc-card-link">Request this service &rarr;</a>
        </div>

        <div class="svc-card" data-animate="fade-up" data-delay="320">
          <div class="svc-card-icon svc-card-cyan">&#128187;</div>
          <h3>IT Support</h3>
          <p>Technology infrastructure, system integration, digital transformation, and ongoing technical support.</p>
          <div class="svc-card-tags">
            <span>Infrastructure</span><span>Digital</span><span>Support</span>
          </div>
          <a href="#contact" class="svc-card-link">Request this service &rarr;</a>
        </div>

        <div class="svc-card" data-animate="fade-up" data-delay="400">
          <div class="svc-card-icon svc-card-red">&#128221;</div>
          <h3>Tax Services</h3>
          <p>Tax planning, compliance, filing, and advisory to minimize liabilities and ensure full regulatory adherence.</p>
          <div class="svc-card-tags">
            <span>Planning</span><span>Filing</span><span>Compliance</span>
          </div>
          <a href="#contact" class="svc-card-link">Request this service &rarr;</a>
        </div>

        <div class="svc-card svc-card-wide" data-animate="fade-up" data-delay="480">
          <div class="svc-card-icon svc-card-teal">&#128221;</div>
          <h3>Accounting Services</h3>
          <p>Full-cycle accounting, bookkeeping, financial reporting, and audit preparation for businesses of all sizes.</p>
          <div class="svc-card-tags">
            <span>Bookkeeping</span><span>Reporting</span><span>Audit</span>
          </div>
          <a href="#contact" class="svc-card-link">Request this service &rarr;</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== NUMBERS ===================== -->
  <section class="numbers-section">
    <div class="container">
      <div class="numbers-grid">
        <div class="number-card" data-animate="fade-up">
          <div class="number-value"><span class="counter" data-target="500">0</span>+</div>
          <div class="number-label">Projects Delivered</div>
        </div>
        <div class="number-card" data-animate="fade-up" data-delay="100">
          <div class="number-value"><span class="counter" data-target="200">0</span>+</div>
          <div class="number-label">Happy Clients</div>
        </div>
        <div class="number-card" data-animate="fade-up" data-delay="200">
          <div class="number-value"><span class="counter" data-target="7">0</span></div>
          <div class="number-label">Core Services</div>
        </div>
        <div class="number-card" data-animate="fade-up" data-delay="300">
          <div class="number-value"><span class="counter" data-target="10">0</span>+</div>
          <div class="number-label">Years Experience</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== TEAM PREVIEW ===================== -->
  <section class="team-preview-section" id="team">
    <div class="container">
      <div class="section-header" data-animate="fade-up">
        <span class="section-tag">Our Leadership</span>
        <h2>Meet the team behind Portfolio Hub.</h2>
        <p>Experienced directors guiding our firm with vision, integrity, and deep expertise.</p>
      </div>
      <div class="team-preview-grid">
        <div class="tp-card" data-animate="fade-up" data-delay="0">
          <div class="tp-card-top tp-blue">
            <div class="tp-avatar tp-avatar-blue">XN</div>
          </div>
          <div class="tp-card-body">
            <h3>Xavier Nshimiyimana</h3>
            <span class="tp-role tp-role-blue">Chief of Staff / Director</span>
            <p>12+ years leading financial advisory and business strategy across East Africa.</p>
          </div>
        </div>
        <div class="tp-card" data-animate="fade-up" data-delay="120">
          <div class="tp-card-top tp-green">
            <div class="tp-avatar tp-avatar-green">JB</div>
          </div>
          <div class="tp-card-body">
            <h3>Jean Baptiste Nkurunziza</h3>
            <span class="tp-role tp-role-green">Director</span>
            <p>10+ years in project management, operations, and corporate development.</p>
          </div>
        </div>
        <div class="tp-card" data-animate="fade-up" data-delay="240">
          <div class="tp-card-top tp-purple">
            <div class="tp-avatar tp-avatar-purple">DN</div>
          </div>
          <div class="tp-card-body">
            <h3>Daniel Niyonyirimirimo</h3>
            <span class="tp-role tp-role-purple">Director</span>
            <p>8+ years specializing in IT strategy, digital transformation, and systems integration.</p>
          </div>
        </div>
      </div>
      <div class="tp-cta" data-animate="fade-up">
        <a href="team.php" class="btn btn-outline btn-lg">View full team &rarr;</a>
      </div>
    </div>
  </section>

  <!-- ===================== CTA ===================== -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-card" data-animate="fade-up">
        <div class="cta-content">
          <h2>Ready to grow your business?</h2>
          <p>Join 500+ businesses that trust Portfolio Hub for strategic financial guidance and expert advisory services.</p>
        </div>
        <div class="cta-actions">
          <a href="register.php" class="btn btn-white btn-lg">Register today</a>
          <a href="#contact" class="btn btn-outline-light btn-lg">Contact us</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== CONTACT ===================== -->
  <section class="contact-section" id="contact">
    <div class="container">
      <div class="contact-grid">
        <div class="contact-info" data-animate="fade-right">
          <span class="section-tag">Get In Touch</span>
          <h2>Let's discuss your business needs.</h2>
          <p>Reach out to us for a free initial consultation. We'll discuss your goals and recommend the best path forward.</p>
          <div class="contact-details">
            <div class="contact-detail">
              <div class="contact-detail-icon">&#128231;</div>
              <div>
                <strong>Email Us</strong>
                <a href="mailto:portfoliohubwanda@gmail.com">portfoliohubwanda@gmail.com</a>
              </div>
            </div>
            <div class="contact-detail">
              <div class="contact-detail-icon">&#128222;</div>
              <div>
                <strong>Call Us</strong>
                <span>+250 787 258 624 / +250 789 916 388</span>
              </div>
            </div>
            <div class="contact-detail">
              <div class="contact-detail-icon">&#128205;</div>
              <div>
                <strong>Visit Us</strong>
                <span>Kigali, Rwanda</span>
              </div>
            </div>
          </div>
        </div>
        <div class="contact-form-wrap" data-animate="fade-left">
          <form class="contact-form" id="contactForm" method="POST" action="contact.php">
            <div class="form-row">
              <label>
                <span>Full name</span>
                <input type="text" name="name" placeholder="Your full name" required />
              </label>
              <label>
                <span>Email address</span>
                <input type="email" name="email" placeholder="you@example.com" required />
              </label>
            </div>
            <div class="form-row">
              <label>
                <span>Phone number</span>
                <input type="tel" name="phone" placeholder="+250 7XX XXX XXX" required />
              </label>
              <label>
                <span>Service needed</span>
                <select name="service" required>
                  <option value="">Select a service</option>
                  <option>Business Advisory</option>
                  <option>Financial Modeling</option>
                  <option>Project Management</option>
                  <option>Business Valuation</option>
                  <option>IT Support</option>
                  <option>Tax Services</option>
                  <option>Accounting Services</option>
                </select>
              </label>
            </div>
            <label>
              <span>Message</span>
              <textarea name="message" rows="4" placeholder="Tell us about your project or business need" required></textarea>
            </label>
            <button type="submit" class="btn btn-primary btn-lg btn-full">Send Inquiry</button>
            <p class="form-status" aria-live="polite"></p>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== FOOTER ===================== -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="index.php" class="footer-logo-group">
            <img src="assets/images/logo.png" alt="Portfolio Hub" class="footer-logo" />
            <div>
              <strong>Portfolio Hub</strong>
              <small>Beyond compliance, we build trust</small>
            </div>
          </a>
          <p>Professional business advisory, financial modeling, tax support, valuation, and IT services for growing businesses in Rwanda and beyond.</p>
        </div>
        <div class="footer-links">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="#home">Home</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="team.php">Our Team</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>
        <div class="footer-links">
          <h4>Services</h4>
          <ul>
            <li><a href="#services">Business Advisory</a></li>
            <li><a href="#services">Financial Modeling</a></li>
            <li><a href="#services">Project Management</a></li>
            <li><a href="#services">Tax Services</a></li>
            <li><a href="#services">Accounting</a></li>
          </ul>
        </div>
        <div class="footer-links">
          <h4>Account</h4>
          <ul>
            <?php if ($user): ?>
              <li><a href="<?= ($user['role'] ?? 'customer') === 'admin' ? 'admin-dashboard.php' : 'dashboard.php' ?>">My Dashboard</a></li>
              <li><a href="logout.php">Sign Out</a></li>
            <?php else: ?>
              <li><a href="login.php">Sign In</a></li>
              <li><a href="register.php">Register</a></li>
            <?php endif; ?>
            <li><a href="team.php">Leadership</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Portfolio Hub. All rights reserved.</p>
        <p>Built for growth, compliance, and trust.</p>
      </div>
    </div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
