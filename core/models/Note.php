<?php

class Note
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByContactId($contactId)
    {
        $sql = "SELECT * FROM notes WHERE contact_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$contactId]);
    }

    public function create($contactId, $content, $createdAt = null)
    {
        if ($createdAt) {
            $sql = "INSERT INTO notes (contact_id, content, created_at) VALUES (?, ?, ?)";
            return $this->db->execute($sql, [$contactId, $content, $createdAt]);
        }
        $sql = "INSERT INTO notes (contact_id, content) VALUES (?, ?)";
        return $this->db->execute($sql, [$contactId, $content]);
    }

    public function delete($id)
    {
        return $this->db->execute("DELETE FROM notes WHERE id = ?", [$id]);
    }

    /**
     * Get all notes with contact name for CSV export
     */
    public function getAllWithContactName()
    {
        $sql = "
            SELECT 
                n.id,
                n.contact_id,
                c.name as contact_name,
                n.content,
                n.created_at
            FROM notes n
            LEFT JOIN contacts c ON n.contact_id = c.id
            ORDER BY n.contact_id, n.created_at DESC
        ";
        return $this->db->fetchAll($sql);
    }
}
