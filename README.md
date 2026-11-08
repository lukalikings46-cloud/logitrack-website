# LogiTrack Pro — PHP + MySQL Setup

Your original design, layout and JavaScript behaviour are unchanged. This
package adds a real backend: a MySQL database plus PHP pages/APIs so
shipments, vehicles, inventory and user accounts are stored permanently
instead of living only in the browser.

## What was added

```
logitrack/
├── database/
│   ├── schema.sql            ← import this to create the database
│   ├── migration_v2.sql      ← upgrade an older install in place
│   └── reset_admin.sql       ← wipe old accounts, create the admin login
├── vendor/phpmailer/         ← bundled PHPMailer (real SMTP email sending)
├── config/
│   ├── database.php          ← set your DB host/user/password here
│   └── mail.php              ← Gmail SMTP / App Password settings
├── includes/
│   ├── session.php           ← starts the PHP session
│   ├── auth_check.php        ← guards pages that require login
│   └── api_helpers.php       ← shared helpers for the /api endpoints
├── api/
│   ├── auth.php              ← login / register / logout / session check
│   ├── shipments.php         ← CRUD for shipments
│   ├── vehicles.php          ← CRUD for the fleet
│   ├── inventory.php         ← CRUD for warehouse stock
│   ├── tracking.php          ← public lookup used by shipmenttracker.php
│   └── profile.php           ← profile + settings + password change
├── login.php                 ← new
├── register.php               ← new
├── logout.php                 ← new
├── index.php                  ← was index.html, now requires login
├── newshipment-portal.php     ← was .html, now posts to api/shipments.php
├── shipmenttracker.php        ← was .html, now queries api/tracking.php
├── style.css                  ← unchanged
├── script.js                  ← same UI logic, now talks to the API
└── truckicon.jpg               ← unchanged
```

## 1. Create the database

In phpMyAdmin (or the `mysql` CLI), import `database/schema.sql`. It will:

- Create the `logitracker_db` database and every table
- Create one admin account:
  - **Email:** `lukalikings46@gmail.com`
  - **Password:** `112007@Lukali`
- Seed the same sample shipments, vehicles and inventory that used to be
  hard-coded in `script.js`, so the dashboard looks identical the first
  time you load it.

```bash
mysql -u root -p < database/schema.sql
```

### Already have a database from before?

If you previously imported `schema.sql` and already have old accounts
in it (e.g. the old `kingslukali9@gmail.com` demo login), run
`database/reset_admin.sql` instead — it deletes every existing user
account (and anything tied only to those accounts: notifications,
login history, API keys) and creates the one admin account above in
their place. Shipments/vehicles/inventory data is kept.

```bash
mysql -u root -p < database/reset_admin.sql
```

## 2. Point the app at your database

Open `config/database.php` and edit the four constants:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'logitracker_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## 3. Run it

Copy the whole `logitrack/` folder into your web server's document root
(e.g. `htdocs/` for XAMPP, or `/var/www/html/`), then visit:

```
http://localhost/logitrack/login.php
```

Log in with the starter account above (or register a new one), and
you'll land on `index.php` — the same dashboard as before, now backed
by MySQL.

## How the data flows

- **On load**, `index.php` queries the database and hands the current
  shipments/fleet/inventory to `script.js` through `window.__SEED__`.
  If the database is ever unreachable, `script.js` quietly falls back
  to its original built-in sample data so the UI never breaks.
- **Creating/editing/deleting** a shipment, vehicle, or stock item now
  also sends a request to the matching file in `/api`, so the change is
  saved to MySQL, not just to the page you're looking at.
- **Login/logout/register** are handled by PHP sessions
  (`login.php`, `register.php`, `logout.php`, `api/auth.php`).
- **Tracking** (`shipmenttracker.php`) checks real shipments in the
  database first, and falls back to the original demo tracking numbers
  (`TRK-001`, `SH-2026-001`, `TRK-002`) if nothing is found.
- **Profile / password changes** in Settings now call `api/profile.php`
  and are saved against your logged-in user.

## New: Security, Roles & Integrations

**Password strength** — registration and password changes now require
8+ characters with upper/lowercase letters, a number, and a symbol
(enforced server-side in `includes/security.php`, with a live meter
on `register.php` and in Settings).

**Roles (admin / manager / staff)** — every user has a role, matching
exactly what's advertised on `landing.php`:
- `staff` — can view shipments, create new ones, and track
  deliveries. Cannot edit or delete shipments, and the
  Warehouse/Fleet/Reports nav items and pages aren't even rendered
  for them (not just hidden — `index.php` skips the HTML entirely).
- `manager` — everything staff can, plus editing/deleting shipments
  and full fleet & warehouse management.
- `admin` — everything manager can, plus a **Users** tab in Settings
  (`api/users.php`) to change anyone's role or remove their account,
  and generating API keys.

Enforced in `includes/roles.php` and checked inside every mutating
`/api` endpoint — not just hidden in the UI. For example, `PUT
/api/shipments.php` (editing a shipment) now rejects staff with a 403
even if called directly, not just when the Edit button is clicked.

**Manage user accounts** — admins get a "Users" tab in Settings
listing every account, with a dropdown to change their role and a
button to delete the account. You can't change or delete your own
account from there (to avoid locking yourself out), and the system
always keeps at least one admin.

**Delayed-shipment emails** — when a shipment's status is changed to
"Delayed", the person who created it gets an email + in-app
notification automatically (`api/shipments.php`).

**Login-attempt alerts** — every failed login is logged, and the real
account owner gets an email + in-app notification saying someone
tried their email. Three or more failed attempts in 15 minutes
triggers a stronger "multiple failed attempts" alert. See
`includes/login_security.php`.

**API keys & rate limiting** — admins can generate API keys from
Settings → Security → API Keys. External systems can then call:

```
GET  /api/external/shipments.php            (list shipments)
GET  /api/external/shipments.php?id=TRK001  (one shipment + timeline)
POST /api/external/shipments.php             (create a shipment)
```

with header `X-API-Key: <your key>`. Each key has its own rate limit
(default 60 requests/minute); exceeding it returns HTTP 429.

**Notification bell** — the bell icon in the header shows unread
alerts (delayed shipments, login alerts, etc.) pulled from
`api/notifications.php`.

### Applying this to an existing database

If you already imported the original `schema.sql` and just need the
newer columns/tables (`login_history.email/success`, `api_keys`,
`api_request_log`) without touching your accounts, run
`database/migration_v2.sql`. If you want to wipe old accounts and
replace them with the one admin login, use `database/reset_admin.sql`
instead (see above) — it's the one that matches a fresh install.

### About email delivery (so alerts reach the Gmail app)

Emails (login-attempt alerts, delayed-shipment notices) now send over
real, authenticated Gmail SMTP using the bundled PHPMailer library
(`vendor/phpmailer/`), so they land in an actual inbox and can trigger
a push notification on your phone — not just get written to a log
file. One-time setup, in `config/mail.php`:

1. Turn on 2-Step Verification on the Gmail account that will send
   the alerts (`lukalikings46@gmail.com`), then generate an **App
   Password** at <https://myaccount.google.com/apppasswords>.
2. Paste that 16-character code into `SMTP_PASSWORD` in
   `config/mail.php`. (Your normal Gmail password won't work — Google
   requires an App Password for SMTP apps like this one.)
3. Leave `MAIL_ENABLED` as `true`.

If the App Password is ever missing/wrong, or the server has no
outbound network access, sending fails safely and the email is
written to `mail_log.txt` instead — nothing is ever lost, and the app
keeps working either way. Set `MAIL_ENABLED` to `false` at any time to
go back to log-only mode (useful on a local dev machine with no
internet).


## Notes

- Passwords are hashed with PHP's `password_hash()` — never stored in
  plain text.
- All database queries use prepared statements (PDO) to prevent SQL
  injection.
- Requires PHP 8+ with the `pdo_mysql` extension, and MySQL/MariaDB.
