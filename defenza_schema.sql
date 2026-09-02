-- Defenza SQL schema
-- Creates the main tables for authentication, contacts, SOS alerts, live location, and incident reports.

CREATE DATABASE IF NOT EXISTS `defenza_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `defenza_db`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL DEFAULT '',
  `email` VARCHAR(150) NOT NULL DEFAULT '',
  `password` VARCHAR(255) NOT NULL DEFAULT '',
  `phone` VARCHAR(50) NOT NULL DEFAULT '',
  `emergency_phone` VARCHAR(80) DEFAULT NULL,
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `profile_image` VARCHAR(255) NOT NULL DEFAULT 'default-avatar.png',
  `bio` TEXT DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `contact_name` VARCHAR(150) NOT NULL,
  `contact_phone` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_user_id` (`user_id`),
  KEY `idx_contacts_created_at` (`created_at`),
  CONSTRAINT `fk_contacts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `relationship` VARCHAR(80) NOT NULL DEFAULT 'Contact',
  `phone` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emergency_contacts_user_id` (`user_id`),
  CONSTRAINT `fk_emergency_contacts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sos_alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `accuracy` DECIMAL(8,2) DEFAULT NULL,
  `address` VARCHAR(255) NOT NULL DEFAULT '',
  `status` ENUM('active','resolved') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `event_timestamp` DATETIME NULL,
  `google_maps_url` VARCHAR(1024) NULL,
  `location_source` VARCHAR(20) NOT NULL DEFAULT 'gps',
  `location_captured_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sos_user_id` (`user_id`),
  KEY `idx_sos_status_created_at` (`status`, `created_at`),
  CONSTRAINT `fk_sos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `live_locations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `accuracy` DECIMAL(8,2) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `event_timestamp` DATETIME NULL,
  `google_maps_url` VARCHAR(1024) NULL,
  `location_source` VARCHAR(20) NOT NULL DEFAULT 'gps',
  `location_captured_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_live_locations_user_id` (`user_id`),
  KEY `idx_live_locations_user_id` (`user_id`),
  CONSTRAINT `fk_live_locations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `incident_reports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `incident_type` VARCHAR(100) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_incident_reports_user_id` (`user_id`),
  KEY `idx_incident_reports_created_at` (`created_at`),
  CONSTRAINT `fk_incident_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `safe_places` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(40) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(80) NOT NULL DEFAULT '',
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_safe_places_category` (`category`),
  KEY `idx_safe_places_lat_lng` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `safe_places` (`category`, `name`, `address`, `phone`, `latitude`, `longitude`)
SELECT 'police', 'Karachi Central Police Station', 'Saddar, Karachi', '021-99203111', 24.8607, 67.0011
WHERE NOT EXISTS (SELECT 1 FROM `safe_places` WHERE `name` = 'Karachi Central Police Station');

INSERT INTO `safe_places` (`category`, `name`, `address`, `phone`, `latitude`, `longitude`)
SELECT 'police', 'Gulshan-e-Iqbal Police Station', 'Block 6, Gulshan-e-Iqbal, Karachi', '021-99243864', 24.9180, 67.0970
WHERE NOT EXISTS (SELECT 1 FROM `safe_places` WHERE `name` = 'Gulshan-e-Iqbal Police Station');

INSERT INTO `safe_places` (`category`, `name`, `address`, `phone`, `latitude`, `longitude`)
SELECT 'police', 'Clifton Police Station', 'Block 5, Clifton, Karachi', '021-99250552', 24.8215, 67.0345
WHERE NOT EXISTS (SELECT 1 FROM `safe_places` WHERE `name` = 'Clifton Police Station');

INSERT INTO `safe_places` (`category`, `name`, `address`, `phone`, `latitude`, `longitude`)
SELECT 'hospital', 'Liaquat National Hospital', 'National Stadium Road, Karachi', '021-111-456-456', 24.8940, 67.0720
WHERE NOT EXISTS (SELECT 1 FROM `safe_places` WHERE `name` = 'Liaquat National Hospital');

INSERT INTO `safe_places` (`category`, `name`, `address`, `phone`, `latitude`, `longitude`)
SELECT 'hospital', 'Aga Khan University Hospital', 'Stadium Road, Karachi', '021-111-911-911', 24.8922, 67.0750
WHERE NOT EXISTS (SELECT 1 FROM `safe_places` WHERE `name` = 'Aga Khan University Hospital');

INSERT INTO `safe_places` (`category`, `name`, `address`, `phone`, `latitude`, `longitude`)
SELECT 'helpline', 'Sindh Women Protection Cell', 'Protection Block, Karachi', '1094', 24.8210, 67.0340
WHERE NOT EXISTS (SELECT 1 FROM `safe_places` WHERE `name` = 'Sindh Women Protection Cell');

INSERT INTO `safe_places` (`category`, `name`, `address`, `phone`, `latitude`, `longitude`)
SELECT 'helpline', 'Edhi Rescue Centre', 'Sohrab Goth HQ, Karachi', '115', 24.8600, 67.0011
WHERE NOT EXISTS (SELECT 1 FROM `safe_places` WHERE `name` = 'Edhi Rescue Centre');
