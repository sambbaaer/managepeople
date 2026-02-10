<?php
// Phase 2: Refactored Dashboard Template using Layout
$user_name = $data['user_name'] ?? 'Guest';
$todos = $data['todos'] ?? [];
$geburtstage = $data['geburtstage'] ?? [];
$vernachlaessigt = $data['vernachlaessigt'] ?? [];
$heute = date('l, d. F');
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/layout/head.php'; ?>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <header class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div class="relative">
                <h1 class="text-3xl font-bold font-display text-gray-800">Dashboard</h1>
                <p class="text-gray-500 mt-1">heute <span class="highlighter">
                        <?php echo $data['heute_datum'] ?? date('d.m.Y'); ?>
                    </span></p>
                <?php echo getDoodle('Sonne', 'doodle doodle-yellow w-12 h-12 -top-6 -right-6 rotate-12 opacity-80'); ?>
            </div>
            <form action="index.php" method="GET" class="relative doodle-container w-full md:w-auto">
                <input type="hidden" name="page" value="search">
                <input type="text" name="q" placeholder="Suche..."
                    class="pl-10 pr-4 py-2 border-none rounded-full glass-card focus:ring-2 focus:ring-secondary outline-none w-full md:w-64 relative z-10 transition-shadow">
                <i data-lucide="search" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400 z-20"></i>
                <?php echo getDoodle('bearbeiten', 'doodle doodle-teal w-8 h-8 -left-4 -bottom-2 -rotate-12 op-10'); ?>
            </form>
        </header>

        <!-- GRID LAYOUT -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">

            <!-- TO DO'S -->
            <section class="glass-card p-6 relative overflow-hidden">
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <h3 class="text-xl font-bold flex items-center font-display">
                        <i data-lucide="list-checks" class="mr-2 text-primary"></i> Deine Aufgaben
                    </h3>
                    <a href="index.php?page=tasks" class="text-xs text-secondary font-semibold hover:underline">Alle
                        ansehen</a>
                </div>
                <?php echo getDoodle('Aufgaben', 'doodle doodle-coral w-16 h-16 -top-4 -right-2 -rotate-6 opacity-10'); ?>
                <ul class="space-y-3 relative z-10">
                    <?php if (empty($todos)): ?>
                        <li class="text-gray-400 text-sm flex items-center">
                            <i data-lucide="party-popper" class="w-4 h-4 mr-2"></i> Alles erledigt - Super!
                        </li>
                    <?php else: ?>
                        <?php foreach ($todos as $todo): ?>
                            <li
                                class="flex items-center justify-between group hover:bg-gray-50/50 rounded-lg p-2 -ml-2 transition-colors">
                                <div class="flex items-start flex-1">
                                    <a href="index.php?page=task_toggle&id=<?= $todo['id'] ?>"
                                        class="mt-1 mr-3 w-5 h-5 border-2 <?= $todo['done'] ? 'bg-secondary border-secondary' : 'border-gray-300 hover:border-secondary' ?> rounded flex items-center justify-center transition-colors flex-shrink-0">
                                        <?php if ($todo['done']): ?>
                                            <i data-lucide="check" class="w-3 h-3 text-white"></i>
                                        <?php endif; ?>
                                    </a>
                                    <div class="flex-1">
                                        <span
                                            class="<?= $todo['done'] ? 'line-through text-gray-400' : ($todo['is_today'] ? 'text-primary font-bold' : 'text-gray-700') ?>">
                                            <?php echo htmlspecialchars($todo['text']); ?>
                                            <?php if ($todo['is_today']): ?>
                                                <span
                                                    class="ml-2 text-xs px-2 py-0.5 bg-primary/10 text-primary rounded-full font-bold">Heute</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($todo['due_date']): ?>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                <i data-lucide="calendar" class="w-3 h-3 inline"></i>
                                                <?= date('d.m.Y', strtotime($todo['due_date'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="p-1.5 hover:bg-gray-200 rounded transition-colors task-action"
                                        data-action="reschedule" data-id="<?= $todo['id'] ?>" title="Verschieben">
                                        <i data-lucide="calendar-clock" class="w-4 h-4 text-gray-600"></i>
                                    </button>
                                    <button class="p-1.5 hover:bg-red-100 rounded transition-colors task-action"
                                        data-action="delete" data-id="<?= $todo['id'] ?>" title="Löschen">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <div class="mt-4 pt-2 border-t border-gray-100">
                    <button onclick="window.location='index.php?page=tasks'"
                        class="text-sm font-medium text-gray-500 hover:text-primary flex items-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Aufgabe hinzufügen
                    </button>
                </div>
            </section>

            <!-- GEPLANT DIESEN MONAT (NEU) -->
            <section class="glass-card p-6 doodle-container relative overflow-hidden">
                <h3 class="text-xl font-bold mb-4 relative z-10 font-display flex items-center">
                    <i data-lucide="calendar-check" class="mr-2 text-secondary"></i>
                    Geplant im <?= $monthName ?>
                </h3>
                <?php echo getDoodle('Kalender', 'doodle doodle-teal w-14 h-14 -top-2 -right-2 rotate-12 opacity-10'); ?>

                <div class="space-y-3 relative z-10">
                    <?php
                    $planned = $data['planned_contacts'] ?? [];
                    if (empty($planned)):
                        ?>
                        <div class="text-gray-400 text-sm flex items-center justify-center p-4 bg-gray-50/50 rounded-lg">
                            <i data-lucide="calendar-off" class="w-4 h-4 mr-2"></i>
                            Nichts geplant für diesen Monat.
                        </div>
                    <?php else: ?>
                        <?php foreach ($planned as $contact):
                            $phaseDate = strtotime($contact['phase_date']);
                            $isFullDate = (date('d', $phaseDate) != '01'); // Estimate if full date or month/year
                            $dateDisplay = $isFullDate ? date('d.m.', $phaseDate) : '';
                            ?>
                            <a href="index.php?page=contact_detail&id=<?= $contact['id'] ?>"
                                class="flex items-center justify-between p-3 bg-white/60 hover:bg-white rounded-xl border border-transparent hover:border-secondary/30 transition-all group shadow-sm hover:shadow-md no-underline">
                                <div class="flex items-center min-w-0">
                                    <div
                                        class="w-10 h-10 rounded-full bg-secondary/10 text-secondary flex items-center justify-center font-bold text-sm mr-3 shrink-0">
                                        <?= substr($contact['name'], 0, 1) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div
                                            class="font-bold text-gray-800 truncate group-hover:text-secondary transition-colors">
                                            <?= htmlspecialchars($contact['name']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center">
                                            <span class="bg-gray-100 px-1.5 py-0.5 rounded text-gray-600 mr-2">
                                                <?= htmlspecialchars($contact['phase']) ?>
                                            </span>
                                            <?php if ($dateDisplay): ?>
                                                <span class="text-secondary font-medium">am <?= $dateDisplay ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <i data-lucide="chevron-right"
                                    class="w-4 h-4 text-gray-300 group-hover:text-secondary opacity-0 group-hover:opacity-100 transition-all"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($planned)): ?>
                    <div class="mt-4 pt-3 border-t border-gray-100 text-center">
                        <a href="index.php?page=calendar" class="text-xs text-secondary font-semibold hover:underline">
                            Zum Kalender
                        </a>
                    </div>
                <?php endif; ?>
            </section>
            <section class="glass-card p-6 border-t-4 border-accent doodle-container relative">
                <h3 class="text-xl font-bold mb-4 relative z-10 font-display">Hast du das gemacht?</h3>
                <?php echo getDoodle('OK', 'doodle doodle-purple w-12 h-12 -top-2 -right-4 rotate-12 opacity-10'); ?>
                <div class="space-y-2 relative z-10">
                    <?php if (empty($data['todos_overdue'])): ?>
                        <p class="text-gray-400 text-sm flex items-center">
                            <i data-lucide="check-circle-2" class="w-4 h-4 mr-2"></i> Keine überfälligen Aufgaben - Gut
                            gemacht!
                        </p>
                    <?php else: ?>
                        <?php foreach ($data['todos_overdue'] as $todo): ?>
                            <div
                                class="flex items-center justify-between p-2 hover:bg-white/50 rounded-lg transition-all border border-transparent hover:border-gray-100 group">
                                <div class="flex-1">
                                    <span class="text-gray-700 font-medium">
                                        <?php echo htmlspecialchars($todo['text']); ?>
                                    </span>
                                    <div class="text-xs text-red-500 mt-0.5 flex items-center">
                                        <i data-lucide="alert-circle" class="w-3 h-3 mr-1 inline"></i>
                                        Fällig: <?= date('d.m.Y', strtotime($todo['due_date'])) ?>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="p-1 hover:text-secondary transition-colors task-action"
                                        data-action="complete" data-id="<?= $todo['id'] ?>" title="Erledigt">
                                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                                    </button>
                                    <button class="p-1 hover:text-primary transition-colors task-action" data-action="delete"
                                        data-id="<?= $todo['id'] ?>" title="Löschen">
                                        <i data-lucide="x-circle" class="w-6 h-6"></i>
                                    </button>
                                    <button class="p-1 hover:text-gray-400 transition-colors task-action"
                                        data-action="reschedule" data-id="<?= $todo['id'] ?>" title="Verschieben">
                                        <i data-lucide="rotate-ccw" class="w-6 h-6"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- MEINE ZIELE -->
            <section class="glass-card p-6 doodle-container relative">
                <div class="mb-6 relative z-10">
                    <h3 class="text-xl font-bold font-display text-gray-800">Meine Ziele</h3>
                    <?php
                    $metricNames = [
                        'conversions' => 'Neue Kunden & Partner',
                        'new_contacts' => 'Erstellte Kontakte',
                        'new_customers' => 'Gewonnene Kunden',
                        'new_partners' => 'Neue Partnerinnen',
                        'tasks_done' => 'Erledigte Aufgaben'
                    ];
                    $currentMetric = $metricNames[$data['goals']['metric'] ?? 'conversions'] ?? 'Ziele';
                    ?>
                    <p class="text-xs text-gray-500 font-medium mt-1">Fokus: <span
                            class="text-primary"><?= $currentMetric ?></span></p>
                </div>

                <?php echo getDoodle('Sterne', 'doodle doodle-yellow w-14 h-14 -top-3 -right-3 -rotate-12 opacity-20'); ?>

                <?php
                if (!isset($data['goals'])) {
                    $goals = ['metric' => 'conversions', 'month_current' => 0, 'month_target' => 10, 'year_current' => 0, 'year_target' => 100];
                } else {
                    $goals = $data['goals'];
                }

                // Helper for circle progress
                $r = 36;
                $circumference = 2 * M_PI * $r; // ~226
                $monthPct = $goals['month_target'] > 0 ? min(1, $goals['month_current'] / $goals['month_target']) : 0;
                $yearPct = $goals['year_target'] > 0 ? min(1, $goals['year_current'] / $goals['year_target']) : 0;

                $monthOffset = $circumference * (1 - $monthPct);
                $yearOffset = $circumference * (1 - $yearPct);
                ?>

                <div class="flex justify-around items-center relative z-10">
                    <!-- MONTH -->
                    <div class="text-center group">
                        <p class="text-xs text-gray-400 mb-3 font-bold uppercase tracking-wider">Monat</p>
                        <div
                            class="progress-circle mx-auto group-hover:scale-105 transition-transform duration-300 relative">
                            <span
                                class="font-bold text-lg font-display text-gray-700 absolute inset-0 flex items-center justify-center">
                                <?= $goals['month_current'] ?>/<?= $goals['month_target'] ?>
                            </span>
                            <svg width="80" height="80" class="transform -rotate-90">
                                <circle cx="40" cy="40" r="36" fill="transparent" stroke="#f3f4f6" stroke-width="6">
                                </circle>
                                <circle cx="40" cy="40" r="36" fill="transparent" stroke="#FF6B6B" stroke-width="6"
                                    stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $monthOffset ?>"
                                    stroke-linecap="round" class="drop-shadow-md transition-all duration-1000 ease-out">
                                </circle>
                            </svg>
                        </div>
                    </div>

                    <div class="h-16 w-px bg-gray-100"></div>

                    <!-- YEAR -->
                    <div class="text-center group">
                        <p class="text-xs text-gray-400 mb-3 font-bold uppercase tracking-wider">Jahr</p>
                        <div
                            class="progress-circle mx-auto group-hover:scale-105 transition-transform duration-300 relative">
                            <span
                                class="font-bold text-lg font-display text-gray-700 absolute inset-0 flex items-center justify-center">
                                <?= $goals['year_current'] ?>/<?= $goals['year_target'] ?>
                            </span>
                            <svg width="80" height="80" class="transform -rotate-90">
                                <circle cx="40" cy="40" r="36" fill="transparent" stroke="#f3f4f6" stroke-width="6">
                                </circle>
                                <circle cx="40" cy="40" r="36" fill="transparent" stroke="#4ECDC4" stroke-width="6"
                                    stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $yearOffset ?>"
                                    stroke-linecap="round" class="drop-shadow-md transition-all duration-1000 ease-out">
                                </circle>
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- GEBURTSTAGE & VERNACHLÄSSIGTE -->
            <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- BIRTHDAYS -->
                <div class="glass-card p-4 relative overflow-hidden group doodle-container">
                    <div class="absolute top-0 right-0 p-2 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="gift" class="w-12 h-12 text-primary"></i>
                    </div>
                    <?php echo getDoodle('Krone', 'doodle doodle-yellow w-10 h-10 -bottom-2 -right-2 -rotate-12 opacity-10'); ?>
                    <h4 class="font-bold text-sm mb-3 flex items-center font-display">
                        <i data-lucide="gift" class="w-4 h-4 mr-1 text-primary"></i> Geburtstage
                    </h4>

                    <?php if (empty($geburtstage)): ?>
                        <div class="flex flex-col items-center justify-center h-24 text-center">
                            <span class="text-xs text-gray-400 mb-1">Keine anstehenden Geburtstage.</span>
                            <a href="index.php?page=contacts" class="text-[10px] text-secondary hover:underline">
                                Jetzt Geburtstagsdaten ergänzen
                            </a>
                        </div>
                    <?php else: ?>
                        <ul class="text-xs space-y-3 relative z-10 w-full">
                            <?php foreach ($geburtstage as $gb): ?>
                                <li class="flex items-center space-x-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold shadow-sm shrink-0">
                                        <?php echo substr($gb['name'], 0, 1); ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-800 truncate">
                                            <?php echo htmlspecialchars($gb['name']); ?>
                                        </p>
                                        <p class="text-gray-400">
                                            <?php echo htmlspecialchars($gb['datum']); ?>
                                            (<?php echo htmlspecialchars($gb['alter']); ?>)
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <!-- NEGLECTED -->
                <div
                    class="glass-card p-4 border-l-4 border-red-300 relative overflow-hidden flex flex-col doodle-container">
                    <?php echo getDoodle('Ausrufezeichen', 'doodle doodle-coral w-12 h-12 -top-2 -right-2 rotate-12 opacity-10'); ?>
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-sm text-red-600 font-display flex items-center">
                            <i data-lucide="alert-circle" class="w-4 h-4 mr-1"></i> Vernachlässigt
                        </h4>
                        <button onclick="window.location.reload();"
                            class="p-1 text-red-300 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
                            title="Neu würfeln">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                        </button>
                    </div>

                    <?php if (!empty($vernachlaessigt)): ?>
                        <p class="text-[10px] text-gray-400 mb-2 italic">Zufällige Auswahl (>
                            <?= $data['neglect_months'] ?? 8 ?> Monate inaktiv)
                        </p>
                    <?php endif; ?>

                    <?php if (empty($vernachlaessigt)): ?>
                        <div class="flex flex-col items-center justify-center flex-1 text-center py-4">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mb-2">
                                <i data-lucide="thumbs-up" class="w-4 h-4 text-green-600"></i>
                            </div>
                            <span class="text-xs text-gray-500 font-medium">Alles im grünen Bereich!</span>
                            <span class="text-[10px] text-gray-400 mt-0.5">Keine vernachlässigten Kontakte.</span>
                        </div>
                    <?php else: ?>
                        <?php
                        // Take first one as HERO
                        $hero = $vernachlaessigt[0];
                        $others = array_slice($vernachlaessigt, 1, 2); // Max 2 others
                        ?>

                        <!-- Hero Contact -->
                        <a href="index.php?page=contact_detail&id=<?= $hero['id'] ?>"
                            class="bg-red-50 rounded-xl p-3 mb-3 hover:bg-red-100 transition-all cursor-pointer group flex items-start gap-3 relative no-underline">
                            <div
                                class="w-10 h-10 rounded-full bg-white text-red-500 font-bold flex items-center justify-center shadow-sm text-lg border border-red-100 shrink-0">
                                <?= substr($hero['name'], 0, 1) ?>
                            </div>
                            <div class="min-w-0 overflow-hidden">
                                <p class="font-bold text-gray-800 truncate group-hover:text-red-700 transition-colors">
                                    <?= htmlspecialchars($hero['name']) ?>
                                </p>
                                <p class="text-[10px] text-red-400 font-medium">Lange nicht gemeldet!</p>
                            </div>
                            <i data-lucide="chevron-right"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-red-300 opacity-0 group-hover:opacity-100 transition-all"></i>
                        </a>

                        <!-- Small List -->
                        <ul class="space-y-2 relative z-10 flex-1">
                            <?php foreach ($others as $other): ?>
                                <li class="flex items-center group cursor-pointer p-1 hover:bg-red-50 rounded transition-colors"
                                    onclick="window.location='index.php?page=contact_detail&id=<?= $other['id'] ?>'">
                                    <div
                                        class="w-5 h-5 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-[10px] font-bold mr-2 group-hover:bg-white group-hover:text-red-500 transition-colors">
                                        <?= substr($other['name'], 0, 1) ?>
                                    </div>
                                    <span
                                        class="text-xs text-gray-600 group-hover:text-red-600 transition-colors truncate max-w-[120px]"><?php echo htmlspecialchars($other['name']); ?></span>
                                    <i data-lucide="chevron-right"
                                        class="w-3 h-3 ml-auto text-gray-300 opacity-0 group-hover:opacity-100 transition-all"></i>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>

            <!-- SCHNELLAKTIONEN -->
            <section class="glass-card p-6 md:col-span-2 doodle-container relative">
                <h3 class="text-xl font-bold mb-6 relative z-10 font-display">Schnellaktionen</h3>
                <?php echo getDoodle('Blitz', 'doodle doodle-yellow w-12 h-12 top-4 right-8 rotate-12'); ?>
                <?php echo getDoodle('Herz2', 'doodle doodle-coral w-10 h-10 bottom-4 left-8 -rotate-12 opacity-10'); ?>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <!-- Neuer Kontakt -->
                    <button onclick="window.location='index.php?page=contact_create'"
                        class="flex flex-col items-center group">
                        <div
                            class="w-16 h-16 rounded-2xl bg-secondary text-white flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:shadow-xl transition-all duration-300 mb-3 relative overflow-hidden">
                            <i data-lucide="user-plus" class="w-8 h-8 relative z-10"></i>
                            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity">
                            </div>
                        </div>
                        <span
                            class="text-xs md:text-sm font-semibold text-gray-700 group-hover:text-secondary transition-colors text-center">Neuer
                            Kontakt</span>
                    </button>

                    <!-- Neue Aufgabe -->
                    <button onclick="window.location='index.php?page=task_create&redirect_to=index.php?page=dashboard'"
                        class="flex flex-col items-center group">
                        <div
                            class="w-16 h-16 rounded-2xl bg-highlight text-white flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:shadow-xl transition-all duration-300 mb-3 relative overflow-hidden">
                            <i data-lucide="check-square" class="w-8 h-8 relative z-10"></i>
                            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity">
                            </div>
                        </div>
                        <span
                            class="text-xs md:text-sm font-semibold text-gray-700 group-hover:text-highlight transition-colors text-center">Neue
                            Aufgabe</span>
                    </button>

                    <!-- Kalender -->
                    <button onclick="window.location='index.php?page=calendar'"
                        class="flex flex-col items-center group">
                        <div
                            class="w-16 h-16 rounded-2xl bg-blue-500 text-white flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:shadow-xl transition-all duration-300 mb-3 relative overflow-hidden">
                            <i data-lucide="calendar" class="w-8 h-8 relative z-10"></i>
                            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity">
                            </div>
                        </div>
                        <span
                            class="text-xs md:text-sm font-semibold text-gray-700 group-hover:text-blue-500 transition-colors text-center">Kalender</span>
                    </button>

                    <!-- Automation -->
                    <button onclick="window.location='index.php?page=automation'"
                        class="flex flex-col items-center group">
                        <div
                            class="w-16 h-16 rounded-2xl bg-primary text-white flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:shadow-xl transition-all duration-300 mb-3 relative overflow-hidden">
                            <i data-lucide="zap" class="w-8 h-8 relative z-10"></i>
                            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity">
                            </div>
                        </div>
                        <span
                            class="text-xs md:text-sm font-semibold text-gray-700 group-hover:text-primary transition-colors text-center">Automation</span>
                    </button>
                </div>
            </section>

        </div>
    </main>

    <script src="assets/js/dashboard.js"></script>
    <?php include __DIR__ . '/layout/footer.php'; ?>