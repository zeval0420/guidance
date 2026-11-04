-- Guidance account for Pangda (password: admin123, stored as MD5 like auth.php expects).
-- Manangbao (manangbaogs) and Morales (moralesrm) already have accounts (position SST-I),
-- so they are NOT inserted again. Make them admins from the "Choose Admins" button
-- on admin/admins.php while logged in as pangdasl.
-- NOTE: the employeeID below is a PLACEHOLDER - replace it with the real ID (format NN-NNN).

INSERT INTO `accounts`
  (`employeeID`, `username`, `firstname`, `middlename`, `lastname`, `password`, `position`, `email`, `type`, `status`)
VALUES
  ('GD-001', 'pangdasl', 'Sheenah Pearl', 'L.', 'Pangda', MD5('admin123'), 'G.S.A. I', 'pangdasl@irc.pshs.edu.ph', 'staff', 'active');

-- Remove the duplicate accounts that an earlier version of this file inserted.
-- (Only the placeholder-ID rows with the Guidance Designate position; the original SST-I accounts are untouched.)
DELETE FROM `accounts`
WHERE `employeeID` IN ('GD-002', 'GD-003')
  AND `position` = 'Guidance Designate'
  AND `email` IN ('manangbaogs@irc.pshs.edu.ph', 'moralesrm@irc.pshs.edu.ph');
