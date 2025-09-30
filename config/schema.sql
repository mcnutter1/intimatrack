-- config/schema.sql
CREATE DATABASE IF NOT EXISTS intimatrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE intimatrack;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) UNIQUE NOT NULL,
  pass_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Partners
CREATE TABLE IF NOT EXISTS partners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(190) NOT NULL,
  relationship_context VARCHAR(190) DEFAULT NULL,
  height_cm INT DEFAULT NULL,
  build ENUM('slim','average','athletic','curvy','plus','other') DEFAULT 'other',
  dimensions_note VARCHAR(255) DEFAULT NULL, -- optional non-explicit sizing note for health reference
  notes_enc TEXT DEFAULT NULL, -- encrypted notes (non-explicit)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Encounters
CREATE TABLE IF NOT EXISTS encounters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  occurred_at DATETIME NOT NULL,
  location_label VARCHAR(190) DEFAULT NULL,
  location_type ENUM('home','hotel','outdoors','travel','other') DEFAULT 'other',
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  physical_intensity TINYINT UNSIGNED DEFAULT NULL, -- 1-10
  emotional_intensity TINYINT UNSIGNED DEFAULT NULL, -- 1-10
  overall_rating TINYINT UNSIGNED DEFAULT NULL, -- 1-5
  outcome_placement_enc TEXT DEFAULT NULL, -- encrypted short descriptor for health tracking (e.g., internal/external)
  cleanup_needed TINYINT(1) DEFAULT 0,
  cleanup_method ENUM('none','tissues','wipe','shower','other') DEFAULT 'none',
  aftercare_notes_enc TEXT DEFAULT NULL, -- encrypted non-explicit aftercare/hygiene notes
  scenario_tag ENUM('standard','cuckold_observer','cuckold_present_partner','group','other') DEFAULT 'standard',
  summary_enc TEXT DEFAULT NULL, -- encrypted freeform notes (non-explicit)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Encounter participants (partners)
CREATE TABLE IF NOT EXISTS encounter_participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  encounter_id INT NOT NULL,
  partner_id INT NOT NULL,
  role ENUM('primary','secondary','observer','other') DEFAULT 'primary',
  FOREIGN KEY (encounter_id) REFERENCES encounters(id) ON DELETE CASCADE,
  FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Simple audit log (optional)
CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(190) NOT NULL,
  meta JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
