-- =========================================================
-- LogiTrack Pro — Reset accounts & create the one admin login
-- =========================================================
-- Run this against your EXISTING, already-imported database to:
--   1. Log every current user out (clears all active sessions'
--      backing rows is not needed since sessions are server-side,
--      but this removes every account record so old logins can no
--      longer be used at all).
--   2. Delete every existing user account (including the old demo
--      admin, kingslukali9@gmail.com, and anyone who self-registered).
--   3. Create a single new admin account:
--        email:    lukalikings46@gmail.com
--        password: 112007@Lukali
--
-- Safe to run more than once. Existing shipments/vehicles/inventory
-- rows are kept — only their created_by reference is cleared, since
-- the user that created them no longer exists.
-- =========================================================

USE logitracker_db;   -- change this if your database has a different name

SET FOREIGN_KEY_CHECKS = 0;

-- Detach shipments from users about to be deleted (keeps the shipment
-- records themselves so nothing in the dashboard's history is lost).
UPDATE shipments SET created_by = NULL;

-- Wipe everything that belongs to the accounts being removed.
DELETE FROM notifications;
DELETE FROM api_request_log;
DELETE FROM api_keys;
DELETE FROM login_history;
DELETE FROM user_settings;
DELETE FROM users;

-- Reset the auto-increment counter so the new admin becomes id = 1.
ALTER TABLE users AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Create the one admin account.
-- Password is: 112007@Lukali
-- (hash generated with PHP's password_hash(); change it from
-- Settings > Security after your first login if you'd like.)
INSERT INTO users (full_name, email, password_hash, phone, position, department, avatar, role)
VALUES (
  'Lukali Admin',
  'lukalikings46@gmail.com',
  '$2y$10$mZysIqQfpCsBOSWOnpdA8OaGDkh9veq7t5TmXu7JtP4HKKn4wTfxu',
  NULL,
  'Administrator',
  'Logistics Operations',
  'truckicon.jpg',
  'admin'
);

INSERT INTO user_settings (user_id) VALUES (LAST_INSERT_ID());
