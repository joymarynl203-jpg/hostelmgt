-- PostgreSQL: older HMS databases may have users.role CHECK without 'super_admin',
-- which causes ERROR: new row for relation "users" violates check constraint ...
-- when assigning super_admin (e.g. joymarynl203@gmail.com).
-- Render free tier: the deployed app also applies this fix automatically on first DB
-- connection (db.php); use this file if you want the manual path or Joymary password reset below.

-- Drop any existing CHECK constraints on public.users whose definition mentions role
-- (typically the single role enum-style check).
DO $$
DECLARE
  conname text;
BEGIN
  FOR conname IN
    SELECT c.conname::text
    FROM pg_constraint c
    JOIN pg_class rel ON rel.oid = c.conrelid
    JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
    WHERE c.contype = 'c'
      AND nsp.nspname = 'public'
      AND rel.relname = 'users'
      AND pg_get_constraintdef(c.oid) ILIKE '%role%'
  LOOP
    EXECUTE format('ALTER TABLE users DROP CONSTRAINT IF EXISTS %I', conname);
  END LOOP;
END $$;

ALTER TABLE users
  ADD CONSTRAINT users_role_check
  CHECK (role IN ('student', 'warden', 'university_admin', 'super_admin'));

-- Optional: make joymarynl203@gmail.com a super_admin (same password as seed: SuperAdmin2026!)
UPDATE users
SET
  name = 'Super Admin 2',
  role = 'super_admin',
  nin = NULL,
  phone = NULL,
  password_hash = '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2',
  is_active = 1
WHERE LOWER(email) = 'joymarynl203@gmail.com';

UPDATE users
SET
  name = 'Super Admin 3',
  role = 'super_admin',
  nin = NULL,
  phone = NULL,
  password_hash = '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2',
  is_active = 1
WHERE LOWER(email) = 'sekiddeumar@gmail.com';
