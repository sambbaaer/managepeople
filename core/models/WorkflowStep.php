<?php

require_once __DIR__ . '/../Database.php';

/**
 * WorkflowStep Model
 * 
 * Verwaltet einzelne Schritte innerhalb eines Workflow-Templates
 */
class WorkflowStep
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Einzelnen Schritt abrufen
     */
    public function find($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM workflow_steps WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Schritt erstellen
     */
    public function create($data)
    {
        // Nächste Sortierreihenfolge ermitteln falls nicht angegeben
        if (!isset($data['sort_order'])) {
            $stmt = $this->db->prepare('SELECT MAX(sort_order) as max_order FROM workflow_steps WHERE template_id = ?');
            $stmt->execute([$data['template_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $data['sort_order'] = ($result['max_order'] ?? 0) + 1;
        }

        $stmt = $this->db->prepare('
            INSERT INTO workflow_steps (template_id, title, description, days_offset, priority, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['template_id'],
            $data['title'],
            $data['description'] ?? '',
            $data['days_offset'],
            $data['priority'] ?? 'normal',
            $data['sort_order']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Schritt aktualisieren
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        $allowedFields = ['title', 'description', 'days_offset', 'priority', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $stmt = $this->db->prepare('UPDATE workflow_steps SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    /**
     * Schritt löschen
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare('DELETE FROM workflow_steps WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Alle Schritte eines Templates löschen
     */
    public function deleteByTemplateId($templateId)
    {
        $stmt = $this->db->prepare('DELETE FROM workflow_steps WHERE template_id = ?');
        return $stmt->execute([$templateId]);
    }

    /**
     * Schritte neu sortieren
     */
    public function reorder($templateId, $stepIds)
    {
        foreach ($stepIds as $order => $stepId) {
            $stmt = $this->db->prepare('UPDATE workflow_steps SET sort_order = ? WHERE id = ? AND template_id = ?');
            $stmt->execute([$order, $stepId, $templateId]);
        }
        return true;
    }

    /**
     * Offset-Beschreibung für UI generieren
     */
    public static function formatOffset($days)
    {
        if ($days == 0) {
            return 'Am Stichtag';
        } elseif ($days == 1) {
            return '+1 Tag danach';
        } elseif ($days == -1) {
            return '1 Tag vorher';
        } elseif ($days > 0) {
            return "+{$days} Tage danach";
        } else {
            return abs($days) . ' Tage vorher';
        }
    }
}
