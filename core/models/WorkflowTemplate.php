<?php

require_once __DIR__ . '/../Database.php';

/**
 * WorkflowTemplate Model
 * 
 * Verwaltet Workflow-Vorlagen (Abläufe)
 * Templates definieren eine Reihe von Schritten mit relativen Datumsangaben
 */
class WorkflowTemplate
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Tabellen erstellen falls nicht vorhanden
     */
    public function ensureTablesExist()
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS workflow_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            start_date_label TEXT DEFAULT 'Startdatum',
            icon TEXT DEFAULT 'calendar',
            color TEXT DEFAULT 'secondary',
            is_active BOOLEAN DEFAULT 1,
            is_system BOOLEAN DEFAULT 0,
            export_key TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS workflow_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            template_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            days_offset INTEGER NOT NULL,
            priority TEXT DEFAULT 'normal',
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(template_id) REFERENCES workflow_templates(id) ON DELETE CASCADE
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS workflow_instances (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            template_id INTEGER NOT NULL,
            template_name TEXT NOT NULL,
            contact_id INTEGER,
            target_date DATE NOT NULL,
            status TEXT DEFAULT 'active',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            FOREIGN KEY(template_id) REFERENCES workflow_templates(id) ON DELETE SET NULL,
            FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE SET NULL
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS workflow_instance_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            instance_id INTEGER NOT NULL,
            task_id INTEGER NOT NULL,
            step_id INTEGER,
            step_title TEXT NOT NULL,
            target_date DATE NOT NULL,
            FOREIGN KEY(instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
            FOREIGN KEY(task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY(step_id) REFERENCES workflow_steps(id) ON DELETE SET NULL
        )");

        // Seed system templates if empty
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM workflow_templates');
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $this->seedDefaultTemplates();
        }
    }

    /**
     * Standard-Workflows einfügen
     */
    protected function seedDefaultTemplates()
    {
        $this->db->exec("INSERT INTO workflow_templates (id, name, description, start_date_label, icon, color, is_system, export_key) VALUES
            (1, 'Freshdate', 'Kompletter Ablauf für eine Verkaufsveranstaltung', 'Freshdate-Datum', 'party-popper', 'accent', 1, 'freshdate-v1')");
        $this->db->exec("INSERT INTO workflow_steps (template_id, title, description, days_offset, priority, sort_order) VALUES
            (1, 'Einladungen versenden', 'Kontakte für das Freshdate einladen', -14, 'high', 1),
            (1, 'Bestätigungen sammeln', 'Zusagen und Absagen dokumentieren', -10, 'normal', 2),
            (1, 'Location bestätigen', 'Veranstaltungsort reservieren/bestätigen', -7, 'high', 3),
            (1, 'Einkaufsliste erstellen', 'Materialien und Produkte für das Event planen', -5, 'normal', 4),
            (1, 'Einkäufe erledigen', 'Alle benötigten Produkte besorgen', -3, 'normal', 5),
            (1, 'Freshdate durchführen', 'Der grosse Tag!', 0, 'urgent', 6),
            (1, 'Follow-up mit Teilnehmern', 'Nachfassen bei interessierten Gästen', 3, 'high', 7)");

        $this->db->exec("INSERT INTO workflow_templates (id, name, description, start_date_label, icon, color, is_system, export_key) VALUES
            (2, 'Kennenlern-Gespräch', 'Vorbereitung und Nachbereitung eines persönlichen Gesprächs', 'Gesprächs-Datum', 'users', 'secondary', 1, 'kennenlernen-v1')");
        $this->db->exec("INSERT INTO workflow_steps (template_id, title, description, days_offset, priority, sort_order) VALUES
            (2, 'Termin bestätigen', 'Termin mit Kontakt nochmals bestätigen', -2, 'normal', 1),
            (2, 'Materialien vorbereiten', 'Unterlagen, Muster, Präsentation bereitstellen', -1, 'normal', 2),
            (2, 'Gespräch durchführen', 'Das Kennenlern-Gespräch', 0, 'high', 3),
            (2, 'Notizen zusammenfassen', 'Wichtige Punkte aus dem Gespräch dokumentieren', 1, 'normal', 4),
            (2, 'Nachfassen', 'Follow-up mit weiteren Infos oder Terminvorschlag', 3, 'normal', 5)");

        $this->db->exec("INSERT INTO workflow_templates (id, name, description, start_date_label, icon, color, is_system, export_key) VALUES
            (3, 'Produktvorstellung', 'Mini-Event zur Produktpräsentation', 'Vorstellungs-Datum', 'package', 'primary', 1, 'produkt-v1')");
        $this->db->exec("INSERT INTO workflow_steps (template_id, title, description, days_offset, priority, sort_order) VALUES
            (3, 'Produkte bestellen', 'Muster und Demo-Produkte organisieren', -5, 'high', 1),
            (3, 'Einladungen versenden', 'Interessierte Kontakte einladen', -4, 'normal', 2),
            (3, 'Produktmuster vorbereiten', 'Alle Materialien zusammenstellen', -1, 'normal', 3),
            (3, 'Vorstellung durchführen', 'Die Produktpräsentation', 0, 'high', 4),
            (3, 'Feedback einholen', 'Rückmeldungen der Teilnehmer sammeln', 2, 'normal', 5)");
    }

    /**
     * Alle Templates abrufen
     */
    public function getAll($includeInactive = false)
    {
        $sql = 'SELECT * FROM workflow_templates';
        if (!$includeInactive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Schrittanzahl und Zeitspanne hinzufügen
        foreach ($templates as &$template) {
            $steps = $this->getSteps($template['id']);
            $template['step_count'] = count($steps);

            if (!empty($steps)) {
                $offsets = array_column($steps, 'days_offset');
                $template['min_offset'] = min($offsets);
                $template['max_offset'] = max($offsets);
            } else {
                $template['min_offset'] = 0;
                $template['max_offset'] = 0;
            }
        }

        return $templates;
    }

    /**
     * Einzelnes Template abrufen
     */
    public function find($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM workflow_templates WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Template erstellen
     */
    public function create($data)
    {
        $stmt = $this->db->prepare('
            INSERT INTO workflow_templates (name, description, start_date_label, icon, color, is_active, export_key)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['start_date_label'] ?? 'Startdatum',
            $data['icon'] ?? 'calendar',
            $data['color'] ?? 'secondary',
            $data['is_active'] ?? 1,
            $this->generateExportKey()
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Template aktualisieren
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        $allowedFields = ['name', 'description', 'start_date_label', 'icon', 'color', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';
        $values[] = $id;

        $stmt = $this->db->prepare('UPDATE workflow_templates SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    /**
     * Template löschen (nur wenn nicht System-Template)
     */
    public function delete($id)
    {
        // System-Templates können nicht gelöscht werden
        $template = $this->find($id);
        if ($template && $template['is_system']) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM workflow_templates WHERE id = ? AND is_system = 0');
        return $stmt->execute([$id]);
    }

    /**
     * Template duplizieren
     */
    public function duplicate($id)
    {
        $template = $this->find($id);
        if (!$template) {
            return false;
        }

        // Neues Template erstellen
        $newId = $this->create([
            'name' => $template['name'] . ' (Kopie)',
            'description' => $template['description'],
            'start_date_label' => $template['start_date_label'],
            'icon' => $template['icon'],
            'color' => $template['color']
        ]);

        // Schritte kopieren
        $steps = $this->getSteps($id);
        $stepModel = new WorkflowStep();

        foreach ($steps as $step) {
            $stepModel->create([
                'template_id' => $newId,
                'title' => $step['title'],
                'description' => $step['description'],
                'days_offset' => $step['days_offset'],
                'priority' => $step['priority'],
                'sort_order' => $step['sort_order']
            ]);
        }

        return $newId;
    }

    /**
     * Alle Schritte eines Templates abrufen
     */
    public function getSteps($templateId)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM workflow_steps 
            WHERE template_id = ? 
            ORDER BY sort_order ASC, days_offset ASC
        ');
        $stmt->execute([$templateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Template als JSON exportieren
     */
    public function exportToJson($id)
    {
        $template = $this->find($id);
        if (!$template) {
            return null;
        }

        $steps = $this->getSteps($id);

        return json_encode([
            'version' => '1.0',
            'type' => 'workflow_template',
            'export_key' => $template['export_key'],
            'name' => $template['name'],
            'description' => $template['description'],
            'start_date_label' => $template['start_date_label'],
            'icon' => $template['icon'],
            'color' => $template['color'],
            'steps' => array_map(function ($step) {
                return [
                    'title' => $step['title'],
                    'description' => $step['description'],
                    'days_offset' => $step['days_offset'],
                    'priority' => $step['priority'],
                    'sort_order' => $step['sort_order']
                ];
            }, $steps)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Template aus JSON importieren
     */
    public function importFromJson($json)
    {
        $data = is_string($json) ? json_decode($json, true) : $json;

        if (!$data || !isset($data['type']) || $data['type'] !== 'workflow_template') {
            return ['success' => false, 'error' => 'Ungültiges Import-Format'];
        }

        // Prüfen ob bereits existiert (anhand export_key)
        if (!empty($data['export_key'])) {
            $stmt = $this->db->prepare('SELECT id FROM workflow_templates WHERE export_key = ?');
            $stmt->execute([$data['export_key']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Dieser Ablauf existiert bereits'];
            }
        }

        // Template erstellen
        $templateId = $this->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'start_date_label' => $data['start_date_label'] ?? 'Startdatum',
            'icon' => $data['icon'] ?? 'calendar',
            'color' => $data['color'] ?? 'secondary'
        ]);

        // Export-Key übernehmen falls vorhanden
        if (!empty($data['export_key'])) {
            $stmt = $this->db->prepare('UPDATE workflow_templates SET export_key = ? WHERE id = ?');
            $stmt->execute([$data['export_key'], $templateId]);
        }

        // Schritte erstellen
        $stepModel = new WorkflowStep();
        foreach ($data['steps'] ?? [] as $stepData) {
            $stepModel->create([
                'template_id' => $templateId,
                'title' => $stepData['title'],
                'description' => $stepData['description'] ?? '',
                'days_offset' => $stepData['days_offset'],
                'priority' => $stepData['priority'] ?? 'normal',
                'sort_order' => $stepData['sort_order'] ?? 0
            ]);
        }

        return ['success' => true, 'template_id' => $templateId];
    }

    /**
     * Export-Key generieren
     */
    protected function generateExportKey()
    {
        return bin2hex(random_bytes(8));
    }
}
