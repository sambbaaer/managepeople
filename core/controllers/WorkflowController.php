<?php

// Load required models
require_once __DIR__ . '/../models/WorkflowTemplate.php';
require_once __DIR__ . '/../models/WorkflowStep.php';
require_once __DIR__ . '/../models/WorkflowInstance.php';
require_once __DIR__ . '/../models/Contact.php';

/**
 * WorkflowController
 * 
 * Controller für Abläufe (Workflows)
 * Verwaltet Templates, Schritte und Instanzen
 */
class WorkflowController
{
    protected $templateModel;
    protected $stepModel;
    protected $instanceModel;

    public function __construct()
    {
        $this->templateModel = new WorkflowTemplate();
        $this->templateModel->ensureTablesExist();
        $this->stepModel = new WorkflowStep();
        $this->instanceModel = new WorkflowInstance();
    }

    /**
     * Haupt-Seite: Übersicht aller Workflows
     */
    public function index()
    {
        $templates = $this->templateModel->getAll();
        $activeInstances = $this->instanceModel->getActive();

        $data = [
            'templates' => $templates,
            'active_instances' => $activeInstances,
            'page_title' => 'Abläufe'
        ];

        include __DIR__ . '/../../templates/workflows.php';
    }

    /**
     * Template-Editor anzeigen
     */
    public function editTemplate()
    {
        $id = $_GET['id'] ?? null;
        $template = null;
        $steps = [];

        if ($id) {
            $template = $this->templateModel->find($id);
            if ($template) {
                $steps = $this->templateModel->getSteps($id);
            }
        }

        $data = [
            'template' => $template,
            'steps' => $steps,
            'is_new' => !$template,
            'page_title' => $template ? 'Ablauf bearbeiten' : 'Neuer Ablauf'
        ];

        include __DIR__ . '/../../templates/workflows/edit.php';
    }

    /**
     * Template speichern (erstellen/aktualisieren)
     */
    public function saveTemplate()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $id = $_POST['id'] ?? null;
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'start_date_label' => trim($_POST['start_date_label'] ?? 'Startdatum'),
            'icon' => $_POST['icon'] ?? 'calendar',
            'color' => $_POST['color'] ?? 'secondary'
        ];

        // Validierung
        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'error' => 'Name ist erforderlich']);
            return;
        }

        if ($id) {
            // Aktualisieren
            $result = $this->templateModel->update($id, $data);
            $templateId = $id;
        } else {
            // Erstellen
            $templateId = $this->templateModel->create($data);
            $result = $templateId !== false;
        }

        if ($result) {
            echo json_encode([
                'success' => true,
                'template_id' => $templateId,
                'message' => $id ? 'Ablauf aktualisiert' : 'Ablauf erstellt'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Speichern']);
        }
    }

    /**
     * Template löschen
     */
    public function deleteTemplate()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID fehlt']);
            return;
        }

        $result = $this->templateModel->delete($id);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Ablauf gelöscht']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ablauf konnte nicht gelöscht werden (System-Abläufe nicht löschbar)']);
        }
    }

    /**
     * Template duplizieren
     */
    public function duplicateTemplate()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? $_GET['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID fehlt']);
            return;
        }

        $newId = $this->templateModel->duplicate($id);

        if ($newId) {
            echo json_encode([
                'success' => true,
                'template_id' => $newId,
                'message' => 'Ablauf dupliziert'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Duplizieren']);
        }
    }

    /**
     * Schritt speichern
     */
    public function saveStep()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $stepId = $_POST['step_id'] ?? null;
        $data = [
            'template_id' => $_POST['template_id'] ?? null,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'days_offset' => (int) ($_POST['days_offset'] ?? 0),
            'priority' => $_POST['priority'] ?? 'normal'
        ];

        // Validierung
        if (empty($data['title'])) {
            echo json_encode(['success' => false, 'error' => 'Titel ist erforderlich']);
            return;
        }

        if (!$data['template_id'] && !$stepId) {
            echo json_encode(['success' => false, 'error' => 'Template-ID fehlt']);
            return;
        }

        if ($stepId) {
            // Aktualisieren
            $result = $this->stepModel->update($stepId, $data);
            $id = $stepId;
        } else {
            // Erstellen
            $id = $this->stepModel->create($data);
            $result = $id !== false;
        }

        if ($result) {
            echo json_encode([
                'success' => true,
                'step_id' => $id,
                'message' => $stepId ? 'Schritt aktualisiert' : 'Schritt hinzugefügt'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Speichern']);
        }
    }

    /**
     * Schritt löschen
     */
    public function deleteStep()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID fehlt']);
            return;
        }

        $result = $this->stepModel->delete($id);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Schritt gelöscht' : 'Fehler beim Löschen'
        ]);
    }

    /**
     * Schritte neu sortieren
     */
    public function reorderSteps()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        $templateId = $_POST['template_id'] ?? null;
        $stepIds = $_POST['step_ids'] ?? [];

        if (!$templateId || empty($stepIds)) {
            echo json_encode(['success' => false, 'error' => 'Parameter fehlen']);
            return;
        }

        $result = $this->stepModel->reorder($templateId, $stepIds);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Reihenfolge gespeichert' : 'Fehler'
        ]);
    }

    /**
     * Workflow starten
     */
    public function start()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        $templateId = $_POST['template_id'] ?? null;
        $targetDate = $_POST['target_date'] ?? null;
        $contactId = $_POST['contact_id'] ?? null;
        $notes = $_POST['notes'] ?? null;

        if (!$templateId || !$targetDate) {
            echo json_encode(['success' => false, 'error' => 'Template-ID und Datum sind erforderlich']);
            return;
        }

        // Datum validieren
        $dateObj = DateTime::createFromFormat('Y-m-d', $targetDate);
        if (!$dateObj) {
            echo json_encode(['success' => false, 'error' => 'Ungültiges Datum']);
            return;
        }

        $result = $this->instanceModel->start($templateId, $targetDate, $contactId ?: null, $notes);

        echo json_encode($result);
    }

    /**
     * Vorschau der Daten für Workflow
     */
    public function preview()
    {
        header('Content-Type: application/json');

        $templateId = $_GET['template_id'] ?? $_POST['template_id'] ?? null;
        $targetDate = $_GET['target_date'] ?? $_POST['target_date'] ?? null;

        if (!$templateId || !$targetDate) {
            echo json_encode(['success' => false, 'error' => 'Parameter fehlen']);
            return;
        }

        $result = $this->instanceModel->preview($templateId, $targetDate);
        echo json_encode($result);
    }

    /**
     * Instanz-Details anzeigen
     */
    public function viewInstance()
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            header('Location: index.php?page=workflows');
            exit;
        }

        $instance = $this->instanceModel->find($id);

        if (!$instance) {
            header('Location: index.php?page=workflows');
            exit;
        }

        $data = [
            'instance' => $instance,
            'page_title' => $instance['template_name']
        ];

        include __DIR__ . '/../../templates/workflows/instance.php';
    }

    /**
     * Instanz abbrechen
     */
    public function cancelInstance()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? null;
        $deleteTasks = isset($_POST['delete_tasks']) && $_POST['delete_tasks'] === '1';

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID fehlt']);
            return;
        }

        $result = $this->instanceModel->cancel($id, $deleteTasks);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Ablauf abgebrochen' : 'Fehler'
        ]);
    }

    /**
     * Template als JSON exportieren
     */
    public function export()
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            header('HTTP/1.1 400 Bad Request');
            echo 'ID fehlt';
            return;
        }

        $template = $this->templateModel->find($id);
        $json = $this->templateModel->exportToJson($id);

        if (!$json) {
            header('HTTP/1.1 404 Not Found');
            echo 'Template nicht gefunden';
            return;
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $template['name']) . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $json;
    }

    /**
     * Template aus JSON importieren
     */
    public function import()
    {
        Auth::denyMentorWriteAccess();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid method']);
            return;
        }

        // JSON aus POST-Body oder Datei-Upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $json = file_get_contents($_FILES['file']['tmp_name']);
        } else {
            $json = file_get_contents('php://input');
            if (empty($json)) {
                $json = $_POST['json'] ?? null;
            }
        }

        if (!$json) {
            echo json_encode(['success' => false, 'error' => 'Keine Daten empfangen']);
            return;
        }

        $result = $this->templateModel->importFromJson($json);
        echo json_encode($result);
    }

    /**
     * Kontakte für Autocomplete laden
     */
    public function getContacts()
    {
        header('Content-Type: application/json');

        $search = $_GET['q'] ?? '';

        $contactModel = new Contact();
        $contacts = $contactModel->search($search, 10);

        echo json_encode(array_map(function ($c) {
            return ['id' => $c['id'], 'name' => $c['name']];
        }, $contacts));
    }
}
