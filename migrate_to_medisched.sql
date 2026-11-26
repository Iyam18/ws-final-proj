USE daymark;

SET @rename_staff_users = IF(
  (SELECT COUNT(*) FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users') = 1
  AND (SELECT COUNT(*) FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_users') = 0,
  'RENAME TABLE users TO staff_users',
  'SELECT ''staff user table already migrated'''
);
PREPARE rename_staff_users_statement FROM @rename_staff_users;
EXECUTE rename_staff_users_statement;
DEALLOCATE PREPARE rename_staff_users_statement;

SET @archive_tasks = IF(
  (SELECT COUNT(*) FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks') = 1
  AND (SELECT COUNT(*) FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'legacy_tasks') = 0,
  'RENAME TABLE tasks TO legacy_tasks',
  'SELECT ''legacy task table already archived'''
);
PREPARE archive_tasks_statement FROM @archive_tasks;
EXECUTE archive_tasks_statement;
DEALLOCATE PREPARE archive_tasks_statement;

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

SET @add_patient_archived_at = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'archived_at') = 0,
  'ALTER TABLE patients ADD COLUMN archived_at DATETIME NULL AFTER medical_notes',
  'SELECT ''patients.archived_at already exists'''
);
PREPARE add_patient_archived_at_statement FROM @add_patient_archived_at;
EXECUTE add_patient_archived_at_statement;
DEALLOCATE PREPARE add_patient_archived_at_statement;

SET @add_patient_archive_index = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND INDEX_NAME = 'idx_patients_staff_archive_name') = 0,
  'ALTER TABLE patients ADD INDEX idx_patients_staff_archive_name (staff_user_id, archived_at, full_name)',
  'SELECT ''idx_patients_staff_archive_name already exists'''
);
PREPARE add_patient_archive_index_statement FROM @add_patient_archive_index;
EXECUTE add_patient_archive_index_statement;
DEALLOCATE PREPARE add_patient_archive_index_statement;

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

SET @drop_appointment_priority = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'priority') > 0,
  'ALTER TABLE appointments DROP COLUMN priority',
  'SELECT ''appointments.priority already removed'''
);
PREPARE drop_appointment_priority_statement FROM @drop_appointment_priority;
EXECUTE drop_appointment_priority_statement;
DEALLOCATE PREPARE drop_appointment_priority_statement;

SET @drop_appointment_recurrence = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'recurrence') > 0,
  'ALTER TABLE appointments DROP COLUMN recurrence',
  'SELECT ''appointments.recurrence already removed'''
);
PREPARE drop_appointment_recurrence_statement FROM @drop_appointment_recurrence;
EXECUTE drop_appointment_recurrence_statement;
DEALLOCATE PREPARE drop_appointment_recurrence_statement;
