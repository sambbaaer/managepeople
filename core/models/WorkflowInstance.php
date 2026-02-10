<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/WorkflowTemplate.php';
require_once __DIR__ . '/WorkflowStep.php';
require_once __DIR__ . '/Task.php';
require_once __DIR__ . '/Activity.php';

/**
 * WorkflowInstance Model
 * 
 * Verwaltet gestartete Workflow-Instanzen
 * Erstellt ToDos basierend auf Template-Schritten und Zieldatum
 */
class WorkflowInstance
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Alle Instanzen abrufen (optional gefiltert)
     */
    public function getAll($status = null)
    {
        $sql = 'SELECT wi.*, c.name as contact_name FROM workflow_instances wi
                LEFT JOIN contacts c ON wi.contact_id = c.id';

        $params = [];
        if ($status) {
            $sql .= ' WHERE wi.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY wi.target_date ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fortschritt berechnen
        foreach ($instances as &$instance) {
            $instance['progress'] = $this->calculateProgress($instance['id']);
        }

        return $instances;
    }

    /**
     * Aktive Instanzen abrufen
     */
    public function getActive()
    {
        return $this->getAll('active');
    }

    /**
     * Einzelne Instanz abrufen
     */
    public function find($id)
    {
        $stmt = $this->db->prepare('
            SELECT wi.*, c.name as contact_name 
            FROM workflow_instances wi
            LEFT JOIN contacts c ON wi.contact_id = c.id
            WHERE wi.id = ?
        ');
        $stmt->execute([$id]);
        $instance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($instance) {
            $instance['progress'] = $this->calculateProgress($id);
            $instance['tasks'] = $this->getTasks($id);
        }

        return $instance;
    }

    /**
     * Workflow starten
     * 
     * @param int $templateId Template-ID
     * @param string $targetDate Zieldatum (Y-m-d)
     * @param int|null $contactId Optional: Kontakt-ID
     * @param string|null $notes Optional: Notizen
     * @return array Ergebnis mit instance_id und erstellten Tasks
     */
    public function start($templateId, $targetDate, $contactId = null, $notes = null)
    {
        $templateModel = new WorkflowTemplate();
        $template = $templateModel->find($templateId);

        if (!$template) {
            return ['success' => false, 'error' => 'Template nicht gefunden'];
        }

        $steps = $templateModel->getSteps($templateId);

        if (empty($steps)) {
            return ['success' => false, 'error' => 'Template hat keine Schritte'];
        }

        // Instanz erstellen
        $stmt = $this->db->prepare('
            INSERT INTO workflow_instances (template_id, template_name, contact_id, target_date, notes)
            VALUES (?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $templateId,
            $template['name'],
            $contactId,
            $targetDate,
            $notes
        ]);

        $instanceId = $this->db->lastInsertId();

        // Tasks erstellen
        $taskModel = new Task();
        $createdTasks = [];

        foreach ($steps as $step) {
            // Datum berechnen
            $taskDate = date('Y-m-d', strtotime("$targetDate {$step['days_offset']} days"));

            // Task erstellen
            $taskId = $taskModel->create([
                'contact_id' => $contactId,
                'title' => $step['title'],
                'description' => $step['description'],
                'priority' => $step['priority'],
                'due_date' => $taskDate,
                'auto_generated' => 'workflow'
            ]);

            // Verknüpfung speichern
            $stmt = $this->db->prepare('
                INSERT INTO workflow_instance_tasks (instance_id, task_id, step_id, step_title, target_date)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$instanceId, $taskId, $step['id'], $step['title'], $taskDate]);

            $createdTasks[] = [
                'task_id' => $taskId,
                'title' => $step['title'],
                'date' => $taskDate
            ];
        }

        // Activity loggen falls Kontakt verknüpft
        if ($contactId) {
            $activityModel = new Activity();
            $activityModel->create([
                'contact_id' => $contactId,
                'type' => 'workflow_started',
                'description' => "Ablauf «{$template['name']}» gestartet für " . date('d.m.Y', strtotime($targetDate)),
                'new_value' => $template['name']
            ]);
        }

        return [
            'success' => true,
            'instance_id' => $instanceId,
            'tasks_created' => count($createdTasks),
            'tasks' => $createdTasks
        ];
    }

    /**
     * Alle Tasks einer Instanz abrufen
     */
    public function getTasks($instanceId)
    {
        $stmt = $this->db->prepare('
            SELECT wit.*, t.completed, t.completed_at, t.priority, t.due_date
            FROM workflow_instance_tasks wit
            JOIN tasks t ON wit.task_id = t.id
            WHERE wit.instance_id = ?
            ORDER BY wit.target_date ASC
        ');
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fortschritt berechnen
     */
    public function calculateProgress($instanceId)
    {
        $stmt = $this->db->prepare('
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN t.completed = 1 THEN 1 ELSE 0 END) as completed
            FROM workflow_instance_tasks wit
            JOIN tasks t ON wit.task_id = t.id
            WHERE wit.instance_id = ?
        ');
        $stmt->execute([$instanceId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total'] == 0) {
            return ['percent' => 0, 'completed' => 0, 'total' => 0];
        }

        $percent = round(($result['completed'] / $result['total']) * 100);

        // Auto-Complete wenn alle Tasks erledigt
        if ($result['completed'] == $result['total']) {
            $this->complete($instanceId);
        }

        return [
            'percent' => $percent,
            'completed' => (int) $result['completed'],
            'total' => (int) $result['total']
        ];
    }

    /**
     * Instanz als abgeschlossen markieren
     */
    public function complete($instanceId)
    {
        $stmt = $this->db->prepare('
            UPDATE workflow_instances 
            SET status = ?, completed_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND status = ?
        ');
        return $stmt->execute(['completed', $instanceId, 'active']);
    }

    /**
     * Instanz abbrechen (löscht auch alle zugehörigen Tasks)
     */
    public function cancel($instanceId, $deleteTasks = false)
    {
        if ($deleteTasks) {
            // Alle verknüpften Tasks löschen
            $stmt = $this->db->prepare('
                DELETE FROM tasks WHERE id IN (
                    SELECT task_id FROM workflow_instance_tasks WHERE instance_id = ?
                )
            ');
            $stmt->execute([$instanceId]);
        }

        $stmt = $this->db->prepare('UPDATE workflow_instances SET status = ? WHERE id = ?');
        return $stmt->execute(['cancelled', $instanceId]);
    }

    /**
     * Instanzen für einen Kontakt abrufen
     */
    public function getByContactId($contactId)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM workflow_instances 
            WHERE contact_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$contactId]);
        $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($instances as &$instance) {
            $instance['progress'] = $this->calculateProgress($instance['id']);
        }

        return $instances;
    }

    /**
     * Vorschau der Daten berechnen (ohne zu speichern)
     */
    public function preview($templateId, $targetDate)
    {
        $templateModel = new WorkflowTemplate();
        $template = $templateModel->find($templateId);

        if (!$template) {
            return ['success' => false, 'error' => 'Template nicht gefunden'];
        }

        $steps = $templateModel->getSteps($templateId);
        $preview = [];

        foreach ($steps as $step) {
            $taskDate = date('Y-m-d', strtotime("$targetDate {$step['days_offset']} days"));
            $preview[] = [
                'title' => $step['title'],
                'date' => $taskDate,
                'formatted_date' => date('d.m.Y', strtotime($taskDate)),
                'day_name' => $this->germanDayName(date('N', strtotime($taskDate))),
                'days_offset' => $step['days_offset'],
                'priority' => $step['priority']
            ];
        }

        return ['success' => true, 'steps' => $preview];
    }

    /**
     * Deutscher Wochentagname
     */
    protected function germanDayName($dayNumber)
    {
        $days = ['', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
        return $days[$dayNumber] ?? '';
    }
}
