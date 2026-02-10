-- DATA SCHEMA FOR MANAGEPEOPLE V3 --

-- 1. USERS
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'owner',
    reset_token TEXT,
    reset_expires DATETIME,
    calendar_token TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. CONTACTS
CREATE TABLE IF NOT EXISTS contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    address TEXT,
    beziehung TEXT DEFAULT 'Bekannte',
    status TEXT DEFAULT 'Offen',
    sub_status TEXT,
    phase TEXT,           -- Die aktuelle Phase innerhalb des Status
    phase_date DATE,      -- Datum für Phasen die ein Datum erfordern (z.B. "Geplant für März 2026")
    phase_notes TEXT,     -- Optionale Notizen zur aktuellen Phase
    notes TEXT, -- Legacy simple notes
    recommended_by TEXT, -- Text or ID reference
    birthday DATE,
    whatsapp TEXT,
    
    -- Social Media Handles
    social_instagram TEXT,
    social_tiktok TEXT,
    social_facebook TEXT,
    social_linkedin TEXT,
    
    last_contacted_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. NOTES (Timeline)
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contact_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE
);

-- 4. ACTIVITIES (History)
CREATE TABLE IF NOT EXISTS activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contact_id INTEGER NOT NULL,
    type TEXT NOT NULL, -- e.g. status_change, note_added
    description TEXT,
    old_value TEXT,
    new_value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE
);

-- 5. TASKS
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contact_id INTEGER, -- Optional linkage
    title TEXT NOT NULL,
    description TEXT,
    priority TEXT DEFAULT 'normal', -- normal, high, urgent
    due_date DATETIME,
    completed BOOLEAN DEFAULT 0,
    completed_at DATETIME,
    auto_generated TEXT, -- NULL, 'status_change', 'phase_change', 'birthday', 'followup', 'custom'
    triggered_by_status TEXT, -- Status der das ToDo ausgelöst hat (für Rollback)
    triggered_by_phase TEXT,  -- Phase die das ToDo ausgelöst hat (für Rollback)
    automation_rule_id INTEGER, -- FK zu automation_rules
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY(automation_rule_id) REFERENCES automation_rules(id) ON DELETE SET NULL
);

-- 6. PRODUCTS (Ringana Import)
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    imported_id TEXT UNIQUE, -- ID from JSON
    name TEXT NOT NULL,
    image_url TEXT,
    product_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. CONTACT PRODUCTS (Favorites)
CREATE TABLE IF NOT EXISTS contact_products (
    contact_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (contact_id, product_id),
    FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 8. SMART LISTS
CREATE TABLE IF NOT EXISTS smart_lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    filter_criteria TEXT NOT NULL, -- JSON
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 9. AUTOMATION RULES
CREATE TABLE IF NOT EXISTS automation_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL, -- 'predefined' oder 'custom'
    trigger_status TEXT NOT NULL, -- Status der triggert
    trigger_sub_status TEXT, -- Optional: Sub-Status Filter
    action_type TEXT NOT NULL DEFAULT 'create_task',
    task_title TEXT NOT NULL,
    task_description TEXT,
    task_priority TEXT DEFAULT 'normal',
    days_offset INTEGER DEFAULT 0,
    is_enabled BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 10. SETTINGS
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 11. MENTOR LOGS
CREATE TABLE IF NOT EXISTS mentor_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    logged_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- INITIAL SEED DATA --

-- Default Smart Lists
INSERT OR IGNORE INTO smart_lists (name, filter_criteria) VALUES 
('Geburtstagskinder (Aktueller Monat)', '{"birthday_month":"current"}'),
('Partnerinnen', '{"status":"Partnerin"}'),
('Kundinnen', '{"status":"Kundin"}'),
('Interessenten', '{"status":"Interessent"}');

-- Default Settings
INSERT OR IGNORE INTO settings (key, value) VALUES 
('backup_enabled', '0'),
('automation_enabled', '1'),
('automation_log_enabled', '0');
