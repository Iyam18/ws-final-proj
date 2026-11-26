CREATE DATABASE IF NOT EXISTS daymark
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE daymark;

CREATE TABLE IF NOT EXISTS staff_users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_staff_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS patients (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_user_id BIGINT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  date_of_birth DATE NULL,
  phone VARCHAR(11) NOT NULL DEFAULT '',
  email VARCHAR(254) NOT NULL DEFAULT '',
  address VARCHAR(255) NOT NULL DEFAULT '',
  allergies VARCHAR(2000) NOT NULL DEFAULT '',
  medical_notes VARCHAR(2000) NOT NULL DEFAULT '',
  archived_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_patients_staff_archive_name (staff_user_id, archived_at, full_name),
  CONSTRAINT fk_patients_staff_user FOREIGN KEY (staff_user_id) REFERENCES staff_users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_user_id BIGINT UNSIGNED NOT NULL,
  patient_id BIGINT UNSIGNED NOT NULL,
  reason VARCHAR(160) NOT NULL,
  notes VARCHAR(2000) NOT NULL DEFAULT '',
  scheduled_at DATETIME NOT NULL,
  status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_appointments_staff_schedule (staff_user_id, status, scheduled_at),
  KEY idx_appointments_patient_schedule (patient_id, scheduled_at),
  CONSTRAINT fk_appointments_staff_user FOREIGN KEY (staff_user_id) REFERENCES staff_users (id) ON DELETE CASCADE,
  CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE RESTRICT
) ENGINE=InnoDB;
