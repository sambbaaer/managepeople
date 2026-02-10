<?php

class AutomationRule
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Alle Regeln abrufen (optional nach Typ filtern)
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM automation_rules WHERE 1=1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        if (isset($filters['is_enabled'])) {
            $sql .= " AND is_enabled = ?";
            $params[] = $filters['is_enabled'];
        }

        $sql .= " ORDER BY 
            CASE type WHEN 'predefined' THEN 1 ELSE 2 END,
            trigger_status ASC, 
            trigger_sub_status ASC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Nur aktive Regeln abrufen
     */
    public function getEnabled($status = null, $subStatus = null)
    {
        $sql = "SELECT * FROM automation_rules WHERE is_enabled = 1";
        $params = [];

        if ($status !== null) {
            $sql .= " AND trigger_status = ?";
            $params[] = $status;
        }

        if ($subStatus !== null) {
            $sql .= " AND (trigger_sub_status = ? OR trigger_sub_status IS NULL)";
            $params[] = $subStatus;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Einzelne Regel abrufen
     */
    public function find($id)
    {
        return $this->db->fetch("SELECT * FROM automation_rules WHERE id = ?", [$id]);
    }

    /**
     * Neue Regel erstellen (für Custom Rules)
     */
    public function create($data)
    {
        $fields = [
            'name',
            'type',
            'trigger_status',
            'trigger_sub_status',
            'action_type',
            'task_title',
            'task_description',
            'task_priority',
            'days_offset',
            'is_enabled'
        ];

        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = '?';
                $values[] = $data[$field];
            }
        }

        $sql = "INSERT INTO automation_rules (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->execute($sql, $values);
        return $this->db->lastInsertId();
    }

    /**
     * Regel bearbeiten
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        // Automatisch updated_at setzen
        if (empty($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($fields))
            return false;

        $values[] = $id;
        $sql = "UPDATE automation_rules SET " . implode(', ', $fields) . " WHERE id = ?";

        return $this->db->execute($sql, $values);
    }

    /**
     * Regel aktivieren/deaktivieren
     */
    public function toggleEnabled($id)
    {
        $rule = $this->find($id);
        if (!$rule)
            return false;

        $newState = $rule['is_enabled'] ? 0 : 1;
        return $this->update($id, ['is_enabled' => $newState]);
    }

    /**
     * Regel löschen (nur Custom Rules erlaubt)
     */
    public function delete($id)
    {
        $rule = $this->find($id);
        if (!$rule || $rule['type'] === 'predefined') {
            return false; // Vordefinierte Regeln dürfen nicht gelöscht werden
        }

        return $this->db->execute("DELETE FROM automation_rules WHERE id = ?", [$id]);
    }

    /**
     * Vordefinierte Regeln initialisieren (Seeding)
     * Wird beim Setup aufgerufen
     */
    public function seedPredefinedRules()
    {
        $rules = [
            // STATUS: INTERESSENT
            [
                'name' => 'Interessent - Nachhaken',
                'type' => 'predefined',
                'trigger_status' => 'Interessent',
                'trigger_sub_status' => 'Feedback ausstehend',
                'task_title' => 'Nachhaken beim Kontakt',
                'task_description' => 'Kontakt hat noch nicht geantwortet. Nachhaken!',
                'task_priority' => 'normal',
                'days_offset' => 3
            ],
            [
                'name' => 'Interessent - Freshdate Einladung',
                'type' => 'predefined',
                'trigger_status' => 'Interessent',
                'trigger_sub_status' => 'Interesse Freshdate',
                'task_title' => 'Freshdate-Einladung senden',
                'task_description' => 'Kontakt zum Freshdate einladen.',
                'task_priority' => 'normal',
                'days_offset' => 7
            ],
            [
                'name' => 'Interessent - Erstgespräch',
                'type' => 'predefined',
                'trigger_status' => 'Interessent',
                'trigger_sub_status' => 'Interesse Produkte',
                'task_title' => 'Erstgespräch vereinbaren',
                'task_description' => 'Produkte vorstellen und Beratungsgespräch führen.',
                'task_priority' => 'normal',
                'days_offset' => 5
            ],
            [
                'name' => 'Interessent - Meeting/Event',
                'type' => 'predefined',
                'trigger_status' => 'Interessent',
                'trigger_sub_status' => 'Interesse Business',
                'task_title' => 'Meeting/Event einladen',
                'task_description' => 'Zum Business-Meeting oder Veranstaltung einladen.',
                'task_priority' => 'high',
                'days_offset' => 14
            ],

            // STATUS: KUNDIN
            [
                'name' => 'Kundin - Business-Interesse',
                'type' => 'predefined',
                'trigger_status' => 'Kundin',
                'trigger_sub_status' => null, // Gilt für alle Sub-Status
                'task_title' => 'Business-Interesse nachfragen',
                'task_description' => 'Nach 1 Jahr: Interesse am Business abklären.',
                'task_priority' => 'normal',
                'days_offset' => 365 // 1 Jahr
            ],
            [
                'name' => 'Kundin - Partner-Vorbereitung',
                'type' => 'predefined',
                'trigger_status' => 'Kundin',
                'trigger_sub_status' => 'Business Interesse',
                'task_title' => 'Vorbereitung Partner-Status',
                'task_description' => 'Partner-Einstieg vorbereiten und begleiten.',
                'task_priority' => 'high',
                'days_offset' => 14
            ],

            // STATUS: PARTNERIN
            [
                'name' => 'Partnerin - Inaktivität nachfragen',
                'type' => 'predefined',
                'trigger_status' => 'Partnerin',
                'trigger_sub_status' => 'Inaktiv',
                'task_title' => 'Nachfragen wegen Inaktivität',
                'task_description' => 'Partner ist inaktiv. Unterstützung anbieten.',
                'task_priority' => 'high',
                'days_offset' => 180 // 6 Monate
            ],
            [
                'name' => 'Partnerin - Ausschluss-Warnung',
                'type' => 'predefined',
                'trigger_status' => 'Partnerin',
                'trigger_sub_status' => 'Inaktiv',
                'task_title' => '⚠️ Ausschluss-Warnung: 1 Monat verbleibt',
                'task_description' => 'Nach 11 Monaten Inaktivität: Letzte Warnung vor automatischem Ausschluss gemäss Ringana-Vorgaben.',
                'task_priority' => 'urgent',
                'days_offset' => 330 // 11 Monate
            ],

            // STATUS: STILLGELEGT
            [
                'name' => 'Stillgelegt - Rückfrage',
                'type' => 'predefined',
                'trigger_status' => 'Stillgelegt',
                'trigger_sub_status' => null,
                'task_title' => 'Rückfrage nach Interesse',
                'task_description' => 'Nach 2 Jahren: Situation hat sich vielleicht geändert - erneut nachfragen.',
                'task_priority' => 'low',
                'days_offset' => 730 // 2 Jahre
            ]
        ];

        foreach ($rules as $rule) {
            // Prüfen ob Regel bereits existiert (by name)
            $existing = $this->db->fetch(
                "SELECT id FROM automation_rules WHERE name = ? AND type = 'predefined'",
                [$rule['name']]
            );

            if (!$existing) {
                $this->create($rule);
            }
        }

        return true;
    }

    /**
     * Regeln nach Status gruppiert abrufen (für UI)
     */
    public function getGroupedByStatus()
    {
        $allRules = $this->getAll();
        $grouped = [];

        foreach ($allRules as $rule) {
            $status = $rule['trigger_status'];
            if (!isset($grouped[$status])) {
                $grouped[$status] = [];
            }
            $grouped[$status][] = $rule;
        }

        return $grouped;
    }

    /**
     * Custom-Regeln abrufen (für UI-Sektion)
     */
    public function getCustomRules()
    {
        return $this->getAll(['type' => 'custom']);
    }

    /**
     * Bedingungen für eine Custom-Regel laden
     */
    public function getConditions($ruleId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM automation_conditions WHERE rule_id = ? ORDER BY group_id, id",
            [$ruleId]
        );
    }

    /**
     * Aktionen für eine Custom-Regel laden
     */
    public function getActions($ruleId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM automation_actions WHERE rule_id = ? ORDER BY id",
            [$ruleId]
        );
    }

    /**
     * Custom-Regel mit Bedingungen und Aktionen erstellen
     */
    public function createCustomRule($data, $conditions = [], $actions = [])
    {
        $data['type'] = 'custom';
        $ruleId = $this->create($data);

        if (!$ruleId) {
            return false;
        }

        // Bedingungen speichern
        foreach ($conditions as $condition) {
            $this->db->execute(
                "INSERT INTO automation_conditions (rule_id, group_id, operator, field, comparison, value) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $ruleId,
                    $condition['group_id'] ?? 0,
                    $condition['operator'] ?? 'AND',
                    $condition['field'],
                    $condition['comparison'],
                    $condition['value']
                ]
            );
        }

        // Aktionen speichern
        foreach ($actions as $action) {
            $config = is_array($action['config']) ? json_encode($action['config']) : $action['config'];
            $this->db->execute(
                "INSERT INTO automation_actions (rule_id, action_type, config) 
                 VALUES (?, ?, ?)",
                [$ruleId, $action['action_type'], $config]
            );
        }

        return $ruleId;
    }

    /**
     * Custom-Regel aktualisieren (inkl. Bedingungen und Aktionen)
     */
    public function updateCustomRule($id, $data, $conditions = [], $actions = [])
    {
        $rule = $this->find($id);
        if (!$rule || $rule['type'] !== 'custom') {
            return false;
        }

        // Regel-Daten aktualisieren
        $this->update($id, $data);

        // Alte Bedingungen löschen und neu erstellen
        $this->db->execute("DELETE FROM automation_conditions WHERE rule_id = ?", [$id]);
        foreach ($conditions as $condition) {
            $this->db->execute(
                "INSERT INTO automation_conditions (rule_id, group_id, operator, field, comparison, value) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $id,
                    $condition['group_id'] ?? 0,
                    $condition['operator'] ?? 'AND',
                    $condition['field'],
                    $condition['comparison'],
                    $condition['value']
                ]
            );
        }

        // Alte Aktionen löschen und neu erstellen
        $this->db->execute("DELETE FROM automation_actions WHERE rule_id = ?", [$id]);
        foreach ($actions as $action) {
            $config = is_array($action['config']) ? json_encode($action['config']) : $action['config'];
            $this->db->execute(
                "INSERT INTO automation_actions (rule_id, action_type, config) 
                 VALUES (?, ?, ?)",
                [$id, $action['action_type'], $config]
            );
        }

        return true;
    }

    /**
     * Regel mit allen Details laden (inkl. Bedingungen und Aktionen)
     */
    public function findWithDetails($id)
    {
        $rule = $this->find($id);
        if (!$rule) {
            return null;
        }

        $rule['conditions'] = $this->getConditions($id);
        $rule['actions'] = $this->getActions($id);

        return $rule;
    }

    /**
     * Custom-Regel als JSON exportieren
     */
    public function exportToJson($id)
    {
        $rule = $this->findWithDetails($id);
        if (!$rule) {
            return null;
        }

        // IDs entfernen für Export
        unset($rule['id']);
        foreach ($rule['conditions'] as &$condition) {
            unset($condition['id'], $condition['rule_id']);
        }
        foreach ($rule['actions'] as &$action) {
            unset($action['id'], $action['rule_id']);
        }

        $rule['export_version'] = '1.0';
        $rule['exported_at'] = date('Y-m-d H:i:s');

        return json_encode($rule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Custom-Regel aus JSON importieren
     */
    public function importFromJson($json)
    {
        $data = json_decode($json, true);
        if (!$data || !isset($data['name'])) {
            return ['success' => false, 'error' => 'Ungültiges JSON-Format'];
        }

        $conditions = $data['conditions'] ?? [];
        $actions = $data['actions'] ?? [];

        // Meta-Felder entfernen
        unset($data['conditions'], $data['actions'], $data['export_version'], $data['exported_at']);

        // Name anpassen falls bereits vorhanden
        $existing = $this->db->fetch(
            "SELECT id FROM automation_rules WHERE name = ? AND type = 'custom'",
            [$data['name']]
        );

        if ($existing) {
            $data['name'] = $data['name'] . ' (Import ' . date('d.m.Y H:i') . ')';
        }

        $ruleId = $this->createCustomRule($data, $conditions, $actions);

        if ($ruleId) {
            return ['success' => true, 'rule_id' => $ruleId, 'message' => 'Regel importiert'];
        }

        return ['success' => false, 'error' => 'Fehler beim Importieren'];
    }

    /**
     * Regel gegen einen Kontakt auswerten
     */
    public function evaluate($ruleId, $contact)
    {
        $rule = $this->find($ruleId);
        if (!$rule || !$rule['is_enabled']) {
            return false;
        }

        // Predefined Rules: Simple Status-Match
        if ($rule['type'] === 'predefined') {
            if ($rule['trigger_status'] !== $contact['status']) {
                return false;
            }
            if ($rule['trigger_sub_status'] && $rule['trigger_sub_status'] !== ($contact['sub_status'] ?? null)) {
                return false;
            }
            return true;
        }

        // Custom Rules: Komplexe Bedingungslogik
        $conditions = $this->getConditions($ruleId);
        if (empty($conditions)) {
            return true; // Keine Bedingungen = immer wahr
        }

        require_once __DIR__ . '/AutomationCondition.php';
        $conditionModel = new AutomationCondition();

        // Gruppiere Bedingungen nach group_id
        $groups = [];
        foreach ($conditions as $cond) {
            $groupId = $cond['group_id'] ?? 0;
            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [];
            }
            $groups[$groupId][] = $cond;
        }

        // OR zwischen Gruppen, AND innerhalb einer Gruppe
        foreach ($groups as $groupConditions) {
            $groupResult = true;
            foreach ($groupConditions as $cond) {
                $condResult = $conditionModel->evaluateSingle($cond, $contact);
                if ($cond['operator'] === 'AND') {
                    $groupResult = $groupResult && $condResult;
                } else {
                    // Bei OR innerhalb der Gruppe: neue Gruppe beginnen
                    if ($condResult) {
                        $groupResult = true;
                        break;
                    }
                }
            }
            if ($groupResult) {
                return true; // Eine Gruppe ist wahr = Gesamtergebnis wahr
            }
        }

        return false;
    }

    /**
     * Aktionen für eine Regel ausführen
     */
    public function executeActions($ruleId, $contactId)
    {
        require_once __DIR__ . '/AutomationAction.php';
        $actionModel = new AutomationAction();

        $actions = $this->getActions($ruleId);
        $results = [];

        foreach ($actions as $action) {
            $results[] = $actionModel->execute($action, $contactId);
        }

        return $results;
    }
}

