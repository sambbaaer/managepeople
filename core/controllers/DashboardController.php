<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Contact.php';

class DashboardController
{

    public function index()
    {
        $user = Auth::user();

        $taskModel = new Task();
        $contactModel = new Contact();
        $settingsModel = new \Settings(); // Root namespace since we are in controller
        require_once __DIR__ . '/../models/Settings.php';
        require_once __DIR__ . '/../models/Activity.php';
        $activityModel = new \Activity();

        // 1. Fetch Tasks
        $today = date('Y-m-d');

        // Deine Aufgaben: Alle offenen Tasks
        $rawTodos = $taskModel->getTodayTasks();
        $todos = [];
        foreach ($rawTodos as $t) {
            $dueDate = $t['due_date'];
            $isToday = false;

            if ($dueDate) {
                $taskDate = date('Y-m-d', strtotime($dueDate));
                $isToday = ($taskDate === $today);
            }

            $todos[] = [
                'id' => $t['id'],
                'text' => $t['title'] . ($t['contact_name'] ? ' (' . $t['contact_name'] . ')' : ''),
                'done' => (bool) $t['completed'],
                'due_date' => $dueDate,
                'is_today' => $isToday,
                'priority' => $t['priority'],
                'contact_id' => $t['contact_id']
            ];
        }

        // Hast du das gemacht?: Überfällige Tasks
        $rawOverdue = $taskModel->getOverdueTasks();
        $todosOverdue = [];
        foreach ($rawOverdue as $t) {
            $todosOverdue[] = [
                'id' => $t['id'],
                'text' => $t['title'] . ($t['contact_name'] ? ' (' . $t['contact_name'] . ')' : ''),
                'due_date' => $t['due_date'],
                'priority' => $t['priority'],
                'contact_id' => $t['contact_id']
            ];
        }

        // 2. Goal Calculation Logic
        $goalSettings = $settingsModel->getAll();
        $metric = $goalSettings['goal_metric'] ?? 'conversions'; // Default

        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');
        $currentYearStart = date('Y-01-01');
        $currentYearEnd = date('Y-12-31');

        $monthCount = 0;
        $yearCount = 0;

        switch ($metric) {
            case 'new_contacts':
                // Using Contact Model (we might need a countByRange method, let's adhoc query for now via DB instance or add to model)
                // For speed, let's use direct query here or add helper. 
                // Let's rely on adding a helper to Contact Model later if this gets complex.
                // Or simply:
                $db = Database::getInstance();
                $monthCount = $db->fetchColumn("SELECT COUNT(*) FROM contacts WHERE created_at BETWEEN ? AND ?", [$currentMonthStart . ' 00:00:00', $currentMonthEnd . ' 23:59:59']);
                $yearCount = $db->fetchColumn("SELECT COUNT(*) FROM contacts WHERE created_at BETWEEN ? AND ?", [$currentYearStart . ' 00:00:00', $currentYearEnd . ' 23:59:59']);
                break;

            case 'tasks_done':
                $db = Database::getInstance();
                $monthCount = $db->fetchColumn("SELECT COUNT(*) FROM tasks WHERE completed = 1 AND completed_at BETWEEN ? AND ?", [$currentMonthStart . ' 00:00:00', $currentMonthEnd . ' 23:59:59']);
                $yearCount = $db->fetchColumn("SELECT COUNT(*) FROM tasks WHERE completed = 1 AND completed_at BETWEEN ? AND ?", [$currentYearStart . ' 00:00:00', $currentYearEnd . ' 23:59:59']);
                break;

            case 'new_customers':
                $monthCount = $activityModel->countByDateRange('status_change', $currentMonthStart, $currentMonthEnd, ['Kundin']);
                $yearCount = $activityModel->countByDateRange('status_change', $currentYearStart, $currentYearEnd, ['Kundin']);
                break;

            case 'new_partners':
                $monthCount = $activityModel->countByDateRange('status_change', $currentMonthStart, $currentMonthEnd, ['Partnerin']);
                $yearCount = $activityModel->countByDateRange('status_change', $currentYearStart, $currentYearEnd, ['Partnerin']);
                break;

            case 'conversions':
            default:
                $monthCount = $activityModel->countByDateRange('status_change', $currentMonthStart, $currentMonthEnd, ['Kundin', 'Partnerin']);
                $yearCount = $activityModel->countByDateRange('status_change', $currentYearStart, $currentYearEnd, ['Kundin', 'Partnerin']);
                break;
        }

        // 3. Other Data
        // 3. Other Data
        // Birthdays
        $geburtstageRaw = $contactModel->getUpcomingBirthdays(3);
        $geburtstage = [];
        foreach ($geburtstageRaw as $gb) {
            $geburtstage[] = [
                "name" => $gb['name'],
                "datum" => $gb['date_formatted'],
                "alter" => $gb['age'] . " Jahre"
            ];
        }

        // Neglected
        $months = (int) ($settingsModel->get('neglect_months', '8'));
        $vernachlaessigtRaw = $contactModel->getNeglectedContacts($months, 20);

        // Randomly pick 3 (1 hero + 2 list) from the pool if pool is large
        shuffle($vernachlaessigtRaw);
        $vernachlaessigt = array_slice($vernachlaessigtRaw, 0, 3);


        // Date String German
        $days = ['Sunday' => 'Sonntag', 'Monday' => 'Montag', 'Tuesday' => 'Dienstag', 'Wednesday' => 'Mittwoch', 'Thursday' => 'Donnerstag', 'Friday' => 'Freitag', 'Saturday' => 'Samstag'];
        $monthNames = ['January' => 'Januar', 'February' => 'Februar', 'March' => 'März', 'April' => 'April', 'May' => 'Mai', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'August', 'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Dezember'];

        $dayName = $days[date('l')];
        $dayNum = date('d');
        $monthName = $monthNames[date('F')];
        $monthName = $monthNames[date('F')];
        $heute = "$dayName, $dayNum. $monthName";

        // 4. Geplante Kontakte für diesen Monat (Phasen-System)
        $currentMonth = date('m');
        $currentYear = date('Y');
        $plannedContacts = $contactModel->getContactsByPhaseDate($currentMonth, $currentYear);

        $data = [
            'user_name' => $user['name'] ?? 'User',
            'todos' => $todos,
            'todos_overdue' => $todosOverdue,
            'geburtstage' => $geburtstage,
            'vernachlaessigt' => $vernachlaessigt,
            'planned_contacts' => $plannedContacts, // NEU
            'heute_datum' => $heute,
            'goals' => [
                'metric' => $metric,
                'month_current' => $monthCount,
                'month_target' => $goalSettings['goal_month'] ?? 10,
                'year_current' => $yearCount,
                'year_target' => $goalSettings['goal_year'] ?? 100
            ],
            'neglect_months' => $months
        ];

        require __DIR__ . '/../../templates/dashboard.php';
    }
}
