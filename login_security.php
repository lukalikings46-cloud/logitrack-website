<?php
require_once __DIR__ . '/mailer.php';

/**
 * Records a login attempt and, on failure, alerts the account owner
 * (in-app notification + email) that someone tried to sign in with
 * their email. This is what powers "someone tried to log in with my
 * credentials" alerts.
 */
function record_login_attempt(PDO $pdo, string $email, bool $success, ?array $user = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $pdo->prepare(
        'INSERT INTO login_history (user_id, email, success, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)'
    )->execute([$user['id'] ?? null, $email, $success ? 1 : 0, $ip, $agent]);

    if ($success || !$user) {
        // Either it worked, or the email doesn't belong to any account
        // (nothing to alert — don't reveal which emails are registered).
        return;
    }

    // Failed attempt against a REAL account — alert the owner.
    $when = date('Y-m-d H:i:s');
    $message = "A failed login attempt was made on your account from IP $ip at $when.";

    notify_user(
        $pdo,
        $user['id'],
        $message,
        'warning',
        $user['email'],
        'Security alert: failed login attempt on your LogiTrack Pro account',
        "Hi {$user['full_name']},\n\n$message\n\n"
            . "If this wasn't you, consider changing your password from Settings > Security.\n"
            . "If it was you, you can safely ignore this message.\n\n— LogiTrack Pro"
    );

    // Extra alert if there have been several failed attempts recently —
    // this looks more like a real attack than a mistyped password.
    $recent = $pdo->prepare(
        "SELECT COUNT(*) FROM login_history WHERE email = ? AND success = 0 AND logged_in_at > (NOW() - INTERVAL 15 MINUTE)"
    );
    $recent->execute([$email]);
    if ((int) $recent->fetchColumn() >= 3) {
        notify_user(
            $pdo,
            $user['id'],
            'Multiple failed login attempts were detected on your account in the last 15 minutes.',
            'error',
            $user['email'],
            'Security alert: multiple failed login attempts',
            "Hi {$user['full_name']},\n\nThere have been 3 or more failed login attempts on your account "
                . "in the last 15 minutes. If this wasn't you, please change your password immediately.\n\n— LogiTrack Pro"
        );
    }
}
