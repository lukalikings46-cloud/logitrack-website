-- =========================================================
-- LogiTrack Pro - Database Schema
-- Import this file in phpMyAdmin / MySQL CLI to create
-- the database and all tables used by the PHP backend.
-- =========================================================

CREATE DATABASE IF NOT EXISTS logitracker_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE logitracker_db;

-- ---------------------------------------------------------
-- Users (login, profile, settings)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    phone           VARCHAR(30)   DEFAULT NULL,
    position        VARCHAR(100)  DEFAULT NULL,
    department      VARCHAR(100)  DEFAULT NULL,
    avatar          VARCHAR(255)  DEFAULT 'truckicon.jpg',
    role            ENUM('admin','manager','staff') NOT NULL DEFAULT 'manager',
    two_factor      TINYINT(1)    NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- User settings (dark mode, theme color, font size, etc.)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_settings (
    user_id         INT UNSIGNED PRIMARY KEY,
    dark_mode       TINYINT(1)   NOT NULL DEFAULT 0,
    theme_color     VARCHAR(20)  NOT NULL DEFAULT '#3498db',
    font_size       ENUM('small','medium','large','xlarge') NOT NULL DEFAULT 'medium',
    card_style      ENUM('default','compact','elevated') NOT NULL DEFAULT 'default',
    animation_speed ENUM('fast','normal','slow','none') NOT NULL DEFAULT 'normal',
    dashboard_view  VARCHAR(30)  DEFAULT 'default',
    shipment_updates TINYINT(1)  NOT NULL DEFAULT 1,
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Login history (successful AND failed attempts)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_history (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,                 -- NULL when the email didn't match any account
    email       VARCHAR(150) NOT NULL,
    success     TINYINT(1)   NOT NULL DEFAULT 1,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    user_agent  VARCHAR(255) DEFAULT NULL,
    logged_in_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- API keys (for external / third-party integrations)
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
-- API request log (sliding-window rate limiting)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_request_log (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id    INT UNSIGNED NOT NULL,
    requested_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_time (api_key_id, requested_at),
    CONSTRAINT fk_reqlog_key FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Shipments
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipments (
    id                  VARCHAR(20) PRIMARY KEY,          -- e.g. TRK001
    origin              VARCHAR(150) NOT NULL,
    destination         VARCHAR(150) NOT NULL,
    weight              DECIMAL(10,2) NOT NULL DEFAULT 0,
    priority            ENUM('Standard','Express','Priority') NOT NULL DEFAULT 'Standard',
    status              ENUM('Pending','In Transit','Delivered','Delayed') NOT NULL DEFAULT 'Pending',
    progress            TINYINT UNSIGNED NOT NULL DEFAULT 0,
    estimated_delivery  DATE NOT NULL,
    instructions        TEXT,
    created_by          INT UNSIGNED DEFAULT NULL,
    created_at          DATE NOT NULL,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_shipment_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Shipment tracking history (timeline shown on tracker page)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS tracking_updates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id     VARCHAR(20) NOT NULL,
    title           VARCHAR(150) NOT NULL,
    location        VARCHAR(150) NOT NULL,
    event_status    ENUM('completed','current','pending') NOT NULL DEFAULT 'pending',
    event_time      DATETIME NOT NULL,
    CONSTRAINT fk_tracking_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Fleet / Vehicles
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicles (
    id                  VARCHAR(20) PRIMARY KEY,          -- e.g. VH001
    type                ENUM('Truck','Van') NOT NULL DEFAULT 'Truck',
    status              ENUM('Available','In Transit','Maintenance') NOT NULL DEFAULT 'Available',
    driver              VARCHAR(150) DEFAULT 'N/A',
    location            VARCHAR(150) NOT NULL,
    fuel                TINYINT UNSIGNED NOT NULL DEFAULT 0,
    next_maintenance    DATE DEFAULT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Warehouse inventory
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory (
    sku         VARCHAR(20) PRIMARY KEY,                  -- e.g. ITM-001
    description VARCHAR(150) NOT NULL,
    quantity    INT UNSIGNED NOT NULL DEFAULT 0,
    location    VARCHAR(100) NOT NULL,
    status      ENUM('In Stock','Low Stock','Out of Stock') NOT NULL DEFAULT 'In Stock',
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Notifications (bell icon / in-app alerts)
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

-- =========================================================
-- Seed data (mirrors the sample data that used to be
-- hard-coded in script.js, so the app looks the same
-- the first time it runs against the database)
-- =========================================================

-- Default admin user. Password is: 112007@Lukali
-- (hash generated with PHP password_hash(); change it from Settings > Security after first login)
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

INSERT INTO user_settings (user_id) VALUES (1);

INSERT INTO shipments (id, origin, destination, weight, priority, status, progress, estimated_delivery, created_by, created_at) VALUES
('TRK001', 'Solwezi, NWP',   'Kitwe, CBP',   150, 'Express',  'In Transit', 65,  '2026-03-15', 1, '2026-03-10'),
('TRK002', 'Lusaka, LSKP',   'Ndola, CBP',   75,  'Standard', 'Pending',    0,   '2026-03-18', 1, '2026-03-12'),
('TRK003', 'Kabwe, CP',      'Chipata, EP',  200, 'Priority', 'Delivered',  100, '2026-03-10', 1, '2026-03-05'),
('TRK004', 'Livingstone, SP','Chingola, CBP',120, 'Express',  'Delayed',    50,  '2026-03-14', 1, '2026-03-08'),
('TRK005', 'Kabwe, CP',      'Lusaka, LSKP', 95,  'Standard', 'In Transit', 50,  '2026-03-16', 1, '2026-03-11');

INSERT INTO vehicles (id, type, status, driver, location, fuel, next_maintenance) VALUES
('VH001', 'Truck', 'Available',   'Mike Johnson',    'Chipata, EP',     85, '2026-04-15'),
('VH002', 'Van',   'In Transit',  'Sarah Williams',  'Livingstone, SP', 65, '2026-03-30'),
('VH003', 'Truck', 'Maintenance', 'N/A',              'Kabwe, CP',      45, '2026-03-20'),
('VH004', 'Van',   'Available',   'Tom Brown',        'Lusaka, LSKP',   92, '2026-04-10'),
('VH005', 'Truck', 'In Transit',  'Emma Davis',       'Kitwe, CBP',     78, '2026-04-05');

INSERT INTO inventory (sku, description, quantity, location, status) VALUES
('ITM-001', 'Electronics',       450, 'Aisle 1', 'In Stock'),
('ITM-002', 'Clothing',          780, 'Aisle 2', 'In Stock'),
('ITM-003', 'Furniture',         120, 'Aisle 3', 'Low Stock'),
('ITM-004', 'Food Items',        890, 'Aisle 4', 'In Stock'),
('ITM-005', 'Medical Supplies',  307, 'Aisle 5', 'In Stock');

INSERT INTO tracking_updates (shipment_id, title, location, event_status, event_time) VALUES
('TRK001', 'Shipment Created',        'Solwezi, NWP', 'completed', '2026-03-10 09:00:00'),
('TRK001', 'Picked Up by Carrier',    'Solwezi, NWP', 'completed', '2026-03-11 08:30:00'),
('TRK001', 'In Transit',              'Kitwe, CBP',   'current',   '2026-03-13 14:00:00'),
('TRK003', 'Shipment Created',        'Kabwe, CP',    'completed', '2026-03-05 10:00:00'),
('TRK003', 'Delivered',               'Chipata, EP',  'completed', '2026-03-10 11:45:00');
