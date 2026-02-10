<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Activity.php';

class TaskController
{
    private $model;

    public function __construct()
    {
        $this->model = new Task();
    }

    public function index()
    {
        $page = $_GET['p'] ?? 1;
        $limit = 50; // Show more tasks per page
        $offset = ($page - 1) * $limit;

        $filterCompleted = $_GET['completed'] ?? 0;

        $filters = [
            'completed' => $filterCompleted
        ];

        $tasks = $this->model->getAll($filters, $limit, $offset);

        // Simple grouping for display (Overdue, Today, Upcoming) could be done in view or here
        // For now pass raw list

        require __DIR__ . '/../../templates/tasks/list.php';
    }

    public function create()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;

            // Handle contact_name autocomplete
            if (!empty($data['contact_name']) && empty($data['contact_id'])) {
                require_once __DIR__ . '/../models/Contact.php';
                $contactModel = new Contact();
                $contact = $contactModel->findByName($data['contact_name']);
                if ($contact) {
                    $data['contact_id'] = $contact['id'];
                }
            }

            $this->model->create($data);

            // Redirect back to where we came from if possible
            if (!empty($_POST['redirect_to'])) {
                redirect($_POST['redirect_to']);
            } else {
                redirect('index.php?page=tasks');
            }
        }

        // GET Request: Show form
        require_once __DIR__ . '/../models/Contact.php';
        $contactModel = new Contact();
        $allContacts = $contactModel->getAll([], 1000, 0); // Get more contacts for autocomplete

        // Pre-fill data from URL params
        $prefill = [
            'due_date' => $_GET['due_date'] ?? '',
            'contact_id' => $_GET['contact_id'] ?? '',
            'contact_name' => $_GET['contact_name'] ?? '',
            'redirect_to' => $_GET['redirect_to'] ?? 'index.php?page=tasks'
        ];

        // If contact_id given, fetch name
        if ($prefill['contact_id'] && !$prefill['contact_name']) {
            $contact = $contactModel->find($prefill['contact_id']);
            $prefill['contact_name'] = $contact['name'] ?? '';
        }

        require __DIR__ . '/../../templates/tasks/form.php';
    }

    public function toggle()
    {
        Auth::denyMentorWriteAccess();
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $taskBefore = $this->model->find($id);
            if ($taskBefore) {
                $this->model->toggleComplete($id);
                $taskAfter = $this->model->find($id);

                // Log in Activity History if task is now completed and assigned to a contact
                if ($taskAfter && $taskAfter['completed'] && $taskAfter['contact_id']) {
                    $activityModel = new Activity();
                    $description = "Aufgabe erledigt: " . $taskAfter['title'];
                    $activityModel->log(
                        $taskAfter['contact_id'],
                        'task_completed',
                        $description,
                        null,
                        $taskAfter['title']
                    );
                }
            }
        }

        // Handle AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php?page=tasks');
    }

    public function delete()
    {
        Auth::denyMentorWriteAccess();
        // Support POST for AJAX
        $id = $_POST['id'] ?? $_GET['id'] ?? 0;

        if ($id) {
            $success = $this->model->delete($id);

            // Handle AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Aufgabe gelöscht' : 'Fehler beim Löschen'
                ]);
                exit;
            }

            // Handle POST without AJAX
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Aufgabe gelöscht' : 'Fehler beim Löschen'
                ]);
                exit;
            }
        }

        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php?page=tasks');
    }

    public function reschedule()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        $taskId = $_POST['task_id'] ?? null;
        $newDate = $_POST['new_date'] ?? null;

        if (!$taskId || !$newDate) {
            echo json_encode([
                'success' => false,
                'error' => 'Fehlende Parameter'
            ]);
            exit;
        }

        // Validate date format
        $timestamp = strtotime($newDate);
        if ($timestamp === false) {
            echo json_encode([
                'success' => false,
                'error' => 'Ungültiges Datumsformat'
            ]);
            exit;
        }

        // Format to database format (YYYY-MM-DD HH:MM:SS)
        $formattedDate = date('Y-m-d 12:00:00', $timestamp);

        $result = $this->model->update($taskId, ['due_date' => $formattedDate]);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Termin verschoben' : 'Fehler beim Verschieben',
            'new_date' => $formattedDate
        ]);
        exit;
    }
}
