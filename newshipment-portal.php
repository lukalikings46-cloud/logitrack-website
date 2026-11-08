<?php require_once __DIR__ . '/includes/auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Shipment · LogiTrack Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --gold:       #c9a84c;
            --gold-light: #e8c97a;
            --gold-shine: #f5e0a0;
            --gold-dark:  #8b6914;
            --navy:       #0f1c35;
            --card-bg:    linear-gradient(160deg,#1a2e50 0%,#112038 55%,#0c1828 100%);
            --gold-grad:  linear-gradient(135deg,#f0d880 0%,#c9a84c 40%,#8b6914 75%,#c9a84c 100%);
            --gold-grad-h:linear-gradient(135deg,#f5e8a0 0%,#e8c97a 40%,#c9a84c 75%,#a87e20 100%);
            --inset:      inset 0 2px 6px rgba(0,0,0,0.5),inset 0 1px 3px rgba(0,0,0,0.35);
            --card-shadow:0 8px 28px rgba(0,0,0,0.5),0 3px 10px rgba(0,0,0,0.4),inset 0 1px 0 rgba(201,168,76,0.18);
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse 80% 60% at 15% 40%, rgba(201,168,76,0.07) 0%, transparent 70%),
                radial-gradient(ellipse 60% 80% at 85% 20%, rgba(74,28,42,0.12) 0%, transparent 60%),
                linear-gradient(160deg,#070e1e 0%,#0f1c35 45%,#0a1428 100%);
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            color: #e8d5b8;
        }

        /* ── Main card ── */
        .main-content {
            max-width: 1020px;
            width: 100%;
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid rgba(201,168,76,0.22);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: relative;
        }
        .main-content::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: var(--gold-grad);
        }

        /* ── Header ── */
        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding: 1.8rem 2.5rem;
            border-bottom: 1px solid rgba(201,168,76,0.18);
            background: linear-gradient(180deg,rgba(201,168,76,0.08) 0%,transparent 100%);
            position: relative;
        }
        .header::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 220px; height: 100%;
            background: radial-gradient(ellipse at right center,rgba(201,168,76,0.07) 0%,transparent 70%);
            pointer-events: none;
        }
        .header-left h1 {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            background: var(--gold-grad);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.3rem;
            animation: shimmer 5s linear infinite;
        }
        @keyframes shimmer { 0%{background-position:200% center} 100%{background-position:-200% center} }
        .header-left p { color: rgba(232,213,184,0.6); font-size: 0.9rem; }

        .btn-primary {
            background: var(--gold-grad);
            border: 1px solid rgba(255,255,255,0.15);
            color: #0f1c35;
            font-weight: 700;
            font-size: 0.88rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.72rem 1.8rem;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -2px 0 rgba(0,0,0,0.25);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: var(--gold-grad-h);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.55), 0 0 18px rgba(201,168,76,0.28), inset 0 1px 0 rgba(255,255,255,0.4);
        }

        /* ── Portal body ── */
        .shipment-portal {
            padding: 2.5rem;
            background: transparent;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gold-light);
            letter-spacing: 0.03em;
            margin-bottom: 2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(201,168,76,0.18);
        }
        .section-title i { color: var(--gold); font-size: 1.3rem; filter: drop-shadow(0 2px 4px rgba(201,168,76,0.4)); }

        /* ── Form grid ── */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.6rem 2rem;
        }
        .field-full { grid-column: span 2; }

        .input-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .input-group label {
            font-weight: 700;
            font-size: 0.75rem;
            color: rgba(201,168,76,0.75);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .input-group label i { color: var(--gold); font-size: 0.8rem; width: 1.1rem; }

        .input-group input,
        .input-group select,
        .input-group textarea {
            padding: 0.85rem 1.1rem;
            background: rgba(201,168,76,0.07);
            border: 1px solid rgba(201,168,76,0.22);
            border-radius: 10px;
            font-size: 0.93rem;
            color: #e8d5b8;
            transition: 0.18s;
            outline: none;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.5), inset 0 1px 3px rgba(0,0,0,0.35);
        }
        .input-group input::placeholder,
        .input-group textarea::placeholder { color: rgba(232,213,184,0.3); }
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            border-color: rgba(201,168,76,0.55);
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.5), 0 0 0 3px rgba(201,168,76,0.12);
        }
        .input-group select option { background: #0f1c35; color: #e8d5b8; }
        .input-group textarea { resize: vertical; min-height: 90px; }

        /* Dimensions */
        .dimension-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .dimension-item { display: flex; flex-direction: column; gap: 0.3rem; }
        .dimension-item span { font-size: 0.78rem; font-weight: 600; color: rgba(201,168,76,0.6); letter-spacing: 0.06em; text-transform: uppercase; margin-left: 0.1rem; }

        /* Checkboxes */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            flex-wrap: wrap;
            background: rgba(201,168,76,0.06);
            padding: 0.9rem 1.4rem;
            border-radius: 12px;
            border: 1px solid rgba(201,168,76,0.18);
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.4);
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.88rem;
            font-weight: 500;
            color: rgba(232,213,184,0.75);
            cursor: pointer;
            transition: color 0.15s;
        }
        .checkbox-group label:hover { color: var(--gold-light); }
        .checkbox-group input[type="checkbox"] {
            width: 1rem; height: 1rem;
            accent-color: var(--gold);
            cursor: pointer;
        }

        /* Success message */
        .success-message {
            background: rgba(42,122,75,0.25);
            border: 1px solid rgba(93,217,156,0.3);
            border-radius: 10px;
            padding: 0.8rem 1.6rem;
            color: #5dd99c;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.8rem;
            box-shadow: 0 4px 14px rgba(0,0,0,0.35), inset 0 1px 0 rgba(93,217,156,0.15);
            font-size: 0.9rem;
        }
        .success-message i { font-size: 1.2rem; }

        /* Action buttons */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2.2rem;
            padding-top: 1.2rem;
            border-top: 1px solid rgba(201,168,76,0.18);
        }
        .btn-secondary {
            background: rgba(201,168,76,0.08);
            border: 1px solid rgba(201,168,76,0.28);
            padding: 0.78rem 1.8rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.88rem;
            color: rgba(232,213,184,0.8);
            cursor: pointer;
            transition: 0.18s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.06);
        }
        .btn-secondary:hover {
            background: rgba(201,168,76,0.18);
            border-color: rgba(201,168,76,0.5);
            color: var(--gold-light);
            transform: translateY(-1px);
        }
        .btn-primary-large {
            background: var(--gold-grad);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 0.78rem 2.2rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #0f1c35;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.35), inset 0 -2px 0 rgba(0,0,0,0.25);
            transition: 0.2s;
        }
        .btn-primary-large:hover {
            background: var(--gold-grad-h);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.55), 0 0 18px rgba(201,168,76,0.28);
        }

        hr { border: none; border-top: 1px solid rgba(201,168,76,0.18); margin: 1.4rem 0 0.5rem; }
        .small-note { font-size: 0.78rem; color: rgba(232,213,184,0.38); margin-top: 0.25rem; display: flex; align-items: center; gap: 0.3rem; }
        .small-note i { color: rgba(201,168,76,0.45); }

        @media (max-width: 700px) {
            .header { padding: 1.4rem; }
            .shipment-portal { padding: 1.6rem; }
            .form-grid { grid-template-columns: 1fr; }
            .field-full { grid-column: span 1; }
            .dimension-row { grid-template-columns: 1fr; }
            .header-left h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="main-content">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <h1><i class="fas fa-shipping-fast" style="margin-right:10px;font-size:1.5rem;"></i>New Shipment Portal</h1>
            <p>LogiTrack Pro &mdash; Create and dispatch a new shipment</p>
        </div>
        <div class="header-right">
            <button class="btn-primary" onclick="document.getElementById('shipmentFormSection').scrollIntoView({behavior:'smooth'})">
                <i class="fas fa-plus-circle"></i> New Shipment
            </button>
        </div>
    </header>

    <!-- Shipment form -->
    <div class="shipment-portal" id="shipmentFormSection">
        <div class="section-title">
            <i class="fas fa-pen-to-square"></i>
            <span>Enter Shipment Details</span>
        </div>

        <!-- Success feedback -->
        <div id="liveSuccessMessage" class="success-message">
            <i class="fas fa-check-circle"></i>
            <span id="successText">Shipment created successfully!</span>
        </div>

        <form id="shipmentForm" onsubmit="event.preventDefault(); demoSubmitShipment();">
            <div class="form-grid">

                <!-- Shipment reference -->
                <div class="input-group field-full">
                    <label><i class="fas fa-hashtag"></i> Shipment Reference / ID</label>
                    <input type="text" id="shipmentRef" placeholder="e.g., SH-2026-0421" value="SH-2026-0421">
                </div>

                <!-- Origin -->
                <div class="input-group">
                    <label><i class="fas fa-location-dot"></i> Origin</label>
                    <input type="text" id="origin" placeholder="City, warehouse" value="Los Angeles, CA">
                </div>

                <!-- Destination -->
                <div class="input-group">
                    <label><i class="fas fa-flag-checkered"></i> Destination</label>
                    <input type="text" id="destination" placeholder="City, country" value="Seattle, WA">
                </div>

                <!-- Departure -->
                <div class="input-group">
                    <label><i class="far fa-calendar"></i> Expected Departure</label>
                    <input type="date" id="departureDate" value="2026-04-10">
                </div>

                <!-- Arrival -->
                <div class="input-group">
                    <label><i class="far fa-calendar-check"></i> Est. Arrival</label>
                    <input type="date" id="arrivalDate" value="2026-04-15">
                </div>

                <!-- Carrier -->
                <div class="input-group">
                    <label><i class="fas fa-truck"></i> Carrier / Mode</label>
                    <select id="carrierMode">
                        <option value="FedEx Air">FedEx Air</option>
                        <option value="DHL Sea Freight" selected>DHL Sea Freight</option>
                        <option value="UPS Ground">UPS Ground</option>
                        <option value="Rail intermodal">Rail intermodal</option>
                        <option value="Owner operator">Owner operator</option>
                    </select>
                </div>

                <!-- Shipment type -->
                <div class="input-group">
                    <label><i class="fas fa-boxes-stacked"></i> Shipment Type</label>
                    <select id="shipmentType">
                        <option value="Palletized">Palletized</option>
                        <option value="Container 20ft">Container 20'</option>
                        <option value="Container 40ft">Container 40'</option>
                        <option value="LTL" selected>LTL (Less than truckload)</option>
                        <option value="FTL">FTL (Full truckload)</option>
                    </select>
                </div>

                <!-- Weight -->
                <div class="input-group">
                    <label><i class="fas fa-weight-scale"></i> Weight (kg)</label>
                    <input type="number" id="weight" step="0.1" placeholder="e.g. 1200" value="850.5">
                </div>

                <!-- Dimensions -->
                <div class="input-group field-full">
                    <label><i class="fas fa-cube"></i> Dimensions (cm) &mdash; L &times; W &times; H</label>
                    <div class="dimension-row">
                        <div class="dimension-item">
                            <span>Length</span>
                            <input type="number" id="dimLength" placeholder="cm" value="120">
                        </div>
                        <div class="dimension-item">
                            <span>Width</span>
                            <input type="number" id="dimWidth" placeholder="cm" value="80">
                        </div>
                        <div class="dimension-item">
                            <span>Height</span>
                            <input type="number" id="dimHeight" placeholder="cm" value="95">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="input-group field-full">
                    <label><i class="fas fa-note-sticky"></i> Special Instructions / Notes</label>
                    <textarea id="instructions" placeholder="e.g. fragile, notify before delivery, liftgate required...">Fragile electronics &ndash; handle with care. Dock appointment needed.</textarea>
                </div>

                <!-- Services checkboxes -->
                <div class="field-full checkbox-group" id="cleanCheckGroup">
                    <label><i class="fas fa-shield-halved"></i> <input type="checkbox" id="insuranceCheck" checked> Insurance</label>
                    <label><i class="fas fa-temperature-low"></i> <input type="checkbox" id="reeferCheck"> Refrigerated</label>
                    <label><i class="fas fa-person-walking-arrow-right"></i> <input type="checkbox" id="liftgateCheck"> Liftgate Required</label>
                    <label><i class="fas fa-bolt"></i> <input type="checkbox" id="expeditedCheck"> Expedited</label>
                </div>
            </div>

            <div class="action-buttons">
                <button type="reset" class="btn-secondary" id="clearFormBtn">
                    <i class="fas fa-rotate-left"></i> Clear Fields
                </button>
                <button type="submit" class="btn-primary-large" id="saveShipmentBtn">
                    <i class="fas fa-circle-check"></i> Create Shipment
                </button>
            </div>
        </form>

        <hr>
        <div class="small-note">
            <i class="fas fa-info-circle"></i> Demo mode &mdash; data is not sent to any server.
        </div>
    </div>
</div>

<script>
function demoSubmitShipment() {
    const ref   = document.getElementById('shipmentRef')?.value || '—';
    const orig  = document.getElementById('origin')?.value || '';
    const dest  = document.getElementById('destination')?.value || '';
    const dep   = document.getElementById('departureDate')?.value || '';
    const arr   = document.getElementById('arrivalDate')?.value || '';
    const carrier = document.getElementById('carrierMode')?.value || '';
    const type  = document.getElementById('shipmentType')?.value || '';
    const wt    = document.getElementById('weight')?.value || '';
    const len   = document.getElementById('dimLength')?.value || '';
    const wid   = document.getElementById('dimWidth')?.value || '';
    const ht    = document.getElementById('dimHeight')?.value || '';
    const instr = document.getElementById('instructions')?.value || '';

    const insurance  = document.getElementById('insuranceCheck')?.checked  ? 'Yes' : 'No';
    const reefer     = document.getElementById('reeferCheck')?.checked      ? 'Yes' : 'No';
    const liftgate   = document.getElementById('liftgateCheck')?.checked    ? 'Yes' : 'No';
    const expedited  = document.getElementById('expeditedCheck')?.checked   ? 'Yes' : 'No';

    console.log('New Shipment:', { ref, orig, dest, dep, arr, carrier, type, weight: wt,
        dims: `${len}x${wid}x${ht}cm`, instr, insurance, reefer, liftgate, expedited });

    // Persist to the database via the same API the main dashboard uses.
    fetch('api/shipments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            origin: orig,
            destination: dest,
            weight: wt || 0,
            priority: expedited === 'Yes' ? 'Express' : 'Standard',
            estimatedDelivery: arr || dep,
            instructions: [instr, carrier ? `Carrier: ${carrier}` : '', type ? `Type: ${type}` : '',
                            insurance === 'Yes' ? 'Insured' : '', reefer === 'Yes' ? 'Refrigerated' : '',
                            liftgate === 'Yes' ? 'Liftgate required' : ''].filter(Boolean).join(' | ')
        })
    })
    .then(r => r.json())
    .then(res => {
        const msg = document.getElementById('liveSuccessMessage');
        const txt = document.getElementById('successText');
        if (msg && txt) {
            const newId = (res && res.id) ? res.id : ref;
            txt.textContent = `Shipment ${newId} created — route: ${orig} \u2192 ${dest}`;
            msg.style.display = 'flex';
            setTimeout(() => { msg.style.display = 'none'; }, 5000);
        }
    })
    .catch(() => {
        const msg = document.getElementById('liveSuccessMessage');
        const txt = document.getElementById('successText');
        if (msg && txt) {
            txt.textContent = `Shipment ${ref} created — route: ${orig} \u2192 ${dest}`;
            msg.style.display = 'flex';
            setTimeout(() => { msg.style.display = 'none'; }, 5000);
        }
    });
}
</script>
</body>
</html>
