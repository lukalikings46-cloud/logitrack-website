<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Tracking | LogiTrack Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --gold:        #c9a84c;
            --gold-light:  #e8c97a;
            --gold-shine:  #f5e0a0;
            --gold-dark:   #8b6914;
            --navy:        #0f1c35;
            --cream:       #e8d5b8;
            --gold-grad:   linear-gradient(135deg,#f0d880 0%,#c9a84c 40%,#8b6914 75%,#c9a84c 100%);
            --gold-grad-h: linear-gradient(135deg,#f5e8a0 0%,#e8c97a 40%,#c9a84c 75%,#a87e20 100%);
            --card-grad:   linear-gradient(160deg,#1a2e50 0%,#112038 55%,#0c1828 100%);
            --inset:       inset 0 2px 6px rgba(0,0,0,0.5),inset 0 1px 3px rgba(0,0,0,0.35);
            --card-shadow: 0 8px 28px rgba(0,0,0,0.5),0 3px 10px rgba(0,0,0,0.4),inset 0 1px 0 rgba(201,168,76,0.18);
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse 80% 60% at 15% 40%, rgba(201,168,76,0.07) 0%, transparent 70%),
                radial-gradient(ellipse 60% 80% at 85% 20%, rgba(74,28,42,0.12) 0%, transparent 60%),
                linear-gradient(160deg,#070e1e 0%,#0f1c35 45%,#0a1428 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #e8d5b8;
        }

        .container { max-width: 1160px; margin: 0 auto; padding: 0 22px; }

        /* ── Header / nav ── */
        .tracking-header {
            background: linear-gradient(180deg,rgba(15,28,53,0.97) 0%,rgba(10,20,38,0.97) 100%);
            border-bottom: 1px solid rgba(201,168,76,0.2);
            box-shadow: 0 4px 20px rgba(0,0,0,0.6), 0 0 0 1px rgba(201,168,76,0.08);
            position: sticky; top: 0; z-index: 1000;
            backdrop-filter: blur(12px);
        }
        .tracking-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 22px;
        }
        .logo {
            font-size: 24px; font-weight: 800;
            display: flex; align-items: center; gap: 10px;
            letter-spacing: 0.03em;
        }
        .logo-icon {
            width: 40px; height: 40px;
            background: var(--gold-grad);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #0f1c35;
            box-shadow: 0 3px 10px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.3);
        }
        .logo-text {
            background: var(--gold-grad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        nav a {
            color: rgba(232,213,184,0.55);
            text-decoration: none;
            margin-left: 22px;
            font-weight: 500; font-size: 13px;
            transition: color 0.25s;
            letter-spacing: 0.02em;
        }
        nav a:hover, nav a.active { color: var(--gold-light); }
        nav a i { margin-right: 5px; }

        /* ── Hero ── */
        .tracking-hero {
            text-align: center;
            padding: 56px 0 36px;
            position: relative;
        }
        .tracking-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 50%;
            transform: translateX(-50%);
            width: 600px; height: 200px;
            background: radial-gradient(ellipse, rgba(201,168,76,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(201,168,76,0.12);
            border: 1px solid rgba(201,168,76,0.28);
            border-radius: 20px;
            padding: 5px 16px;
            font-size: 11px; font-weight: 700;
            color: var(--gold);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.35);
        }
        .hero-badge::before {
            content: '';
            width: 7px; height: 7px;
            background: var(--gold);
            border-radius: 50%;
            animation: heroGlow 1.8s ease-in-out infinite;
            box-shadow: 0 0 6px rgba(201,168,76,0.6);
        }
        @keyframes heroGlow { 0%,100%{opacity:1} 50%{opacity:0.4} }

        .tracking-hero h1 {
            font-size: 46px; font-weight: 800;
            background: var(--gold-grad);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 14px;
            letter-spacing: -0.01em;
            animation: shimmer 5s linear infinite;
        }
        @keyframes shimmer { 0%{background-position:200% center} 100%{background-position:-200% center} }
        .tracking-hero p {
            font-size: 16px;
            color: rgba(232,213,184,0.6);
            max-width: 480px;
            margin: 0 auto;
        }
        @media (max-width: 768px) { .tracking-hero h1 { font-size: 30px; } }

        /* ── Search section ── */
        .tracking-search { padding: 18px 0 36px; }
        .search-card {
            background: var(--card-grad);
            border-radius: 18px;
            border: 1px solid rgba(201,168,76,0.22);
            padding: 28px;
            box-shadow: var(--card-shadow);
            position: relative; overflow: hidden;
        }
        .search-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: var(--gold-grad);
        }
        .search-wrapper {
            position: relative;
            display: flex;
            gap: 14px;
            margin-bottom: 18px;
        }
        .search-icon {
            position: absolute;
            left: 18px; top: 50%;
            transform: translateY(-50%);
            color: rgba(201,168,76,0.5);
            font-size: 16px;
        }
        .search-wrapper input {
            flex: 1;
            padding: 16px 18px 16px 48px;
            background: rgba(201,168,76,0.07);
            border: 1px solid rgba(201,168,76,0.22);
            border-radius: 12px;
            font-size: 15px;
            color: #e8d5b8;
            font-family: 'Inter', sans-serif;
            transition: all 0.25s;
            outline: none;
            box-shadow: var(--inset);
        }
        .search-wrapper input::placeholder { color: rgba(232,213,184,0.3); }
        .search-wrapper input:focus {
            border-color: rgba(201,168,76,0.5);
            box-shadow: var(--inset), 0 0 0 3px rgba(201,168,76,0.12);
        }
        .search-wrapper button {
            padding: 0 28px;
            background: var(--gold-grad);
            color: #0f1c35;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -2px 0 rgba(0,0,0,0.25);
            white-space: nowrap;
        }
        .search-wrapper button:hover {
            background: var(--gold-grad-h);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.55), 0 0 18px rgba(201,168,76,0.28);
        }
        @media (max-width: 768px) { .search-wrapper { flex-direction: column; } .search-wrapper button { padding: 14px 20px; justify-content: center; } }

        .tracking-examples { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .tracking-examples p { color: rgba(232,213,184,0.5); font-size: 13px; }
        .example-btn {
            padding: 5px 14px;
            background: rgba(201,168,76,0.08);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: 20px;
            cursor: pointer; font-size: 12px; font-weight: 600;
            color: rgba(201,168,76,0.75);
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .example-btn:hover {
            background: var(--gold-grad);
            color: #0f1c35;
            border-color: transparent;
            box-shadow: 0 3px 10px rgba(0,0,0,0.4);
        }

        /* ── Spinner ── */
        .loading-indicator { text-align: center; padding: 56px 0; }
        .spinner {
            width: 50px; height: 50px;
            border: 3px solid rgba(201,168,76,0.2);
            border-top-color: var(--gold);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto 18px;
            box-shadow: 0 0 18px rgba(201,168,76,0.15);
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-indicator p { color: rgba(201,168,76,0.7); font-size: 14px; letter-spacing: 0.06em; }

        /* ── Tracking results ── */
        .tracking-results { padding: 18px 0 56px; }

        /* Shipment summary */
        .shipment-summary {
            background: var(--card-grad);
            border-radius: 18px;
            border: 1px solid rgba(201,168,76,0.22);
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
            position: relative; overflow: hidden;
        }
        .shipment-summary::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: var(--gold-grad);
        }
        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap; gap: 14px;
        }
        .summary-header h2 { font-size: 22px; color: var(--gold-light); font-weight: 700; margin-bottom: 4px; }
        .summary-header p  { color: rgba(232,213,184,0.5); font-size: 13px; }

        .status-badge {
            padding: 6px 18px; border-radius: 20px;
            font-weight: 700; font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.08em;
            box-shadow: 0 3px 10px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.12);
        }
        .status-delivered { background: rgba(42,122,75,0.3);   color: #5dd99c; border: 1px solid rgba(93,217,156,0.3); }
        .status-transit   { background: rgba(26,80,128,0.35);  color: #6ab3e8; border: 1px solid rgba(106,179,232,0.3); }
        .status-pending   { background: rgba(184,114,10,0.25); color: #f0c060; border: 1px solid rgba(240,192,96,0.3); }
        .status-delayed   { background: rgba(139,28,28,0.3);   color: #e07070; border: 1px solid rgba(224,112,112,0.3); }

        .summary-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }
        .detail-item { display: flex; align-items: center; gap: 14px; }
        .detail-item i {
            font-size: 22px; color: var(--gold);
            filter: drop-shadow(0 2px 4px rgba(201,168,76,0.35));
            flex-shrink: 0;
        }
        .detail-item label { font-size: 11px; color: rgba(201,168,76,0.6); display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }
        .detail-item p     { font-size: 15px; font-weight: 700; color: var(--cream); }

        /* Map */
        .map-container {
            background: var(--card-grad);
            border-radius: 18px;
            border: 1px solid rgba(201,168,76,0.2);
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
            position: relative;
        }
        .map-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: var(--gold-grad); opacity: 0.6;
        }
        .map-header { margin-bottom: 14px; }
        .map-header h3 { font-size: 16px; color: var(--gold-light); font-weight: 700; margin-bottom: 4px; }
        .map-header h3 i { color: var(--gold); margin-right: 8px; }
        .map-header p { color: rgba(232,213,184,0.5); font-size: 13px; }
        .map {
            height: 280px;
            background: linear-gradient(160deg,#0e1e38 0%,#071428 100%);
            border-radius: 12px;
            border: 1px solid rgba(201,168,76,0.15);
            position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            box-shadow: var(--inset);
        }
        /* Decorative grid lines on map */
        .map::before {
            content: '';
            position: absolute; inset: 0;
            background: repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(201,168,76,0.05) 39px, rgba(201,168,76,0.05) 40px),
                        repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(201,168,76,0.05) 39px, rgba(201,168,76,0.05) 40px);
            pointer-events: none;
        }
        .map-placeholder { text-align: center; color: rgba(201,168,76,0.6); position: relative; z-index: 1; }
        .map-placeholder i { font-size: 56px; margin-bottom: 14px; display: block; filter: drop-shadow(0 4px 12px rgba(201,168,76,0.3)); }
        .map-placeholder p { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
        .map-placeholder small { font-size: 12px; opacity: 0.55; }

        /* Timeline */
        .tracking-timeline {
            background: var(--card-grad);
            border-radius: 18px;
            border: 1px solid rgba(201,168,76,0.2);
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
            position: relative;
        }
        .tracking-timeline::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: var(--gold-grad); opacity: 0.55;
        }
        .tracking-timeline h3 { font-size: 16px; color: var(--gold-light); font-weight: 700; margin-bottom: 24px; }
        .tracking-timeline h3 i { color: var(--gold); margin-right: 8px; }
        .timeline { position: relative; padding-left: 30px; }
        .timeline-item {
            position: relative;
            padding-bottom: 28px;
            padding-left: 22px;
            border-left: 2px solid rgba(201,168,76,0.2);
        }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px; top: 0;
            width: 14px; height: 14px;
            border-radius: 50%;
            background: rgba(201,168,76,0.2);
            border: 2px solid rgba(201,168,76,0.3);
        }
        .timeline-item.completed::before {
            background: rgba(42,122,75,0.5);
            border-color: #5dd99c;
            box-shadow: 0 0 8px rgba(93,217,156,0.3);
        }
        .timeline-item.current::before {
            background: rgba(201,168,76,0.5);
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(201,168,76,0.15), 0 0 10px rgba(201,168,76,0.3);
            animation: pulseGold 2s infinite;
        }
        @keyframes pulseGold { 0%,100%{box-shadow:0 0 0 0 rgba(201,168,76,0.5)} 50%{box-shadow:0 0 0 7px rgba(201,168,76,0)} }
        .timeline-date  { font-size: 11px; color: rgba(201,168,76,0.6); margin-bottom: 4px; letter-spacing: 0.04em; }
        .timeline-title { font-weight: 700; margin-bottom: 4px; color: var(--cream); font-size: 14px; }
        .timeline-location { font-size: 12px; color: rgba(232,213,184,0.5); }

        /* Additional info cards */
        .additional-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; }
        .info-card {
            background: var(--card-grad);
            border-radius: 16px;
            border: 1px solid rgba(201,168,76,0.18);
            padding: 24px; text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(0,0,0,0.4), inset 0 1px 0 rgba(201,168,76,0.08);
            position: relative; overflow: hidden;
        }
        .info-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: var(--gold-grad); opacity: 0.4;
        }
        .info-card:hover { transform: translateY(-5px); border-color: rgba(201,168,76,0.35); box-shadow: 0 12px 28px rgba(0,0,0,0.5), 0 0 14px rgba(201,168,76,0.08); }
        .info-card i { font-size: 38px; color: var(--gold); margin-bottom: 14px; display: block; filter: drop-shadow(0 3px 8px rgba(201,168,76,0.35)); }
        .info-card h4 { margin-bottom: 10px; font-size: 17px; color: var(--gold-light); font-weight: 700; }
        .info-card p  { color: rgba(232,213,184,0.55); margin-bottom: 16px; font-size: 13px; line-height: 1.6; }
        .info-card p strong { color: var(--cream); }

        .support-btn, .notify-btn, .docs-btn {
            padding: 9px 20px;
            background: rgba(201,168,76,0.1);
            border: 1px solid rgba(201,168,76,0.25);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600; font-size: 13px;
            color: var(--gold);
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.06);
        }
        .support-btn:hover, .notify-btn:hover, .docs-btn:hover {
            background: var(--gold-grad);
            color: #0f1c35;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 5px 14px rgba(0,0,0,0.45), 0 0 10px rgba(201,168,76,0.2);
        }

        /* No results */
        .no-results { padding: 56px 0; }
        .no-results-card {
            background: var(--card-grad);
            border-radius: 18px;
            border: 1px solid rgba(201,168,76,0.18);
            padding: 50px; text-align: center;
            box-shadow: var(--card-shadow);
        }
        .no-results-card i { font-size: 72px; color: rgba(201,168,76,0.2); margin-bottom: 20px; display: block; }
        .no-results-card h3 { font-size: 22px; color: var(--gold-light); margin-bottom: 10px; font-weight: 700; }
        .no-results-card p  { color: rgba(232,213,184,0.5); margin-bottom: 8px; font-size: 14px; }
        .no-results-card button {
            margin-top: 20px;
            padding: 12px 28px;
            background: var(--gold-grad);
            color: #0f1c35;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700; font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.3);
            transition: all 0.2s;
        }
        .no-results-card button:hover { background: var(--gold-grad-h); transform: translateY(-2px); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 7px; }
        ::-webkit-scrollbar-track { background: rgba(201,168,76,0.06); }
        ::-webkit-scrollbar-thumb { background: linear-gradient(180deg,var(--gold),var(--gold-dark)); border-radius: 6px; }

        @media (max-width: 768px) {
            .tracking-hero h1 { font-size: 30px; }
            .summary-header { flex-direction: column; align-items: flex-start; }
            nav a { margin-left: 12px; font-size: 12px; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="tracking-header">
        <div class="container">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-truck-fast"></i></div>
                <span class="logo-text">LogiTrack <span style="-webkit-text-fill-color:rgba(201,168,76,0.6);font-size:13px;font-weight:600;letter-spacing:0.06em;">PRO</span></span>
            </div>
            <nav>
                <a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="#"><i class="fas fa-box"></i> Shipments</a>
                <a href="#" class="active"><i class="fas fa-map-marker-alt"></i> Tracking</a>
                <a href="#"><i class="fas fa-user"></i> Account</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="tracking-hero">
        <div class="container">
            <div class="hero-badge">Real-Time Tracking</div>
            <h1>Track Your Shipment</h1>
            <p>Enter your tracking number for live updates on your package location and status</p>
        </div>
    </section>

    <!-- Search -->
    <section class="tracking-search">
        <div class="container">
            <div class="search-card">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="trackingNumber" placeholder="Enter tracking number (e.g., TRK-001, SH-2026-001)" autocomplete="off">
                    <button id="trackBtn" onclick="trackShipment()">
                        <i class="fas fa-location-dot"></i> Track Shipment
                    </button>
                </div>
                <div class="tracking-examples">
                    <p>Try these examples:</p>
                    <button class="example-btn" onclick="setExample('TRK-001')">TRK-001</button>
                    <button class="example-btn" onclick="setExample('SH-2026-001')">SH-2026-001</button>
                    <button class="example-btn" onclick="setExample('TRK-002')">TRK-002</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Loading -->
    <div id="loadingIndicator" class="loading-indicator" style="display:none;">
        <div class="spinner"></div>
        <p>Fetching tracking information...</p>
    </div>

    <!-- Results -->
    <section id="trackingResults" class="tracking-results" style="display:none;">
        <div class="container">

            <div class="shipment-summary">
                <div class="summary-header">
                    <div>
                        <h2 id="shipmentId">Shipment ID</h2>
                        <p id="trackingNumberDisplay">Tracking Number</p>
                    </div>
                    <div class="status-badge" id="statusBadge">In Transit</div>
                </div>
                <div class="summary-details">
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div>
                            <label>Estimated Delivery</label>
                            <p id="estimatedDelivery">Apr 15, 2026</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-weight-hanging"></i>
                        <div>
                            <label>Weight</label>
                            <p id="weightDisplay">850.5 kg</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-boxes-stacked"></i>
                        <div>
                            <label>Shipment Type</label>
                            <p id="shipmentTypeDisplay">LTL</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-truck-fast"></i>
                        <div>
                            <label>Carrier</label>
                            <p id="carrierDisplay">DHL Sea Freight</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="map-container">
                <div class="map-header">
                    <h3><i class="fas fa-map-marked-alt"></i> Current Location</h3>
                    <p id="currentLocation">Searching for live position...</p>
                </div>
                <div id="map" class="map">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>Interactive Map</p>
                        <small>Live map integration available via Google Maps API</small>
                    </div>
                </div>
            </div>

            <div class="tracking-timeline">
                <h3><i class="fas fa-history"></i> Tracking History</h3>
                <div class="timeline" id="timeline"></div>
            </div>

            <div class="additional-info">
                <div class="info-card">
                    <i class="fas fa-phone-alt"></i>
                    <h4>Need Help?</h4>
                    <p>Contact our support team at <strong>1-800-555-0123</strong></p>
                    <button class="support-btn" onclick="alert('Connecting to support...')">Chat with Support</button>
                </div>
                <div class="info-card">
                    <i class="fas fa-bell"></i>
                    <h4>Get Updates</h4>
                    <p>Receive SMS or email notifications about your shipment status</p>
                    <button class="notify-btn" onclick="requestNotification()">Notify Me</button>
                </div>
                <div class="info-card">
                    <i class="fas fa-file-alt"></i>
                    <h4>Documents</h4>
                    <p>View shipping documents and proof of delivery</p>
                    <button class="docs-btn" onclick="alert('Documents will be available here.')">View Documents</button>
                </div>
            </div>

        </div>
    </section>

    <!-- No Results -->
    <div id="noResults" class="no-results" style="display:none;">
        <div class="container">
            <div class="no-results-card">
                <i class="fas fa-search"></i>
                <h3>No Shipment Found</h3>
                <p>We couldn't find any shipment with that tracking number.</p>
                <p>Please double-check your tracking number and try again.</p>
                <button onclick="clearResults()"><i class="fas fa-redo" style="margin-right:8px;"></i> Try Again</button>
            </div>
        </div>
    </div>

<script>
    // Demo tracking data
    const trackingData = {
        'TRK-001': {
            id: 'Shipment #TRK-001',
            trackingNumber: 'TRK-001-2026-DEMO',
            status: 'In Transit',
            statusClass: 'status-transit',
            estimatedDelivery: 'Apr 20, 2026',
            weight: '850.5 kg',
            type: 'LTL',
            carrier: 'DHL Sea Freight',
            location: 'Currently in Denver, CO — last scan 2 hours ago',
            timeline: [
                { date: 'Apr 10, 2026 09:00', title: 'Shipment Created', location: 'Los Angeles, CA', status: 'completed' },
                { date: 'Apr 11, 2026 14:30', title: 'Picked Up by Carrier', location: 'LA Distribution Hub', status: 'completed' },
                { date: 'Apr 12, 2026 08:15', title: 'Departed Origin Hub', location: 'Los Angeles, CA', status: 'completed' },
                { date: 'Apr 13, 2026 16:45', title: 'In Transit — Denver Hub', location: 'Denver, CO', status: 'current' },
                { date: 'Apr 14, 2026', title: 'Arriving at Destination Hub', location: 'Seattle, WA', status: 'pending' },
                { date: 'Apr 20, 2026', title: 'Estimated Delivery', location: 'Seattle, WA', status: 'pending' }
            ]
        },
        'SH-2026-001': {
            id: 'Shipment #SH-2026-001',
            trackingNumber: 'SH-2026-001-DEMO',
            status: 'Delivered',
            statusClass: 'status-delivered',
            estimatedDelivery: 'Apr 8, 2026',
            weight: '120 kg',
            type: 'Palletized',
            carrier: 'FedEx Air',
            location: 'Delivered successfully on Apr 8, 2026',
            timeline: [
                { date: 'Apr 1, 2026 10:00',  title: 'Shipment Created',   location: 'Chicago, IL',   status: 'completed' },
                { date: 'Apr 2, 2026 08:00',  title: 'Picked Up',          location: 'Chicago, IL',   status: 'completed' },
                { date: 'Apr 4, 2026 13:00',  title: 'In Transit',         location: 'Memphis, TN',   status: 'completed' },
                { date: 'Apr 7, 2026 07:30',  title: 'Out for Delivery',   location: 'New York, NY',  status: 'completed' },
                { date: 'Apr 8, 2026 11:45',  title: 'Delivered',          location: 'New York, NY',  status: 'completed' }
            ]
        },
        'TRK-002': {
            id: 'Shipment #TRK-002',
            trackingNumber: 'TRK-002-2026-DEMO',
            status: 'Pending',
            statusClass: 'status-pending',
            estimatedDelivery: 'Apr 25, 2026',
            weight: '340 kg',
            type: 'Container 20\'',
            carrier: 'UPS Ground',
            location: 'Awaiting carrier pickup — origin warehouse',
            timeline: [
                { date: 'Apr 12, 2026 15:00', title: 'Shipment Created',   location: 'Miami, FL',     status: 'completed' },
                { date: 'Apr 14, 2026',       title: 'Pending Pickup',     location: 'Miami, FL',     status: 'current' },
                { date: 'Apr 15, 2026',       title: 'Expected Departure', location: 'Miami, FL',     status: 'pending' },
                { date: 'Apr 25, 2026',       title: 'Estimated Delivery', location: 'Houston, TX',   status: 'pending' }
            ]
        }
    };

    function setExample(trackingNum) {
        document.getElementById('trackingNumber').value = trackingNum;
    }

    function trackShipment() {
        const input = document.getElementById('trackingNumber').value.trim();
        if (!input) { alert('Please enter a tracking number.'); return; }
        hideAll();
        document.getElementById('loadingIndicator').style.display = 'block';

        // Look the tracking number up in the real database first.
        fetch('api/tracking.php?id=' + encodeURIComponent(input))
            .then(r => r.json())
            .then(res => {
                document.getElementById('loadingIndicator').style.display = 'none';

                if (res && res.found && res.shipment) {
                    displayResults(mapDbShipmentToView(res.shipment, res.timeline));
                    return;
                }

                // Fall back to the built-in demo tracking numbers.
                const found = trackingData[input] || trackingData[input.toUpperCase()];
                if (found) { displayResults(found); }
                else { document.getElementById('noResults').style.display = 'block'; }
            })
            .catch(() => {
                document.getElementById('loadingIndicator').style.display = 'none';
                const found = trackingData[input] || trackingData[input.toUpperCase()];
                if (found) { displayResults(found); }
                else { document.getElementById('noResults').style.display = 'block'; }
            });
    }

    // Convert a row from api/tracking.php into the shape displayResults() expects.
    function mapDbShipmentToView(s, timeline) {
        const statusClassMap = {
            'Pending': 'status-pending', 'In Transit': 'status-transit',
            'Delivered': 'status-delivered', 'Delayed': 'status-delayed'
        };
        return {
            id: 'Shipment #' + s.id,
            trackingNumber: s.id,
            status: s.status,
            statusClass: statusClassMap[s.status] || 'status-pending',
            estimatedDelivery: s.estimated_delivery,
            weight: s.weight + ' kg',
            type: s.priority,
            carrier: 'LogiTrack Fleet',
            location: s.destination + ' (from ' + s.origin + ')',
            timeline: (timeline || []).map(ev => ({
                date: ev.event_time,
                title: ev.title,
                location: ev.location,
                status: ev.event_status
            }))
        };
    }

    function displayResults(data) {
        document.getElementById('shipmentId').textContent    = data.id;
        document.getElementById('trackingNumberDisplay').textContent = data.trackingNumber;
        document.getElementById('estimatedDelivery').textContent = data.estimatedDelivery;
        document.getElementById('weightDisplay').textContent = data.weight;
        document.getElementById('shipmentTypeDisplay').textContent  = data.type;
        document.getElementById('carrierDisplay').textContent   = data.carrier;
        document.getElementById('currentLocation').textContent  = data.location;

        const badge = document.getElementById('statusBadge');
        badge.textContent  = data.status;
        badge.className    = 'status-badge ' + data.statusClass;

        const timeline = document.getElementById('timeline');
        timeline.innerHTML = '';
        data.timeline.forEach(ev => {
            const item = document.createElement('div');
            item.className = 'timeline-item ' + (ev.status !== 'pending' ? ev.status : '');
            item.innerHTML = `
                <div class="timeline-date">${ev.date}</div>
                <div class="timeline-title">${ev.title}</div>
                <div class="timeline-location"><i class="fas fa-map-pin" style="margin-right:5px;color:rgba(201,168,76,0.5);"></i>${ev.location}</div>
            `;
            timeline.appendChild(item);
        });
        document.getElementById('trackingResults').style.display = 'block';
    }

    function hideAll() {
        document.getElementById('trackingResults').style.display = 'none';
        document.getElementById('noResults').style.display       = 'none';
    }
    function clearResults() {
        hideAll();
        document.getElementById('trackingNumber').value = '';
    }

    function requestNotification() {
        if ('Notification' in window) {
            Notification.requestPermission().then(p => {
                alert(p === 'granted' ? 'Notifications enabled! You will be notified of updates.' : 'Notification permission not granted.');
            });
        } else {
            alert('Notifications are not supported in your browser.');
        }
    }

    // Allow Enter key in search field
    document.addEventListener('DOMContentLoaded', function() {
        const inp = document.getElementById('trackingNumber');
        if (inp) inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') trackShipment(); });
    });
</script>
</body>
</html>
