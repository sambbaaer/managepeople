<?php

class Task
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT t.*, c.name as contact_name FROM tasks t 
                LEFT JOIN contacts c ON t.contact_id = c.id 
                WHERE 1=1";
        $params = [];

        if (isset($filters['completed'])) {
            $sql .= " AND t.completed = ?";
            $params[] = $filters['completed']; // 0 or 1
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Sort by due date (closest first), then priority
        $sql .= " ORDER BY t.completed ASC, t.due_date ASC, CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END LIMIT $limit OFFSET $offset";

        return $this->db->fetchAll($sql, $params);
    }

    public function getByContactId($contactId)
    {
        $sql = "SELECT * FROM tasks WHERE contact_id = ? ORDER BY completed ASC, due_date ASC";
        return $this->db->fetchAll($sql, [$contactId]);
    }

    // For Dashboard: All open tasks, sorted by due_date
    public function getTodayTasks($userId = null)
    {
        // userId ignored for now as it's single user app roughly
        $sql = "SELECT t.*, c.name as contact_name, c.id as contact_id 
                FROM tasks t 
                LEFT JOIN contacts c ON t.contact_id = c.id 
                WHERE t.completed = 0
                ORDER BY 
                    CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END,
                    t.due_date ASC,
                    t.priority DESC";
        return $this->db->fetchAll($sql);
    }

    // For Dashboard: Overdue/Past tasks that are not completed
    public function getOverdueTasks($userId = null)
    {
        $sql = "SELECT t.*, c.name as contact_name, c.id as contact_id 
                FROM tasks t 
                LEFT JOIN contacts c ON t.contact_id = c.id 
                WHERE t.completed = 0 AND t.due_date < date('now')
                ORDER BY t.due_date ASC, t.priority DESC";
        return $this->db->fetchAll($sql);
    }

    public function create($data)
    {
        $fields = ['contact_id', 'title', 'description', 'due_date', 'priority', 'auto_generated', 'triggered_by_status', 'triggered_by_phase', 'automation_rule_id'];
        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $columns[] = $field;
                $placeholders[] = '?';
                $values[] = $data[$field];
            }
        }

        $sql = "INSERT INTO tasks (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->execute($sql, $values);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($fields))
            return false;

        $values[] = $id;
        $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?";

        return $this->db->execute($sql, $values);
    }

    public function find($id)
    {
        $sql = "SELECT * FROM tasks WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function toggleComplete($id)
    {
        $task = $this->db->fetch("SELECT completed FROM tasks WHERE id = ?", [$id]);
        if (!$task)
            return false;

        $newState = $task['completed'] ? 0 : 1;
        $completedAt = $newState ? date('Y-m-d H:i:s') : null;

        $sql = "UPDATE tasks SET completed = ?, completed_at = ? WHERE id = ?";
        return $this->db->execute($sql, [$newState, $completedAt, $id]);
    }

    public function delete($id)
    {
        return $this->db->execute("DELETE FROM tasks WHERE id = ?", [$id]);
    }

    /**
     * Findet automatische ToDos für einen bestimmten Status
     */
    public function getAutomatedTasksByStatus($contactId, $status)
    {
        $sql = "SELECT * FROM tasks 
                WHERE contact_id = ? 
                AND triggered_by_status = ? 
                AND auto_generated IS NOT NULL
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, [$contactId, $status]);
    }

    /**
     * Löscht unerledigte automatische ToDos für einen bestimmten Status (Rollback)
     */
    public function deleteUncompletedAutomatedTasks($contactId, $triggeredByStatus)
    {
        $sql = "DELETE FROM tasks 
                WHERE contact_id = ? 
                AND triggered_by_status = ? 
                AND auto_generated IS NOT NULL
                AND completed = 0";

        return $this->db->execute($sql, [$contactId, $triggeredByStatus]);
    }

    /**
     * Findet automatische ToDos für eine bestimmte Phase
     */
    public function getAutomatedTasksByPhase($contactId, $phase)
    {
        $sql = "SELECT * FROM tasks 
                WHERE contact_id = ? 
                AND triggered_by_phase = ? 
                AND auto_generated = 'phase_change'
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, [$contactId, $phase]);
    }

    /**
     * Löscht unerledigte automatische ToDos für eine bestimmte Phase (Rollback)
     */
    public function deleteUncompletedPhaseTasks($contactId, $triggeredByPhase)
    {
        $sql = "DELETE FROM tasks 
                WHERE contact_id = ? 
                AND triggered_by_phase = ? 
                AND auto_generated = 'phase_change'
                AND completed = 0";

        return $this->db->execute($sql, [$contactId, $triggeredByPhase]);
    }

    /**
     * Holen aller Aufgaben für Kalender-Export
     */
    public function getTasksForCalendar($userId = null)
    {
        // Alle offenen Aufgaben + erledigte der letzten 30 Tage
        $sql = "SELECT t.*, c.name as contact_name 
                FROM tasks t 
                LEFT JOIN contacts c ON t.contact_id = c.id 
                WHERE t.completed = 0 
                   OR (t.completed = 1 AND t.completed_at >= date('now', '-30 days'))
                ORDER BY t.due_date ASC";

        return $this->db->fetchAll($sql);
    }
}
