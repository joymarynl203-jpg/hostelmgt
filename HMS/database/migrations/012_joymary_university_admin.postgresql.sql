-- Optional: promote joymarynl203@gmail.com to university_admin (not super_admin).
-- If you need super_admin for this email on PostgreSQL, use 013_users_role_allow_super_admin_postgresql.sql instead.
-- Promote joymarynl203@gmail.com to university administrator (day-to-day admin UI + forgot-password).
-- Same initial password as schema seed: SuperAdmin2026!
UPDATE users
SET
    name = 'University Admin',
    role = 'university_admin',
    nin = COALESCE(NULLIF(TRIM(nin), ''), 'HMS-JM203'),
    phone = COALESCE(NULLIF(TRIM(phone), ''), '+256700000001'),
    password_hash = '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2',
    is_active = 1
WHERE LOWER(email) = 'joymarynl203@gmail.com';
