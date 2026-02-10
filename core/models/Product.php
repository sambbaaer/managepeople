<?php

class Product
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function ensureTablesExist()
    {
        $this->db->execute("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            image_url TEXT,
            product_url TEXT,
            imported_id INTEGER UNIQUE,
            archived INTEGER DEFAULT 0
        )");

        // Add archived column if table already exists without it
        try {
            $this->db->execute("ALTER TABLE products ADD COLUMN archived INTEGER DEFAULT 0");
        } catch (Exception $e) {
            // Column already exists, ignore
        }

        $this->db->execute("CREATE TABLE IF NOT EXISTS contact_products (
            contact_id INTEGER,
            product_id INTEGER,
            PRIMARY KEY (contact_id, product_id),
            FOREIGN KEY(contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
    }

    public function importFromJson($filePath)
    {
        $this->ensureTablesExist();

        $absPath = realpath($filePath);
        if (!$absPath || !file_exists($absPath)) {
            throw new Exception("File not found at: " . $filePath . " (Resolved: $absPath)");
        }

        $json = file_get_contents($absPath);
        $data = json_decode($json, true);

        if (!$data) {
            throw new Exception("Invalid JSON data: " . json_last_error_msg());
        }

        $count = 0;
        $this->db->beginTransaction(); // Speed up import
        try {
            foreach ($data as $item) {
                // Check if exists
                $exists = $this->db->fetch("SELECT id FROM products WHERE imported_id = ?", [$item['id']]);

                if ($exists) {
                    // Update
                    $sql = "UPDATE products SET name = ?, image_url = ?, product_url = ? WHERE imported_id = ?";
                    $this->db->execute($sql, [$item['name'], $item['image_url'], $item['product_url'], $item['id']]);
                } else {
                    // Insert
                    $sql = "INSERT INTO products (name, image_url, product_url, imported_id) VALUES (?, ?, ?, ?)";
                    $this->db->execute($sql, [$item['name'], $item['image_url'], $item['product_url'], $item['id']]);
                    $count++;
                }
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $count;
    }

    public function getById($id)
    {
        return $this->db->fetch("SELECT * FROM products WHERE id = ?", [$id]);
    }

    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM products ORDER BY name ASC");
    }

    public function getAllActive()
    {
        return $this->db->fetchAll("SELECT * FROM products WHERE archived = 0 ORDER BY name ASC");
    }

    public function getAllArchived()
    {
        return $this->db->fetchAll("SELECT * FROM products WHERE archived = 1 ORDER BY name ASC");
    }

    public function getAllWithCustomerCount($filter = 'active', $search = '')
    {
        $where = '';
        $params = [];

        if ($filter === 'active') {
            $where = 'WHERE p.archived = 0';
        } elseif ($filter === 'archived') {
            $where = 'WHERE p.archived = 1';
        }

        if ($search) {
            $where .= ($where ? ' AND' : 'WHERE') . ' p.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $sql = "SELECT p.*, COUNT(cp.contact_id) as customer_count
                FROM products p
                LEFT JOIN contact_products cp ON p.id = cp.product_id
                $where
                GROUP BY p.id
                ORDER BY p.name ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function create($name, $imageUrl = '', $productUrl = '')
    {
        $this->ensureTablesExist();
        $sql = "INSERT INTO products (name, image_url, product_url, archived) VALUES (?, ?, ?, 0)";
        $this->db->execute($sql, [$name, $imageUrl, $productUrl]);
        return $this->db->lastInsertId();
    }

    public function update($id, $name, $imageUrl = '', $productUrl = '')
    {
        $sql = "UPDATE products SET name = ?, image_url = ?, product_url = ? WHERE id = ?";
        return $this->db->execute($sql, [$name, $imageUrl, $productUrl, $id]);
    }

    public function archive($id)
    {
        return $this->db->execute("UPDATE products SET archived = 1 WHERE id = ?", [$id]);
    }

    public function unarchive($id)
    {
        return $this->db->execute("UPDATE products SET archived = 0 WHERE id = ?", [$id]);
    }

    public function search($query)
    {
        $term = '%' . $query . '%';
        return $this->db->fetchAll("SELECT * FROM products WHERE name LIKE ? LIMIT 10", [$term]);
    }

    public function getByContactId($contactId)
    {
        $sql = "SELECT p.* FROM products p
                JOIN contact_products cp ON p.id = cp.product_id
                WHERE cp.contact_id = ?";
        return $this->db->fetchAll($sql, [$contactId]);
    }

    public function assignToContact($contactId, $productId)
    {
        // Check if already assigned
        $exists = $this->db->fetch("SELECT 1 FROM contact_products WHERE contact_id = ? AND product_id = ?", [$contactId, $productId]);
        if (!$exists) {
            return $this->db->execute("INSERT INTO contact_products (contact_id, product_id) VALUES (?, ?)", [$contactId, $productId]);
        }
        return true;
    }

    public function removeFromContact($contactId, $productId)
    {
        return $this->db->execute("DELETE FROM contact_products WHERE contact_id = ? AND product_id = ?", [$contactId, $productId]);
    }

    public function getContactsByProductId($productId)
    {
        $sql = "SELECT c.* FROM contacts c
                JOIN contact_products cp ON c.id = cp.contact_id
                WHERE cp.product_id = ?
                ORDER BY c.name ASC";
        return $this->db->fetchAll($sql, [$productId]);
    }

    // Find products by name search and return contacts who have them
    public function findContactsByProductSearch($term)
    {
        $sql = "SELECT DISTINCT c.* FROM contacts c
                JOIN contact_products cp ON c.id = cp.contact_id
                JOIN products p ON cp.product_id = p.id
                WHERE p.name LIKE ?";
        return $this->db->fetchAll($sql, ['%' . $term . '%']);
    }

    public function exportAsJson()
    {
        $products = $this->getAllActive();
        $export = [];
        foreach ($products as $p) {
            $export[] = [
                'id' => (int) ($p['imported_id'] ?? $p['id']),
                'name' => $p['name'],
                'image_url' => $p['image_url'] ?? '',
                'product_url' => $p['product_url'] ?? ''
            ];
        }
        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
