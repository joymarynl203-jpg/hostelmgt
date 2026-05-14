-- Add third seeded super_admin (safe to run multiple times).
INSERT INTO users (name, email, password_hash, role, is_active, nin, phone)
VALUES (
    'Super Admin 3',
    'sekiddeumar@gmail.com',
    '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2',
    'super_admin',
    1,
    NULL,
    NULL
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    is_active = VALUES(is_active),
    nin = VALUES(nin),
    phone = VALUES(phone);
