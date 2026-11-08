<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['manager', 'staff'], true) ? $_POST['role'] : 'staff';

    $strength = check_password_strength($password);

    if ($fullName === '' || $email === '') {
        $error = 'Full name and a valid email are required.';
    } elseif (!$strength['valid']) {
        $error = $strength['message'];
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDB();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'An account with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
                $ins->execute([$fullName, $email, $hash, $role]);
                $newId = (int) $pdo->lastInsertId();
                $pdo->prepare('INSERT INTO user_settings (user_id) VALUES (?)')->execute([$newId]);

                $_SESSION['user_id'] = $newId;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['role'] = $role;
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Could not reach the database. Check config/database.php.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account · LogiTrack Pro</title>
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
    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(ellipse 80% 60% at 15% 40%, rgba(201,168,76,0.07) 0%, transparent 70%),
                    radial-gradient(ellipse 60% 80% at 85% 20%, rgba(74,28,42,0.12) 0%, transparent 60%),
                    linear-gradient(160deg,#070e1e 0%,#0f1c35 45%,#0a1428 100%);
        min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 2rem;
        color: #e8d5b8;
    }
    .auth-card {
        width:100%; max-width: 440px; background: var(--card-bg); border-radius: 18px;
        border: 1px solid rgba(201,168,76,0.22); box-shadow: var(--card-shadow);
        padding: 2.4rem 2.2rem; position: relative; overflow: hidden;
    }
    .auth-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background: var(--gold-grad); }
    .auth-logo { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom: 1.6rem; }
    .auth-logo i { width:42px; height:42px; border-radius:10px; background:var(--gold-grad); color:#0f1c35;
        display:flex; align-items:center; justify-content:center; font-size:19px; }
    .auth-logo span { font-family:'Playfair Display',serif; font-weight:800; font-size:1.4rem; color:var(--gold-light); }
    .auth-logo-link { text-decoration:none; }
    h1 { font-size:1.1rem; text-align:center; color:#e8d5b8; margin-bottom:1.6rem; font-weight:500; }
    .form-group { margin-bottom: 1.1rem; }
    label { display:block; font-size:.85rem; margin-bottom:.4rem; color: var(--gold-light); }
    input {
        width:100%; padding: .75rem .9rem; border-radius:8px; border:1px solid rgba(201,168,76,0.25);
        background: rgba(255,255,255,0.04); color:#f5ede0; font-size:.95rem; outline:none;
    }
    input:focus { border-color: var(--gold); }
    .btn-primary {
        width:100%; padding:.8rem; border:none; border-radius:9px; background: var(--gold-grad);
        color:#0f1c35; font-weight:700; font-size:.95rem; cursor:pointer; margin-top:.4rem;
    }
    .btn-primary:hover { filter:brightness(1.08); }
    .error-msg {
        background: rgba(139,28,28,0.25); border:1px solid rgba(139,28,28,0.5); color:#ffb3b3;
        padding:.6rem .8rem; border-radius:8px; font-size:.85rem; margin-bottom:1rem;
    }
    .auth-footer { text-align:center; margin-top:1.3rem; font-size:.85rem; color:#c9b896; }
    .auth-footer a { color: var(--gold-light); text-decoration:none; font-weight:600; }
    select {
        width:100%; padding: .75rem .9rem; border-radius:8px; border:1px solid rgba(201,168,76,0.25);
        background: rgba(255,255,255,0.04); color:#f5ede0; font-size:.95rem; outline:none;
    }
    .strength-meter { display:flex; gap:5px; margin-top:.5rem; }
    .strength-bar { height:5px; flex:1; border-radius:3px; background: rgba(255,255,255,0.12); transition: background .2s; }
    .strength-label { font-size:.78rem; margin-top:.4rem; color:#c9b896; }
    .strength-hint { font-size:.75rem; margin-top:.3rem; color:#a08f6d; }
</style>
<script>
    function checkStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        return score;
    }
    function updateStrengthUI() {
        const password = document.getElementById('passwordInput').value;
        const score = checkStrength(password);
        const bars = document.querySelectorAll('.strength-bar');
        const label = document.getElementById('strengthLabel');
        const colors = ['#8b1c1c', '#c9601c', '#c9a84c', '#7a9c3f', '#3f9c5c'];
        const labels = ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
        bars.forEach((bar, i) => {
            bar.style.background = i < score ? colors[Math.max(score - 1, 0)] : 'rgba(255,255,255,0.12)';
        });
        label.textContent = password ? labels[Math.max(score - 1, 0)] : '';
    }
</script>
</head>
<body>
    <div class="auth-card">
        <a href="landing.php" class="auth-logo-link"><div class="auth-logo"><i class="fas fa-truck-fast"></i><span>LogiTrack Pro</span></div></a>
        <h1>Create your account</h1>

        <?php if ($error): ?>
            <div class="error-msg"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="passwordInput" required minlength="8" oninput="updateStrengthUI()">
                <div class="strength-meter">
                    <div class="strength-bar"></div><div class="strength-bar"></div><div class="strength-bar"></div>
                    <div class="strength-bar"></div><div class="strength-bar"></div>
                </div>
                <div class="strength-label" id="strengthLabel"></div>
                <div class="strength-hint">Use 8+ characters with upper &amp; lowercase letters, a number, and a symbol.</div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required minlength="8">
            </div>
            <div class="form-group">
                <label>Account Role</label>
                <select name="role">
                    <option value="staff">Staff (view &amp; create shipments)</option>
                    <option value="manager">Manager (full shipment/fleet/warehouse access)</option>
                </select>
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-user-plus"></i> Create Account</button>
        </form>

        <div class="auth-footer">Already have an account? <a href="login.php">Sign in</a></div>
    </div>
</body>
</html>
