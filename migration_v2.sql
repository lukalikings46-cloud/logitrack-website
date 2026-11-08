-- =========================================================
-- LogiTrack Pro — Migration v2
-- Run this AGAINST YOUR EXISTING DATABASE (the one you already
-- imported schema.sql into) to add the new features:
--   - failed-login tracking + security alerts
--   - role-based permissions (admin / manager / staff)
--   - API keys + rate limiting for external integrations
--
-- Safe to run once. If a column/table already exists, MySQL will
-- show a harmless "Duplicate column" / "already exists" error for
-- that one statement — just skip it and run the next one.
-- =========================================================

USE logitracker_db;   -- change this if your database has a different name

-- ---------------------------------------------------------
-- 1. login_history: add email / success columns, make user_id nullable
-- ---------------------------------------------------------
ALTER TABLE login_history
    ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT '' AFTER user_id,
    ADD COLUMN success TINYINT(1) NOT NULL DEFAULT 1 AFTER email,
    MODIFY COLUMN user_id INT UNSIGNED NULL;

-- ---------------------------------------------------------
-- 2. api_keys table
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_keys (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    api_key             VARCHAR(64)  NOT NULL UNIQUE,
    label               VARCHAR(100) NOT NULL DEFAULT 'Integration key',
    rate_limit_per_min  SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    active              TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at        TIMESTAMP    NULL DEFAULT NULL,
    CONSTRAINT fk_apikey_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. api_request_log table (rate limiting)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_request_log (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id    INT UNSIGNED NOT NULL,
    requested_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_time (api_key_id, requested_at),
    CONSTRAINT fk_reqlog_key FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4. users.role — only needed if your users table predates
--    the role column entirely (most installs already have it)
-- ---------------------------------------------------------
ALTER TABLE users
    ADD COLUMN role ENUM('admin','manager','staff') NOT NULL DEFAULT 'manager' AFTER avatar;

-- Make your existing account an admin so you can generate API
-- keys and see admin-only controls. Change the email if needed.
UPDATE users SET role = 'admin' WHERE email = 'lukalikings46@gmail.com';

-- ---------------------------------------------------------
-- 5. notifications table — only needed if it doesn't exist yet
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    message     VARCHAR(255) NOT NULL,
    type        ENUM('info','success','error','warning') NOT NULL DEFAULT 'info',
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
