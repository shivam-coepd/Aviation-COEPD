-- COEPD - Database setup
-- Import this file in phpMyAdmin (Hostinger) or MySQL command line.

CREATE DATABASE IF NOT EXISTS aviation_pm_website
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE aviation_pm_website;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(180) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  experience VARCHAR(50) NOT NULL,
  background VARCHAR(100) NOT NULL,
  preferred_market VARCHAR(100) DEFAULT NULL,
  career_goal TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  status ENUM('new','contacted','interested','not_interested','enrolled') DEFAULT 'new',
  admin_notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at),
  INDEX idx_status (status),
  INDEX idx_email (email)
) ENGINE=InnoDB;

-- Do not insert an admin password directly here.
-- Open /admin/setup-admin.php once after deployment to create the admin account,
-- then delete that file from the server.
