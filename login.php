<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/login_security.php';

// Already logged in? go straight to the dashboard.
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                record_login_attempt($pdo, $email, true, $user);

                header('Location: index.php');
                exit;
            }

            // Wrong password (or unknown email) — log it and alert the
            // real account owner if the email does belong to someone.
            record_login_attempt($pdo, $email, false, $user ?: null);
            $error = 'Invalid email or password.';
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
<title>Sign In · LogiTrack Pro</title>
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
        width:100%; max-width: 420px; background: var(--card-bg); border-radius: 18px;
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
</style>
</head>
<body>
    <div class="auth-card">
        <a href="landing.php" class="auth-logo-link"><div class="auth-logo"><i class="fas fa-truck-fast"></i><span>LogiTrack Pro</span></div></a>
        <h1>Sign in to your dashboard</h1>

        <?php if ($error): ?>
            <div class="error-msg"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-right-to-bracket"></i> Sign In</button>
        </form>

        <div class="auth-footer">Don't have an account? <a href="register.php">Create one</a></div>
    </div>
</body>
</html>
