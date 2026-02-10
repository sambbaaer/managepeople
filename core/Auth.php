<?php

class Auth
{

    public static function login($email, $password)
    {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            // Log Mentor login
            if ($user['role'] === 'mentor') {
                self::ensureMentorLogsTable();
                $db->execute("INSERT INTO mentor_logs (user_id) VALUES (?)", [$user['id']]);
            }

            return true;
        }

        return false;
    }

    public static function logout()
    {
        session_unset();
        session_destroy();
    }

    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    public static function user()
    {
        if (!self::check())
            return null;

        // Cache user in static var if needed, or fetch from DB to be fresh
        // For simplicity returning session data or fetching fresh
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }

    public static function register($name, $email, $password, $role = 'owner')
    {
        $db = Database::getInstance();

        // Check if email exists
        $exists = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exists) {
            throw new Exception("E-Mail bereits registriert.");
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        return $db->execute(
            "INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, datetime('now'))",
            [$name, $email, $hash, $role]
        );
    }

    public static function requireLogin()
    {
        if (!self::check()) {
            redirect('index.php?page=login');
        }
    }

    public static function isMentor()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'mentor';
    }

    public static function isOwner()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'owner';
    }

    public static function denyMentorWriteAccess()
    {
        if (self::isMentor()) {
            // If it's an AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Du hast nur Leserechte (Mentor-Modus).']);
                exit;
            }

            // Otherwise redirect with message (we might need a flash session message system, but for now just redirect)
            redirect('index.php?page=dashboard&error=read_only');
        }
    }

    // Validation helper for passwords, used in Setup
    public static function validatePassword($password)
    {
        if (strlen($password) < 8)
            return false;
        if (!preg_match('/[A-Z]/', $password))
            return false;
        if (!preg_match('/[0-9]/', $password))
            return false;
        if (!preg_match('/[^a-zA-Z0-9]/', $password))
            return false; // Special char
        return true;
    }

    /**
     * Generate a password reset token for the given email.
     * Returns the token string or null if user not found.
     */
    public static function generateResetToken($email)
    {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);

        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Ensure columns exist (for older DBs without migration)
        try {
            $db->execute("ALTER TABLE users ADD COLUMN reset_token TEXT");
        } catch (Exception $e) {
        }
        try {
            $db->execute("ALTER TABLE users ADD COLUMN reset_expires DATETIME");
        } catch (Exception $e) {
        }

        $db->execute(
            "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?",
            [$token, $expires, $user['id']]
        );

        return $token;
    }

    /**
     * Validate a reset token. Returns the user or null.
     */
    public static function validateResetToken($token)
    {
        if (empty($token)) {
            return null;
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT * FROM users WHERE reset_token = ? AND reset_expires > datetime('now')",
            [$token]
        );

        return $user ?: null;
    }

    /**
     * Reset the password using a valid token.
     * Returns true on success, false on failure.
     */
    public static function resetPassword($token, $newPassword)
    {
        $user = self::validateResetToken($token);
        if (!$user) {
            return false;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $db = Database::getInstance();
        $db->execute(
            "UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?",
            [$hash, $user['id']]
        );

        return true;
    }

    /**
     * Get user by email (for password reset flow).
     */
    public static function getUserByEmail($email)
    {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }

    /**
     * Ensure the mentor_logs table exists (fallback for older installations)
     */
    public static function ensureMentorLogsTable()
    {
        $db = Database::getInstance();
        $db->execute("CREATE TABLE IF NOT EXISTS mentor_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            logged_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    }

    public static function getCalendarToken($userId)
    {
        $db = Database::getInstance();

        // Ensure column exists (robust check)
        $columns = $db->fetchAll("PRAGMA table_info(users)");
        $hasColumn = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'calendar_token') {
                $hasColumn = true;
                break;
            }
        }

        if (!$hasColumn) {
            try {
                $db->execute("ALTER TABLE users ADD COLUMN calendar_token TEXT");
                $db->execute("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_calendar_token ON users(calendar_token)");
            } catch (Exception $e) {
                // Log error or ignore if congruent migration
            }
        }

        $user = $db->fetch("SELECT calendar_token FROM users WHERE id = ?", [$userId]);

        if (empty($user['calendar_token'])) {
            $token = bin2hex(random_bytes(32));
            $db->execute("UPDATE users SET calendar_token = ? WHERE id = ?", [$token, $userId]);
            return $token;
        }

        return $user['calendar_token'];
    }

    public static function validateCalendarToken($token)
    {
        if (empty($token))
            return null;
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM users WHERE calendar_token = ?", [$token]);
    }
}
