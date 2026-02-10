-- Migration: Add Mentor System (Logs and Role Support)
-- 1. Create mentor_logs table
CREATE TABLE IF NOT EXISTS mentor_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    logged_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Note: roles are already supported in users.role (owner/mentor)
-- Default roles are 'owner'. We will use 'mentor' for read-only users.
