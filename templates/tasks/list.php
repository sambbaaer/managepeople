<?php
// templates/tasks/list.php
$showCompleted = $_GET['completed'] ?? 0;
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
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold font-display text-gray-800 relative z-10 w-max">
                    Aufgaben
                    <?php echo getDoodle('Aufgaben', 'doodle doodle-coral w-12 h-12 -top-5 -right-8 -rotate-6 opacity-60 hidden md:block'); ?>
                </h1>
                <div class="flex items-center mt-2 text-sm text-gray-500 space-x-4">
                    <a href="index.php?page=tasks&completed=0"
                        class="<?= !$showCompleted ? 'text-primary font-bold' : 'hover:text-gray-700' ?>">Offen</a>
                    <a href="index.php?page=tasks&completed=1"
                        class="<?= $showCompleted ? 'text-primary font-bold' : 'hover:text-gray-700' ?>">Erledigt</a>
                </div>
            </div>

            <a href="index.php?page=task_create&redirect_to=<?= urlencode('index.php?page=tasks') ?>"
                class="bg-secondary hover:bg-teal-500 text-white px-4 py-2 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all flex items-center justify-center w-full md:w-auto">
                <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Neue Aufgabe
            </a>
        </header>

        <!-- Task List -->
        <div class="space-y-3">
            <?php if (empty($tasks)): ?>
                <div
                    class="text-center py-12 bg-white rounded-2xl shadow-sm border border-gray-100 doodle-container relative overflow-hidden">
                    <?php echo getDoodle('Sterne', 'doodle doodle-yellow w-16 h-16 top-4 right-12 rotate-12 opacity-20'); ?>
                    <div class="inline-block p-4 rounded-full bg-gray-50 mb-4 relative z-10">
                        <i data-lucide="check-circle" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 relative z-10">Alles erledigt!</h3>
                    <p class="text-gray-500 relative z-10">Du hast keine Aufgaben in dieser Ansicht.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <div class="glass-card p-4 flex items-center gap-4 group hover:shadow-md transition-all">
                        <!-- Checkbox -->
                        <a href="index.php?page=task_toggle&id=<?= $task['id'] ?>" class="flex-shrink-0">
                            <div
                                class="w-6 h-6 border-2 <?= $task['completed'] ? 'bg-secondary border-secondary' : 'border-gray-300 group-hover:border-secondary' ?> rounded-lg flex items-center justify-center transition-colors">
                                <?php if ($task['completed']): ?>
                                    <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                <?php endif; ?>
                            </div>
                        </a>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <h4
                                class="font-medium <?= $task['completed'] ? 'text-gray-400 line-through' : 'text-gray-800' ?> truncate">
                                <?= htmlspecialchars($task['title']) ?>
                            </h4>
                            <div class="flex items-center text-xs text-gray-500 mt-1 space-x-3">
                                <?php if (!empty($task['contact_name'])): ?>
                                    <span class="flex items-center text-primary">
                                        <i data-lucide="user" class="w-3 h-3 mr-1"></i>
                                        <?= htmlspecialchars($task['contact_name']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($task['due_date'])): ?>
                                    <span
                                        class="flex items-center <?= (strtotime($task['due_date']) < time() && !$task['completed']) ? 'text-red-500 font-bold' : '' ?>">
                                        <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                        <?= date('d.m.', strtotime($task['due_date'])) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($task['priority'] === 'high'): ?>
                                    <span class="text-red-500 font-bold">!!! Hoch</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Delete Action -->
                        <a href="index.php?page=task_delete&id=<?= $task['id'] ?>"
                            class="p-2 text-gray-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100"
                            onclick="return confirm('Wirklich löschen?')">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>