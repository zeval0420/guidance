-- Guidance admin access (separate from reserve-a-lab, which decides its admins by position).
-- Run once in phpMyAdmin (database `dbadmin`). Safe to re-run.
-- Only accounts listed in `guidance_admins` can log in to the Guidance system.

CREATE TABLE IF NOT EXISTS `guidance_admins` (
  `employeeID` VARCHAR(20) NOT NULL,
  `is_super`   TINYINT(1) NOT NULL DEFAULT 0,
  `added_by`   VARCHAR(20) NULL DEFAULT NULL,
  `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`employeeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Remove the lab admins that an earlier version copied in by position.
DELETE FROM `guidance_admins`
WHERE `employeeID` IN (
  SELECT `employeeID` FROM `accounts`
  WHERE `position` IN ('Sci. Res. Assist.', 'Sci. Research Specialist I')
);

-- Pangda = super admin (can choose other admins from admin/admins.php).
INSERT INTO `guidance_admins` (`employeeID`, `is_super`)
SELECT `employeeID`, 1 FROM `accounts` WHERE `email` = 'pangdasl@irc.pshs.edu.ph'
ON DUPLICATE KEY UPDATE `is_super` = 1;

-- Other Guidance staff admins.
INSERT IGNORE INTO `guidance_admins` (`employeeID`, `is_super`)
SELECT `employeeID`, 0 FROM `accounts`
WHERE `email` IN ('manangbaogs@irc.pshs.edu.ph', 'moralesrm@irc.pshs.edu.ph');

-- Check:
SELECT g.employeeID, g.is_super, a.email, a.position, a.status
FROM guidance_admins g JOIN accounts a ON a.employeeID = g.employeeID;
