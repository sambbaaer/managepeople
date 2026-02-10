<?php
// Workflow-Instanz-Details
$instance = $data['instance'] ?? null;

if (!$instance) {
    header('Location: index.php?page=workflows');
    exit;
}

$tasks = $instance['tasks'] ?? [];
$progress = $instance['progress'] ?? ['percent' => 0, 'completed' => 0, 'total' => 0];
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
    <style>
        .task-row {
            transition: all 0.2s;
        }

        .task-row.completed {
            opacity: 0.6;
        }

        .task-row.completed .task-title {
            text-decoration: line-through;
        }
    </style>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- HEADER -->
        <header class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <a href="index.php?page=workflows" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 text-gray-400"></i>
                </a>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold font-display text-gray-800 flex items-center gap-3">
                        <?= htmlspecialchars($instance['template_name']) ?>
                        <span class="text-sm font-normal px-3 py-1 rounded-full 
                            <?php if ($instance['status'] === 'active'): ?>
                                bg-green-100 text-green-700
                            <?php elseif ($instance['status'] === 'completed'): ?>
                                bg-blue-100 text-blue-700
                            <?php else: ?>
                                bg-gray-100 text-gray-500
                            <?php endif; ?>
                        ">
                            <?= $instance['status'] === 'active' ? 'Aktiv' : ($instance['status'] === 'completed' ? 'Abgeschlossen' : 'Abgebrochen') ?>
                        </span>
                    </h1>
                    <?php if ($instance['contact_name']): ?>
                        <p class="text-gray-500 text-sm flex items-center gap-2 mt-1">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            <?= htmlspecialchars($instance['contact_name']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="glass-card p-4">
                <div class="text-sm text-gray-500">Zieldatum</div>
                <div class="text-xl font-bold text-gray-800">
                    📅
                    <?= date('d.m.Y', strtotime($instance['target_date'])) ?>
                </div>
            </div>
            <div class="glass-card p-4">
                <div class="text-sm text-gray-500">Fortschritt</div>
                <div class="text-xl font-bold text-gray-800">
                    <?= $progress['percent'] ?>% (
                    <?= $progress['completed'] ?>/
                    <?= $progress['total'] ?>)
                </div>
            </div>
            <div class="glass-card p-4">
                <div class="text-sm text-gray-500">Gestartet am</div>
                <div class="text-xl font-bold text-gray-800">
                    <?= date('d.m.Y', strtotime($instance['created_at'])) ?>
                </div>
            </div>
        </div>

        <!-- PROGRESS BAR -->
        <div class="glass-card p-6 mb-8">
            <div class="flex items-center justify-between mb-3">
                <span class="font-medium text-gray-700">Gesamtfortschritt</span>
                <span class="text-secondary font-bold">
                    <?= $progress['percent'] ?>%
                </span>
            </div>
            <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-secondary to-primary transition-all duration-500"
                    style="width: <?= $progress['percent'] ?>%"></div>
            </div>
        </div>

        <!-- TASKS -->
        <section class="glass-card p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i data-lucide="list-checks" class="w-5 h-5 mr-2 text-gray-400"></i>
                Aufgaben
            </h2>

            <div class="space-y-3">
                <?php foreach ($tasks as $task): ?>
                    <div
                        class="task-row flex items-center gap-4 p-4 bg-white rounded-xl border border-gray-100 <?= $task['completed'] ? 'completed' : '' ?>">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0
                        <?= $task['completed'] ? 'bg-secondary text-white' : 'border-2 border-gray-300' ?>">
                            <?php if ($task['completed']): ?>
                                <i data-lucide="check" class="w-4 h-4"></i>
                            <?php endif; ?>
                        </div>

                        <div class="flex-1">
                            <div class="task-title font-medium text-gray-800">
                                <?= htmlspecialchars($task['step_title']) ?>
                            </div>
                            <div class="text-xs text-gray-400">
                                Fällig:
                                <?= date('d.m.Y', strtotime($task['target_date'])) ?>
                                <?php if ($task['priority'] !== 'normal'): ?>
                                    <span
                                        class="ml-2 px-2 py-0.5 rounded-full 
                                    <?= $task['priority'] === 'urgent' ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600' ?>">
                                        <?= $task['priority'] === 'urgent' ? 'Dringend' : 'Hoch' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!$task['completed']): ?>
                            <a href="index.php?page=task_toggle&id=<?= $task['task_id'] ?>&redirect=workflow_instance&instance=<?= $instance['id'] ?>"
                                class="px-3 py-1 bg-secondary/10 text-secondary rounded-lg text-sm hover:bg-secondary/20 transition-colors">
                                Erledigen
                            </a>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">
                                ✓
                                <?= date('d.m.', strtotime($task['completed_at'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($instance['status'] === 'active'): ?>
            <div class="mt-6 flex justify-end">
                <button onclick="cancelInstance()"
                    class="px-4 py-2 border border-red-200 text-red-500 rounded-xl hover:bg-red-50 transition-colors">
                    <i data-lucide="x-circle" class="w-4 h-4 inline mr-1"></i>
                    Ablauf abbrechen
                </button>
            </div>
        <?php endif; ?>

    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>

    <script>
        function cancelInstance() {
            if (!confirm('Diesen Ablauf wirklich abbrechen? Die bereits erstellten ToDos bleiben erhalten.')) return;

            fetch('index.php?page=workflow_cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=<?= $instance['id'] ?>'
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index.php?page=workflows';
                    } else {
                        alert(data.error || 'Fehler');
                    }
                });
        }
    </script>

</body>

</html>