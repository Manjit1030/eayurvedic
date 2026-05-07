USE `eayurvedic`;

ALTER TABLE `users`
  MODIFY `role` ENUM('user','admin','doctor') NOT NULL DEFAULT 'user';

SET @has_solution_admin_fk := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solutions'
    AND CONSTRAINT_NAME = 'fk_solution_admin'
);
SET @drop_solution_admin_fk := IF(
  @has_solution_admin_fk = 1,
  'ALTER TABLE `solutions` DROP FOREIGN KEY `fk_solution_admin`',
  'SELECT 1'
);
PREPARE stmt FROM @drop_solution_admin_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `solutions`
  MODIFY `admin_id` BIGINT UNSIGNED NULL;

SET @has_solution_admin_fk_after := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solutions'
    AND CONSTRAINT_NAME = 'fk_solution_admin'
);
SET @add_solution_admin_fk := IF(
  @has_solution_admin_fk_after = 0,
  'ALTER TABLE `solutions` ADD CONSTRAINT `fk_solution_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @add_solution_admin_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `doctor_profiles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `gender` VARCHAR(20) NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `years_of_experience` INT NOT NULL,
  `qualification` VARCHAR(255) NOT NULL,
  `ayurveda_degree_certificate` VARCHAR(255) NOT NULL,
  `clinic_license` VARCHAR(255) NOT NULL,
  `verification_status` ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT NULL,
  `verified_by` BIGINT UNSIGNED NULL,
  `verified_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `ux_doctor_profiles_user_id` (`user_id`),
  KEY `idx_doctor_profiles_status` (`verification_status`),
  KEY `idx_doctor_profiles_verified_by` (`verified_by`),
  CONSTRAINT `fk_doctor_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_doctor_profiles_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_doctor_id := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solutions'
    AND COLUMN_NAME = 'doctor_id'
);
SET @add_doctor_id := IF(
  @has_doctor_id = 0,
  'ALTER TABLE `solutions` ADD COLUMN `doctor_id` BIGINT UNSIGNED NULL AFTER `admin_id`',
  'SELECT 1'
);
PREPARE stmt FROM @add_doctor_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_solution_doctor_fk := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solutions'
    AND CONSTRAINT_NAME = 'fk_solution_doctor'
);
SET @add_solution_doctor_fk := IF(
  @has_solution_doctor_fk = 0,
  'ALTER TABLE `solutions` ADD CONSTRAINT `fk_solution_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @add_solution_doctor_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_concern_status_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'patient_concerns'
    AND INDEX_NAME = 'idx_patient_concerns_status'
);
SET @add_concern_status_idx := IF(
  @has_concern_status_idx = 0,
  'CREATE INDEX `idx_patient_concerns_status` ON `patient_concerns` (`status`)',
  'SELECT 1'
);
PREPARE stmt FROM @add_concern_status_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_solution_doctor_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solutions'
    AND INDEX_NAME = 'idx_solutions_doctor_id'
);
SET @add_solution_doctor_idx := IF(
  @has_solution_doctor_idx = 0,
  'CREATE INDEX `idx_solutions_doctor_id` ON `solutions` (`doctor_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @add_solution_doctor_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
