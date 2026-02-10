<?php

require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Task.php';

class ContactController
{
    // ... dependencies ...
    private $model;
    private $noteModel;
    private $activityModel;
    private $taskModel;

    public function __construct()
    {
        $this->model = new Contact();
        $this->noteModel = new Note();
        $this->activityModel = new Activity();
        $this->taskModel = new Task();
    }

    public function index()
    {
        $page = $_GET['p'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $search = $_GET['q'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $sort = $_GET['sort'] ?? 'updated_at_desc';
        $smartListId = $_GET['smart_list_id'] ?? null;

        $filters = [
            'search' => $search,
            'status' => $filterStatus,
            'sort' => $sort // Passing sort param to model
        ];

        // Helper: Apply Smart List Criteria
        if ($smartListId) {
            require_once __DIR__ . '/../models/SmartList.php';
            $slModel = new SmartList();
            $sl = $slModel->get($smartListId);
            if ($sl) {
                $criteria = json_decode($sl['filter_criteria'], true);
                if (!empty($criteria['status']))
                    $filters['status'] = $criteria['status'];
                if (!empty($criteria['birthday_month']))
                    $filters['birthday_month'] = $criteria['birthday_month'];

                // New Filters
                if (!empty($criteria['neglected']))
                    $filters['neglected'] = true;
                if (!empty($criteria['search_term'])) {
                    // Force search term from smart list
                    $filters['search'] = $criteria['search_term'];
                }
            }
        }

        $contacts = $this->model->getAll($filters, $limit, $offset);
        $total = $this->model->count($filters);
        $totalPages = ceil($total / $limit);

        require __DIR__ . '/../../templates/contacts/list.php';
    }

    // ... rest of methods ...
    public function show($id)
    {
        $contact = $this->model->find($id);
        if (!$contact) {
            redirect('index.php?page=contacts');
        }
        $notes = $this->noteModel->getByContactId($id);
        $activities = $this->activityModel->getEnrichedActivities($id);
        $tasks = $this->taskModel->getByContactId($id);

        // Products
        require_once __DIR__ . '/../models/Product.php';
        $productModel = new Product();
        $assignedProducts = $productModel->getByContactId($id);
        // We might want all products for the add-dropdown (could be heavy if list is huge, but ~100 is fine)
        // Optimization: Fetch only for autocomplete if needed, but here we can fetch all for <datalist>
        $allProducts = $productModel->getAll();

        $statusConfig = Contact::getStatusConfig(); // Get Config for View

        require __DIR__ . '/../../templates/contacts/detail.php';
    }

    public function addNote($contactId)
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = $_POST['content'] ?? '';
            if (!empty($content)) {
                $this->noteModel->create($contactId, $content);
                // Store note content in new_value for history display
                $this->activityModel->log($contactId, 'note_added', 'Notiz hinzugefügt', null, $content);
            }
            redirect('index.php?page=contact_detail&id=' . $contactId);
        }
    }

    public function edit($id)
    {
        $contact = $this->model->find($id);
        if (!$contact) {
            redirect('index.php?page=contacts');
        }
        $contactNames = $this->model->getAllNames();
        require __DIR__ . '/../../templates/contacts/form.php';
    }

    public function create()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            if (!empty($data['phone'])) {
                $data['phone'] = $this->normalizePhone($data['phone']);
            }
            $id = $this->model->create($data);
            $this->activityModel->log($id, 'contact_created', 'Kontakt erstellt');
            if (!empty($data['notes'])) {
                $this->noteModel->create($id, $data['notes']);
            }
            redirect('index.php?page=contact_detail&id=' . $id);
        }
        $contactNames = $this->model->getAllNames();
        require __DIR__ . '/../../templates/contacts/form.php';
    }

    private function normalizePhone($phone)
    {
        $phone = preg_replace('/[\s\-\(\)\.\/]/', '', $phone);
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }
        return $phone;
    }

    public function update($id)
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            if (!empty($data['phone'])) {
                $data['phone'] = $this->normalizePhone($data['phone']);
            }
            $currentContact = $this->model->find($id);

            // Status-Änderung prüfen
            $statusChanged = isset($data['status']) && $data['status'] !== $currentContact['status'];

            // Phase-Änderung prüfen
            $phaseChanged = isset($data['phase']) && $data['phase'] !== ($currentContact['phase'] ?? '');

            // ContactStatusHandler einbinden für automatische ToDo-Generierung
            require_once __DIR__ . '/../ContactStatusHandler.php';
            $statusHandler = new ContactStatusHandler();

            // Status-Änderung verarbeiten
            if ($statusChanged) {
                $statusHandler->handleStatusChange(
                    $id,
                    $currentContact['status'],
                    $data['status'],
                    $data['sub_status'] ?? null
                );
            }

            // Phase-Änderung verarbeiten
            if ($phaseChanged) {
                $statusHandler->handlePhaseChange(
                    $id,
                    $data['status'] ?? $currentContact['status'],
                    $currentContact['phase'] ?? null,
                    $data['phase'],
                    $data['phase_date'] ?? null,
                    $data['phase_notes'] ?? null
                );
            }

            // Kontakt aktualisieren (ohne phase-Felder, da diese vom Handler gesetzt werden)
            // Entferne auch temporäre Formularfelder die keine DB-Spalten sind
            $updateData = $data;
            unset(
                $updateData['phase'],
                $updateData['phase_date'],
                $updateData['phase_notes'],
                $updateData['phase_month'],      // Temporäres Formularfeld
                $updateData['phase_year'],       // Temporäres Formularfeld  
                $updateData['phase_full_date']   // Temporäres Formularfeld
            );

            if (!empty($updateData)) {
                $this->model->update($id, $updateData);
            }

            // AJAX Response
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true]);
                exit;
            }
            redirect('index.php?page=contact_detail&id=' . $id);
        }
    }
    public function assignProduct()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../models/Product.php';
            $contactId = $_POST['contact_id'] ?? 0;
            $productId = $_POST['product_id'] ?? 0;
            $productName = $_POST['product_name'] ?? '';

            if ($contactId) {
                $productModel = new Product();

                if (!$productId && $productName) {
                    $all = $productModel->getAll();
                    foreach ($all as $p) {
                        if (strtolower($p['name']) === strtolower(trim($productName))) {
                            $productId = $p['id'];
                            break;
                        }
                    }
                }

                if ($productId) {
                    // Check logic moved to model
                    $productModel->assignToContact($contactId, $productId);
                    // Store product ID in old_value for enriched query
                    $this->activityModel->log($contactId, 'product_assigned', 'Produkt zugewiesen', $productId);
                }
            }
            redirect('index.php?page=contact_detail&id=' . $contactId);
        }
    }

    public function removeProduct()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../models/Product.php';
            $contactId = $_POST['contact_id'] ?? 0;
            $productId = $_POST['product_id'] ?? 0;

            if ($contactId && $productId) {
                $productModel = new Product();
                $productModel->removeFromContact($contactId, $productId);
                // Log product removal
                $this->activityModel->log($contactId, 'product_removed', 'Produkt entfernt', $productId);
            }
            redirect('index.php?page=contact_detail&id=' . $contactId);
        }
    }

    public function getWorkflowUI($id)
    {
        $contact = $this->model->find($id);
        if (!$contact) {
            echo "Fehler: Kontakt nicht gefunden.";
            exit;
        }

        // Benötigte Variablen für das Partial
        require_once __DIR__ . '/../PhaseConfig.php';

        include __DIR__ . '/../../templates/contacts/_workflow_ui.php';
        exit;
    }

    public function quickCreate()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $status = $_POST['status'] ?? 'Offen';
            $lastContactDate = $_POST['last_contact'] ?? date('Y-m-d');
            $noteContent = $_POST['note'] ?? '';
            $tags = $_POST['tags'] ?? '';
            $beziehung = !empty($_POST['beziehung']) ? $_POST['beziehung'] : 'Bekannte';

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Name ist erforderlich.']);
                exit;
            }

            // 1. Create Contact
            $contactData = [
                'name' => $name,
                'status' => $status,
                'beziehung' => $beziehung,
                'tags' => $tags,
                'last_contacted_at' => $lastContactDate . ' ' . date('H:i:s')
            ];

            $contactId = $this->model->create($contactData);

            if ($contactId) {
                // 2. Create Note with specific date
                if (!empty($noteContent)) {
                    $this->noteModel->create($contactId, $noteContent, $lastContactDate . ' ' . date('H:i:s'));
                }

                // 3. Log Activity
                $this->activityModel->log($contactId, 'contact_created', 'Schnellerfassung: Kontakt erstellt');
                if (!empty($noteContent)) {
                    $this->activityModel->log($contactId, 'note_added', 'Schnellerfassung: Notiz hinzugefügt', null, $noteContent);
                }

                echo json_encode(['success' => true, 'id' => $contactId]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage.']);
        exit;
    }
}
