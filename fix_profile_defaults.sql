-- Run once on an existing database.
-- New accounts no longer get "Logistics Manager" / "Logistics Operations" by default.
ALTER TABLE users
  MODIFY position   VARCHAR(100) DEFAULT NULL,
  MODIFY department VARCHAR(100) DEFAULT NULL;

-- Blank out the old auto-filled defaults so profiles show only what the user entered.
UPDATE users SET position   = NULL WHERE position   = 'Logistics Manager';
UPDATE users SET department = NULL WHERE department = 'Logistics Operations';
