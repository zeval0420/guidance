-- Guidance accounts (password for all: admin123, stored as MD5 like auth.php expects)
-- NOTE: employeeID values below are PLACEHOLDERS - replace with the real IDs (format NN-NNN).

INSERT INTO `accounts`
  (`employeeID`, `username`, `firstname`, `middlename`, `lastname`, `password`, `position`, `email`, `type`, `status`)
VALUES
  ('GD-001', 'pangdasl',    'Sheenah Pearl',        'L.', 'Pangda',     MD5('admin123'), 'G.S.A. I',          'pangdasl@irc.pshs.edu.ph',    'staff', 'active'),
  ('GD-002', 'manangbaogs', 'Glyza Rowhee Shalom',  'S.', 'Manangbao',  MD5('admin123'), 'Guidance Designate', 'manangbaogs@irc.pshs.edu.ph', 'staff', 'active'),
  ('GD-003', 'moralesrm',   'Rose Angelie',         'M.', 'Morales',    MD5('admin123'), 'Guidance Designate', 'moralesrm@irc.pshs.edu.ph',   'staff', 'active');
