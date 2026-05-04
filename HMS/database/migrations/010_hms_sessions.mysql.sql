-- PHP session storage (optional; use when HMS_SESSION_STORE=database on MySQL).
CREATE TABLE IF NOT EXISTS hms_sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    expire INT NOT NULL,
    data MEDIUMTEXT NOT NULL,
    KEY idx_hms_sessions_expire (expire)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
