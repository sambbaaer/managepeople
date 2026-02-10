<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Auth.php';

class StatisticsController
{
    public function index()
    {
        if (!Auth::check()) {
            redirect('index.php?page=login');
        }

        $db = Database::getInstance();

        // 1. KPI: Total Contacts
        $totalContacts = $db->fetchColumn("SELECT COUNT(*) FROM contacts");

        // 2. KPI: Client Conversion Rate (Interessent vs Kundin/Partnerin)
        // Assuming 'Kundin', 'Partnerin' are success states.
        $customers = $db->fetchColumn("SELECT COUNT(*) FROM contacts WHERE status IN ('Kundin', 'Partnerin')");
        $conversionRate = $totalContacts > 0 ? round(($customers / $totalContacts) * 100, 1) : 0;

        // 3. KPI: Open Tasks
        $openTasks = $db->fetchColumn("SELECT COUNT(*) FROM tasks WHERE completed = 0");

        // 4. Chart: Contacts by Status
        $statusStats = $db->fetchAll("SELECT status, COUNT(*) as count FROM contacts GROUP BY status ORDER BY count DESC");
        // Prepare for chart (max value for scaling)
        $maxStatusCount = 0;
        foreach ($statusStats as $stat) {
            if ($stat['count'] > $maxStatusCount)
                $maxStatusCount = $stat['count'];
        }

        // 5. Chart: Growth (Last 12 Months) - SQLite Syntax
        // Note: SQLite uses strftime for date formatting
        $growthStats = $db->fetchAll("
            SELECT strftime('%Y-%m', created_at) as month, COUNT(*) as count 
            FROM contacts 
            WHERE created_at >= date('now', '-11 months', 'start of month')
            GROUP BY month 
            ORDER BY month ASC
        ");

        // Fill in missing months with 0
        $filledGrowth = [];
        $current = new DateTime();
        $current->modify('-11 months');
        $end = new DateTime();

        while ($current <= $end) {
            $m = $current->format('Y-m');
            $count = 0;
            foreach ($growthStats as $g) {
                if ($g['month'] === $m) {
                    $count = $g['count'];
                    break;
                }
            }
            $filledGrowth[] = [
                'month' => $current->format('M'), // Short name like Jan, Feb
                'full_month' => $m,
                'count' => $count
            ];
            $current->modify('+1 month');
        }

        // 6. Top Products
        $topProducts = $db->fetchAll("
            SELECT p.name, COUNT(cp.product_id) as count 
            FROM contact_products cp
            JOIN products p ON cp.product_id = p.id 
            GROUP BY cp.product_id 
            ORDER BY count DESC 
            LIMIT 5
        ");

        // 7. Neglect/Activity Distribution
        // Categorize by last_contacted_at
        // < 30 days, 30-90 days, > 90 days, Never
        $activityDist = [
            'recent' => 0, // < 30 days
            'quarter' => 0, // 30-90 days
            'old' => 0, // > 90 days
            'never' => 0 // NULL
        ];

        $allContacts = $db->fetchAll("SELECT last_contacted_at FROM contacts");
        $now = time();

        foreach ($allContacts as $c) {
            if (!$c['last_contacted_at']) {
                $activityDist['never']++;
                continue;
            }

            $days = ($now - strtotime($c['last_contacted_at'])) / (60 * 60 * 24);

            if ($days < 30)
                $activityDist['recent']++;
            elseif ($days < 90)
                $activityDist['quarter']++;
            else
                $activityDist['old']++; // Includes > 90
        }

        // 8. Current Month Stats
        $currentMonth = date('Y-m');
        $monthStats = [
            'new_contacts' => $db->fetchColumn("SELECT COUNT(*) FROM contacts WHERE strftime('%Y-%m', created_at) = ?", [$currentMonth]),
            'completed_tasks' => $db->fetchColumn("SELECT COUNT(*) FROM tasks WHERE completed = 1 AND strftime('%Y-%m', completed_at) = ?", [$currentMonth]),
            'interactions' => $db->fetchColumn("SELECT COUNT(*) FROM activities WHERE strftime('%Y-%m', created_at) = ?", [$currentMonth])
        ];

        // 9. Relationship Distribution
        $relationshipStats = $db->fetchAll("
            SELECT beziehung as relationship, COUNT(*) as count 
            FROM contacts 
            WHERE beziehung IS NOT NULL AND beziehung != '' 
            GROUP BY beziehung 
            ORDER BY count DESC 
            LIMIT 5
        ");

        // 10. Average Notes per Contact
        $totalNotes = $db->fetchColumn("SELECT COUNT(*) FROM notes");
        $avgNotesPerContact = $totalContacts > 0 ? round($totalNotes / $totalContacts, 1) : 0;

        // Prepare Data for View
        $data = [
            'kpis' => [
                'total_contacts' => $totalContacts,
                'conversion_rate' => $conversionRate,
                'open_tasks' => $openTasks,
                'avg_notes_per_contact' => $avgNotesPerContact
            ],
            'status_stats' => $statusStats,
            'max_status_count' => $maxStatusCount,
            'growth_stats' => $filledGrowth,
            'top_products' => $topProducts,
            'activity_dist' => $activityDist,
            'month_stats' => $monthStats,
            'relationship_stats' => $relationshipStats
        ];

        require __DIR__ . '/../../templates/statistics.php';
    }
}
