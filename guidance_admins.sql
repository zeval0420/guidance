-- Run once in phpMyAdmin (database `dbadmin`) if the app's database user cannot CREATE tables.
-- Creates the admin-access table and makes pangdasl the super admin
-- (pangdasl's account must already exist in `accounts`).

CREATE TABLE IF NOT EXISTS `guidance_admins` (
  `employeeID` VARCHAR(20) NOT NULL,
  `is_super`   TINYINT(1) NOT NULL DEFAULT 0,
  `added_by`   VARCHAR(20) NULL DEFAULT NULL,
  `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`employeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `guidance_admins` (`employeeID`, `is_super`)
SELECT `employeeID`, 1 FROM `accounts` WHERE `email` = 'pangdasl@irc.pshs.edu.ph' LIMIT 1
ON DUPLICATE KEY UPDATE `is_super` = 1;

-- Optional: keep the previous lab admins as admins too.
INSERT IGNORE INTO `guidance_admins` (`employeeID`)
SELECT `employeeID` FROM `accounts` WHERE `position` IN ('Sci. Res. Assist.', 'Sci. Research Specialist I');

-- Check:
SELECT g.employeeID, g.is_super, a.email, a.status FROM guidance_admins g JOIN accounts a ON a.employeeID = g.employeeID;
