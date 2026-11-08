<?php
require_once __DIR__ . '/includes/session.php';

// Already signed in? Skip the marketing page and go straight to work.
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LogiTrack Pro — Logistics Management, Simplified</title>
<meta name="description" content="Track shipments, manage your fleet and warehouse, and keep every team member in sync — all from one dashboard.">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    :root {
        --gold: #c9a84c; --gold-light:#e8c97a; --gold-shine:#f5e0a0; --gold-dark:#8b6914;
        --navy:#0f1c35; --card-bg: linear-gradient(160deg,#1a2e50 0%,#112038 55%,#0c1828 100%);
        --gold-grad: linear-gradient(135deg,#f0d880 0%,#c9a84c 40%,#8b6914 75%,#c9a84c 100%);
        --card-shadow: 0 8px 28px rgba(0,0,0,0.5),0 3px 10px rgba(0,0,0,0.4),inset 0 1px 0 rgba(201,168,76,0.18);
    }
    html { scroll-behavior: smooth; }
    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(ellipse 80% 60% at 15% 10%, rgba(201,168,76,0.08) 0%, transparent 70%),
                    radial-gradient(ellipse 60% 80% at 85% 20%, rgba(74,28,42,0.12) 0%, transparent 60%),
                    linear-gradient(160deg,#070e1e 0%,#0f1c35 45%,#0a1428 100%);
        color: #e8d5b8;
        min-height: 100vh;
    }

    /* ---- Top nav ---- */
    .nav {
        display:flex; align-items:center; justify-content:space-between;
        padding: 1.3rem 5%; position: sticky; top:0; z-index: 50;
        background: rgba(7,14,30,0.75); backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(201,168,76,0.15);
    }
    .nav-logo { display:flex; align-items:center; gap:10px; }
    .nav-logo i { width:38px; height:38px; border-radius:9px; background:var(--gold-grad); color:#0f1c35;
        display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
    .nav-logo span { font-family:'Playfair Display',serif; font-weight:800; font-size:1.25rem; color:var(--gold-light); }
    .nav-links { display:flex; align-items:center; gap:1.8rem; }
    .nav-links a { color:#c9b896; text-decoration:none; font-size:.92rem; font-weight:500; transition:color .2s; }
    .nav-links a:hover { color: var(--gold-light); }
    .nav-actions { display:flex; align-items:center; gap:.8rem; }
    .btn {
        display:inline-flex; align-items:center; gap:.5rem; padding:.65rem 1.3rem; border-radius:9px;
        font-weight:700; font-size:.88rem; text-decoration:none; cursor:pointer; border:none; transition: all .2s;
    }
    .btn-primary { background: var(--gold-grad); color:#0f1c35; }
    .btn-primary:hover { filter:brightness(1.08); transform: translateY(-1px); }
    .btn-ghost { background: transparent; color: var(--gold-light); border:1px solid rgba(201,168,76,0.35); }
    .btn-ghost:hover { background: rgba(201,168,76,0.08); }
    .btn-lg { padding:.95rem 1.9rem; font-size:1rem; }

    @media (max-width: 760px) {
        .nav-links { display:none; }
    }

    /* ---- Hero ---- */
    .hero {
        max-width: 1100px; margin: 0 auto; padding: 5.5rem 5% 4rem; text-align:center;
    }
    .hero-badge {
        display:inline-flex; align-items:center; gap:.5rem; padding:.4rem .9rem; border-radius:999px;
        background: rgba(201,168,76,0.1); border:1px solid rgba(201,168,76,0.3); color: var(--gold-light);
        font-size:.8rem; font-weight:600; margin-bottom: 1.6rem;
    }
    .hero h1 {
        font-family:'Playfair Display', serif; font-weight:800; font-size: clamp(2.1rem, 5vw, 3.4rem);
        line-height:1.18; color:#f5ede0; margin-bottom: 1.3rem;
    }
    .hero h1 em { font-style: normal; background: var(--gold-grad); -webkit-background-clip:text; background-clip:text; color:transparent; }
    .hero p { font-size:1.08rem; color:#c9b896; max-width:620px; margin:0 auto 2.2rem; line-height:1.6; }
    .hero-ctas { display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; margin-bottom: 3.5rem; }
    .hero-note { font-size:.82rem; color:#8d9bb8; }

    /* ---- Dashboard preview mock ---- */
    .preview-frame {
        max-width: 920px; margin: 0 auto; border-radius: 16px; overflow:hidden;
        border: 1px solid rgba(201,168,76,0.22); box-shadow: var(--card-shadow);
        background: var(--card-bg);
    }
    .preview-bar { display:flex; gap:6px; padding:.9rem 1.1rem; border-bottom:1px solid rgba(201,168,76,0.12); }
    .preview-bar span { width:11px; height:11px; border-radius:50%; background: rgba(201,168,76,0.25); }
    .preview-stats { display:grid; grid-template-columns: repeat(4,1fr); gap: 1px; background: rgba(201,168,76,0.12); }
    .preview-stat { background: #0c1828; padding: 1.3rem 1rem; text-align:left; }
    .preview-stat .num { font-size:1.6rem; font-weight:800; color: var(--gold-light); font-family:'Playfair Display',serif; }
    .preview-stat .label { font-size:.78rem; color:#9fa8bc; margin-top:.2rem; }
    @media (max-width:640px){ .preview-stats{ grid-template-columns: repeat(2,1fr);} }

    /* ---- Sections ---- */
    section { padding: 4.5rem 5%; }
    .section-inner { max-width: 1100px; margin:0 auto; }
    .section-head { text-align:center; max-width:620px; margin: 0 auto 3rem; }
    .section-head .kicker { color: var(--gold-light); font-size:.82rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; margin-bottom:.6rem; display:block; }
    .section-head h2 { font-family:'Playfair Display',serif; font-size: clamp(1.6rem, 3.5vw, 2.3rem); color:#f5ede0; font-weight:800; margin-bottom:.8rem; }
    .section-head p { color:#c9b896; line-height:1.6; }

    .features-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }
    @media (max-width: 900px) { .features-grid { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 600px) { .features-grid { grid-template-columns: 1fr; } }
    .feature-card {
        background: var(--card-bg); border:1px solid rgba(201,168,76,0.16); border-radius:14px;
        padding: 1.6rem 1.4rem; box-shadow: var(--card-shadow);
    }
    .feature-card i { width:44px; height:44px; border-radius:10px; background: rgba(201,168,76,0.12); color: var(--gold-light);
        display:flex; align-items:center; justify-content:center; font-size:18px; margin-bottom: 1rem; }
    .feature-card h3 { font-size:1.05rem; color:#f5ede0; margin-bottom:.5rem; font-weight:700; }
    .feature-card p { font-size:.88rem; color:#aab4cc; line-height:1.55; }

    .roles-row { display:flex; gap:1.2rem; flex-wrap:wrap; justify-content:center; }
    .role-chip {
        flex:1 1 260px; max-width:320px; background: rgba(255,255,255,0.03); border:1px solid rgba(201,168,76,0.18);
        border-radius:12px; padding:1.3rem 1.2rem;
    }
    .role-chip .tag { display:inline-block; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
        padding:.25rem .6rem; border-radius:999px; margin-bottom:.7rem; }
    .role-chip.staff .tag { background: rgba(93,217,156,0.15); color:#7fe0ad; }
    .role-chip.manager .tag { background: rgba(106,179,232,0.15); color:#8cc5f0; }
    .role-chip.admin .tag { background: rgba(201,168,76,0.18); color: var(--gold-light); }
    .role-chip h4 { font-size:1rem; color:#f5ede0; margin-bottom:.5rem; }
    .role-chip ul { list-style:none; font-size:.85rem; color:#aab4cc; line-height:1.9; }
    .role-chip ul li i { color: var(--gold-light); width:16px; margin-right:.4rem; font-size:.8rem; }

    .cta-band {
        background: var(--card-bg); border:1px solid rgba(201,168,76,0.22); border-radius: 18px;
        padding: 3rem 2rem; text-align:center; box-shadow: var(--card-shadow); position:relative; overflow:hidden;
    }
    .cta-band::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: var(--gold-grad); }
    .cta-band h2 { font-family:'Playfair Display',serif; font-size:1.8rem; color:#f5ede0; margin-bottom:.7rem; font-weight:800; }
    .cta-band p { color:#c9b896; margin-bottom:1.6rem; }

    footer {
        border-top:1px solid rgba(201,168,76,0.12); padding: 2.2rem 5%; text-align:center;
        color:#7c879e; font-size:.82rem;
    }
    footer a { color: var(--gold-light); text-decoration:none; }
</style>
</head>
<body>

    <nav class="nav">
        <div class="nav-logo"><i class="fas fa-truck-fast"></i><span>LogiTrack Pro</span></div>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#roles">Who it's for</a>
            <a href="shipmenttracker.php">Track a Shipment</a>
        </div>
        <div class="nav-actions">
            <a href="login.php" class="btn btn-ghost">Sign In</a>
            <a href="register.php" class="btn btn-primary">Get Started</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-badge"><i class="fas fa-circle-check"></i> Trusted logistics workflow, all in one place</div>
        <h1>Every shipment, vehicle &amp; pallet — <em>tracked in real time.</em></h1>
        <p>LogiTrack Pro brings shipment tracking, fleet management and warehouse inventory into a single dashboard,
           with role-based access so the right people see exactly what they need.</p>
        <div class="hero-ctas">
            <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Create a free account</a>
            <a href="shipmenttracker.php" class="btn btn-ghost btn-lg"><i class="fas fa-magnifying-glass-location"></i> Track a shipment</a>
        </div>
        <p class="hero-note">Already have an account? <a href="login.php" style="color:var(--gold-light); font-weight:600;">Sign in →</a></p>
    </section>

    <section style="padding-top:0;">
        <div class="preview-frame">
            <div class="preview-bar"><span></span><span></span><span></span></div>
            <div class="preview-stats">
                <div class="preview-stat"><div class="num">128</div><div class="label"><i class="fas fa-box"></i> Active Shipments</div></div>
                <div class="preview-stat"><div class="num">24</div><div class="label"><i class="fas fa-truck"></i> Vehicles On The Road</div></div>
                <div class="preview-stat"><div class="num">97%</div><div class="label"><i class="fas fa-chart-line"></i> On-Time Delivery</div></div>
                <div class="preview-stat"><div class="num">5</div><div class="label"><i class="fas fa-warehouse"></i> Warehouses Synced</div></div>
            </div>
        </div>
    </section>

    <section id="features">
        <div class="section-inner">
            <div class="section-head">
                <span class="kicker">What you get</span>
                <h2>One dashboard, every moving part</h2>
                <p>Built for teams who need shipments, fleet and stock to stay in sync without juggling spreadsheets.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-box-open"></i>
                    <h3>Shipment Tracking</h3>
                    <p>Create, update and monitor shipments with a live status and progress view — Pending, In&nbsp;Transit, Delayed or Delivered.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-truck-fast"></i>
                    <h3>Fleet Management</h3>
                    <p>Keep tabs on every vehicle's status, driver, fuel level and upcoming maintenance from one screen.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-warehouse"></i>
                    <h3>Warehouse Inventory</h3>
                    <p>Track stock levels by SKU and location, with automatic low-stock and out-of-stock flags.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bell"></i>
                    <h3>Real-Time Notifications</h3>
                    <p>In-app and email alerts the moment a shipment is delayed, or if someone tries signing in with your credentials.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-shield"></i>
                    <h3>Role-Based Access</h3>
                    <p>Admins, managers and staff each see exactly what their role allows — nothing more, nothing less.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-plug"></i>
                    <h3>API for Integrations</h3>
                    <p>Generate API keys to connect your own systems to shipment data, with built-in rate limiting.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="roles">
        <div class="section-inner">
            <div class="section-head">
                <span class="kicker">Built around your team</span>
                <h2>Pick the role that fits</h2>
                <p>Every account gets one of three roles, chosen at sign-up (admin accounts are provisioned separately).</p>
            </div>
            <div class="roles-row">
                <div class="role-chip staff">
                    <span class="tag">Staff</span>
                    <h4>Logistics Staff</h4>
                    <ul>
                        <li><i class="fas fa-check"></i> View all shipments</li>
                        <li><i class="fas fa-check"></i> Create new shipments</li>
                        <li><i class="fas fa-check"></i> Track deliveries</li>
                    </ul>
                </div>
                <div class="role-chip manager">
                    <span class="tag">Manager</span>
                    <h4>Logistics Manager</h4>
                    <ul>
                        <li><i class="fas fa-check"></i> Everything in Staff</li>
                        <li><i class="fas fa-check"></i> Edit &amp; delete shipments</li>
                        <li><i class="fas fa-check"></i> Manage fleet &amp; warehouse</li>
                    </ul>
                </div>
                <div class="role-chip admin">
                    <span class="tag">Admin</span>
                    <h4>Administrator</h4>
                    <ul>
                        <li><i class="fas fa-check"></i> Everything in Manager</li>
                        <li><i class="fas fa-check"></i> Manage user accounts</li>
                        <li><i class="fas fa-check"></i> Generate API keys</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="section-inner">
            <div class="cta-band">
                <h2>Ready to see it in action?</h2>
                <p>Create a free account in under a minute — no credit card required.</p>
                <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-arrow-right"></i> Create your account</a>
            </div>
        </div>
    </section>

    <footer>
        &copy; <?php echo date('Y'); ?> LogiTrack Pro. &nbsp;·&nbsp;
        <a href="login.php">Sign In</a> &nbsp;·&nbsp;
        <a href="register.php">Register</a> &nbsp;·&nbsp;
        <a href="shipmenttracker.php">Track a Shipment</a>
    </footer>

</body>
</html>
