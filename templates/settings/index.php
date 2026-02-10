<?php
// templates/settings/index.php
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
        <header class="mb-8">
            <h1 class="text-3xl font-bold font-display text-gray-800">Einstellungen</h1>
            <p class="text-gray-500">System konfigurieren</p>
        </header>

        <!-- Messages -->
        <?php if ($success === 'saved'): ?>
            <div
                class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center shadow-sm border border-green-200">
                <i data-lucide="check-circle" class="mr-2"></i> Einstellungen gespeichert.
            </div>
        <?php elseif ($success === 'pw_changed'): ?>
            <div
                class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center shadow-sm border border-green-200">
                <i data-lucide="check-circle" class="mr-2"></i> Passwort erfolgreich geändert.
            </div>
        <?php elseif ($success === 'backup_created'): ?>
            <div class="bg-blue-100 text-blue-700 p-4 rounded-xl mb-6 flex items-center shadow-sm border border-blue-200">
                <i data-lucide="database" class="mr-2"></i> Backup erfolgreich erstellt.
            </div>
        <?php elseif ($_GET['msg'] ?? '' === 'products_imported'): ?>
            <div
                class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center shadow-sm border border-green-200">
                <i data-lucide="package-check" class="mr-2"></i> <?= htmlspecialchars($_GET['count'] ?? 0) ?> Produkte
                importiert/aktualisiert.
            </div>
        <?php elseif ($success === 'mentor_created'): ?>
            <div
                class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center shadow-sm border border-green-200">
                <i data-lucide="user-plus" class="mr-2"></i> Mentorin erfolgreich angelegt und Einladung gesendet.
            </div>
        <?php elseif ($success === 'mentor_deleted'): ?>
            <div
                class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center shadow-sm border border-green-200">
                <i data-lucide="user-minus" class="mr-2"></i> Mentorin erfolgreich entfernt.
            </div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 flex items-center shadow-sm border border-red-200">
                <i data-lucide="alert-circle" class="mr-2"></i>
                <?php
                switch ($error) {
                    case 'wrong_pw':
                        echo "Aktuelles Passwort falsch.";
                        break;
                    case 'mismatch':
                        echo "Passwörter stimmen nicht überein.";
                        break;
                    case 'weak_pw':
                        echo "Passwort zu schwach (min 8 Zeichen, Großbuchstabe, Zahl, Sonderzeichen).";
                        break;
                    case 'mentor_fields_missing':
                        echo "Bitte Name und E-Mail für die Mentorin angeben.";
                        break;
                    case 'delete_failed':
                        echo "Löschen fehlgeschlagen.";
                        break;
                    default:
                        echo htmlspecialchars(urldecode($error));
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- GOALS -->
            <section class="glass-card p-6 doodle-container relative">
                <h3 class="text-xl font-bold mb-6 relative z-10 font-display flex items-center">
                    <i data-lucide="target" class="w-6 h-6 mr-2 text-primary"></i> Ziele definieren
                </h3>
                <?php echo getDoodle('Sterne', 'doodle doodle-yellow w-16 h-16 -top-2 -right-2 rotate-12 opacity-10'); ?>

                <form action="index.php?page=settings_update" method="POST" class="space-y-6 relative z-10">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Was soll gezählt werden?</label>
                        <select name="goal_metric"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                            <?php
                            $metric = $settings['goal_metric'] ?? 'conversions';
                            $options = [
                                'conversions' => 'Neue Kunden / Partner (Statuswechsel)',
                                'new_contacts' => 'Neu erstellte Kontakte',
                                'new_customers' => 'Nur neue Kunden',
                                'new_partners' => 'Nur neue Partner',
                                'tasks_done' => 'Erledigte Aufgaben'
                            ];
                            foreach ($options as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $metric === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monatsziel (Zähler gemäss
                            Auswahl)</label>
                        <input type="number" name="goal_month"
                            value="<?= htmlspecialchars($settings['goal_month'] ?? '10') ?>"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jahresziel</label>
                        <input type="number" name="goal_year"
                            value="<?= htmlspecialchars($settings['goal_year'] ?? '100') ?>"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i data-lucide="clock" class="w-4 h-4 inline mr-1 text-red-400"></i> "Vernachlässigt"
                            Zeitspanne (Monate)
                        </label>
                        <input type="number" name="neglect_months" min="1" max="60"
                            value="<?= htmlspecialchars($settings['neglect_months'] ?? '8') ?>"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        <p class="text-[10px] text-gray-400 mt-1">Kontakte ohne Aktivität länger als X Monate gelten als
                            vernachlässigt.</p>
                    </div>

                    <button type="submit"
                        class="bg-gray-800 text-white px-6 py-2 rounded-xl font-bold hover:bg-black transition-colors w-full md:w-auto">
                        Speichern
                    </button>
                </form>
            </section>

            <!-- Smart Lists -->
            <div class="glass-card p-6 relative group cursor-pointer hover:shadow-md transition-shadow doodle-container"
                onclick="window.location='index.php?page=settings_smart_lists'">
                <div class="flex items-center space-x-4 relative z-10">
                    <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center text-teal-600">
                        <i data-lucide="filter" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Smart Listen (Sidebar)</h3>
                        <p class="text-sm text-gray-500">Filter für Kontakte erstellen & verwalten</p>
                    </div>
                    <i data-lucide="chevron-right"
                        class="w-5 h-5 text-gray-300 ml-auto group-hover:text-primary transition-colors"></i>
                </div>
            </div>

            <!-- BACKUP & DATA -->
            <section class="glass-card p-6 doodle-container relative border-t-4 border-secondary">
                <h3 class="text-xl font-bold mb-6 relative z-10 font-display flex items-center">
                    <i data-lucide="database" class="w-6 h-6 mr-2 text-secondary"></i> Daten & Backup
                </h3>
                <?php echo getDoodle('Cloud', 'doodle doodle-teal w-20 h-20 -bottom-4 -left-4 opacity-10'); ?>

                <!-- Auto Backup Toggle -->
                <form action="index.php?page=settings_update" method="POST" class="mb-8 relative z-10">
                    <div class="flex items-center justify-between bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <span class="font-bold text-gray-800 block">Automatisches Backup</span>
                            <span class="text-xs text-gray-500">Wöchentlich, speichert die letzen 3 Versionen.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="backup_enabled" value="1" class="sr-only peer"
                                onchange="this.form.submit()" <?= ($settings['backup_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary">
                            </div>
                        </label>
                    </div>
                </form>

                <div class="relative z-10 space-y-4">
                    <h4 class="font-bold text-gray-600 text-sm uppercase tracking-wide">Aktionen</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <a href="index.php?page=settings_trigger_backup"
                            class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors text-blue-700 cursor-pointer">
                            <i data-lucide="save" class="w-6 h-6 mb-2"></i>
                            <span class="text-xs font-bold">Backup jetzt</span>
                        </a>
                        <a href="index.php?page=export_contacts"
                            class="flex flex-col items-center justify-center p-4 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors text-purple-700 cursor-pointer">
                            <i data-lucide="download" class="w-6 h-6 mb-2"></i>
                            <span class="text-xs font-bold">CSV-Export</span>
                        </a>
                        <form action="index.php?page=settings_import_products" method="POST"
                            enctype="multipart/form-data" class="flex flex-col">
                            <label
                                class="flex flex-col items-center justify-center p-4 bg-green-50 hover:bg-green-100 rounded-xl transition-colors text-green-700 cursor-pointer h-full">
                                <i data-lucide="package-plus" class="w-6 h-6 mb-2"></i>
                                <span class="text-xs font-bold">Produkte JSON laden</span>
                                <input type="file" name="product_file" accept=".json" class="hidden"
                                    onchange="this.form.submit()">
                            </label>
                        </form>
                    </div>
                </div>

                <!-- List of backups -->
                <?php if (!empty($backupList)): ?>
                    <div class="mt-8 relative z-10">
                        <h4 class="font-bold text-gray-600 text-sm uppercase tracking-wide mb-2">Vorhandene Backups</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <?php foreach ($backupList as $bk): ?>
                                <li class="flex justify-between border-b border-gray-100 pb-1">
                                    <span>
                                        <?= $bk['date'] ?>
                                    </span>
                                    <span class="text-gray-400">
                                        <?= $bk['size'] ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </section>

            <!-- PASSWORD -->
            <section class="glass-card p-6 doodle-container relative">
                <h3 class="text-xl font-bold mb-6 relative z-10 font-display flex items-center">
                    <i data-lucide="shield-check" class="w-6 h-6 mr-2 text-primary"></i> Passwort ändern
                </h3>
                <?php echo getDoodle('Schloss', 'doodle doodle-coral w-12 h-12 top-4 right-4 rotate-12 opacity-10'); ?>

                <form action="index.php?page=settings_password" method="POST" class="space-y-4 relative z-10">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Aktuelles Passwort</label>
                        <input type="password" name="password_current" required
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Neues Passwort</label>
                        <input type="password" name="password_new" required
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        <p class="text-[10px] text-gray-400 mt-1">Min. 8 Zeichen, Großbuchstabe, Zahl, Sonderzeichen</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Wiederholen</label>
                        <input type="password" name="password_repeat" required
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>

                    <button type="submit"
                        class="w-full bg-primary hover:bg-red-500 text-white px-6 py-2 rounded-xl font-bold shadow-lg transition-all">
                        Passwort ändern
                    </button>
                </form>
            </section>

            <!-- MENTOREN VERWALTUNG -->
            <section class="glass-card p-6 doodle-container relative lg:col-span-2">
                <h3 class="text-xl font-bold mb-6 relative z-10 font-display flex items-center">
                    <i data-lucide="users" class="w-6 h-6 mr-2 text-indigo-500"></i> Mentoren (Lese-Logins)
                </h3>
                <?php echo getDoodle('Herz', 'doodle doodle-coral w-12 h-12 -top-2 -right-4 rotate-12 opacity-10'); ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative z-10">
                    <!-- Formular -->
                    <div class="md:col-span-1">
                        <h4 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wide">Neue Mentorin einladen
                        </h4>
                        <form action="index.php?page=settings_mentor_add" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Name</label>
                                <input type="text" name="name" required placeholder="Vorname Nachname"
                                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">E-Mail</label>
                                <input type="email" name="email" required placeholder="mail@beispiel.ch"
                                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <button type="submit"
                                class="w-full bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold shadow-md transition-all flex items-center justify-center">
                                <i data-lucide="send" class="w-4 h-4 mr-2"></i> Einladung senden
                            </button>
                        </form>
                        <p class="text-[10px] text-gray-400 mt-4 leading-normal">
                            Die Mentorin erhält eine E-Mail mit einem Link, um ein Passwort zu setzen.
                            Sie hat ausschliesslich Leserechte auf alle Daten der Applikation.
                        </p>
                    </div>

                    <!-- Liste der Mentoren -->
                    <div class="md:col-span-2">
                        <h4 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wide">Aktive Mentoren</h4>
                        <div class="bg-gray-50 rounded-xl border border-gray-100 overflow-hidden">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-100 text-gray-600 text-[10px] uppercase font-bold">
                                    <tr>
                                        <th class="px-4 py-2">Name</th>
                                        <th class="px-4 py-2">E-Mail</th>
                                        <th class="px-4 py-2">Erstellt am</th>
                                        <th class="px-4 py-2 text-right">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (empty($mentors)): ?>
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                                Noch keine Mentoren angelegt.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($mentors as $m): ?>
                                            <tr class="hover:bg-white transition-colors">
                                                <td class="px-4 py-3 font-semibold text-gray-800"><?= h($m['name']) ?></td>
                                                <td class="px-4 py-3 text-gray-500"><?= h($m['email']) ?></td>
                                                <td class="px-4 py-3 text-gray-400 text-xs">
                                                    <?= date('d.m.Y', strtotime($m['created_at'])) ?>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <a href="index.php?page=settings_mentor_delete&id=<?= $m['id'] ?>"
                                                        onclick="return confirm('Möchtest du dieses Mentor-Login wirklich löschen?')"
                                                        class="text-red-400 hover:text-red-600 p-1 transition-colors"
                                                        title="Löschen">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Login Logs -->
                        <h4 class="font-bold text-gray-700 mt-8 mb-4 text-sm uppercase tracking-wide">Login-Aktivität
                        </h4>
                        <div
                            class="bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm max-h-60 overflow-y-auto">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="bg-indigo-50 text-indigo-700 text-[10px] uppercase font-bold sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 border-b border-indigo-100">Mentor</th>
                                        <th class="px-4 py-2 border-b border-indigo-100">E-Mail</th>
                                        <th class="px-4 py-2 border-b border-indigo-100">Zeitpunkt</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php if (empty($mentorLogs)): ?>
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-gray-400 italic">
                                                Noch keine Login-Aktivitäten verzeichnet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($mentorLogs as $log): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 font-medium"><?= h($log['mentor_name']) ?></td>
                                                <td class="px-4 py-2 text-gray-400"><?= h($log['mentor_email']) ?></td>
                                                <td class="px-4 py-2 text-gray-500">
                                                    <?= date('d.m.Y H:i:s', strtotime($log['logged_in_at'])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>