<?php
// Automation Template - basierend auf Dashboard-Design
$grouped_rules = $data['grouped_rules'] ?? [];
$automation_enabled = $data['automation_enabled'] ?? true;
$active_count = $data['active_count'] ?? 0;
$total_count = $data['total_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/layout/head.php'; ?>
    <style>
        /* Accordion Animation */
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .accordion-content.active {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            background-color: #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-switch.active {
            background-color: #4ECDC4;
        }

        .toggle-switch-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch.active .toggle-switch-slider {
            transform: translateX(20px);
        }

        /* Input Inline Edit */
        .inline-edit-input {
            width: 60px;
            text-align: center;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .inline-edit-input:hover {
            border-color: #4ECDC4;
        }

        .inline-edit-input:focus {
            outline: none;
            border-color: #4ECDC4;
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
        }
    </style>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- HEADER -->
        <header class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold font-display text-gray-800 flex items-center">
                    <i data-lucide="settings" class="mr-3 text-accent w-8 h-8"></i>
                    Automation
                </h1>
                <p class="text-gray-500 mt-1">
                    Automatische ToDo-Erstellung bei <span class="highlighter">Statuswechsel</span>
                </p>
            </div>

            <!-- Global Master Toggle -->
            <div class="glass-card px-4 py-3 flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700">Automation Global:</span>
                <div class="toggle-switch <?= $automation_enabled ? 'active' : '' ?>" id="global-toggle"
                    data-enabled="<?= $automation_enabled ? '1' : '0' ?>">
                    <div class="toggle-switch-slider"></div>
                </div>
                <span class="text-xs text-gray-500" id="global-status">
                    <?= $automation_enabled ? 'Aktiv' : 'Inaktiv' ?>
                </span>
            </div>
        </header>

        <!-- TABS -->
        <div class="flex gap-2 mb-8 border-b border-gray-200">
            <a href="index.php?page=automation"
                class="px-4 py-3 text-secondary font-medium border-b-2 border-secondary">
                Automatisierungen
            </a>
            <a href="index.php?page=workflows"
                class="px-4 py-3 text-gray-500 hover:text-gray-700 font-medium border-b-2 border-transparent">
                Abläufe
            </a>
        </div>

        <!-- STATISTIK -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card p-6 border-l-4 border-secondary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Aktive Regeln</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">
                            <?= $active_count ?>
                        </p>
                    </div>
                    <i data-lucide="check-circle-2" class="w-12 h-12 text-secondary opacity-20"></i>
                </div>
            </div>

            <div class="glass-card p-6 border-l-4 border-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Gesamt Regeln</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">
                            <?= $total_count ?>
                        </p>
                    </div>
                    <i data-lucide="list" class="w-12 h-12 text-primary opacity-20"></i>
                </div>
            </div>

            <div class="glass-card p-6 border-l-4 border-accent">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Status</p>
                        <p class="text-lg font-bold text-gray-800 mt-1">
                            <?= $automation_enabled ? '✅ Läuft' : '⏸️ Pausiert' ?>
                        </p>
                    </div>
                    <i data-lucide="activity" class="w-12 h-12 text-accent opacity-20"></i>
                </div>
            </div>
        </div>

        <!-- BENUTZERDEFINIERTE AUTOMATISIERUNGEN -->
        <section class="glass-card p-6 md:p-8 relative overflow-hidden mb-8">
            <div class="flex justify-between items-center mb-6 relative z-10">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold font-display text-gray-800">Benutzerdefinierte Automatisierungen</h2>
                    <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full font-medium">Neu</span>
                </div>
                <div class="flex gap-2">
                    <button onclick="document.getElementById('import-rule-input').click()"
                       class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        Import
                    </button>
                    <input type="file" id="import-rule-input" class="hidden" accept=".json" onchange="importRule(this)">

                    <a href="index.php?page=automation_edit_rule"
                       class="bg-secondary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary-dark transition-colors flex items-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Neue Regel
                    </a>
                </div>
                <?php echo getDoodle('Sterne', 'doodle doodle-yellow w-12 h-12 -top-2 -right-4 rotate-12 opacity-10'); ?>
            </div>

            <div class="space-y-3 relative z-10">
                <?php if (empty($data['custom_rules'] ?? [])): ?>
                    <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <i data-lucide="sparkles" class="w-10 h-10 text-gray-300 mx-auto mb-3"></i>
                        <p class="text-gray-500 font-medium">Noch keine eigenen Regeln erstellt</p>
                        <p class="text-sm text-gray-400 mb-4">Erstelle deine erste Automatisierung mit Wenn-Dann-Logik.</p>
                        <a href="index.php?page=automation_edit_rule"
                            class="text-secondary hover:underline text-sm font-medium">
                            Jetzt erstellen &rarr;
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($data['custom_rules'] as $rule): ?>
                            <div
                                class="bg-white p-5 rounded-xl border border-gray-200 hover:border-secondary transition-colors relative group">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($rule['name']) ?></h3>
                                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded-full">
                                            <?= htmlspecialchars($rule['trigger_status']) ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="index.php?page=automation_edit_rule&id=<?= $rule['id'] ?>"
                                           class="p-2 text-gray-400 hover:text-secondary hover:bg-secondary/10 rounded-lg transition-colors" title="Bearbeiten">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>

                                        <!-- Export Button -->
                                        <a href="index.php?page=automation_export_rule&id=<?= $rule['id'] ?>" target="_blank"
                                           class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Exportieren">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </a>
                                        <!-- Löschen Button -->
                                        <button onclick="window.deleteRule(<?= $rule['id'] ?>)"
                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Regel löschen">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Toggle Switch -->
                                        <div class="toggle-switch rule-toggle <?= $rule['is_enabled'] ? 'active' : '' ?>"
                                            data-rule-id="<?= $rule['id'] ?>">
                                            <div class="toggle-switch-slider"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 text-sm text-gray-500 mt-2">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="arrow-right-circle" class="w-4 h-4 text-gray-400"></i>
                                        <span>Trigger: <?= htmlspecialchars($rule['trigger_status']) ?></span>
                                    </div>
                                    <?php if ($rule['days_offset'] != 0): ?>
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                                            <span><?= $rule['days_offset'] > 0 ? '+' : '' ?><?= $rule['days_offset'] ?> Tage</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- VORDEFINIERTE AUTOMATISIERUNGEN -->
        <section class="glass-card p-6 md:p-8 relative overflow-hidden mb-8 opacity-90">
            <div class="flex justify-between items-center mb-6 relative z-10">
                <h2 class="text-2xl font-bold font-display text-gray-800">Vordefinierte Automatisierungen</h2>
                <?php echo getDoodle('Einstellungen', 'doodle doodle-purple w-14 h-14 -top-2 -right-4 rotate-12 opacity-10'); ?>
            </div>

            <div class="space-y-3 relative z-10">
                <?php
                $statusOrder = ['Interessent', 'Kundin', 'Partnerin', 'Stillgelegt'];
                $statusColors = [
                    'Interessent' => 'orange',
                    'Kundin' => 'green',
                    'Partnerin' => 'purple',
                    'Stillgelegt' => 'gray'
                ];

                foreach ($statusOrder as $status):
                    if (!isset($grouped_rules[$status]))
                        continue;
                    $rules = $grouped_rules[$status];
                    $color = $statusColors[$status];
                    ?>

                    <!-- Accordion Header -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                        <button
                            class="w-full px-5 py-4 flex items-center justify-between bg-white hover:bg-gray-50 transition-colors accordion-trigger"
                            data-target="accordion-<?= strtolower($status) ?>">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-<?= $color ?>-400"></div>
                                <span class="font-bold text-gray-800">
                                    <?= $status ?>
                                </span>
                                <span class="text-xs text-gray-400">
                                    (
                                    <?= count($rules) ?>
                                    <?= count($rules) == 1 ? 'Regel' : 'Regeln' ?>)
                                </span>
                            </div>
                            <i data-lucide="chevron-down"
                                class="w-5 h-5 text-gray-400 transition-transform accordion-icon"></i>
                        </button>

                        <!-- Accordion Content -->
                        <div class="accordion-content bg-gray-50" id="accordion-<?= strtolower($status) ?>">
                            <div class="px-5 py-4 space-y-4">
                                <?php foreach ($rules as $rule): ?>
                                    <div
                                        class="bg-white p-4 rounded-lg border border-gray-100 hover:border-<?= $color ?>-200 transition-colors">
                                        <!-- Regel Header -->
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="font-bold text-gray-800">
                                                        <?= htmlspecialchars($rule['name']) ?>
                                                    </h4>
                                                    <?php if ($rule['trigger_sub_status']): ?>
                                                        <span
                                                            class="text-xs px-2 py-1 bg-<?= $color ?>-100 text-<?= $color ?>-700 rounded-full">
                                                            <?= htmlspecialchars($rule['trigger_sub_status']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($rule['task_description']): ?>
                                                    <p class="text-xs text-gray-500">
                                                        <?= htmlspecialchars($rule['task_description']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Toggle Switch -->
                                            <div class="toggle-switch rule-toggle <?= $rule['is_enabled'] ? 'active' : '' ?>"
                                                data-rule-id="<?= $rule['id'] ?>">
                                                <div class="toggle-switch-slider"></div>
                                            </div>
                                        </div>

                                        <!-- Regel Details -->
                                        <div class="flex items-center gap-6 text-sm">
                                            <div class="flex items-center gap-2">
                                                <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                                                <span class="text-gray-600">Erstellt ToDo in:</span>
                                                <input type="number" min="0" step="1" class="inline-edit-input days-input"
                                                    value="<?= $rule['days_offset'] ?>" data-rule-id="<?= $rule['id'] ?>">
                                                <span class="text-gray-600">
                                                    <?= $rule['days_offset'] == 1 ? 'Tag' : 'Tagen' ?>
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <i data-lucide="clipboard-list" class="w-4 h-4 text-gray-400"></i>
                                                <span class="text-gray-600">ToDo:</span>
                                                <span class="font-medium text-gray-800">
                                                    «
                                                    <?= htmlspecialchars($rule['task_title']) ?>»
                                                </span>
                                            </div>

                                            <?php if ($rule['task_priority'] === 'high' || $rule['task_priority'] === 'urgent'): ?>
                                                <div class="flex items-center gap-1 text-red-600">
                                                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                                    <span class="text-xs font-semibold uppercase">
                                                        <?= $rule['task_priority'] === 'urgent' ? 'Dringend' : 'Hoch' ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/layout/footer.php'; ?>

    <!-- Automation JavaScript -->
    <script src="assets/js/automation.js"></script>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

</body>

</html>