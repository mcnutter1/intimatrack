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
  relationship_context ENUM('spouse','long_term_partner','dating','casual','poly_partner','friend','other') DEFAULT 'other',
  relationship_details VARCHAR(190) DEFAULT NULL,
  height_cm INT DEFAULT NULL,
  build ENUM('slim','average','athletic','curvy','plus','other') DEFAULT 'other',
  penis_size_rating ENUM('xs_under_4','small_4_5','average_5_6','above_avg_6_7','large_7_8','xl_over_8') DEFAULT NULL,
  circumcised TINYINT(1) DEFAULT NULL, -- NULL = unknown, 1 = yes, 0 = no
  race VARCHAR(100) DEFAULT NULL,
  met_location VARCHAR(190) DEFAULT NULL,
  first_met_notes VARCHAR(255) DEFAULT NULL,
  dimensions_note VARCHAR(255) DEFAULT NULL, -- optional non-explicit sizing note for health reference
  notes_enc TEXT DEFAULT NULL, -- encrypted notes (non-explicit)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS partner_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  partner_id INT NOT NULL,
  user_id INT NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  image_data LONGBLOB NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
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
  outcome_placement_enc TEXT DEFAULT NULL, -- encrypted JSON array of outcomes (non-explicit)
  cleanup_needed TINYINT(1) DEFAULT 0,
  cleanup_method ENUM('none','tissues','wipe','shower','other') DEFAULT 'none',
  cleanup_performed_by_partner_id INT DEFAULT NULL,
  aftercare_notes_enc TEXT DEFAULT NULL, -- encrypted non-explicit aftercare/hygiene notes
  scenario_tag ENUM('standard','cuckold_observer','cuckold_present_partner','group','other') DEFAULT 'standard',
  summary_enc TEXT DEFAULT NULL, -- encrypted freeform notes (non-explicit)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (cleanup_performed_by_partner_id) REFERENCES partners(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Encounter participants (partners)
CREATE TABLE IF NOT EXISTS encounter_participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  encounter_id INT NOT NULL,
  partner_id INT NOT NULL,
  scenario_role ENUM(
    'lead_partner',
    'receiving_partner',
    'support_partner',
    'observer',
    'cuckold_partner',
    'cuckold_cleanup',
    'aftercare_support',
    'other'
  ) DEFAULT 'lead_partner',
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
