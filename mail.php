<?php
/**
 * Email settings.
 *
 * PHP's built-in mail() function needs a mail server (SMTP relay or
 * sendmail) configured on the host, which most dev setups (XAMPP/WAMP)
 * and many shared hosts don't have — so alerts sent that way usually
 * never arrive, or land in spam. To reliably reach a Gmail inbox (and
 * trigger a push notification in the Gmail app) this project sends
 * mail through real authenticated SMTP using PHPMailer, logging
 * locally to mail_log.txt only as a fallback / dev preview.
 *
 * ---------------------------------------------------------------
 * ONE-TIME SETUP — do this before emails will actually send:
 * ---------------------------------------------------------------
 * 1. Go to https://myaccount.google.com/apppasswords (you must have
 *    2-Step Verification turned on for the Gmail account below).
 * 2. Create an "App Password" for "Mail" / "Other (Custom name)".
 *    Google gives you a 16-character code like "abcd efgh ijkl mnop".
 * 3. Paste that code (spaces don't matter) into SMTP_PASSWORD below.
 *    Do NOT use your normal Gmail password — Google blocks that for
 *    SMTP apps and it will fail to authenticate.
 * 4. Leave MAIL_ENABLED as true. If the App Password is ever revoked
 *    or wrong, sending fails safely and falls back to mail_log.txt
 *    instead of breaking the app.
 */

define('MAIL_ENABLED', true); // set to false to only log emails to mail_log.txt (no sending)

// --- SMTP connection (Gmail) ---
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // STARTTLS on port 587
define('SMTP_USERNAME', 'lukalikings46@gmail.com'); // the Gmail account that sends the alerts
define('SMTP_PASSWORD', 'REPLACE_WITH_16_CHAR_APP_PASSWORD'); // <-- paste your Gmail App Password here

// --- From / fallback log ---
define('MAIL_FROM', SMTP_USERNAME);
define('MAIL_FROM_NAME', 'LogiTrack Pro');
define('MAIL_LOG_FILE', __DIR__ . '/../mail_log.txt');
