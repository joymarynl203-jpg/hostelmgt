-- PHP session storage (fixes CSRF "verification failed" on Render when file sessions are lost).
CREATE TABLE IF NOT EXISTS hms_sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    expire BIGINT NOT NULL,
    data TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_hms_sessions_expire ON hms_sessions (expire);
