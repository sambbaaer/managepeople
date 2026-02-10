<?php

class SmartList
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create($name, $filterCriteria)
    {
        $sql = "INSERT INTO smart_lists (name, filter_criteria) VALUES (?, ?)";
        $criteriaJson = json_encode($filterCriteria);
        return $this->db->execute($sql, [$name, $criteriaJson]);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM smart_lists ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    public function get($id)
    {
        $sql = "SELECT * FROM smart_lists WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM smart_lists WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
}
