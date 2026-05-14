-- Promote joymarynl203@gmail.com to university administrator (day-to-day admin UI + forgot-password).
-- Same initial password as schema seed: SuperAdmin2026!
UPDATE users
SET
    name = 'University Admin',
    role = 'university_admin',
    nin = IF(nin IS NULL OR TRIM(nin) = '', 'HMS-JM203', nin),
    phone = IF(phone IS NULL OR TRIM(phone) = '', '+256700000001', phone),
    password_hash = '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2',
    is_active = 1
WHERE LOWER(email) = 'joymarynl203@gmail.com';
