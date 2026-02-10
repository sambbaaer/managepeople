<?php
// templates/search/results.php
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
            <h1 class="text-3xl font-bold font-display text-gray-800">Suche</h1>

            <form action="index.php" method="GET" class="mt-4 relative max-w-xl">
                <input type="hidden" name="page" value="search">
                <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Suchen..." autofocus
                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-secondary outline-none text-lg">
                <i data-lucide="search" class="absolute left-4 top-3.5 w-6 h-6 text-gray-400"></i>
            </form>
        </header>

        <?php if (empty($query)): ?>
            <div class="text-center py-20 text-gray-400">
                <i data-lucide="search" class="w-16 h-16 mx-auto mb-4 opacity-20"></i>
                <p>Gib einen Suchbegriff ein, um Kontakte oder Aufgaben zu finden.</p>
            </div>
        <?php else: ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Contacts Results -->
                <div>
                    <h2 class="font-bold text-gray-700 mb-4 flex items-center">
                        <i data-lucide="users" class="w-5 h-5 mr-2 text-primary"></i> Kontakte (
                        <?= count($contacts) ?>)
                    </h2>

                    <?php if (empty($contacts)): ?>
                        <p class="text-gray-400 text-sm italic">Keine Kontakte gefunden.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($contacts as $contact): ?>
                                <a href="index.php?page=contact_detail&id=<?= $contact['id'] ?>"
                                    class="block glass-card p-4 hover:border-primary/30 transition-colors group">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold mr-3 group-hover:bg-primary group-hover:text-white transition-colors">
                                            <?= substr($contact['name'], 0, 1) ?>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($contact['name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= htmlspecialchars($contact['status']) ?> •
                                                <?= htmlspecialchars($contact['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tasks Results -->
                <div>
                    <h2 class="font-bold text-gray-700 mb-4 flex items-center">
                        <i data-lucide="check-square" class="w-5 h-5 mr-2 text-secondary"></i> Aufgaben (
                        <?= count($tasks) ?>)
                    </h2>

                    <?php if (empty($tasks)): ?>
                        <p class="text-gray-400 text-sm italic">Keine Aufgaben gefunden.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($tasks as $task): ?>
                                <div class="glass-card p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start">
                                        <div
                                            class="mt-1 mr-3 w-4 h-4 border-2 <?= $task['completed'] ? 'bg-secondary border-secondary' : 'border-gray-300' ?> rounded flex items-center justify-center">
                                            <?php if ($task['completed']): ?><i data-lucide="check" class="w-3 h-3 text-white"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h4
                                                class="font-medium <?= $task['completed'] ? 'text-gray-400 line-through' : 'text-gray-800' ?>">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </h4>
                                            <?php if ($task['contact_name']): ?>
                                                <p class="text-xs text-primary mt-1">
                                                    <a href="index.php?page=contact_detail&id=<?= $task['contact_id'] ?>"
                                                        class="hover:underline">
                                                        <i data-lucide="user" class="w-3 h-3 inline"></i>
                                                        <?= htmlspecialchars($task['contact_name']) ?>
                                                    </a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        <?php endif; ?>

    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>