<?php

require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../models/Task.php';

class SearchController
{

    public function results()
    {
        $query = $_GET['q'] ?? '';
        $query = trim($query);

        $contacts = [];
        $tasks = [];

        if (!empty($query)) {
            // Search Contacts
            $contactModel = new Contact();
            // Reuse getAll but with a limit, or write a specific search method
            // Using existing getAll which supports 'search' filter
            $contacts = $contactModel->getAll(['search' => $query], 20);

            // Search Tasks
            // Simple SQL for tasks search since model might not have it fully exposed
            $db = Database::getInstance();
            $sql = "SELECT t.*, c.name as contact_name FROM tasks t 
                    LEFT JOIN contacts c ON t.contact_id = c.id 
                    WHERE t.title LIKE ? OR t.description LIKE ?";
            $term = '%' . $query . '%';
            $tasks = $db->fetchAll($sql, [$term, $term]);
        }

        require __DIR__ . '/../../templates/search/results.php';
    }
}
