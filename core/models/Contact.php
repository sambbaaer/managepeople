<?php

class Contact
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTagsColumnExists();
    }

    private function ensureTagsColumnExists()
    {
        try {
            // Check if tags column exists
            $this->db->query("SELECT tags FROM contacts LIMIT 1");
        } catch (Exception $e) {
            // Column doesn't exist, add it
            $this->db->execute("ALTER TABLE contacts ADD COLUMN tags TEXT");
        }
    }

    public function getAll($filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT 
                    contacts.*,
                    (SELECT description FROM activities WHERE contact_id = contacts.id ORDER BY created_at DESC LIMIT 1) as last_activity_desc,
                    (SELECT created_at FROM activities WHERE contact_id = contacts.id ORDER BY created_at DESC LIMIT 1) as last_activity_at
                FROM contacts 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (
                name LIKE ? 
                OR email LIKE ? 
                OR phone LIKE ? 
                OR tags LIKE ?
                OR EXISTS (
                    SELECT 1 FROM contact_products cp 
                    JOIN products p ON cp.product_id = p.id 
                    WHERE cp.contact_id = contacts.id 
                    AND p.name LIKE ?
                )
            )";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm; // For tags search
            $params[] = $searchTerm; // For product search
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['birthday_month']) && $filters['birthday_month'] === 'current') {
            // SQLite strftime('%m', birthday) match current month
            $currentMonth = date('m');
            $sql .= " AND strftime('%m', birthday) = ?";
            $params[] = $currentMonth;
        }

        if (!empty($filters['neglected'])) {
            // Neglected: last_contacted_at > 8 months ago OR null
            $months = 8;
            $sql .= " AND (last_contacted_at IS NULL OR last_contacted_at < date('now', '-$months months'))";
        }

        // Sorting Logic
        $sort = $filters['sort'] ?? 'updated_at_desc';
        switch ($sort) {
            case 'created_at_desc':
                $sql .= " ORDER BY created_at DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY name ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY name DESC";
                break;
            case 'updated_at_desc':
            default:
                $sql .= " ORDER BY updated_at DESC";
                break;
        }

        $sql .= " LIMIT $limit OFFSET $offset";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Einfache Suche für Autcomplete (Name / Email)
     */
    public function search($query, $limit = 10)
    {
        $sql = "SELECT id, name, email FROM contacts 
                WHERE name LIKE ? OR email LIKE ? 
                ORDER BY name ASC LIMIT ?";
        $searchTerm = '%' . $query . '%';
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $limit]);
    }

    // ... rest of the model ...
    public function count($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM contacts WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR tags LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        return $this->db->fetchColumn($sql, $params);
    }

    public function find($id)
    {
        return $this->db->fetch("SELECT * FROM contacts WHERE id = ?", [$id]);
    }

    public function findByName($name)
    {
        $sql = "SELECT * FROM contacts WHERE LOWER(name) = LOWER(?) LIMIT 1";
        return $this->db->fetch($sql, [trim($name)]);
    }

    public function getAllNames()
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT name FROM contacts WHERE name IS NOT NULL AND name != '' ORDER BY name ASC"
        );
    }

    public function create($data)
    {
        $fields = [
            'name',
            'phone',
            'email',
            'beziehung',
            'status',
            'sub_status',
            'notes',
            'recommended_by',
            'address',
            'birthday',
            'social_instagram',
            'social_tiktok',
            'social_facebook',
            'social_linkedin',
            'phase',
            'phase_date',
            'phase_notes',
            'tags'
        ];
        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = '?';
                $values[] = $data[$field];
            }
        }

        $sql = "INSERT INTO contacts (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->execute($sql, $values);
        return $this->db->lastInsertId();
    }
    public static function getMainStatuses()
    {
        return ['Offen', 'Interessent', 'Kundin', 'Partnerin'];
    }

    // Status & Sub-Status Definitions based on Statussystem.md
    public static function getStatusConfig()
    {
        return [
            'Offen' => [
                'icon' => 'circle',
                'color' => 'bg-gray-100 text-gray-600',
                'sub_statuses' => [
                    'Vorgemerkt' => 'Kein direktes To-Do',
                    'Geplant' => 'Monat/Jahr zugewiesen',
                    'Angefragt' => 'Warten auf Rückmeldung'
                ]
            ],
            'Interessent' => [
                'icon' => 'target',
                'color' => 'bg-orange-100 text-orange-700',
                'sub_statuses' => [
                    'Feedback ausstehend' => 'Nachhaken nötig',
                    'Interesse Freshdate' => 'Einladen',
                    'Interesse Produkte' => 'Erstgespräch',
                    'Interesse Business' => 'Meeting/Event'
                ]
            ],
            'Kundin' => [
                'icon' => 'shopping-bag',
                'color' => 'bg-green-100 text-green-700',
                'sub_statuses' => [
                    'Aktiv Kunde' => 'Nutzt Produkte',
                    'Business Interesse' => 'Vorbereitung Partner',
                    'Abgeschlossen' => 'Kein Business-Interesse'
                ]
            ],
            'Partnerin' => [
                'icon' => 'star',
                'color' => 'bg-purple-100 text-purple-700',
                'sub_statuses' => [
                    'Aktiv (Team)' => 'Hat bereits Team',
                    'Aktiv (Ohne Team)' => 'Baut auf',
                    'Hat Potenzial' => 'Coaching Fokus',
                    'Inaktiv' => 'Gefährdet'
                ]
            ],
            'Stillgelegt' => [
                'icon' => 'archive',
                'color' => 'bg-gray-200 text-gray-500',
                'sub_statuses' => []
            ]
        ];
    }

    public function getSubStatuses($status)
    {
        $config = self::getStatusConfig();
        return isset($config[$status]) ? array_keys($config[$status]['sub_statuses']) : [];
    }

    public function update($id, $data)
    {
        // Alten Status abrufen (für Status-Change-Detection)
        $oldContact = $this->find($id);
        $oldStatus = $oldContact['status'] ?? null;
        $oldSubStatus = $oldContact['sub_status'] ?? null;

        $fields = [];
        $values = [];

        if (empty($data['updated_at'])) {
            // Automatically touch updated_at
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($fields))
            return false;

        $values[] = $id;
        $sql = "UPDATE contacts SET " . implode(', ', $fields) . " WHERE id = ?";

        $result = $this->db->execute($sql, $values);

        // Status-Änderung erkennen und Handler triggern
        if ($result && isset($data['status'])) {
            $newStatus = $data['status'];
            $newSubStatus = $data['sub_status'] ?? null;

            // Nur bei tatsächlicher Änderung
            if ($newStatus !== $oldStatus || $newSubStatus !== $oldSubStatus) {
                require_once __DIR__ . '/../ContactStatusHandler.php';
                $handler = new ContactStatusHandler();
                $handler->handleStatusChange($id, $oldStatus, $newStatus, $newSubStatus);
            }
        }

        return $result;
    }

    public function delete($id)
    {
        return $this->db->execute("DELETE FROM contacts WHERE id = ?", [$id]);
    }

    public function getUpcomingBirthdays($limit = 5)
    {
        // SQLite approach for upcoming birthdays
        // This is complex in SQLite. Simplification:
        // load all contacts with birthday defined, process in PHP.
        // Assuming database is not huge (few thousands ok).

        $sql = "SELECT id, name, birthday FROM contacts WHERE birthday IS NOT NULL AND status NOT IN ('Stillgelegt', 'Abgeschlossen')";
        $contacts = $this->db->fetchAll($sql);

        if (empty($contacts))
            return [];

        $today = new DateTime();
        $today->setTime(0, 0);
        $currentYear = (int) $today->format('Y');

        $upcoming = [];

        foreach ($contacts as $c) {
            $bday = new DateTime($c['birthday']);
            $b_month = (int) $bday->format('m');
            $b_day = (int) $bday->format('d');

            // Birthday this year
            $nextBday = new DateTime("$currentYear-$b_month-$b_day");

            if ($nextBday < $today) {
                // Birthday passed this year, next is next year
                $nextBday = new DateTime(($currentYear + 1) . "-$b_month-$b_day");
            }

            $daysUntil = $today->diff($nextBday)->days;
            // Also calc age
            $age = $nextBday->format('Y') - $bday->format('Y');

            $upcoming[] = [
                'id' => $c['id'],
                'name' => $c['name'],
                'date_formatted' => $nextBday->format('d.m.'),
                'age' => $age,
                'days_until' => $daysUntil,
                'next_date' => $nextBday // for sorting
            ];
        }

        // Sort by days_until
        usort($upcoming, function ($a, $b) {
            return $a['days_until'] <=> $b['days_until'];
        });

        return array_slice($upcoming, 0, $limit);
    }

    public function getNeglectedContacts($months = 8, $limit = 50)
    {
        // Neglected if no recent activity in X months
        // Join activities table to find last activity date

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$months months"));

        $sql = "SELECT c.* FROM contacts c
                LEFT JOIN (
                    SELECT contact_id, MAX(created_at) as last_activity
                    FROM activities
                    GROUP BY contact_id
                ) a ON c.id = a.contact_id
                WHERE c.status NOT IN ('Stillgelegt', 'Abgeschlossen')
                AND (
                    a.last_activity IS NULL
                    OR a.last_activity < ?
                )
                ORDER BY RANDOM() LIMIT $limit";

        return $this->db->fetchAll($sql, [$cutoffDate]);
    }

    public function getBirthdaysByMonth($month)
    {
        // SQLite: strftime('%m', birthday)
        // Pad month with 0 if needed
        $m = str_pad($month, 2, '0', STR_PAD_LEFT);

        $sql = "SELECT id, name, birthday FROM contacts 
                WHERE birthday IS NOT NULL 
                AND status NOT IN ('Stillgelegt', 'Abgeschlossen')
                AND strftime('%m', birthday) = ?";

        return $this->db->fetchAll($sql, [$m]);
    }

    /**
     * Findet Kontakte mit Phasen-Termin in einem bestimmten Monat/Jahr
     */
    public function getContactsByPhaseDate($month, $year)
    {
        // Format YYYY-MM
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        $dateLike = "$year-$monthStr-%";

        // Wir suchen Kontakte die eine Phase haben UND deren phase_date in diesem Monat liegt
        // Auch sicherstellen dass der Status nicht abgeschlossen/stillgelegt ist (optional, aber sinnvoll)
        $sql = "SELECT * FROM contacts 
                WHERE phase_date LIKE ? 
                AND phase IS NOT NULL 
                AND phase != ''
                AND status NOT IN ('Stillgelegt', 'Abgeschlossen')
                ORDER BY phase_date ASC, name ASC";

        return $this->db->fetchAll($sql, [$dateLike]);
    }

    /**
     * Helper methods for full Calendar Export (ICS)
     */
    public function getAllBirthdays()
    {
        $sql = "SELECT id, name, birthday FROM contacts 
                WHERE birthday IS NOT NULL 
                AND status NOT IN ('Stillgelegt', 'Abgeschlossen')";
        return $this->db->fetchAll($sql);
    }

    public function getAllPhaseDates()
    {
        $sql = "SELECT id, name, phase, phase_date FROM contacts 
                WHERE phase_date IS NOT NULL 
                AND phase_date != '' 
                AND phase IS NOT NULL 
                AND status NOT IN ('Stillgelegt', 'Abgeschlossen')
                AND phase_date >= date('now', '-1 month')"; // Include slightly past phases too
        return $this->db->fetchAll($sql);
    }

    /**
     * Get all contacts with tags and favorite products for CSV export
     */
    public function getAllForExport()
    {
        $sql = "SELECT * FROM contacts ORDER BY id ASC";
        $contacts = $this->db->fetchAll($sql);

        // Enrich each contact with products
        foreach ($contacts as &$contact) {
            // Get favorite products
            $productsSql = "
                SELECT p.name 
                FROM contact_products cp
                JOIN products p ON cp.product_id = p.id
                WHERE cp.contact_id = ?
                ORDER BY p.name ASC
            ";
            $products = $this->db->fetchAll($productsSql, [$contact['id']]);
            $contact['products'] = array_column($products, 'name');
        }

        return $contacts;
    }
}
