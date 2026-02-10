<?php
// templates/calendar/month.php

// German Month Names
$deMonths = [
    'January' => 'Januar',
    'February' => 'Februar',
    'March' => 'März',
    'April' => 'April',
    'May' => 'Mai',
    'June' => 'Juni',
    'July' => 'Juli',
    'August' => 'August',
    'September' => 'September',
    'October' => 'Oktober',
    'November' => 'November',
    'December' => 'Dezember'
];
$displayMonth = $deMonths[(string) ($monthName ?? '')] ?? $monthName;

// Navigation logic
$prevMonth = date('m', strtotime("$year-$month-01 -1 month"));
$prevYear = date('Y', strtotime("$year-$month-01 -1 month"));
$nextMonth = date('m', strtotime("$year-$month-01 +1 month"));
$nextYear = date('Y', strtotime("$year-$month-01 +1 month"));

// Grid Logic
$startDow = date('N', strtotime($firstDayOfMonth)); // 1 (Mon) - 7 (Sun)
$startDow = $startDow - 1; // 0 for Mon to match loop offset if simple
// Actually let's just use standard calendar grid logic
// We need empty cells before 1st
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
</head>

<body class="min-h-screen flex bg-gray-50">
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <div class="relative w-max">
                <h1 class="text-3xl font-bold font-display text-gray-800 relative z-10">Kalender</h1>
                <?php echo getDoodle('Kalender', 'doodle doodle-teal w-12 h-12 -top-4 -right-10 rotate-12 opacity-60 hidden md:block'); ?>
            </div>

            <div class="flex items-center space-x-4 bg-white rounded-xl p-1 shadow-sm border border-gray-100">
                <a href="index.php?page=calendar&month=<?= $prevMonth ?>&year=<?= $prevYear ?>"
                    class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </a>
                <span class="font-bold text-gray-700 w-32 text-center">
                    <?= $displayMonth ?>
                    <?= $year ?>
                </span>
                <a href="index.php?page=calendar&month=<?= $nextMonth ?>&year=<?= $nextYear ?>"
                    class="p-2 hover:bg-gray-100 rounded-lg text-gray-500">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </a>
            </div>

            <button
                onclick="window.location='index.php?page=task_create&redirect_to=' + encodeURIComponent(window.location.href)"
                class="hidden md:flex bg-secondary hover:bg-teal-500 text-white px-4 py-2 rounded-xl font-medium shadow-lg transition-all items-center">
                <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Termin / To-Do
            </button>
        </header>

        <!-- Calendar Grid -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Days Header -->
            <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50">
                <?php foreach (['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $day): ?>
                    <div class="py-3 text-center text-sm font-semibold text-gray-500 uppercase tracking-wider">
                        <?= $day ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Days Grid -->
            <div class="grid grid-cols-7 auto-rows-fr bg-gray-100 gap-px border-b border-gray-200">
                <?php
                // Empty cells before start
                for ($i = 1; $i < date('N', strtotime($firstDayOfMonth)); $i++) {
                    echo '<div class="bg-white min-h-[120px] p-2 bg-gray-50/30"></div>';
                }

                // Days
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday = $dateStr === date('Y-m-d');
                    $dayTasks = $tasksByDate[$dateStr] ?? [];
                    ?>
                    <div class="bg-white min-h-[120px] p-2 hover:bg-gray-50 transition-colors relative group">
                        <div class="flex justify-between items-start mb-1">
                            <span
                                class="w-7 h-7 flex items-center justify-center rounded-full text-sm font-medium <?= $isToday ? 'bg-primary text-white' : 'text-gray-700' ?>">
                                <?= $day ?>
                            </span>
                            <a href="index.php?page=task_create&due_date=<?= $dateStr ?>&redirect_to=<?= urlencode("index.php?page=calendar&month=$month&year=$year") ?>"
                                class="opacity-0
                            group-hover:opacity-100 text-gray-400 hover:text-secondary p-1">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </a>
                        </div>

                        <!-- Events / Tasks -->
                        <div class="space-y-1">
                            <?php foreach ($dayTasks as $task):
                                $isBirthday = $task['is_birthday'] ?? false;
                                $clickAction = $isBirthday
                                    ? "window.location='index.php?page=contact_detail&id=" . ($task['contact_id'] ?? 0) . "'"
                                    : "window.location='index.php?page=tasks'";

                                $bgClass = 'bg-blue-50 text-blue-700 border-l-2 border-blue-400';
                                if ($task['completed']) {
                                    $bgClass = 'bg-gray-100 text-gray-400 line-through';
                                } elseif (($task['priority'] ?? '') == 'high') {
                                    $bgClass = 'bg-red-50 text-red-700 border-l-2 border-red-500';
                                } elseif (($task['priority'] ?? '') == 'birthday') {
                                    $bgClass = 'bg-purple-50 text-purple-700 border-l-2 border-purple-400';
                                } elseif (($task['priority'] ?? '') == 'phase' || ($task['is_phase'] ?? false)) {
                                    $bgClass = 'bg-secondary/10 text-secondary border-l-2 border-secondary';
                                }
                                ?>
                                <div class="px-2 py-1 rounded text-xs truncate <?= $bgClass ?> cursor-pointer hover:opacity-80 transition-opacity"
                                    onclick="<?= $clickAction ?>">
                                    <?= htmlspecialchars($task['title']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                }

                // Empty cells after end to fill row (optional, grid handles it but for aesthetics)
                ?>
            </div>
        </div>

        <!-- Calendar Subscription Section -->
        <?php
        $calToken = Auth::getCalendarToken($_SESSION['user_id']);
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $feedUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?page=calendar_feed&token=" . $calToken;
        // Clean up URL (remove double slashes if any, except http://)
        ?>
        <div class="mt-12 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
            <div class="flex items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-full mr-4 text-secondary">
                    <i data-lucide="rss" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Kalender abonnieren</h2>
                    <p class="text-gray-500 text-sm">Integriere deine Termine und Aufgaben in deinen privaten Kalender.
                    </p>
                </div>
            </div>

            <div class="space-y-6">
                <!-- URL Display -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dein persönlicher Abo-Link (ICS)</label>
                    <div class="flex space-x-2">
                        <input type="text" readonly value="<?= $feedUrl ?>" id="ics-url"
                            class="flex-1 bg-gray-50 border border-gray-200 text-gray-600 text-sm rounded-xl focus:ring-secondary focus:border-secondary block w-full p-2.5">
                        <button
                            onclick="navigator.clipboard.writeText(document.getElementById('ics-url').value); alert('Link kopiert!');"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium transition-colors flex items-center">
                            <i data-lucide="copy" class="w-4 h-4 mr-2"></i> Kopieren
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Diesen Link nicht teilen, da er Zugriff auf deine Termine
                        gewährt.</p>
                </div>

                <!-- Instructions Accordion -->
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Google Calendar -->
                    <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                        <div class="flex items-center mb-3 text-gray-800 font-semibold">
                            <i data-lucide="calendar" class="w-5 h-5 mr-2 text-blue-500"></i> Google Kalender
                        </div>
                        <ol class="list-decimal list-outside ml-4 space-y-2 text-sm text-gray-600">
                            <li>Öffne <a href="https://calendar.google.com" target="_blank"
                                    class="text-blue-500 hover:underline">calendar.google.com</a> am PC.</li>
                            <li>Klicke links bei "Weitere Kalender" auf das <strong>+</strong>.</li>
                            <li>Wähle <strong>"Per URL"</strong>.</li>
                            <li>Füge den kopierten Link ein.</li>
                            <li>Klicke auf <strong>"Kalender hinzufügen"</strong>.</li>
                        </ol>
                    </div>

                    <!-- Apple Calendar -->
                    <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                        <div class="flex items-center mb-3 text-gray-800 font-semibold">
                            <i data-lucide="smartphone" class="w-5 h-5 mr-2 text-gray-800"></i> Apple / iPhone
                        </div>
                        <ol class="list-decimal list-outside ml-4 space-y-2 text-sm text-gray-600">
                            <li>Gehe zu <strong>Einstellungen</strong> > <strong>Kalender</strong>.</li>
                            <li>Wähle <strong>Accounts</strong> > <strong>Account hinzufügen</strong>.</li>
                            <li>Wähle <strong>Andere</strong> > <strong>Kalenderabo hinzufügen</strong>.</li>
                            <li>Füge den Link ein und klicke auf "Weiter".</li>
                        </ol>
                    </div>

                    <!-- Outlook -->
                    <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                        <div class="flex items-center mb-3 text-gray-800 font-semibold">
                            <i data-lucide="mail" class="w-5 h-5 mr-2 text-blue-700"></i> Outlook
                        </div>
                        <ol class="list-decimal list-outside ml-4 space-y-2 text-sm text-gray-600">
                            <li>Gehe zur Kalender-Ansicht.</li>
                            <li>Klicke auf <strong>"Kalender hinzufügen"</strong>.</li>
                            <li>Wähle <strong>"Aus dem Internet abonnieren"</strong>.</li>
                            <li>Füge die URL ein und gib dem Kalender einen Namen.</li>
                            <li>Klicke auf <strong>"Importieren"</strong>.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>