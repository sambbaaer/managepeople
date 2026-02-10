<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Contact.php';

class CalendarController
{

    public function index()
    {
        $taskModel = new Task();
        $contactModel = new Contact();

        // Month navigation
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');

        // Calculate dates
        $firstDayOfMonth = "$year-$month-01";
        $daysInMonth = date('t', strtotime($firstDayOfMonth));
        $monthName = date('F', strtotime($firstDayOfMonth)); // English name for now, localization later

        // Fetch Tasks for this month
        // Ideally we'd filter query by date range, but fetching all for now or simple approximation
        // Improving filtering:
        $allTasks = $taskModel->getAll([], 1000);

        $tasksByDate = [];
        foreach ($allTasks as $task) {
            if (!empty($task['due_date'])) {
                $tDate = date('Y-m-d', strtotime($task['due_date']));
                $tasksByDate[$tDate][] = $task;
            }
        }

        // Fetch Birthdays for this month
        $birthdays = $contactModel->getBirthdaysByMonth($month);
        foreach ($birthdays as $bday) {
            // Set date to current year's birthday
            $d = new DateTime($bday['birthday']);
            $currentBday = "$year-$month-" . $d->format('d');

            // Age calculation
            $age = $year - $d->format('Y');

            // Add as pseudo-task
            $tasksByDate[$currentBday][] = [
                'id' => 'bday-' . $bday['id'], // String ID to distinction
                'title' => "🎂 " . $bday['name'] . " ($age)",
                'completed' => false,
                'priority' => 'birthday', // Custom priority for styling
                'due_date' => $currentBday,
                'is_birthday' => true,
                'contact_id' => $bday['id']
            ];
        }

        // Fetch Phase Dates for this month
        $phaseContacts = $contactModel->getContactsByPhaseDate($month, $year);
        foreach ($phaseContacts as $pc) {
            $pDate = date('Y-m-d', strtotime($pc['phase_date']));

            // Add as pseudo-task
            $tasksByDate[$pDate][] = [
                'id' => 'phase-' . $pc['id'],
                'title' => "📍 " . $pc['name'] . " (" . $pc['phase'] . ")",
                'completed' => false,
                'priority' => 'phase', // Custom priority for styling
                'due_date' => $pDate,
                'is_phase' => true,
                'contact_id' => $pc['id']
            ];
        }

        require __DIR__ . '/../../templates/calendar/month.php';
    }

    public function feed()
    {
        $token = $_GET['token'] ?? '';
        $user = Auth::validateCalendarToken($token);

        if (!$user) {
            http_response_code(403);
            die("Invalid Calendar Token");
        }

        $taskModel = new Task();
        $contactModel = new Contact();

        // Fetch data
        $tasks = $taskModel->getTasksForCalendar($user['id']);
        $birthdays = $contactModel->getAllBirthdays();
        $phases = $contactModel->getAllPhaseDates();

        // Start ICS Output
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="managepeople.ics"');

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//ManagePeople//V3//DE\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        echo "X-WR-CALNAME:ManagePeople\r\n";
        echo "X-WR-TIMEZONE:Europe/Zurich\r\n";

        // Current Year for Birthdays (include current and next year)
        $currentYear = date('Y');
        $years = [$currentYear, $currentYear + 1];

        // 1. Birthdays
        foreach ($birthdays as $bday) {
            try {
                $date = new DateTime($bday['birthday']);
                $md = $date->format('md');

                foreach ($years as $y) {
                    $bdayDate = "$y$md";
                    $age = $y - $date->format('Y');

                    echo "BEGIN:VEVENT\r\n";
                    echo "UID:bday-{$bday['id']}-$y@managepeople.local\r\n";
                    echo "DTSTAMP:" . date('Ymd\THHis\Z') . "\r\n";
                    echo "DTSTART;VALUE=DATE:$bdayDate\r\n";
                    echo "SUMMARY:🎂 {$bday['name']} ($age)\r\n";
                    echo "TRANSP:TRANSPARENT\r\n"; // Available
                    echo "END:VEVENT\r\n";
                }
            } catch (Exception $e) {
                continue;
            }
        }

        // 2. Phases
        foreach ($phases as $phase) {
            try {
                $date = new DateTime($phase['phase_date']);
                $dtStart = $date->format('Ymd');

                echo "BEGIN:VEVENT\r\n";
                echo "UID:phase-{$phase['id']}-{$dtStart}@managepeople.local\r\n";
                echo "DTSTAMP:" . date('Ymd\THHis\Z') . "\r\n";
                echo "DTSTART;VALUE=DATE:$dtStart\r\n";
                echo "SUMMARY:📍 {$phase['name']} ({$phase['phase']})\r\n";
                echo "TRANSP:TRANSPARENT\r\n";
                echo "END:VEVENT\r\n";
            } catch (Exception $e) {
                continue;
            }
        }

        // 3. Tasks
        foreach ($tasks as $task) {
            try {
                if (empty($task['due_date']))
                    continue;

                $date = new DateTime($task['due_date']);
                // Check if it has time (if H:i:s is 00:00:00, treat as all day?)
                // Or just always treat as all day
                $isAllDay = ($date->format('H:i:s') === '00:00:00' || $date->format('H:i:s') == '12:00:00'); // Our default creates times often at 00:00 or 12:00

                // Using value=DATE ensures it shows at the top in GCal
                $dtStart = $date->format('Ymd');

                $status = $task['completed'] ? " [Erledigt]" : "";
                $prio = ($task['priority'] === 'high') ? "❗ " : "";

                echo "BEGIN:VEVENT\r\n";
                echo "UID:task-{$task['id']}@managepeople.local\r\n";
                echo "DTSTAMP:" . date('Ymd\THHis\Z') . "\r\n";
                echo "DTSTART;VALUE=DATE:$dtStart\r\n";
                echo "SUMMARY:$prio{$task['title']}$status\r\n";
                if (!empty($task['description'])) {
                    // Escape description
                    $desc = str_replace(["\r", "\n", ","], ["", "\\n", "\,"], $task['description']);
                    echo "DESCRIPTION:$desc\r\n";
                }
                echo "STATUS:" . ($task['completed'] ? "CONFIRMED" : "TENTATIVE") . "\r\n";
                echo "END:VEVENT\r\n";
            } catch (Exception $e) {
                continue;
            }
        }

        echo "END:VCALENDAR\r\n";
        exit;
    }
}
