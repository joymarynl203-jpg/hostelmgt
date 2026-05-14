-- Reset seeded super admin passwords to a known initial value (PHP bcrypt $2a$).
-- Initial password: SuperAdmin2026! — change immediately after login in production.
UPDATE users
SET password_hash = '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2'
WHERE role = 'super_admin';
