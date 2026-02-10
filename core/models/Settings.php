<?php

class Settings
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        // Fail-safe to ensure table exists if migration failed
        $this->db->execute("CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function get($key, $default = null)
    {
        $result = $this->db->fetch("SELECT value FROM settings WHERE key = ?", [$key]);
        return $result ? $result['value'] : $default;
    }

    public function set($key, $value)
    {
        // SQLite upsert or simple logic
        $exists = $this->db->fetch("SELECT key FROM settings WHERE key = ?", [$key]);
        if ($exists) {
            return $this->db->execute("UPDATE settings SET value = ?, updated_at = datetime('now') WHERE key = ?", [$value, $key]);
        } else {
            return $this->db->execute("INSERT INTO settings (key, value) VALUES (?, ?)", [$key, $value]);
        }
    }

    public function getAll()
    {
        $rows = $this->db->fetchAll("SELECT key, value FROM settings");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }
}
