<?php
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Appends an email to the local log file, used both as the
 * "MAIL_ENABLED = false" mode and as a safety-net fallback if a real
 * SMTP send fails for any reason (bad credentials, no network, etc).
 */
function log_app_email(string $to, string $subject, string $body, ?string $note = null): void
{
    $entry = "----- " . date('Y-m-d H:i:s') . " -----\n"
           . "To: $to\nSubject: $subject\n"
           . ($note ? "Note: $note\n" : '')
           . "\n$body\n\n";
    @file_put_contents(MAIL_LOG_FILE, $entry, FILE_APPEND);
}

/**
 * Sends an email for real over authenticated Gmail SMTP so it lands
 * in the recipient's inbox (and can trigger a phone push notification
 * via the Gmail app) — or, when MAIL_ENABLED is false or the SMTP
 * send fails, falls back to logging it to mail_log.txt instead.
 */
function send_app_email(string $to, string $subject, string $body): bool
{
    if (!MAIL_ENABLED) {
        log_app_email($to, $subject, $body);
        return true;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 10; // seconds — fail fast instead of hanging the request

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->isHTML(false);

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        // Never let a mail failure break the request — log it instead
        // so nothing is silently lost, and surface the reason.
        log_app_email($to, $subject, $body, 'SMTP SEND FAILED: ' . $e->getMessage());
        return false;
    }
}

/**
 * Convenience wrapper: sends an email AND creates a matching in-app
 * notification row for the given user, so alerts show up both in
 * their inbox and in the notification bell.
 */
function notify_user(PDO $pdo, ?int $userId, string $message, string $type = 'info', ?string $email = null, ?string $emailSubject = null, ?string $emailBody = null): void
{
    if ($userId !== null) {
        $pdo->prepare('INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)')
            ->execute([$userId, $message, $type]);
    }

    if ($email && $emailSubject && $emailBody) {
        send_app_email($email, $emailSubject, $emailBody);
    }
}
