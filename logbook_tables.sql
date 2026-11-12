-- Logbook module (Guidance & Counseling Unit + Wellness Room).
-- logbook/index.php creates this automatically; kept here for the SQL Runner.
CREATE TABLE IF NOT EXISTS `guidance_logbook` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `unit`          ENUM('gcu','wellness') NOT NULL,
  `LRN`           VARCHAR(50) NULL DEFAULT NULL,
  `student_name`  VARCHAR(255) NOT NULL,
  `grade_section` VARCHAR(100) NOT NULL DEFAULT '',
  `log_date`      DATE NOT NULL,
  `time_in`       DATETIME NOT NULL,
  `time_out`      DATETIME NULL DEFAULT NULL,
  `source`        ENUM('qr','id','name') NOT NULL DEFAULT 'name',
  `logged_by`     VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_logbook_unit_date` (`unit`, `log_date`),
  KEY `idx_logbook_lrn` (`LRN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
