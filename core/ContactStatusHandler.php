<?php

// Load required models
require_once __DIR__ . '/models/AutomationRule.php';
require_once __DIR__ . '/models/Task.php';
require_once __DIR__ . '/models/Activity.php';
require_once __DIR__ . '/models/Settings.php';
require_once __DIR__ . '/PhaseConfig.php';

/**
 * ContactStatusHandler
 * 
 * Handhabt Status- und Phasen-Wechsel bei Kontakten und triggert automatische Aktionen
 * - Erkennt Status-Änderungen
 * - Erkennt Phasen-Änderungen (NEU)
 * - Erstellt automatische ToDos basierend auf Regeln und PhaseConfig
 * - Löscht unerledigte ToDos bei Status/Phasen-Rückwechsel (Rollback)
 * - Loggt alle Änderungen
 */
class ContactStatusHandler
{
    protected $db;
    protected $automationRuleModel;
    protected $taskModel;
    protected $activityModel;
    protected $settingsModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->automationRuleModel = new AutomationRule();
        $this->taskModel = new Task();
        $this->activityModel = new Activity();
        $this->settingsModel = new Settings();
    }

    /**
     * Haupteinstiegspunkt: Wird aufgerufen wenn sich Status ändert
     * 
     * @param int $contactId
     * @param string $oldStatus Vorheriger Status
     * @param string $newStatus Neuer Status
     * @param string|null $newSubStatus Neuer Sub-Status (optional)
     * @return bool
     */
    public function handleStatusChange($contactId, $oldStatus, $newStatus, $newSubStatus = null)
    {
        // Prüfen ob Automation global aktiviert ist
        $automationEnabled = $this->settingsModel->get('automation_enabled');
        if (!$automationEnabled || $automationEnabled === '0') {
            // Auch ohne Automation: Activity-Log schreiben
            $this->logStatusChange($contactId, $oldStatus, $newStatus, $newSubStatus);
            return true;
        }

        try {
            // 1. Activity Log schreiben
            $this->logStatusChange($contactId, $oldStatus, $newStatus, $newSubStatus);

            // 2. Rollback: Unerledigte automatische ToDos vom alten Status löschen
            if ($oldStatus && $oldStatus !== $newStatus) {
                $this->rollbackAutomatedTasks($contactId, $oldStatus);
            }

            // 3. Neue automatische ToDos erstellen
            $contact = $this->getContact($contactId);
            if ($contact) {
                $applicableRules = $this->getApplicableRules($newStatus, $newSubStatus);
                if (!empty($applicableRules)) {
                    $this->createAutomatedTasks($contact, $applicableRules);
                }
            }

            return true;
        } catch (Exception $e) {
            // Bei Fehler loggen (falls aktiviert)
            if ($this->settingsModel->get('automation_log_enabled') === '1') {
                error_log("ContactStatusHandler Error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Findet zutreffende Automatisierungsregeln für Status
     */
    protected function getApplicableRules($status, $subStatus = null)
    {
        $rules = $this->automationRuleModel->getEnabled($status, $subStatus);

        // Filtern: Nur Regeln die zum Sub-Status passen
        $applicable = [];
        foreach ($rules as $rule) {
            // Regel gilt wenn:
            // 1. Kein Sub-Status definiert (gilt für alle)
            // 2. Sub-Status matched exakt
            if (
                $rule['trigger_sub_status'] === null ||
                $rule['trigger_sub_status'] === $subStatus
            ) {
                $applicable[] = $rule;
            }
        }

        return $applicable;
    }

    /**
     * Erstellt automatische ToDos basierend auf Regeln
     */
    protected function createAutomatedTasks($contact, $rules)
    {
        foreach ($rules as $rule) {
            // Fälligkeitsdatum berechnen
            $dueDate = null;
            if ($rule['days_offset'] > 0) {
                $dueDate = date('Y-m-d', strtotime('+' . $rule['days_offset'] . ' days'));
            }

            // ToDo erstellen
            $taskData = [
                'contact_id' => $contact['id'],
                'title' => $rule['task_title'],
                'description' => $rule['task_description'],
                'priority' => $rule['task_priority'],
                'due_date' => $dueDate,
                'auto_generated' => 'status_change',
                'triggered_by_status' => $contact['status'],
                'automation_rule_id' => $rule['id']
            ];

            $taskId = $this->taskModel->create($taskData);

            // Optional: Activity-Log für automatisches ToDo
            if ($taskId && $this->settingsModel->get('automation_log_enabled') === '1') {
                $this->activityModel->log(
                    $contact['id'],
                    'auto_task_created',
                    "Automatisches ToDo erstellt: " . $rule['task_title']
                );
            }
        }
    }

    /**
     * Rollback: Löscht unerledigte automatische ToDos vom vorherigen Status
     */
    protected function rollbackAutomatedTasks($contactId, $fromStatus)
    {
        $deleted = $this->taskModel->deleteUncompletedAutomatedTasks($contactId, $fromStatus);

        // Optional: Log schreiben
        if ($deleted && $this->settingsModel->get('automation_log_enabled') === '1') {
            $this->activityModel->log(
                $contactId,
                'auto_task_rollback',
                "Automatische ToDos von Status '$fromStatus' entfernt (Rollback)"
            );
        }
    }

    /**
     * Loggt Status-Änderung im Activity-Log
     */
    protected function logStatusChange($contactId, $oldStatus, $newStatus, $newSubStatus = null)
    {
        $description = "Status: $oldStatus → $newStatus";
        if ($newSubStatus) {
            $description .= " ($newSubStatus)";
        }

        $this->activityModel->log(
            $contactId,
            'status_change',
            $description,
            $oldStatus,
            $newStatus
        );
    }

    /**
     * Hilfsmethode: Kontakt abrufen
     */
    protected function getContact($contactId)
    {
        return $this->db->fetch("SELECT * FROM contacts WHERE id = ?", [$contactId]);
    }

    // =====================================================
    // PHASEN-SYSTEM (NEU)
    // =====================================================

    /**
     * Haupteinstiegspunkt: Wird aufgerufen wenn sich die Phase ändert
     * 
     * @param int $contactId
     * @param string $status Aktueller Haupt-Status
     * @param string|null $oldPhase Vorherige Phase
     * @param string $newPhase Neue Phase
     * @param string|null $phaseDate Optionales Datum für die Phase
     * @param string|null $phaseNotes Optionale Notizen zur Phase
     * @return bool
     */
    public function handlePhaseChange($contactId, $status, $oldPhase, $newPhase, $phaseDate = null, $phaseNotes = null)
    {
        // Prüfen ob Automation global aktiviert ist
        $automationEnabled = $this->settingsModel->get('automation_enabled');

        // Auch ohne Automation: Activity-Log und DB-Update schreiben
        $this->logPhaseChange($contactId, $status, $oldPhase, $newPhase, $phaseDate);
        $this->updateContactPhase($contactId, $newPhase, $phaseDate, $phaseNotes);

        if (!$automationEnabled || $automationEnabled === '0') {
            return true;
        }

        try {
            // 1. Rollback: Unerledigte automatische ToDos von der alten Phase löschen
            if ($oldPhase && $oldPhase !== $newPhase) {
                $this->rollbackPhaseTasks($contactId, $oldPhase);
            }

            // 2. Neue automatische ToDos erstellen basierend auf PhaseConfig
            $this->createPhaseAutomatedTasks($contactId, $status, $newPhase, $phaseDate);

            return true;
        } catch (Exception $e) {
            // Bei Fehler loggen
            if ($this->settingsModel->get('automation_log_enabled') === '1') {
                error_log("ContactStatusHandler Phase Error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Erstellt automatische ToDos basierend auf PhaseConfig
     */
    protected function createPhaseAutomatedTasks($contactId, $status, $phase, $phaseDate = null)
    {
        $todoData = PhaseConfig::generateAutoTodo($status, $phase, $phaseDate);

        if (!$todoData) {
            return; // Keine ToDo-Generierung für diese Phase
        }

        // ToDo erstellen
        $taskData = [
            'contact_id' => $contactId,
            'title' => $todoData['title'],
            'description' => $todoData['description'],
            'priority' => $todoData['priority'],
            'due_date' => $todoData['due_date'],
            'auto_generated' => 'phase_change',
            'triggered_by_phase' => $phase
        ];

        $taskId = $this->taskModel->create($taskData);

        // Optional: Activity-Log für automatisches ToDo
        if ($taskId && $this->settingsModel->get('automation_log_enabled') === '1') {
            $this->activityModel->log(
                $contactId,
                'auto_task_created',
                "Automatisches ToDo erstellt für Phase \"$phase\": " . $todoData['title']
            );
        }
    }

    /**
     * Rollback: Löscht unerledigte automatische ToDos von der vorherigen Phase
     */
    protected function rollbackPhaseTasks($contactId, $fromPhase)
    {
        $deleted = $this->taskModel->deleteUncompletedPhaseTasks($contactId, $fromPhase);

        // Optional: Log schreiben
        if ($deleted && $this->settingsModel->get('automation_log_enabled') === '1') {
            $this->activityModel->log(
                $contactId,
                'auto_task_rollback',
                "Automatische ToDos von Phase '$fromPhase' entfernt (Rollback)"
            );
        }
    }

    /**
     * Loggt Phasen-Änderung im Activity-Log
     */
    protected function logPhaseChange($contactId, $status, $oldPhase, $newPhase, $phaseDate = null)
    {
        // Datum formatieren wenn vorhanden
        $formattedDate = '';
        if ($phaseDate) {
            $formattedDate = PhaseConfig::formatPhaseDate($status, $newPhase, $phaseDate);
        }

        // Details zusammenbauen
        $oldValue = $oldPhase ?: '(keine)';
        $newValue = $newPhase;
        if ($formattedDate) {
            $newValue .= ' (' . $formattedDate . ')';
        }

        $description = "Phase: $oldValue → $newValue";

        $this->activityModel->log(
            $contactId,
            'phase_change',
            $description,
            $oldValue,
            $newValue
        );
    }

    /**
     * Aktualisiert die Phasen-Felder beim Kontakt
     */
    protected function updateContactPhase($contactId, $phase, $phaseDate = null, $phaseNotes = null)
    {
        $sql = "UPDATE contacts SET phase = ?, phase_date = ?, phase_notes = ?, updated_at = ? WHERE id = ?";
        $params = [
            $phase,
            $phaseDate,
            $phaseNotes,
            date('Y-m-d H:i:s'),
            $contactId
        ];

        $this->db->execute($sql, $params);
    }

    /**
     * Kombinierte Methode: Status UND Phase gleichzeitig ändern
     * Nützlich wenn beides auf einmal geändert wird
     */
    public function handleFullChange($contactId, $oldStatus, $newStatus, $oldPhase, $newPhase, $phaseDate = null)
    {
        // 1. Erst Status-Wechsel behandeln
        if ($oldStatus !== $newStatus) {
            $this->handleStatusChange($contactId, $oldStatus, $newStatus);
        }

        // 2. Dann Phasen-Wechsel behandeln
        if ($newPhase && ($oldPhase !== $newPhase)) {
            $this->handlePhaseChange($contactId, $newStatus, $oldPhase, $newPhase, $phaseDate);
        }
    }
}
