<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Task.php';

/**
 * AutomationAction Model
 * 
 * Verwaltet Aktionen für Custom-Automatisierungen
 * Führt die DANN-Aktionen aus wenn Bedingungen erfüllt sind
 */
class AutomationAction
{
    protected $db;

    // Verfügbare Aktionstypen
    public const ACTION_TYPES = [
        'create_task' => [
            'label' => 'ToDo erstellen',
            'icon' => 'clipboard-list',
            'config_fields' => [
                'title' => ['label' => 'Titel', 'type' => 'text', 'required' => true],
                'description' => ['label' => 'Beschreibung', 'type' => 'textarea', 'required' => false],
                'days_offset' => ['label' => 'Fällig in X Tagen', 'type' => 'number', 'required' => true, 'default' => 0],
                'priority' => ['label' => 'Priorität', 'type' => 'select', 'options' => ['normal', 'high', 'urgent'], 'default' => 'normal']
            ]
        ],
        'change_status' => [
            'label' => 'Status ändern',
            'icon' => 'arrow-right-circle',
            'config_fields' => [
                'new_status' => ['label' => 'Neuer Status', 'type' => 'select', 'options' => ['Offen', 'Interessent', 'Kundin', 'Partnerin', 'Stillgelegt', 'Abgeschlossen'], 'required' => true]
            ]
        ],
        'change_phase' => [
            'label' => 'Phase ändern',
            'icon' => 'git-branch',
            'config_fields' => [
                'new_phase' => ['label' => 'Neue Phase', 'type' => 'text', 'required' => true]
            ]
        ],
        'add_tag' => [
            'label' => 'Tag hinzufügen',
            'icon' => 'tag',
            'config_fields' => [
                'tag_name' => ['label' => 'Tag-Name', 'type' => 'text', 'required' => true]
            ]
        ],
        'remove_tag' => [
            'label' => 'Tag entfernen',
            'icon' => 'x-circle',
            'config_fields' => [
                'tag_name' => ['label' => 'Tag-Name', 'type' => 'text', 'required' => true]
            ]
        ]
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Alle Aktionen einer Regel abrufen
     */
    public function getByRuleId($ruleId)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM automation_actions 
            WHERE rule_id = ? 
            ORDER BY sort_order ASC
        ');
        $stmt->execute([$ruleId]);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // JSON config dekodieren
        foreach ($actions as &$action) {
            $action['config'] = json_decode($action['action_config'], true) ?? [];
        }

        return $actions;
    }

    /**
     * Aktion erstellen
     */
    public function create($data)
    {
        $configJson = is_array($data['action_config'])
            ? json_encode($data['action_config'])
            : $data['action_config'];

        $stmt = $this->db->prepare('
            INSERT INTO automation_actions (rule_id, action_type, action_config, sort_order)
            VALUES (?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['rule_id'],
            $data['action_type'],
            $configJson,
            $data['sort_order'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Aktion aktualisieren
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        if (isset($data['action_type'])) {
            $fields[] = 'action_type = ?';
            $values[] = $data['action_type'];
        }

        if (isset($data['action_config'])) {
            $fields[] = 'action_config = ?';
            $values[] = is_array($data['action_config'])
                ? json_encode($data['action_config'])
                : $data['action_config'];
        }

        if (isset($data['sort_order'])) {
            $fields[] = 'sort_order = ?';
            $values[] = $data['sort_order'];
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $stmt = $this->db->prepare('UPDATE automation_actions SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    /**
     * Aktion löschen
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare('DELETE FROM automation_actions WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Alle Aktionen einer Regel löschen
     */
    public function deleteByRuleId($ruleId)
    {
        $stmt = $this->db->prepare('DELETE FROM automation_actions WHERE rule_id = ?');
        return $stmt->execute([$ruleId]);
    }

    /**
     * Aktionen für einen Kontakt ausführen
     * 
     * @param array $actions Array von Aktionen
     * @param array $contact Kontakt-Daten
     * @param int $ruleId ID der Regel (für Logging)
     * @return array Ergebnisse der Ausführung
     */
    public function execute($actions, $contact, $ruleId = null)
    {
        $results = [];

        foreach ($actions as $action) {
            $config = is_string($action['action_config'])
                ? json_decode($action['action_config'], true)
                : ($action['config'] ?? $action['action_config']);

            $result = $this->executeAction($action['action_type'], $config, $contact, $ruleId);
            $results[] = [
                'action_type' => $action['action_type'],
                'success' => $result['success'],
                'message' => $result['message'] ?? ''
            ];
        }

        return $results;
    }

    /**
     * Einzelne Aktion ausführen
     */
    protected function executeAction($actionType, $config, $contact, $ruleId = null)
    {
        switch ($actionType) {
            case 'create_task':
                return $this->executeCreateTask($config, $contact, $ruleId);

            case 'change_status':
                return $this->executeChangeStatus($config, $contact);

            case 'change_phase':
                return $this->executeChangePhase($config, $contact);

            case 'add_tag':
                return $this->executeAddTag($config, $contact);

            case 'remove_tag':
                return $this->executeRemoveTag($config, $contact);

            default:
                return ['success' => false, 'message' => 'Unbekannter Aktionstyp'];
        }
    }

    /**
     * ToDo erstellen
     */
    protected function executeCreateTask($config, $contact, $ruleId)
    {
        $taskModel = new Task();

        $dueDate = null;
        if (isset($config['days_offset'])) {
            $dueDate = date('Y-m-d', strtotime("+{$config['days_offset']} days"));
        }

        // Platzhalter im Titel ersetzen
        $title = $this->replacePlaceholders($config['title'] ?? 'Automatische Aufgabe', $contact);
        $description = $this->replacePlaceholders($config['description'] ?? '', $contact);

        $taskId = $taskModel->create([
            'contact_id' => $contact['id'],
            'title' => $title,
            'description' => $description,
            'priority' => $config['priority'] ?? 'normal',
            'due_date' => $dueDate,
            'auto_generated' => 'custom',
            'automation_rule_id' => $ruleId
        ]);

        return [
            'success' => $taskId !== false,
            'message' => $taskId ? "ToDo erstellt: $title" : 'Fehler beim Erstellen des ToDos'
        ];
    }

    /**
     * Status ändern
     */
    protected function executeChangeStatus($config, $contact)
    {
        $stmt = $this->db->prepare('UPDATE contacts SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $result = $stmt->execute([$config['new_status'], $contact['id']]);

        return [
            'success' => $result,
            'message' => $result ? "Status geändert zu: {$config['new_status']}" : 'Fehler beim Ändern des Status'
        ];
    }

    /**
     * Phase ändern
     */
    protected function executeChangePhase($config, $contact)
    {
        $stmt = $this->db->prepare('UPDATE contacts SET phase = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $result = $stmt->execute([$config['new_phase'], $contact['id']]);

        return [
            'success' => $result,
            'message' => $result ? "Phase geändert zu: {$config['new_phase']}" : 'Fehler beim Ändern der Phase'
        ];
    }

    /**
     * Tag hinzufügen
     */
    protected function executeAddTag($config, $contact)
    {
        // Prüfen ob Tag existiert
        $stmt = $this->db->prepare('SELECT id FROM tags WHERE name = ?');
        $stmt->execute([$config['tag_name']]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tag) {
            // Tag erstellen
            $stmt = $this->db->prepare('INSERT INTO tags (name) VALUES (?)');
            $stmt->execute([$config['tag_name']]);
            $tagId = $this->db->lastInsertId();
        } else {
            $tagId = $tag['id'];
        }

        // Tag zuweisen (falls nicht bereits vorhanden)
        $stmt = $this->db->prepare('INSERT OR IGNORE INTO contact_tags (contact_id, tag_id) VALUES (?, ?)');
        $result = $stmt->execute([$contact['id'], $tagId]);

        return [
            'success' => $result,
            'message' => $result ? "Tag hinzugefügt: {$config['tag_name']}" : 'Fehler beim Hinzufügen des Tags'
        ];
    }

    /**
     * Tag entfernen
     */
    protected function executeRemoveTag($config, $contact)
    {
        $stmt = $this->db->prepare('
            DELETE FROM contact_tags 
            WHERE contact_id = ? 
            AND tag_id IN (SELECT id FROM tags WHERE name = ?)
        ');
        $result = $stmt->execute([$contact['id'], $config['tag_name']]);

        return [
            'success' => $result,
            'message' => $result ? "Tag entfernt: {$config['tag_name']}" : 'Fehler beim Entfernen des Tags'
        ];
    }

    /**
     * Platzhalter in Texten ersetzen
     */
    protected function replacePlaceholders($text, $contact)
    {
        $placeholders = [
            '{name}' => $contact['name'] ?? '',
            '{status}' => $contact['status'] ?? '',
            '{phase}' => $contact['phase'] ?? '',
            '{datum}' => date('d.m.Y'),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    /**
     * Verfügbare Aktionstypen für UI abrufen
     */
    public static function getActionTypes()
    {
        return self::ACTION_TYPES;
    }
}
