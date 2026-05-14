-- MySQL: ensure role ENUM includes super_admin (safe if already applied).
ALTER TABLE users
  MODIFY COLUMN role ENUM('student', 'warden', 'university_admin', 'super_admin') NOT NULL DEFAULT 'student';

UPDATE users
SET
  name = 'Super Admin 2',
  role = 'super_admin',
  nin = NULL,
  phone = NULL,
  password_hash = '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2',
  is_active = 1
WHERE LOWER(email) = 'joymarynl203@gmail.com';
