<?php

class Activity
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByContactId($contactId, $limit = 20)
    {
        $sql = "SELECT * FROM activities WHERE contact_id = ? ORDER BY created_at DESC LIMIT $limit";
        return $this->db->fetchAll($sql, [$contactId]);
    }

    /**
     * Get enriched activities with additional details like product names
     */
    public function getEnrichedActivities($contactId, $limit = 50)
    {
        $sql = "
            SELECT 
                a.*,
                p.name as product_name
            FROM activities a
            LEFT JOIN products p ON (a.type = 'product_assigned' OR a.type = 'product_removed') AND CAST(a.old_value AS INTEGER) = p.id
            WHERE a.contact_id = ?
            ORDER BY a.created_at DESC
            LIMIT $limit
        ";
        return $this->db->fetchAll($sql, [$contactId]);
    }

    public function log($contactId, $type, $description, $oldValue = null, $newValue = null)
    {
        $sql = "INSERT INTO activities (contact_id, type, description, old_value, new_value) VALUES (?, ?, ?, ?, ?)";
        return $this->db->execute($sql, [$contactId, $type, $description, $oldValue, $newValue]);
    }

    public function countByDateRange($type, $startDate, $endDate, $values = [])
    {
        $sql = "SELECT COUNT(DISTINCT contact_id) FROM activities WHERE type = ? AND created_at BETWEEN ? AND ? ";
        $params = [$type, $startDate . ' 00:00:00', $endDate . ' 23:59:59'];

        if (!empty($values)) {
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $sql .= " AND new_value IN ($placeholders)";
            $params = array_merge($params, $values);
        }

        return $this->db->fetchColumn($sql, $params);
    }

    /**
     * Get all activities with contact name for CSV export
     */
    public function getAllWithContactName()
    {
        $sql = "
            SELECT 
                a.id,
                a.contact_id,
                c.name as contact_name,
                a.type,
                a.description,
                a.old_value,
                a.new_value,
                a.created_at
            FROM activities a
            LEFT JOIN contacts c ON a.contact_id = c.id
            ORDER BY a.contact_id, a.created_at DESC
        ";
        return $this->db->fetchAll($sql);
    }
}
