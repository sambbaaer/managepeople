-- Migration: Custom Automatisierungen & Workflows
-- Erstellt erweiterte Tabellen für benutzerdefinierte Regeln und Abläufe

-- ============================================
-- PHASE 1: Custom Automatisierungen
-- ============================================

-- Erweiterte Bedingungen für Automatisierungen
CREATE TABLE IF NOT EXISTS automation_conditions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id INTEGER NOT NULL,
    group_id INTEGER DEFAULT 0,          -- Für Gruppierung von UND/ODER
    operator TEXT DEFAULT 'AND',         -- 'AND' oder 'OR' (Verknüpfung zur nächsten Bedingung)
    field TEXT NOT NULL,                 -- z.B. 'status', 'phase', 'tag', 'product', 'beziehung'
    comparison TEXT NOT NULL,            -- 'equals', 'not_equals', 'contains', 'greater_than', 'less_than'
    value TEXT NOT NULL,                 -- Der Vergleichswert
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(rule_id) REFERENCES automation_rules(id) ON DELETE CASCADE
);

-- Erweiterte Aktionen für Automatisierungen
CREATE TABLE IF NOT EXISTS automation_actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id INTEGER NOT NULL,
    action_type TEXT NOT NULL,           -- 'create_task', 'change_status', 'change_phase', 'add_tag', 'remove_tag'
    action_config TEXT NOT NULL,         -- JSON mit Konfiguration
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(rule_id) REFERENCES automation_rules(id) ON DELETE CASCADE
);

-- ============================================
-- PHASE 2: Workflows / Abläufe
-- ============================================

-- Workflow-Templates (die Vorlage)
CREATE TABLE IF NOT EXISTS workflow_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    start_date_label TEXT DEFAULT 'Startdatum',  -- z.B. "Freshdate-Datum"
    icon TEXT DEFAULT 'calendar',                 -- Lucide Icon Name
    color TEXT DEFAULT 'secondary',               -- Farbklasse
    is_active BOOLEAN DEFAULT 1,
    is_system BOOLEAN DEFAULT 0,                  -- System-Templates nicht löschbar
    export_key TEXT,                              -- Für Ex-/Import (UUID)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Workflow-Schritte (die einzelnen ToDos in der Vorlage)
CREATE TABLE IF NOT EXISTS workflow_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    days_offset INTEGER NOT NULL,         -- Negativ = vor dem Datum, Positiv = nach dem Datum
    priority TEXT DEFAULT 'normal',       -- 'normal', 'high', 'urgent'
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(template_id) REFERENCES workflow_templates(id) ON DELETE CASCADE
);

-- Gestartete Workflow-Instanzen
CREATE TABLE IF NOT EXISTS workflow_instances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER NOT NULL,
    template_name TEXT NOT NULL,          -- Snapshot des Template-Namens
    contact_id INTEGER,                   -- Optional: Verknüpfter Kontakt
    target_date DATE NOT NULL,            -- Das Ziel-Datum (z.B. Freshdate)
    status TEXT DEFAULT 'active',         -- 'active', 'completed', 'cancelled'
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY(template_id) REFERENCES workflow_templates(id) ON DELETE SET NULL,
    FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE SET NULL
);

-- Verknüpfung zu erstellten Tasks
CREATE TABLE IF NOT EXISTS workflow_instance_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instance_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    step_id INTEGER,
    step_title TEXT NOT NULL,             -- Snapshot des Schritt-Titels
    target_date DATE NOT NULL,            -- Berechnetes Datum für diesen Schritt
    FOREIGN KEY(instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
    FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY(step_id) REFERENCES workflow_steps(id) ON DELETE SET NULL
);

-- ============================================
-- BEISPIEL-WORKFLOWS (Seeding)
-- ============================================

-- Freshdate Workflow
INSERT OR IGNORE INTO workflow_templates (id, name, description, start_date_label, icon, color, is_system, export_key) VALUES 
(1, 'Freshdate', 'Kompletter Ablauf für eine Verkaufsveranstaltung', 'Freshdate-Datum', 'party-popper', 'accent', 1, 'freshdate-v1');

INSERT OR IGNORE INTO workflow_steps (template_id, title, description, days_offset, priority, sort_order) VALUES
(1, 'Einladungen versenden', 'Kontakte für das Freshdate einladen', -14, 'high', 1),
(1, 'Bestätigungen sammeln', 'Zusagen und Absagen dokumentieren', -10, 'normal', 2),
(1, 'Location bestätigen', 'Veranstaltungsort reservieren/bestätigen', -7, 'high', 3),
(1, 'Einkaufsliste erstellen', 'Materialien und Produkte für das Event planen', -5, 'normal', 4),
(1, 'Einkäufe erledigen', 'Alle benötigten Produkte besorgen', -3, 'normal', 5),
(1, 'Freshdate durchführen', 'Der grosse Tag!', 0, 'urgent', 6),
(1, 'Follow-up mit Teilnehmern', 'Nachfassen bei interessierten Gästen', 3, 'high', 7);

-- Kennenlern-Gespräch Workflow
INSERT OR IGNORE INTO workflow_templates (id, name, description, start_date_label, icon, color, is_system, export_key) VALUES 
(2, 'Kennenlern-Gespräch', 'Vorbereitung und Nachbereitung eines persönlichen Gesprächs', 'Gesprächs-Datum', 'users', 'secondary', 1, 'kennenlernen-v1');

INSERT OR IGNORE INTO workflow_steps (template_id, title, description, days_offset, priority, sort_order) VALUES
(2, 'Termin bestätigen', 'Termin mit Kontakt nochmals bestätigen', -2, 'normal', 1),
(2, 'Materialien vorbereiten', 'Unterlagen, Muster, Präsentation bereitstellen', -1, 'normal', 2),
(2, 'Gespräch durchführen', 'Das Kennenlern-Gespräch', 0, 'high', 3),
(2, 'Notizen zusammenfassen', 'Wichtige Punkte aus dem Gespräch dokumentieren', 1, 'normal', 4),
(2, 'Nachfassen', 'Follow-up mit weiteren Infos oder Terminvorschlag', 3, 'normal', 5);

-- Produktvorstellung Workflow
INSERT OR IGNORE INTO workflow_templates (id, name, description, start_date_label, icon, color, is_system, export_key) VALUES 
(3, 'Produktvorstellung', 'Mini-Event zur Produktpräsentation', 'Vorstellungs-Datum', 'package', 'primary', 1, 'produkt-v1');

INSERT OR IGNORE INTO workflow_steps (template_id, title, description, days_offset, priority, sort_order) VALUES
(3, 'Produkte bestellen', 'Muster und Demo-Produkte organisieren', -5, 'high', 1),
(3, 'Einladungen versenden', 'Interessierte Kontakte einladen', -4, 'normal', 2),
(3, 'Produktmuster vorbereiten', 'Alle Materialien zusammenstellen', -1, 'normal', 3),
(3, 'Vorstellung durchführen', 'Die Produktpräsentation', 0, 'high', 4),
(3, 'Feedback einholen', 'Rückmeldungen der Teilnehmer sammeln', 2, 'normal', 5);
