<?php

// Load required models
require_once __DIR__ . '/../models/AutomationRule.php';
require_once __DIR__ . '/../models/Settings.php';

/**
 * AutomationController
 * 
 * Controller für den Automation-Tab
 * Verwaltet Automatisierungsregeln und Settings
 */
class AutomationController
{
    protected $automationRuleModel;
    protected $settingsModel;

    public function __construct()
    {
        $this->automationRuleModel = new AutomationRule();
        $this->settingsModel = new Settings();
    }

    /**
     * Haupt-Seite: Zeigt alle Automatisierungsregeln
     */
    public function index()
    {
        // Alle Regeln laden für Statistik
        $allRules = $this->automationRuleModel->getAll();

        // Globale Automation-Einstellungen
        $automationEnabled = $this->settingsModel->get('automation_enabled');

        // Statistiken
        $activeCount = count(array_filter($allRules, fn($r) => $r['is_enabled'] == 1));
        $totalCount = count($allRules);

        // Predefined Rules filtern und gruppieren
        $groupedRules = [];
        $pRules = array_filter($allRules, fn($r) => $r['type'] === 'predefined');
        foreach ($pRules as $rule) {
            $groupedRules[$rule['trigger_status']][] = $rule;
        }

        // Custom Rules filtern
        $customRules = array_values(array_filter($allRules, fn($r) => $r['type'] === 'custom'));

        $data = [
            'grouped_rules' => $groupedRules, // Nur Predefined
            'custom_rules' => $customRules,   // Nur Custom
            'automation_enabled' => $automationEnabled === '1',
            'active_count' => $activeCount,
            'total_count' => $totalCount,
            'page_title' => 'Automation'
        ];

        include __DIR__ . '/../../templates/automation.php';
    }

    /**
     * AJAX: Regel aktivieren/deaktivieren
     */
    public function toggleRule()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $ruleId = $_POST['rule_id'] ?? null;

        if (!$ruleId) {
            echo json_encode(['success' => false, 'error' => 'Missing rule_id']);
            return;
        }

        $result = $this->automationRuleModel->toggleEnabled($ruleId);

        if ($result) {
            $rule = $this->automationRuleModel->find($ruleId);
            echo json_encode([
                'success' => true,
                'is_enabled' => $rule['is_enabled'] == 1,
                'message' => $rule['is_enabled'] ? 'Regel aktiviert' : 'Regel deaktiviert'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Regel konnte nicht geändert werden']);
        }
    }

    /**
     * AJAX: Regel bearbeiten (Zeitspanne, Titel, etc.)
     */
    public function updateRule()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $ruleId = $_POST['rule_id'] ?? null;
        $field = $_POST['field'] ?? null;
        $value = $_POST['value'] ?? null;

        if (!$ruleId || !$field) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        // Nur bestimmte Felder erlauben
        $allowedFields = ['days_offset', 'task_title', 'task_description', 'task_priority'];
        if (!in_array($field, $allowedFields)) {
            echo json_encode(['success' => false, 'error' => 'Field not allowed']);
            return;
        }

        // Validierung für days_offset
        if ($field === 'days_offset') {
            if (!is_numeric($value) || $value < 0) {
                echo json_encode(['success' => false, 'error' => 'Ungültiger Wert für Tage']);
                return;
            }
        }

        $result = $this->automationRuleModel->update($ruleId, [$field => $value]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Regel aktualisiert'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Regel konnte nicht aktualisiert werden']);
        }
    }

    /**
     * AJAX: Globale Automation ein/ausschalten
     */
    public function toggleGlobal()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $currentValue = $this->settingsModel->get('automation_enabled');
        $newValue = $currentValue === '1' ? '0' : '1';

        $result = $this->settingsModel->set('automation_enabled', $newValue);

        if ($result) {
            echo json_encode([
                'success' => true,
                'is_enabled' => $newValue === '1',
                'message' => $newValue === '1' ? 'Automation aktiviert' : 'Automation deaktiviert'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Einstellung konnte nicht geändert werden']);
        }
    }

    /**
     * Custom Rule Editor anzeigen (Create/Edit)
     */
    public function editCustomRule()
    {
        require_once __DIR__ . '/../models/AutomationCondition.php';
        require_once __DIR__ . '/../models/AutomationAction.php';

        $ruleId = $_GET['id'] ?? null;
        $rule = null;
        $conditions = [];
        $actions = [];

        if ($ruleId) {
            $rule = $this->automationRuleModel->findWithDetails($ruleId);
            if ($rule) {
                $conditions = $rule['conditions'];
                $actions = $rule['actions'];
            }
        }

        $data = [
            'rule' => $rule,
            'conditions' => $conditions,
            'actions' => $actions,
            'available_fields' => AutomationCondition::getAvailableFields(),
            'comparisons' => AutomationCondition::getComparisons(),
            'action_types' => AutomationAction::getActionTypes(),
            'is_new' => !$rule,
            'page_title' => $rule ? 'Regel bearbeiten' : 'Neue Regel'
        ];

        include __DIR__ . '/../../templates/automation/edit.php';
    }

    /**
     * AJAX: Custom Rule erstellen
     */
    public function createCustomRule()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Name ist erforderlich']);
            return;
        }

        // Conditions und Actions aus JSON dekodieren
        $conditions = json_decode($_POST['conditions'] ?? '[]', true) ?: [];
        $actions = json_decode($_POST['actions'] ?? '[]', true) ?: [];

        if (empty($actions)) {
            echo json_encode(['success' => false, 'error' => 'Mindestens eine Aktion ist erforderlich']);
            return;
        }

        $data = [
            'name' => $name,
            'trigger_status' => $_POST['trigger_status'] ?? 'Interessent',
            'trigger_sub_status' => $_POST['trigger_sub_status'] ?: null,
            'task_title' => $_POST['task_title'] ?? $name,
            'task_description' => $_POST['task_description'] ?? '',
            'task_priority' => $_POST['task_priority'] ?? 'normal',
            'days_offset' => (int) ($_POST['days_offset'] ?? 0),
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0
        ];

        $ruleId = $this->automationRuleModel->createCustomRule($data, $conditions, $actions);

        if ($ruleId) {
            echo json_encode([
                'success' => true,
                'rule_id' => $ruleId,
                'message' => 'Regel erstellt'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Erstellen']);
        }
    }

    /**
     * AJAX: Custom Rule aktualisieren
     */
    public function updateCustomRule()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $ruleId = $_POST['rule_id'] ?? null;
        if (!$ruleId) {
            echo json_encode(['success' => false, 'error' => 'Rule ID fehlt']);
            return;
        }

        $conditions = json_decode($_POST['conditions'] ?? '[]', true) ?: [];
        $actions = json_decode($_POST['actions'] ?? '[]', true) ?: [];

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'trigger_status' => $_POST['trigger_status'] ?? 'Interessent',
            'trigger_sub_status' => $_POST['trigger_sub_status'] ?: null,
            'task_title' => $_POST['task_title'] ?? '',
            'task_description' => $_POST['task_description'] ?? '',
            'task_priority' => $_POST['task_priority'] ?? 'normal',
            'days_offset' => (int) ($_POST['days_offset'] ?? 0),
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0
        ];

        $result = $this->automationRuleModel->updateCustomRule($ruleId, $data, $conditions, $actions);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Regel aktualisiert']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Aktualisieren']);
        }
    }

    /**
     * AJAX: Custom Rule löschen
     */
    public function deleteCustomRule()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $ruleId = $_POST['rule_id'] ?? null;

        if (!$ruleId) {
            echo json_encode(['success' => false, 'error' => 'Missing rule_id']);
            return;
        }

        $result = $this->automationRuleModel->delete($ruleId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Regel gelöscht']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Regel konnte nicht gelöscht werden (nur Custom Rules)']);
        }
    }

    /**
     * Custom Rule als JSON exportieren
     */
    public function exportRule()
    {
        $ruleId = $_GET['id'] ?? null;

        if (!$ruleId) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Rule ID fehlt';
            return;
        }

        $rule = $this->automationRuleModel->find($ruleId);
        $json = $this->automationRuleModel->exportToJson($ruleId);

        if (!$json) {
            header('HTTP/1.1 404 Not Found');
            echo 'Regel nicht gefunden';
            return;
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $rule['name']) . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $json;
    }

    /**
     * AJAX: Custom Rule aus JSON importieren
     */
    public function importRule()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        // JSON aus Datei-Upload oder Raw-Body
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $json = file_get_contents($_FILES['file']['tmp_name']);
        } else {
            $json = file_get_contents('php://input');
            if (empty($json)) {
                $json = $_POST['json'] ?? null;
            }
        }

        if (!$json) {
            echo json_encode(['success' => false, 'error' => 'Keine Daten empfangen']);
            return;
        }

        $result = $this->automationRuleModel->importFromJson($json);
        echo json_encode($result);
    }

    /**
     * AJAX: Daten für Rule-Editor laden (Felder, Comparisons, etc.)
     */
    public function getEditorData()
    {
        header('Content-Type: application/json');

        require_once __DIR__ . '/../models/AutomationCondition.php';
        require_once __DIR__ . '/../models/AutomationAction.php';

        echo json_encode([
            'success' => true,
            'fields' => AutomationCondition::getAvailableFields(),
            'comparisons' => AutomationCondition::getComparisons(),
            'action_types' => AutomationAction::getActionTypes()
        ]);
    }
}

