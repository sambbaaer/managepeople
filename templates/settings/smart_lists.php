<?php
$smartLists = $data['smart_lists'] ?? [];
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex text-slate-800 font-sans">

    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto mb-16 md:mb-0">
        <header class="mb-8 flex items-center gap-4">
            <button onclick="history.back()" class="p-2 hover:bg-white rounded-full transition-colors text-gray-500">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </button>
            <h1 class="text-3xl font-bold font-display text-gray-800">Smart Listen verwalten</h1>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- LIST OF EXISTING SMART LISTS -->
            <section class="glass-card p-6 relative">
                <h2 class="text-xl font-bold mb-4 font-display">Deine Listen</h2>
                <?php if (empty($smartLists)): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i data-lucide="filter" class="w-12 h-12 mx-auto mb-2 opacity-20"></i>
                        <p>Noch keine Smart Listen erstellt.</p>
                    </div>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($smartLists as $list):
                            $criteria = json_decode($list['filter_criteria'], true);
                            $desc = [];
                            if (isset($criteria['status']))
                                $desc[] = "Status: " . htmlspecialchars($criteria['status']);
                            if (isset($criteria['birthday_month']) && $criteria['birthday_month'] == 'current')
                                $desc[] = "Geburtstag diesen Monat";
                            ?>
                            <li
                                class="flex items-center justify-between p-3 bg-white/50 rounded-xl border border-gray-100 group">
                                <div>
                                    <p class="font-bold text-gray-800">
                                        <?= htmlspecialchars($list['name']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= implode(', ', $desc) ?>
                                    </p>
                                </div>
                                <a href="index.php?page=settings_smart_lists_delete&id=<?= $list['id'] ?>"
                                    class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                                    onclick="return confirm('Wirklich löschen?')">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <!-- CREATE NEW LIST FORM -->
            <section class="glass-card p-6 relative doodle-container">
                <h2 class="text-xl font-bold mb-6 font-display">Neue Liste erstellen</h2>
                <?php echo getDoodle('Liste', 'doodle doodle-teal w-16 h-16 -top-4 -right-2 rotate-12 opacity-10'); ?>

                <form action="index.php?page=settings_smart_lists_create" method="POST" class="space-y-4 relative z-10">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name der Liste</label>
                        <input type="text" name="name" required placeholder="z.B. Heiße Leads"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white/80">
                    </div>

                    <div class="p-4 bg-gray-50/50 rounded-xl border border-gray-100 space-y-4">
                        <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">Kriterien (UND-Verknüpft)
                        </p>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                                <option value="">- Egal -</option>
                                <option value="Offen">Offen</option>
                                <option value="Interessent">Interessent</option>
                                <option value="Kundin">Kundin</option>
                                <option value="Partnerin">Partnerin</option>
                                <option value="Stillgelegt">Stillgelegt</option>
                            </select>
                        </div>

                        <!-- Birthday Filter -->
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="birthday_month" value="current" id="cb_birthday"
                                class="rounded text-primary focus:ring-primary">
                            <label for="cb_birthday" class="text-sm text-gray-700">Hat diesen Monat Geburtstag</label>
                        </div>

                        <!-- Neglected Filter -->
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" name="neglected" value="1" id="cb_neglected"
                                class="rounded text-primary focus:ring-primary">
                            <label for="cb_neglected" class="text-sm text-gray-700">Seit > 8 Monaten nicht
                                gemeldet</label>
                        </div>

                        <!-- Search Term (e.g. for Tag or specific word) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Enthält Suchbegriff</label>
                            <input type="text" name="search_term" placeholder="z.B. Tag:VIP oder Name"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-primary text-white py-2 rounded-lg font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                        Liste speichern
                    </button>
                </form>
            </section>

        </div>
    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>