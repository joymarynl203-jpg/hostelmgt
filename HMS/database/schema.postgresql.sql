-- HMS schema for PostgreSQL (Render, etc.)
-- Run once against your Postgres database, e.g.:
--   psql "$HMS_DATABASE_URL" -f database/schema.postgresql.sql
-- Or paste into Render Postgres "Shell" / any SQL client connected to the same DB as the web app.

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'student'
        CHECK (role IN ('student', 'warden', 'university_admin', 'super_admin')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reg_no VARCHAR(60) NULL,
    nin VARCHAR(28) NULL DEFAULT NULL,
    phone VARCHAR(30) NULL,
    institution VARCHAR(200) NULL DEFAULT NULL,
    is_active SMALLINT NOT NULL DEFAULT 1
);

CREATE TABLE hostels (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NOT NULL,
    nearby_institutions VARCHAR(500) NULL,
    map_latitude DECIMAL(10, 7) NULL DEFAULT NULL,
    map_longitude DECIMAL(11, 7) NULL DEFAULT NULL,
    description TEXT,
    rent_period_start DATE NULL,
    rent_period_end DATE NULL,
    is_active SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    managed_by INT NULL REFERENCES users (id) ON DELETE SET NULL
);

CREATE TABLE rooms (
    id SERIAL PRIMARY KEY,
    hostel_id INT NOT NULL REFERENCES hostels (id) ON DELETE CASCADE,
    room_number VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    current_occupancy INT NOT NULL DEFAULT 0,
    gender VARCHAR(16) NOT NULL DEFAULT 'mixed'
        CHECK (gender IN ('male', 'female', 'mixed')),
    monthly_fee DECIMAL(12, 2) NOT NULL
);

CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    student_id INT NOT NULL REFERENCES users (id),
    room_id INT NOT NULL REFERENCES rooms (id),
    status VARCHAR(32) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'approved', 'rejected', 'checked_in', 'checked_out')),
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    months INT NOT NULL DEFAULT 1,
    start_date DATE NULL,
    end_date DATE NULL,
    total_due DECIMAL(12, 2) NOT NULL DEFAULT 0,
    approved_by INT NULL,
    checked_in_by INT NULL,
    checked_out_by INT NULL,
    approved_at TIMESTAMP NULL,
    checked_in_at TIMESTAMP NULL,
    checked_out_at TIMESTAMP NULL
);

CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    booking_id INT NULL REFERENCES bookings (id) ON DELETE CASCADE,
    student_user_id INT NULL REFERENCES users (id) ON DELETE SET NULL,
    room_id INT NULL REFERENCES rooms (id) ON DELETE SET NULL,
    amount DECIMAL(12, 2) NOT NULL,
    method VARCHAR(32) NOT NULL DEFAULT 'mobile_money'
        CHECK (method IN ('cash', 'mobile_money', 'bank')),
    provider VARCHAR(32) NOT NULL DEFAULT 'other'
        CHECK (provider IN ('mtn', 'airtel', 'other')),
    gateway VARCHAR(30) NULL,
    merchant_reference VARCHAR(120) NULL,
    gateway_tracking_id VARCHAR(120) NULL,
    gateway_status VARCHAR(80) NULL,
    callback_payload TEXT NULL,
    transaction_ref VARCHAR(191),
    status VARCHAR(32) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'successful', 'failed')),
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_room_prebooking ON payments (room_id, status, booking_id);

CREATE TABLE maintenance_requests (
    id SERIAL PRIMARY KEY,
    booking_id INT NULL REFERENCES bookings (id) ON DELETE SET NULL,
    student_id INT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    room_id INT NULL REFERENCES rooms (id) ON DELETE SET NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'open'
        CHECK (status IN ('open', 'in_progress', 'resolved', 'closed')),
    priority VARCHAR(16) NOT NULL DEFAULT 'medium'
        CHECK (priority IN ('low', 'medium', 'high')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL
);

CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    type VARCHAR(32) NOT NULL DEFAULT 'system'
        CHECK (type IN ('booking', 'maintenance', 'payment', 'system')),
    is_read SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    actor_user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    details TEXT,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_reset_tokens (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_prt_user ON password_reset_tokens (user_id);
CREATE INDEX idx_prt_expires ON password_reset_tokens (expires_at);

-- PHP sessions (CSRF, flash, login) — required for Render / multi-instance when HMS_SESSION_STORE is auto|database
CREATE TABLE IF NOT EXISTS hms_sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    expire BIGINT NOT NULL,
    data TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_hms_sessions_expire ON hms_sessions (expire);

-- Seed super admins (initial password SuperAdmin2026! — change after first login in production)
INSERT INTO users (name, email, password_hash, role, is_active, nin, phone)
VALUES
    ('Super Admin 1', 'shamirah0mar915@gmail.com', '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2', 'super_admin', 1, NULL, NULL),
    ('Super Admin 2', 'joymarynl203@gmail.com', '$2a$10$EArC7PALCcqHW3MFc4XSKuJa0EbOrfKevhGsG7W1GiDLPI1T3p6h2', 'super_admin', 1, NULL, NULL)
ON CONFLICT (email) DO UPDATE SET
    name = EXCLUDED.name,
    password_hash = EXCLUDED.password_hash,
    role = EXCLUDED.role,
    is_active = EXCLUDED.is_active,
    nin = EXCLUDED.nin,
    phone = EXCLUDED.phone;
