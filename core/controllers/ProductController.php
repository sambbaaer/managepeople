<?php

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Contact.php';

class ProductController
{
    private $productModel;
    private $contactModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->productModel->ensureTablesExist();
        $this->contactModel = new Contact();
    }

    public function index()
    {
        $filter = $_GET['filter'] ?? 'active';
        $search = $_GET['q'] ?? '';

        $products = $this->productModel->getAllWithCustomerCount($filter, $search);

        // Load assigned contacts for each product
        $productContacts = [];
        foreach ($products as $p) {
            $productContacts[$p['id']] = $this->productModel->getContactsByProductId($p['id']);
        }

        // Load all contacts for assignment autocomplete
        $allContacts = $this->contactModel->getAll([], 9999);

        $msg = $_GET['msg'] ?? null;
        $error = $_GET['error'] ?? null;

        require __DIR__ . '/../../templates/products/list.php';
    }

    public function create()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=products');
        }

        $name = trim($_POST['name'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $productUrl = trim($_POST['product_url'] ?? '');

        if (!$name) {
            redirect('index.php?page=products&error=name_required');
        }

        $this->productModel->create($name, $imageUrl, $productUrl);
        redirect('index.php?page=products&msg=product_created');
    }

    public function archive()
    {
        Auth::denyMentorWriteAccess();
        $id = (int) ($_POST['product_id'] ?? $_GET['id'] ?? 0);
        if ($id) {
            $this->productModel->archive($id);
        }
        $filter = $_GET['filter'] ?? 'active';
        redirect('index.php?page=products&filter=' . $filter . '&msg=product_archived');
    }

    public function unarchive()
    {
        Auth::denyMentorWriteAccess();
        $id = (int) ($_POST['product_id'] ?? $_GET['id'] ?? 0);
        if ($id) {
            $this->productModel->unarchive($id);
        }
        $filter = $_GET['filter'] ?? 'archived';
        redirect('index.php?page=products&filter=' . $filter . '&msg=product_unarchived');
    }

    public function export()
    {
        $json = $this->productModel->exportAsJson();

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="produkte_export_' . date('Y-m-d') . '.json"');
        echo $json;
        exit;
    }

    public function assignContact()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=products');
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $contactName = trim($_POST['contact_name'] ?? '');
        $filter = $_POST['filter'] ?? 'active';

        if ($productId && $contactName) {
            // Find contact by name
            $contact = $this->contactModel->findByName($contactName);
            if ($contact) {
                $this->productModel->assignToContact($contact['id'], $productId);
            }
        }

        redirect('index.php?page=products&filter=' . $filter . '&msg=contact_assigned');
    }

    public function removeContact()
    {
        Auth::denyMentorWriteAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?page=products');
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $contactId = (int) ($_POST['contact_id'] ?? 0);
        $filter = $_POST['filter'] ?? 'active';

        if ($productId && $contactId) {
            $this->productModel->removeFromContact($contactId, $productId);
        }

        redirect('index.php?page=products&filter=' . $filter . '&msg=contact_removed');
    }
}
