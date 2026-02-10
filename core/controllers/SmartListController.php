<?php

require_once __DIR__ . '/../models/SmartList.php';

class SmartListController
{
    protected $model;

    public function __construct()
    {
        $this->model = new SmartList();
    }

    public function index()
    {
        $smartLists = $this->model->getAll();
        // Load view (usually part of settings or settings sub-page)
        require __DIR__ . '/../../templates/settings/smart_lists.php';
    }

    public function create()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? 'Neue Liste';

            // Build filter criteria from POST
            // Example: [ 'status' => 'Offen', 'birthday_month' => 'current' ]
            $criteria = [];

            if (!empty($_POST['status'])) {
                $criteria['status'] = $_POST['status'];
            }
            if (!empty($_POST['birthday_month'])) {
                $criteria['birthday_month'] = $_POST['birthday_month'];
            }
            if (!empty($_POST['search_term'])) {
                $criteria['search_term'] = $_POST['search_term'];
            }
            if (!empty($_POST['neglected'])) {
                $criteria['neglected'] = 1;
            }

            $this->model->create($name, $criteria);
            redirect('index.php?page=settings_smart_lists');
        }
    }

    public function delete()
    {
        Auth::denyMentorWriteAccess();
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->model->delete($id);
        }
        redirect('index.php?page=settings_smart_lists');
    }
}
