<?php
require_once __DIR__ . '/includes/session.php';

// Visiting the site without being signed in shows the landing page first,
// instead of jumping straight to the login form.
if (empty($_SESSION['user_id'])) {
    header('Location: landing.php');
    exit;
}

require_once __DIR__ . '/includes/auth_check.php';

// Role flags used throughout this page to show/hide features exactly per
// the roles described on the landing page:
//   staff   - view & create shipments, track deliveries only
//   manager - everything staff can, plus edit/delete shipments and
//             manage the fleet & warehouse
//   admin   - everything manager can, plus manage user accounts and
//             generate API keys
$isStaff = $currentUser['role'] === 'staff';
$isAdmin = $currentUser['role'] === 'admin';
$roleLabels = [
    'admin'   => 'Administrator',
    'manager' => 'Logistics Manager',
    'staff'   => 'Logistics Staff',
];
$roleLabel = $roleLabels[$currentUser['role']] ?? ucfirst($currentUser['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogiTrack Pro - Advanced Logistics Management</title>
    
    <script>
    // Logged-in user's real account data (single source of truth for the profile form)
    window.SERVER_PROFILE = <?php echo json_encode([
        'name'       => (string)$currentUser['full_name'],
        'email'      => (string)$currentUser['email'],
        'phone'      => (string)($currentUser['phone'] ?? ''),
        'position'   => (string)($currentUser['position'] ?? ''),
        'department' => (string)($currentUser['department'] ?? ''),
        'roleLabel'  => $roleLabel,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <!-- CSS Files -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    // Classic gold chart defaults - applied once Chart.js loads
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = 'rgba(201,168,76,0.7)';
            Chart.defaults.borderColor = 'rgba(201,168,76,0.12)';
            Chart.defaults.plugins.legend.labels.color = 'rgba(232,213,184,0.8)';
        }
    });
    </script>
    
    <!-- ✅ FAVICON -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='100'%20height='100'%20viewBox='0%200%20100%20100'%3E%3Crect%20width='100'%20height='100'%20rx='18'%20fill='%23e3b94c'/%3E%3Crect%20x='6'%20y='26'%20width='55'%20height='30'%20rx='4'%20fill='%230a0f1c'/%3E%3Cpath%20d='M61,56%20L61,30%20L75,30%20L91,45%20L91,56%20Z'%20fill='%230a0f1c'/%3E%3Cpath%20d='M77,33%20L88,45%20L77,45%20Z'%20fill='%23fff4d6'/%3E%3Crect%20x='6'%20y='54'%20width='85'%20height='6'%20fill='%230a0f1c'/%3E%3Ccircle%20cx='23'%20cy='63'%20r='11'%20fill='%230a0f1c'/%3E%3Ccircle%20cx='23'%20cy='63'%20r='4.6'%20fill='%23e3b94c'/%3E%3Ccircle%20cx='46'%20cy='63'%20r='11'%20fill='%230a0f1c'/%3E%3Ccircle%20cx='46'%20cy='63'%20r='4.6'%20fill='%23e3b94c'/%3E%3Ccircle%20cx='79'%20cy='63'%20r='11'%20fill='%230a0f1c'/%3E%3Ccircle%20cx='79'%20cy='63'%20r='4.6'%20fill='%23e3b94c'/%3E%3C/svg%3E">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#c9a84c">
</head>
<body>
        <!-- Mobile Menu Toggle Button -->
        <div class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </div>
        <div class="mobile-overlay" id="mobileOverlay"></div>
    <!-- 3D Truck Loading Animation -->
    <div class="loader-wrapper" id="loader">

        <!-- Starfield -->
        <div class="loader-stars" id="loaderStars"></div>
        <!-- Horizon overlay -->
        <div class="loader-horizon"></div>

        <!-- Truck scene -->
        <div class="truck-scene">

            <!-- Headlight cone -->
            <div class="headlight-glow"></div>

            <!-- Smoke puffs from exhaust stack -->
            <div class="smoke-container">
                <div class="smoke" style="--dur:1.5s;--delay:0s;  --dx:6px; --dx2:12px; width:18px;height:18px;top:0;left:0;"></div>
                <div class="smoke" style="--dur:1.9s;--delay:0.5s;--dx:-4px;--dx2:8px;  width:24px;height:24px;top:2px;left:-5px;"></div>
                <div class="smoke" style="--dur:1.3s;--delay:1s;  --dx:10px;--dx2:18px; width:14px;height:14px;top:-4px;left:4px;"></div>
                <div class="smoke" style="--dur:2.1s;--delay:0.25s;--dx:3px;--dx2:9px; width:20px;height:20px;top:3px;left:-9px;"></div>
            </div>

            <!-- 3D Truck SVG -->
            <div class="truck-svg-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 420 130" width="420" height="130">
                    <defs>
                        <!-- Trailer body gradient (dark gunmetal, matches tractor) -->
                        <linearGradient id="trailerSide" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%"   stop-color="#4a4d52"/>
                            <stop offset="18%"  stop-color="#34373c"/>
                            <stop offset="55%"  stop-color="#232529"/>
                            <stop offset="100%" stop-color="#131416"/>
                        </linearGradient>
                        <!-- Trailer roof gradient -->
                        <linearGradient id="trailerTop" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%"   stop-color="#606368"/>
                            <stop offset="50%"  stop-color="#45484d"/>
                            <stop offset="100%" stop-color="#2c2e32"/>
                        </linearGradient>
                        <!-- Cab side gradient (gunmetal charcoal, glossy) -->
                        <linearGradient id="cabSide" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%"   stop-color="#6a6d72"/>
                            <stop offset="10%"  stop-color="#45484c"/>
                            <stop offset="45%"  stop-color="#2c2e31"/>
                            <stop offset="100%" stop-color="#131416"/>
                        </linearGradient>
                        <!-- Cab roof gradient -->
                        <linearGradient id="cabTop" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%"   stop-color="#8a8d92"/>
                            <stop offset="40%"  stop-color="#55585d"/>
                            <stop offset="100%" stop-color="#2a2c2f"/>
                        </linearGradient>
                        <!-- Cab front face -->
                        <linearGradient id="cabFront" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%"   stop-color="#232529"/>
                            <stop offset="100%" stop-color="#4a4d52"/>
                        </linearGradient>
                        <!-- Wheel gradient (matte black tyre) -->
                        <radialGradient id="wheelGrad" cx="40%" cy="35%" r="60%">
                            <stop offset="0%"   stop-color="#3a3a3a"/>
                            <stop offset="60%"  stop-color="#151515"/>
                            <stop offset="100%" stop-color="#050505"/>
                        </radialGradient>
                        <!-- Rim gradient (polished chrome) -->
                        <radialGradient id="rimGrad" cx="40%" cy="35%" r="60%">
                            <stop offset="0%"   stop-color="#f4f6f8"/>
                            <stop offset="35%"  stop-color="#cfd4d8"/>
                            <stop offset="70%"  stop-color="#8d949a"/>
                            <stop offset="100%" stop-color="#565c61"/>
                        </radialGradient>
                        <!-- Window gradient (dark tinted glass) -->
                        <linearGradient id="windowGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%"   stop-color="#5c6672" stop-opacity="0.9"/>
                            <stop offset="100%" stop-color="#181c22" stop-opacity="0.92"/>
                        </linearGradient>
                        <!-- Chrome stripe gradient (replaces gold trim) -->
                        <linearGradient id="goldStripe" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%"   stop-color="#ffffff"/>
                            <stop offset="30%"  stop-color="#c6cbd0"/>
                            <stop offset="70%"  stop-color="#eef1f3"/>
                            <stop offset="100%" stop-color="#8d949a"/>
                        </linearGradient>
                        <!-- Amber marker-light gradient -->
                        <linearGradient id="amberStripe" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%"   stop-color="#ff9a3d"/>
                            <stop offset="100%" stop-color="#ff6a00"/>
                        </linearGradient>
                        <!-- Undercarriage gradient -->
                        <linearGradient id="underGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%"   stop-color="#1a1a1c"/>
                            <stop offset="100%" stop-color="#050506"/>
                        </linearGradient>
                    </defs>

                    <!-- ── Trailer body ── -->
                    <!-- Trailer undercarriage -->
                    <rect x="18" y="88" width="282" height="14" rx="2" fill="url(#underGrad)"/>
                    <!-- Trailer side panel -->
                    <rect x="18" y="30" width="282" height="62" rx="3" fill="url(#trailerSide)"/>
                    <!-- Trailer top face (3D effect) -->
                    <polygon points="18,30 300,30 296,18 22,18" fill="url(#trailerTop)"/>
                    <!-- Trailer front face (3D depth) -->
                    <polygon points="300,30 310,20 310,88 300,92" fill="#0f1012"/>
                    <!-- Trailer panel ribs (vertical lines for realism) -->
                    <line x1="60"  y1="32" x2="60"  y2="90" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                    <line x1="102" y1="32" x2="102" y2="90" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                    <line x1="144" y1="32" x2="144" y2="90" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                    <line x1="186" y1="32" x2="186" y2="90" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                    <line x1="228" y1="32" x2="228" y2="90" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                    <line x1="270" y1="32" x2="270" y2="90" stroke="rgba(255,255,255,0.06)" stroke-width="1.5"/>
                    <!-- Chrome branding stripe on trailer -->
                    <rect x="18" y="70" width="282" height="7" fill="url(#goldStripe)" opacity="0.75"/>
                    <!-- LogiTrack text on trailer -->
                    <text x="140" y="62" text-anchor="middle" font-family="'Inter',Arial,sans-serif" font-size="13" font-weight="700" fill="rgba(230,232,235,0.75)" letter-spacing="3">LOGITRACK</text>
                    <!-- Trailer rear lights -->
                    <rect x="18" y="35" width="7" height="16" rx="2" fill="#c0280a" opacity="0.9"/>
                    <rect x="18" y="55" width="7" height="10" rx="2" fill="#ff9a3d" opacity="0.75"/>

                    <!-- ── Cab body ── -->
                    <!-- Cab undercarriage -->
                    <rect x="296" y="88" width="104" height="14" rx="2" fill="url(#underGrad)"/>
                    <!-- Cab side panel -->
                    <rect x="296" y="36" width="90" height="56" rx="3" fill="url(#cabSide)"/>
                    <!-- Sleeper roof (tall, rounded aero shape like the reference) -->
                    <path d="M296,36 L296,26 Q296,16 308,15 L370,13 Q384,13 386,24 L386,36 Z" fill="url(#cabTop)"/>
                    <!-- Cab front face -->
                    <polygon points="386,22 404,28 404,96 386,92" fill="url(#cabFront)"/>
                    <!-- Cab-trailer connector -->
                    <rect x="290" y="54" width="14" height="36" rx="2" fill="#0a0a0c"/>

                    <!-- Chrome visor above windshield -->
                    <rect x="328" y="34" width="54" height="4" rx="2" fill="url(#goldStripe)" opacity="0.9"/>
                    <!-- Amber marker lights on visor (matches reference cab lights) -->
                    <rect x="333" y="30" width="6" height="2.4" rx="1" fill="url(#amberStripe)"/>
                    <rect x="343" y="29" width="6" height="2.4" rx="1" fill="url(#amberStripe)"/>
                    <rect x="353" y="28.5" width="6" height="2.4" rx="1" fill="url(#amberStripe)"/>
                    <rect x="363" y="29" width="6" height="2.4" rx="1" fill="url(#amberStripe)"/>
                    <rect x="373" y="30" width="6" height="2.4" rx="1" fill="url(#amberStripe)"/>

                    <!-- Cab window (large, dark tinted) -->
                    <rect x="330" y="40" width="50" height="32" rx="3" fill="url(#windowGrad)"/>
                    <!-- Window glare highlight -->
                    <polygon points="332,42 356,42 345,50" fill="rgba(255,255,255,0.22)"/>
                    <!-- Cab door line -->
                    <line x1="340" y1="74" x2="340" y2="92" stroke="rgba(0,0,0,0.4)" stroke-width="1.5"/>
                    <!-- Door handle -->
                    <rect x="326" y="80" width="10" height="3" rx="1.5" fill="url(#goldStripe)"/>

                    <!-- Chrome exhaust stack -->
                    <rect x="374" y="8" width="8" height="28" rx="3" fill="url(#rimGrad)"/>
                    <rect x="372" y="6" width="12" height="4" rx="2" fill="url(#rimGrad)"/>

                    <!-- Headlight cluster (angular, LED style) -->
                    <rect x="400" y="48" width="17" height="15" rx="4" fill="#fdfdf6" opacity="0.97"/>
                    <rect x="400" y="48" width="17" height="15" rx="4" fill="rgba(220,230,240,0.25)"/>
                    <!-- Headlight lens shine -->
                    <ellipse cx="406" cy="53" rx="3" ry="2" fill="rgba(255,255,255,0.7)"/>
                    <!-- Amber DRL strip under headlight -->
                    <rect x="401" y="63.5" width="15" height="2" rx="1" fill="url(#amberStripe)"/>
                    <!-- Chrome grille surround -->
                    <rect x="397" y="67" width="20" height="21" rx="3" fill="url(#rimGrad)"/>
                    <!-- Grille interior -->
                    <rect x="399" y="69" width="16" height="17" rx="2" fill="#0a0a0c"/>
                    <line x1="401" y1="72" x2="413" y2="72" stroke="rgba(220,224,228,0.55)" stroke-width="1.4"/>
                    <line x1="401" y1="76" x2="413" y2="76" stroke="rgba(220,224,228,0.55)" stroke-width="1.4"/>
                    <line x1="401" y1="80" x2="413" y2="80" stroke="rgba(220,224,228,0.55)" stroke-width="1.4"/>
                    <line x1="401" y1="84" x2="413" y2="84" stroke="rgba(220,224,228,0.55)" stroke-width="1.4"/>
                    <!-- Chrome front bumper (deep, like the reference) -->
                    <rect x="393" y="86" width="26" height="9" rx="2" fill="url(#rimGrad)"/>
                    <rect x="393" y="88.5" width="26" height="2" fill="rgba(0,0,0,0.18)"/>

                    <!-- ── Cab wheels (animated) ── -->
                    <g style="animation:wheelSpin 0.4s linear infinite;transform-origin:356px 102px">
                        <circle cx="356" cy="102" r="17" fill="url(#wheelGrad)"/>
                        <circle cx="356" cy="102" r="10" fill="url(#rimGrad)"/>
                        <circle cx="356" cy="102" r="4"  fill="#1a1a1a"/>
                        <line x1="356" y1="87"  x2="356" y2="117" stroke="#8a9096" stroke-width="2"/>
                        <line x1="341" y1="102" x2="371" y2="102" stroke="#8a9096" stroke-width="2"/>
                        <line x1="345" y1="91"  x2="367" y2="113" stroke="#8a9096" stroke-width="1.5"/>
                        <line x1="367" y1="91"  x2="345" y2="113" stroke="#8a9096" stroke-width="1.5"/>
                    </g>
                    <g style="animation:wheelSpin 0.4s linear infinite;transform-origin:395px 102px">
                        <circle cx="395" cy="102" r="17" fill="url(#wheelGrad)"/>
                        <circle cx="395" cy="102" r="10" fill="url(#rimGrad)"/>
                        <circle cx="395" cy="102" r="4"  fill="#1a1a1a"/>
                        <line x1="395" y1="87"  x2="395" y2="117" stroke="#8a9096" stroke-width="2"/>
                        <line x1="380" y1="102" x2="410" y2="102" stroke="#8a9096" stroke-width="2"/>
                        <line x1="384" y1="91"  x2="406" y2="113" stroke="#8a9096" stroke-width="1.5"/>
                        <line x1="406" y1="91"  x2="384" y2="113" stroke="#8a9096" stroke-width="1.5"/>
                    </g>

                    <!-- ── Trailer wheels (animated) ── -->
                    <g style="animation:wheelSpin 0.4s linear infinite;transform-origin:60px 102px">
                        <circle cx="60"  cy="102" r="16" fill="url(#wheelGrad)"/>
                        <circle cx="60"  cy="102" r="9"  fill="url(#rimGrad)"/>
                        <circle cx="60"  cy="102" r="3.5" fill="#1a1a1a"/>
                        <line x1="60"  y1="88"  x2="60"  y2="116" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="46"  y1="102" x2="74"  y2="102" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="50"  y1="92"  x2="70"  y2="112" stroke="#8a9096" stroke-width="1.4"/>
                        <line x1="70"  y1="92"  x2="50"  y2="112" stroke="#8a9096" stroke-width="1.4"/>
                    </g>
                    <g style="animation:wheelSpin 0.4s linear infinite;transform-origin:96px 102px">
                        <circle cx="96"  cy="102" r="16" fill="url(#wheelGrad)"/>
                        <circle cx="96"  cy="102" r="9"  fill="url(#rimGrad)"/>
                        <circle cx="96"  cy="102" r="3.5" fill="#1a1a1a"/>
                        <line x1="96"  y1="88"  x2="96"  y2="116" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="82"  y1="102" x2="110" y2="102" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="86"  y1="92"  x2="106" y2="112" stroke="#8a9096" stroke-width="1.4"/>
                        <line x1="106" y1="92"  x2="86"  y2="112" stroke="#8a9096" stroke-width="1.4"/>
                    </g>
                    <g style="animation:wheelSpin 0.4s linear infinite;transform-origin:218px 102px">
                        <circle cx="218" cy="102" r="16" fill="url(#wheelGrad)"/>
                        <circle cx="218" cy="102" r="9"  fill="url(#rimGrad)"/>
                        <circle cx="218" cy="102" r="3.5" fill="#1a1a1a"/>
                        <line x1="218" y1="88"  x2="218" y2="116" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="204" y1="102" x2="232" y2="102" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="208" y1="92"  x2="228" y2="112" stroke="#8a9096" stroke-width="1.4"/>
                        <line x1="228" y1="92"  x2="208" y2="112" stroke="#8a9096" stroke-width="1.4"/>
                    </g>
                    <g style="animation:wheelSpin 0.4s linear infinite;transform-origin:254px 102px">
                        <circle cx="254" cy="102" r="16" fill="url(#wheelGrad)"/>
                        <circle cx="254" cy="102" r="9"  fill="url(#rimGrad)"/>
                        <circle cx="254" cy="102" r="3.5" fill="#1a1a1a"/>
                        <line x1="254" y1="88"  x2="254" y2="116" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="240" y1="102" x2="268" y2="102" stroke="#8a9096" stroke-width="1.8"/>
                        <line x1="244" y1="92"  x2="264" y2="112" stroke="#8a9096" stroke-width="1.4"/>
                        <line x1="264" y1="92"  x2="244" y2="112" stroke="#8a9096" stroke-width="1.4"/>
                    </g>

                    <!-- Mud flaps -->
                    <rect x="51"  y="98" width="4" height="14" rx="1" fill="#111"/>
                    <rect x="96"  y="98" width="4" height="14" rx="1" fill="#111"/>
                    <rect x="208" y="98" width="4" height="14" rx="1" fill="#111"/>
                    <rect x="255" y="98" width="4" height="14" rx="1" fill="#111"/>
                    <rect x="346" y="98" width="4" height="14" rx="1" fill="#111"/>
                    <rect x="385" y="98" width="4" height="14" rx="1" fill="#111"/>
                </svg>
            </div>

            <!-- Distant skyline (parallax layer) -->
            <div class="loader-skyline"></div>

            <!-- Ground contact shadow under the truck -->
            <div class="truck-ground-shadow"></div>

            <!-- Road -->
            <div class="road-surface">
                <div class="road-top-stripe"></div>
                <div class="road-dashes">
                    <span></span><span></span><span></span><span></span>
                    <span></span><span></span><span></span><span></span>
                    <span></span><span></span><span></span><span></span>
                </div>
                <div class="road-stripe"></div>
            </div>
        </div>

        <!-- Brand + progress -->
        <div class="loader-brand">
            <i class="fas fa-truck-fast loader-brand-icon"></i>
            <span class="loader-brand-text">LogiTrack</span><span class="loader-brand-pro">PRO</span>
        </div>
        <div class="loader-progress-bar">
            <div class="loader-progress-fill" id="loaderProgressFill"></div>
        </div>
        <div class="loader-progress-pct" id="loaderProgressPct">0%</div>
    </div>

    <style>
        @keyframes wheelSpin { from{transform:rotate(0deg)} to{transform:rotate(-360deg)} }
    </style>
    <script>
        // Generate starfield
        (function(){
            const stars = document.getElementById('loaderStars');
            if (!stars) return;
            for (let i = 0; i < 80; i++) {
                const s = document.createElement('span');
                const sz = Math.random() * 2.5 + 0.8;
                s.style.cssText = [
                    'width:' + sz + 'px', 'height:' + sz + 'px',
                    'top:' + Math.random() * 65 + '%',
                    'left:' + Math.random() * 100 + '%',
                    '--dur:' + (2 + Math.random() * 3.5).toFixed(1) + 's',
                    '--delay:-' + (Math.random() * 4).toFixed(1) + 's'
                ].join(';');
                stars.appendChild(s);
            }
        })();

        // Drive a determinate progress readout that lands at 100% right as
        // the dashboard finishes initialising (see loader hide timing in script.js)
        (function(){
            const fill = document.getElementById('loaderProgressFill');
            const pct  = document.getElementById('loaderProgressPct');
            if (!fill || !pct) return;
            const duration = 4800; // ms — matches the loader's real init window (see script.js)
            let start = null;
            function tick(ts){
                if (start === null) start = ts;
                const elapsed = ts - start;
                // Ease-out so it feels like it's settling in, not linear-robotic
                const t = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - t, 3);
                const value = Math.round(eased * 100);
                fill.style.width = value + '%';
                pct.textContent = value + '%';
                if (t < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        })();
    </script>

    <div class="container">
        <!-- Side Navigation -->
        <nav class="center-nav" id="sidebar">
            <div class="logo">
                <i class="fas fa-truck-fast"></i>
                <span>LogiTrack <span class="pro-badge">PRO</span></span>
            </div>
            
            <ul class="nav-links">
                <li data-tooltip="Dashboard Overview">
                    <a href="#dashboard" class="nav-link" data-section="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                        <span class="nav-badge">Live</span>
                    </a>
                </li>
                <li data-tooltip="Manage Shipments">
                    <a href="#shipments" class="nav-link" data-section="shipments">
                        <i class="fas fa-box"></i>
                        <span>Shipments</span>
                        <span class="count-badge" id="shipmentCount">0</span>
                    </a>
                </li>
                <li data-tooltip="Real-time Tracking">
                    <a href="#tracking" class="nav-link" data-section="tracking">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Tracking</span>
                    </a>
                </li>
                <?php if (!$isStaff): ?>
                <li data-tooltip="Warehouse Management">
                    <a href="#warehouse" class="nav-link" data-section="warehouse">
                        <i class="fas fa-warehouse"></i>
                        <span>Warehouse</span>
                    </a>
                </li>
                <li data-tooltip="Fleet Operations">
                    <a href="#fleet" class="nav-link" data-section="fleet">
                        <i class="fas fa-truck-moving"></i>
                        <span>Fleet</span>
                    </a>
                </li>
                <li data-tooltip="Analytics & Reports">
                    <a href="#reports" class="nav-link" data-section="reports">
                        <i class="fas fa-chart-line"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="user-info">
                <div class="notif-bell-wrap" style="position:relative;">
                    <button class="settings-btn" id="notifBellBtn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadge" style="display:none; position:absolute; top:-2px; right:-2px; background:#c0392b; color:#fff; font-size:10px; font-weight:700; border-radius:10px; padding:1px 5px; line-height:1;">0</span>
                    </button>
                </div>
                <div class="user-avatar">
                    <img src="<?php echo htmlspecialchars($currentUser['avatar'] ?: 'truckicon.jpg'); ?>" alt="User" onerror="this.src='https://via.placeholder.com/40'">
                    <div class="online-indicator"></div>
                </div>
                <div>
                    <p class="user-name"><?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                    <!-- Shows only the single role chosen at registration
                         (Staff/Manager/Admin) — see $roleLabel set above. -->
                    <p class="user-role"><?php echo htmlspecialchars($roleLabel); ?></p>
                </div>
                <button class="settings-btn" id="settingsBtn">
                    <i class="fas fa-cog"></i>
                </button>
                <a href="logout.php" class="settings-btn" title="Log out" style="text-decoration:none;">
                    <i class="fas fa-right-from-bracket"></i>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section active">
                <header class="header">
                    <div class="header-left">
                        <div class="welcome-animation">
                            <h1>Shipment Dashboard</h1>
                            <p class="welcome-text">
                                <i class="fas fa-waveform"></i>
                                Welcome back, <?php echo htmlspecialchars(explode(' ', trim($currentUser['full_name']))[0]); ?>! Here's your logistics overview.
                            </p>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="date-time" id="dateTime"></div>
                        <button class="btn-primary" id="newShipmentBtn">
                            <i class="fas fa-plus-circle"></i> New Shipment
                        </button>
                    </div>
                </header>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card stat-card-1">
                        <div class="stat-icon blue">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Shipments</h3>
                            <p class="stat-value" id="totalShipments">0</p>
                            <span class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +12% from last month
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-card-2">
                        <div class="stat-icon green">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Delivered</h3>
                            <p class="stat-value" id="deliveredShipments">0</p>
                            <span class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +5% from last month
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-card-3">
                        <div class="stat-icon orange">
                            <i class="fas fa-truck-ramp-box"></i>
                        </div>
                        <div class="stat-details">
                            <h3>In Transit</h3>
                            <p class="stat-value" id="inTransitShipments">0</p>
                            <span class="stat-change">
                                <i class="fas fa-truck"></i> Active shipments
                            </span>
                        </div>
                        <div class="live-indicator">
                            <span class="pulse-dot"></span> Live Tracking
                        </div>
                    </div>
                    
                    <div class="stat-card stat-card-4">
                        <div class="stat-icon red">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Delayed</h3>
                            <p class="stat-value" id="delayedShipments">0</p>
                            <span class="stat-change negative">
                                <i class="fas fa-arrow-down"></i> Needs attention
                            </span>
                        </div>
                        <button class="alert-btn" onclick="showAlerts()">
                            <i class="fas fa-bell"></i> View Details
                        </button>
                    </div>
                </div>

                <!-- Shipment Table -->
                <div class="shipments-section">
                    <div class="section-header">
                        <div class="title-section">
                            <h2>
                                <i class="fas fa-list-ul"></i> Recent Shipments
                            </h2>
                            <div class="filter-buttons">
                                <button class="filter-btn active" data-filter="all">All</button>
                                <button class="filter-btn" data-filter="In Transit">In Transit</button>
                                <button class="filter-btn" data-filter="Delivered">Delivered</button>
                                <button class="filter-btn" data-filter="Pending">Pending</button>
                                <button class="filter-btn" data-filter="Delayed">Delayed</button>
                            </div>
                        </div>
                        <div class="search-bar">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search by ID, origin, destination...">
                            <button class="filter-icon" id="advancedFilterBtn">
                                <i class="fas fa-sliders-h"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table id="shipmentsTable">
                            <thead>
                                    <th>Tracking ID <i class="fas fa-sort"></i></th>
                                    <th>Origin <i class="fas fa-sort"></i></th>
                                    <th>Destination <i class="fas fa-sort"></i></th>
                                    <th>Status</th>
                                    <th>Estimated Delivery</th>
                                    <th>Priority</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="shipmentsBody">
                                <!-- Shipments will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination" id="pagination">
                        <button class="page-btn" id="prevPage"><i class="fas fa-chevron-left"></i></button>
                        <span id="pageInfo">Page 1 of 1</span>
                        <button class="page-btn" id="nextPage"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>

                <!-- Fleet Status -->
                <div class="fleet-section">
                    <div class="fleet-header">
                        <h2>
                            <i class="fas fa-truck-moving"></i> Fleet Status
                        </h2>
                        <button class="refresh-btn" id="refreshFleet">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="fleet-grid" id="fleetGrid">
                        <!--  vehicles will be dynamically added here -->
                    </div>
                </div>

                <!-- Analytics Section -->
                <div class="analytics-section">
                    <h2>
                        <i class="fas fa-chart-line"></i> Performance Analytics
                    </h2>
                    <div class="analytics-grid">
                        <div class="analytics-card">
                            <h4>Delivery Performance</h4>
                            <div class="chart-container">
                                <canvas id="deliveryChart" width="300" height="200"></canvas>
                            </div>
                        </div>
                        <div class="analytics-card">
                            <h4>Shipment Trends</h4>
                            <div class="chart-container">
                                <canvas id="trendChart" width="300" height="200"></canvas>
                            </div>
                        </div>
                        <div class="analytics-card">
                            <h4>Priority Distribution</h4>
                            <div class="chart-container">
                                <canvas id="priorityChart" width="300" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipments Section -->
            <div id="shipments-section" class="content-section">
                <div class="section-page">
                    <h2><i class="fas fa-box"></i> All Shipments Management</h2>
                    <div class="shipments-controls">
                        <button class="btn-primary" onclick="openModal()">
                            <i class="fas fa-plus"></i> Create New Shipment
                        </button>
                        <div class="export-buttons">
                            <button class="btn-secondary" onclick="exportShipments('csv')">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button class="btn-secondary" onclick="exportShipments('pdf')">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="full-table">
                            <thead>
                                <tr>
                                    <th>Tracking ID</th>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>Weight (kg)</th>
                                    <th>Priority</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="allShipmentsBody">
                                <!-- All shipments will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tracking Section -->
            <div id="tracking-section" class="content-section">
                <div class="section-page">
                    <h2><i class="fas fa-map-marker-alt"></i> Real-time Shipment Tracking</h2>
                    <div class="tracking-search">
                        <input type="text" id="trackingIdInput" placeholder="Enter Tracking ID (e.g., TRK001)" class="tracking-input">
                        <button class="btn-primary" onclick="trackShipmentById()">
                            <i class="fas fa-search"></i> Track Shipment
                        </button>
                    </div>
                    <div id="trackingResult" class="tracking-result">
                        <!-- Tracking results will be displayed here -->
                    </div>
                    <div class="recent-tracking">
                        <h3>Recently Tracked</h3>
                        <div id="recentTrackingList" class="tracking-list">
                            <!-- Recently tracked shipments -->
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$isStaff): ?>
            <!-- Warehouse Section -->
            <div id="warehouse-section" class="content-section">
                <div class="section-page">
                    <div class="warehouse-header-row">
                        <h2><i class="fas fa-warehouse"></i> Warehouse Management</h2>
                        <div class="warehouse-controls">
                            <button class="btn-primary" id="addStockBtn" onclick="addStockItem()">
                                <i class="fas fa-plus-circle"></i> Add Stock Item
                            </button>
                        </div>
                    </div>
                    <div class="warehouse-stats">
                        <div class="warehouse-card">
                            <i class="fas fa-cubes"></i>
                            <h3>Total Inventory</h3>
                            <p class="stat-value" id="totalInventory">2,547</p>
                            <span>Items in stock</span>
                        </div>
                        <div class="warehouse-card">
                            <i class="fas fa-boxes"></i>
                            <h3>Storage Used</h3>
                            <p class="stat-value" id="storageUsed">78<span style="font-size: 20px;">%</span></p>
                            <div class="storage-bar">
                                <div class="storage-fill" style="width: 78%"></div>
                            </div>
                        </div>
                        <div class="warehouse-card">
                            <i class="fas fa-truck-loading"></i>
                            <h3>Pending Pickups</h3>
                            <p class="stat-value" id="pendingPickups">23</p>
                            <span>Ready for dispatch</span>
                        </div>
                    </div>
                    <div class="inventory-section">
                        <h3>Current Inventory</h3>
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th>Item SKU</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryBody">
                                <!-- Inventory items will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Fleet Section -->
            <div id="fleet-section" class="content-section">
                <div class="section-page">
                    <h2><i class="fas fa-truck-moving"></i> Fleet Management</h2>
                    <div class="fleet-controls">
                        <button class="btn-primary" id="addVehicleBtn" onclick="addVehicle()">
                            <i class="fas fa-plus"></i> Add Vehicle
                        </button>
                        <button class="btn-secondary" onclick="refreshFleetData()">
                            <i class="fas fa-sync-alt"></i> Refresh Data
                        </button>
                    </div>
                    <div class="fleet-stats">
                        <div class="fleet-stat-card">
                            <i class="fas fa-truck"></i>
                            <h3>Active Vehicles</h3>
                            <p id="activeVehicles">0</p>
                        </div>
                        <div class="fleet-stat-card">
                            <i class="fas fa-wrench"></i>
                            <h3>In Maintenance</h3>
                            <p id="maintenanceVehicles">0</p>
                        </div>
                        <div class="fleet-stat-card">
                            <i class="fas fa-gas-pump"></i>
                            <h3>Average Fuel</h3>
                            <p id="averageFuel">0<span style="font-size: 20px;">%</span></p>
                        </div>
                    </div>
                    <div class="full-fleet-grid" id="fullFleetGrid">
                        <!-- All fleet vehicles will be displayed here -->
                    </div>
                </div>
            </div>

            <!-- Reports Section -->
            <div id="reports-section" class="content-section">
                <div class="section-page">
                    <h2><i class="fas fa-chart-line"></i> Analytics & Reports</h2>
                    <div class="report-filters">
                        <select id="reportType" class="report-select">
                            <option value="shipments">Shipments Report</option>
                            <option value="fleet">Fleet Performance</option>
                            <option value="warehouse">Warehouse Report</option>
                            <option value="financial">Financial Summary</option>
                        </select>
                        <input type="date" id="reportStartDate" class="date-input">
                        <input type="date" id="reportEndDate" class="date-input">
                        <button class="btn-primary" onclick="generateReport()">
                            <i class="fas fa-chart-bar"></i> Generate Report
                        </button>
                    </div>
                    <div id="reportResults" class="report-results">
                        <div class="report-cards">
                            <div class="report-card">
                                <h4>Total Shipments</h4>
                                <p class="report-value" id="reportTotalShipments">0</p>
                                <span>This month</span>
                            </div>
                            <div class="report-card">
                                <h4>On-Time Delivery Rate</h4>
                                <p class="report-value" id="reportOnTimeRate">94<span style="font-size: 24px;">%</span></p>
                                <span>+5% vs last month</span>
                            </div>
                            <div class="report-card">
                                <h4>Average Delivery Time</h4>
                                <p class="report-value" id="reportAvgTime">3.2<span style="font-size: 24px;"> days</span></p>
                                <span>-0.5 days</span>
                            </div>
                            <div class="report-card">
                                <h4>Customer Satisfaction</h4>
                                <p class="report-value" id="reportSatisfaction">4.8<span style="font-size: 24px;">/5</span></p>
                                <span>Excellent</span>
                            </div>
                        </div>
                        <div class="detailed-report">
                            <canvas id="detailedChart" width="800" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- New Shipment Modal -->
    <div id="shipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Create New Shipment</h3>
                <span class="close">&times;</span>
            </div>
            <form id="shipmentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Origin</label>
                        <input type="text" id="origin" placeholder="Enter origin city" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-flag-checkered"></i> Destination</label>
                        <input type="text" id="destination" placeholder="Enter destination city" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-weight-hanging"></i> Weight (kg)</label>
                        <input type="number" id="weight" placeholder="Enter weight" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Priority</label>
                        <select id="priority" required>
                            <option value="Standard">📦 Standard (5-7 days)</option>
                            <option value="Express">⚡ Express (2-3 days)</option>
                            <option value="Priority">🚀 Priority (24 hours)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Estimated Delivery Date</label>
                    <input type="date" id="estimatedDelivery" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sticky-note"></i> Special Instructions</label>
                    <textarea id="instructions" rows="3" placeholder="Any special handling instructions..."></textarea>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Create Shipment
                </button>
            </form>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div id="vehicleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-truck"></i> Add New Vehicle</h3>
                <span class="close-vehicle">&times;</span>
            </div>
            <form id="vehicleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-truck-moving"></i> Vehicle Type</label>
                        <select id="vehicleType" required>
                            <option value="Truck">🚚 Truck</option>
                            <option value="Van">🚐 Van</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status</label>
                        <select id="vehicleStatus" required>
                            <option value="Available">Available</option>
                            <option value="In Transit">In Transit</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Driver</label>
                        <input type="text" id="vehicleDriver" placeholder="Enter driver name (or leave blank)">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-pin"></i> Current Location</label>
                        <input type="text" id="vehicleLocation" placeholder="Enter current location" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-gas-pump"></i> Fuel Level (%)</label>
                        <input type="number" id="vehicleFuel" min="0" max="100" placeholder="e.g. 85" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Next Service Date</label>
                        <input type="date" id="vehicleNextService" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Add Vehicle
                </button>
            </form>
        </div>
    </div>

    <!-- Add Stock Item Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-boxes"></i> Add Stock Item</h3>
                <span class="close-stock">&times;</span>
            </div>
            <form id="stockForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Description</label>
                        <input type="text" id="stockDescription" placeholder="e.g. Electronics" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-pin"></i> Location</label>
                        <input type="text" id="stockLocation" placeholder="e.g. Aisle 6" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-cubes"></i> Quantity</label>
                        <input type="number" id="stockQuantity" min="0" placeholder="e.g. 250" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status</label>
                        <select id="stockStatus" required>
                            <option value="In Stock">In Stock</option>
                            <option value="Low Stock">Low Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Add Item
                </button>
            </form>
        </div>
    </div>

    <!-- Vehicle Action Popup (Track / Service confirmation) -->
    <div id="vehicleActionModal" class="modal">
        <div class="modal-content vehicle-action-content">
            <div class="modal-header">
                <h3 id="vehicleActionTitle"><i class="fas fa-info-circle"></i> Vehicle Update</h3>
                <span class="close-vehicle-action">&times;</span>
            </div>
            <div id="vehicleActionBody" class="vehicle-action-body">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Shipment Action Popup (View Details / Track) -->
    <div id="shipmentActionModal" class="modal">
        <div class="modal-content vehicle-action-content">
            <div class="modal-header">
                <h3 id="shipmentActionTitle"><i class="fas fa-info-circle"></i> Shipment Update</h3>
                <span class="close-shipment-action">&times;</span>
            </div>
            <div id="shipmentActionBody" class="vehicle-action-body">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Edit Shipment Modal -->
    <div id="editShipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Shipment</h3>
                <span class="close-edit-shipment">&times;</span>
            </div>
            <form id="editShipmentForm">
                <input type="hidden" id="editShipmentId">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Origin</label>
                        <input type="text" id="editOrigin" placeholder="Enter origin city" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-flag-checkered"></i> Destination</label>
                        <input type="text" id="editDestination" placeholder="Enter destination city" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-weight-hanging"></i> Weight (kg)</label>
                        <input type="number" id="editWeight" placeholder="Enter weight" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Priority</label>
                        <select id="editPriority" required>
                            <option value="Standard">📦 Standard (5-7 days)</option>
                            <option value="Express">⚡ Express (2-3 days)</option>
                            <option value="Priority">🚀 Priority (24 hours)</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status</label>
                        <select id="editStatus" required>
                            <option value="Pending">Pending</option>
                            <option value="In Transit">In Transit</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Delayed">Delayed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Estimated Delivery Date</label>
                        <input type="date" id="editEstimatedDelivery" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

        <!-- Notifications Popup -->
        <div id="notifModal" class="notif-modal" aria-hidden="true">
            <div class="notif-modal-card" role="dialog" aria-modal="true" aria-labelledby="notifModalTitle">
                <div class="notif-modal-header">
                    <h3 id="notifModalTitle"><i class="fas fa-bell"></i> Notifications</h3>
                    <button type="button" class="notif-close" id="notifCloseBtn" aria-label="Close">&times;</button>
                </div>
                <div class="notif-summary" id="notifSummary">Checking…</div>
                <div class="notif-tabs">
                    <button type="button" class="notif-tab active" data-filter="unread">Unread <span id="notifUnreadCount">0</span></button>
                    <button type="button" class="notif-tab" data-filter="all">All</button>
                </div>
                <div id="notifList" class="notif-list"><div class="notif-empty">Loading…</div></div>
                <div class="notif-footer">
                    <button type="button" id="notifMarkAllBtn" class="notif-markall"><i class="fas fa-check-double"></i> Mark all as read</button>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <div id="settingsModal" class="modal">
            <div class="modal-content settings-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-cog"></i> Settings</h3>
                    <span class="close-settings">&times;</span>
                </div>
                
                <div class="settings-tabs">
                    <button class="tab-btn active" data-tab="profile">
                        <i class="fas fa-user"></i> Profile
                    </button>
                    <button class="tab-btn" data-tab="preferences">
                        <i class="fas fa-sliders-h"></i> Preferences
                    </button>
                    <button class="tab-btn" data-tab="notifications">
                        <i class="fas fa-bell"></i> Notifications
                    </button>
                    <button class="tab-btn" data-tab="security">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                    <button class="tab-btn" data-tab="appearance">
                        <i class="fas fa-palette"></i> Appearance
                    </button>
                    <?php if ($isAdmin): ?>
                    <button class="tab-btn" data-tab="users">
                        <i class="fas fa-users-cog"></i> Users
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="settings-content">
                    <!-- Profile Tab -->
                    <div id="profile-tab" class="tab-content active">
                        <div class="profile-settings">
                            <div class="profile-avatar-section">
                                <div class="profile-avatar-preview">
                                    <img id="profileAvatar" src="<?php echo htmlspecialchars($currentUser['avatar'] ?: 'truckicon.jpg'); ?>" alt="Profile" onerror="this.src='https://via.placeholder.com/100'">
                                    <button class="change-avatar-btn" id="changeAvatarBtn">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                                <input type="file" id="avatarUpload" accept="image/*" style="display: none;">
                            </div>
                            
                            <div class="profile-form">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Full Name</label>
                                    <input type="text" id="userFullName" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email Address</label>
                                    <input type="email" id="userEmail" value="<?php echo htmlspecialchars($currentUser['email']); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-shield-alt"></i> Account Role</label>
                                    <input type="text" value="<?php echo htmlspecialchars($roleLabel); ?>" readonly disabled style="opacity:.75; cursor:not-allowed;">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Phone Number</label>
                                    <input type="tel" id="userPhone" value="<?php echo htmlspecialchars($currentUser['phone'] ?: ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-briefcase"></i> Position</label>
                                    <input type="text" id="userPosition" value="<?php echo htmlspecialchars($currentUser['position'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-building"></i> Department</label>
                                    <input type="text" id="userDepartment" value="<?php echo htmlspecialchars($currentUser['department'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="profile-actions">
                                <button class="btn-primary" id="saveProfileBtn">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button class="btn-secondary" id="cancelProfileBtn">
                                    <i class="fas fa-undo"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preferences Tab -->
                    <div id="preferences-tab" class="tab-content">
                        <div class="preferences-settings">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-language"></i>
                                    <div>
                                        <h4>Language</h4>
                                        <p>Select your preferred language</p>
                                    </div>
                                </div>
                                <select id="languageSelect" class="setting-select">
                                    <option value="en">English</option>
                                    <option value="es">Español</option>
                                    <option value="fr">Français</option>
                                    <option value="de">Deutsch</option>
                                    <option value="zh">中文</option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-chart-line"></i>
                                    <div>
                                        <h4>Default Dashboard View</h4>
                                        <p>Choose your default dashboard layout</p>
                                    </div>
                                </div>
                                <select id="dashboardView" class="setting-select">
                                    <option value="compact">Compact View</option>
                                    <option value="detailed" selected>Detailed View</option>
                                    <option value="analytics">Analytics Focus</option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-table"></i>
                                    <div>
                                        <h4>Items Per Page</h4>
                                        <p>Number of shipments to display per page</p>
                                    </div>
                                </div>
                                <select id="itemsPerPage" class="setting-select">
                                    <option value="5">5 items</option>
                                    <option value="10" selected>10 items</option>
                                    <option value="25">25 items</option>
                                    <option value="50">50 items</option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-chart-pie"></i>
                                    <div>
                                        <h4>Chart Type</h4>
                                        <p>Preferred chart style for analytics</p>
                                    </div>
                                </div>
                                <select id="chartType" class="setting-select">
                                    <option value="line">Line Chart</option>
                                    <option value="bar" selected>Bar Chart</option>
                                    <option value="pie">Pie Chart</option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-sync-alt"></i>
                                    <div>
                                        <h4>Auto Refresh Data</h4>
                                        <p>Automatically refresh dashboard data</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="autoRefresh" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <h4>Refresh Interval</h4>
                                        <p>How often to refresh data (seconds)</p>
                                    </div>
                                </div>
                                <select id="refreshInterval" class="setting-select">
                                    <option value="30">30 seconds</option>
                                    <option value="60" selected>1 minute</option>
                                    <option value="300">5 minutes</option>
                                    <option value="600">10 minutes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div id="notifications-tab" class="tab-content">
                        <div class="notifications-settings">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-bell"></i>
                                    <div>
                                        <h4>Push Notifications</h4>
                                        <p>Receive browser notifications</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="pushNotifications" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <h4>Email Alerts</h4>
                                        <p>Receive important updates via email</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="emailAlerts" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-truck"></i>
                                    <div>
                                        <h4>Shipment Status Updates</h4>
                                        <p>Notify when shipment status changes</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="shipmentUpdates" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div>
                                        <h4>Delay Alerts</h4>
                                        <p>Get notified about delayed shipments</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="delayAlerts" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-chart-line"></i>
                                    <div>
                                        <h4>Weekly Reports</h4>
                                        <p>Receive weekly performance summary</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="weeklyReports">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-volume-up"></i>
                                    <div>
                                        <h4>Sound Alerts</h4>
                                        <p>Play sound for important notifications</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="soundAlerts">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div id="security-tab" class="tab-content">
                        <div class="security-settings">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-lock"></i>
                                    <div>
                                        <h4>Change Password</h4>
                                        <p>Update your account password</p>
                                    </div>
                                </div>
                                <button class="btn-secondary" id="changePasswordBtn">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-fingerprint"></i>
                                    <div>
                                        <h4>Two-Factor Authentication</h4>
                                        <p>Add an extra layer of security</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="twoFactorAuth">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <h4>Session Timeout</h4>
                                        <p>Auto logout after inactivity</p>
                                    </div>
                                </div>
                                <select id="sessionTimeout" class="setting-select">
                                    <option value="15">15 minutes</option>
                                    <option value="30" selected>30 minutes</option>
                                    <option value="60">1 hour</option>
                                    <option value="120">2 hours</option>
                                    <option value="never">Never</option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-history"></i>
                                    <div>
                                        <h4>Login History</h4>
                                        <p>View your recent login activities</p>
                                    </div>
                                </div>
                                <button class="btn-secondary" id="viewLoginHistoryBtn">
                                    <i class="fas fa-list"></i> View History
                                </button>
                            </div>

                            <?php if ($currentUser['role'] === 'admin'): ?>
                            <div class="setting-item" style="flex-direction:column; align-items:stretch;">
                                <div class="setting-info" style="margin-bottom:10px;">
                                    <i class="fas fa-plug"></i>
                                    <div>
                                        <h4>API Keys <span style="font-size:.7rem; font-weight:600; color:#c9a84c; border:1px solid #c9a84c; border-radius:8px; padding:1px 6px; margin-left:4px;">Admin</span></h4>
                                        <p>Generate keys for external / third-party integrations. Requests are rate-limited per key.</p>
                                    </div>
                                </div>
                                <div style="display:flex; gap:8px; margin-bottom:10px;">
                                    <input type="text" id="newApiKeyLabel" placeholder="Key label, e.g. Partner Portal" style="flex:1; padding:8px 10px; border-radius:6px; border:1px solid #ddd;">
                                    <button class="btn-secondary" id="generateApiKeyBtn"><i class="fas fa-plus"></i> Generate</button>
                                </div>
                                <div id="apiKeysList" style="font-size:.82rem;"></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-database"></i>
                                    <div>
                                        <h4>Data Export</h4>
                                        <p>Export all your data</p>
                                    </div>
                                </div>
                                <button class="btn-secondary" id="exportDataBtn">
                                    <i class="fas fa-download"></i> Export Data
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appearance Tab -->
                    <div id="appearance-tab" class="tab-content">
                        <div class="appearance-settings">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-moon"></i>
                                    <div>
                                        <h4>Dark Mode</h4>
                                        <p>Switch between light and dark theme</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="darkMode">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-palette"></i>
                                    <div>
                                        <h4>Theme Color</h4>
                                        <p>Choose your preferred accent color</p>
                                    </div>
                                </div>
                                <div class="color-picker">
                                    <button class="color-option active" data-color="#3498db" style="background: #3498db;"></button>
                                    <button class="color-option" data-color="#2ecc71" style="background: #2ecc71;"></button>
                                    <button class="color-option" data-color="#e74c3c" style="background: #e74c3c;"></button>
                                    <button class="color-option" data-color="#f39c12" style="background: #f39c12;"></button>
                                    <button class="color-option" data-color="#9b59b6" style="background: #9b59b6;"></button>
                                    <button class="color-option" data-color="#1abc9c" style="background: #1abc9c;"></button>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-font"></i>
                                    <div>
                                        <h4>Font Size</h4>
                                        <p>Adjust the text size</p>
                                    </div>
                                </div>
                                <select id="fontSize" class="setting-select">
                                    <option value="small">Small</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="large">Large</option>
                                    <option value="xlarge">Extra Large</option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-border-all"></i>
                                    <div>
                                        <h4>Card Style</h4>
                                        <p>Choose card display style</p>
                                    </div>
                                </div>
                                <select id="cardStyle" class="setting-select">
                                    <option value="default" selected>Default</option>
                                    <option value="compact">Compact</option>
                                    <option value="elevated">Elevated</option>
                                </select>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <i class="fas fa-chart-simple"></i>
                                    <div>
                                        <h4>Animation Speed</h4>
                                        <p>Control UI animation speed</p>
                                    </div>
                                </div>
                                <select id="animationSpeed" class="setting-select">
                                    <option value="fast">Fast</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="slow">Slow</option>
                                    <option value="none">No Animations</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <?php if ($isAdmin): ?>
                    <!-- Users Tab (admin only) -->
                    <div id="users-tab" class="tab-content">
                        <div class="setting-item" style="flex-direction:column; align-items:stretch;">
                            <div class="setting-info" style="margin-bottom:10px;">
                                <i class="fas fa-users-cog"></i>
                                <div>
                                    <h4>Manage User Accounts <span style="font-size:.7rem; font-weight:600; color:#c9a84c; border:1px solid #c9a84c; border-radius:8px; padding:1px 6px; margin-left:4px;">Admin</span></h4>
                                    <p>Change a user's role (Staff / Manager / Admin) or remove their account. You can't change or delete your own account here.</p>
                                </div>
                            </div>
                            <div id="usersList" style="font-size:.85rem;">
                                <div style="padding:16px; color:#888;">Loading…</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="settings-footer">
                    <button class="btn-primary" id="saveAllSettingsBtn">
                        <i class="fas fa-save"></i> Save All Settings
                    </button>
                    <button class="btn-secondary" id="resetSettingsBtn">
                        <i class="fas fa-undo-alt"></i> Reset to Default
                    </button>
                </div>
            </div>
        </div>
    
        <!-- Change Password Modal -->
        <div id="passwordModal" class="modal">
            <div class="modal-content small-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-key"></i> Change Password</h3>
                    <span class="close-password">&times;</span>
                </div>
                <form id="passwordForm">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="newPassword" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmPassword" required>
                    </div>
                    <div id="passwordStrength" class="password-strength"></div>
                    <button type="submit" class="btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    
        <!-- Login History Modal -->
        <div id="loginHistoryModal" class="modal">
            <div class="modal-content medium-modal">
                <div class="modal-header">
                    <h3><i class="fas fa-history"></i> Login History</h3>
                    <span class="close-history">&times;</span>
                </div>
                <div id="loginHistoryList" class="login-history-list">
                    <!-- Login history will be populated here -->
                </div>
            </div>
        </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <!-- Data loaded from the MySQL database, read by script.js on load -->
    <script>
    window.__SEED__ = {
        currentUser: {
            id: <?php echo (int) $currentUser['id']; ?>,
            role: <?php echo json_encode($currentUser['role']); ?>
        },
        shipments: <?php
            $rows = getDB()->query('SELECT * FROM shipments ORDER BY CAST(SUBSTRING(id, 4) AS UNSIGNED) ASC, id ASC')->fetchAll();
            $mapped = array_map(function ($r) {
                return [
                    'id' => $r['id'], 'origin' => $r['origin'], 'destination' => $r['destination'],
                    'status' => $r['status'], 'estimatedDelivery' => $r['estimated_delivery'],
                    'priority' => $r['priority'], 'weight' => (float) $r['weight'],
                    'progress' => (int) $r['progress'], 'created' => $r['created_at'],
                ];
            }, $rows);
            echo json_encode($mapped);
        ?>,
        fleet: <?php
            $rows = getDB()->query('SELECT * FROM vehicles ORDER BY id')->fetchAll();
            $mapped = array_map(function ($r) {
                return [
                    'id' => $r['id'], 'type' => $r['type'], 'status' => $r['status'],
                    'driver' => $r['driver'], 'location' => $r['location'],
                    'fuel' => (int) $r['fuel'], 'nextMaintenance' => $r['next_maintenance'],
                ];
            }, $rows);
            echo json_encode($mapped);
        ?>,
        inventory: <?php
            $rows = getDB()->query('SELECT * FROM inventory ORDER BY sku')->fetchAll();
            $mapped = array_map(function ($r) {
                return [
                    'sku' => $r['sku'], 'description' => $r['description'], 'quantity' => (int) $r['quantity'],
                    'location' => $r['location'], 'status' => $r['status'],
                ];
            }, $rows);
            echo json_encode($mapped);
        ?>
    };
    </script>
    <script src="script.js"></script>
</body>
</html>