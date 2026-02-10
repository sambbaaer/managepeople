<?php
// templates/contacts/detail.php
require_once __DIR__ . '/../../core/PhaseConfig.php';

$currentStatus = $contact['status'] ?? 'Offen';
$currentConfig = $statusConfig[$currentStatus] ?? $statusConfig['Offen'];
$currentSubStatus = $contact['sub_status'] ?? '';
$mainStatuses = ['Offen', 'Interessent', 'Kundin', 'Partnerin']; // Ordered Flow

// Phasen-System
$currentPhase = $contact['phase'] ?? '';
$currentPhaseDate = $contact['phase_date'] ?? null;
$currentPhaseNotes = $contact['phase_notes'] ?? '';
$availablePhases = PhaseConfig::getPhasesForStatus($currentStatus);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
</head>

<body class="min-h-screen flex bg-gray-50">
    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- Breadcrumbs / Back -->
        <div class="mb-4 md:mb-6 flex justify-between items-center">
            <a href="index.php?page=contacts"
                class="inline-flex items-center text-gray-500 hover:text-primary transition-colors text-sm font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> <span class="hidden md:inline">Zurück zur Liste</span><span class="md:hidden">Zurück</span>
            </a>

            <div class="flex gap-2">
                <?php if (!Auth::isMentor() && $currentStatus !== 'Stillgelegt' && $currentStatus !== 'Abgeschlossen'): ?>
                    <form method="POST" action="index.php?page=contact_update&id=<?= $contact['id'] ?>"
                        onsubmit="return confirm('Kontakt wirklich stilllegen?');">
                        <input type="hidden" name="status" value="Stillgelegt">
                        <button type="submit"
                            class="text-xs text-gray-400 hover:text-red-500 font-medium px-3 py-1 rounded border border-transparent hover:border-red-100 transition-colors">
                            <i data-lucide="archive" class="w-3 h-3 inline mr-1"></i> Stilllegen
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hero Section -->
        <div
            class="bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-gray-100 mb-6 md:mb-8 flex flex-col md:flex-row gap-4 md:gap-6 items-start md:items-center relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity duration-500">
                <i data-lucide="<?= $currentConfig['icon'] ?>" class="w-64 h-64"></i>
            </div>

            <!-- Avatar with Status Ring -->
            <div
                class="w-16 h-16 md:w-24 md:h-24 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-500 text-2xl md:text-3xl font-bold shadow-lg shrink-0 relative z-10 border-4 border-white">
                <?= substr($contact['name'], 0, 1) ?>
                <div
                    class="absolute bottom-0 right-0 w-6 h-6 md:w-8 md:h-8 rounded-full border-3 md:border-4 border-white flex items-center justify-center <?= $currentConfig['color'] ?>">
                    <i data-lucide="<?= $currentConfig['icon'] ?>" class="w-3 h-3 md:w-4 md:h-4"></i>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1 relative z-10 w-full">
                <h1 class="text-2xl md:text-3xl font-bold font-display text-gray-800"><?= htmlspecialchars($contact['name']) ?></h1>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span
                        class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-sm font-medium flex items-center">
                        <i data-lucide="users" class="w-3 h-3 mr-1.5"></i>
                        <?= htmlspecialchars($contact['beziehung'] ?? 'Bekannte') ?>
                    </span>
                    <span
                        class="px-3 py-1 rounded-full <?= $currentConfig['color'] ?> text-sm font-medium flex items-center shadow-sm">
                        <i data-lucide="<?= $currentConfig['icon'] ?>" class="w-3 h-3 mr-1.5"></i>
                        <?= htmlspecialchars($currentStatus) ?>
                    </span>
                    <?php if ($currentPhase):
                        $phaseConfig = PhaseConfig::getPhaseConfig($currentStatus, $currentPhase);
                        $phaseIcon = $phaseConfig['icon'] ?? 'tag';
                        ?>
                        <span
                            class="px-3 py-1 rounded-full bg-secondary/10 border border-secondary/20 text-secondary text-sm font-medium flex items-center">
                            <i data-lucide="<?= $phaseIcon ?>" class="w-3 h-3 mr-1.5"></i>
                            <?= htmlspecialchars($currentPhase) ?>
                            <?php if ($currentPhaseDate): ?>
                                <span
                                    class="ml-1 text-xs opacity-75">(<?= PhaseConfig::formatPhaseDate($currentStatus, $currentPhase, $currentPhaseDate) ?>)</span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>

                    <!-- Tags Section -->
                    <div id="tags-display-container" class="flex flex-wrap gap-2 mt-1 items-center">
                        <?php if (!empty($contact['tags'])):
                            $tags = array_filter(array_map('trim', explode(',', $contact['tags'])));
                            foreach ($tags as $tag): ?>
                                <span
                                    class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-100 text-xs font-bold flex items-center">
                                    <i data-lucide="tag" class="w-2.5 h-2.5 mr-1.5 opacity-70"></i>
                                    <?= htmlspecialchars($tag) ?>
                                </span>
                            <?php endforeach; else: ?>
                            <span class="text-[10px] text-gray-400 italic">Keine Tags</span>
                        <?php endif; ?>

                        <?php if (!Auth::isMentor()): ?>
                            <button onclick="toggleTagEdit(true)"
                                class="p-1 text-gray-400 hover:text-blue-500 transition-colors ml-1"
                                title="Tags bearbeiten">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Tags Edit Form (Hidden by default) -->
                    <div id="tags-edit-container" class="hidden mt-1">
                        <form action="index.php?page=contact_update&id=<?= $contact['id'] ?>" method="POST"
                            class="flex items-center gap-2">
                            <div class="relative flex-1 max-w-[300px]">
                                <i data-lucide="tag" class="absolute left-2.5 top-2 w-4 h-4 text-gray-400"></i>
                                <input type="text" name="tags" value="<?= htmlspecialchars($contact['tags'] ?? '') ?>"
                                    class="w-full pl-9 pr-3 py-1.5 text-xs border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                                    placeholder="Tags (kommagetrennt)">
                            </div>
                            <button type="submit"
                                class="p-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                <i data-lucide="check" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="toggleTagEdit(false)"
                                class="p-1.5 bg-gray-100 text-gray-500 rounded-lg hover:bg-gray-200 transition-colors">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Actions: Mobile = large touch-friendly row, Desktop = compact -->
            <!-- Mobile Quick Actions -->
            <div class="mobile-quick-actions md:hidden w-full relative z-10 mt-2">
                <?php if (!empty($contact['whatsapp']) || !empty($contact['phone'])):
                    $wa = !empty($contact['whatsapp']) ? $contact['whatsapp'] : $contact['phone'];
                ?>
                    <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $wa)) ?>" target="_blank"
                        class="bg-[#25D366] text-white">
                        <i data-lucide="message-circle" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_instagram'])): ?>
                    <a href="<?= getSocialUrl('instagram', $contact['social_instagram']) ?>" target="_blank"
                        class="bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 text-white">
                        <i data-lucide="instagram" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['phone'])): ?>
                    <a href="tel:<?= h($contact['phone']) ?>"
                        class="bg-green-500 text-white">
                        <i data-lucide="phone" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['email'])): ?>
                    <a href="mailto:<?= h($contact['email']) ?>"
                        class="bg-blue-500 text-white">
                        <i data-lucide="mail" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_facebook'])): ?>
                    <a href="<?= getSocialUrl('facebook', $contact['social_facebook']) ?>" target="_blank"
                        class="bg-[#1877F2] text-white">
                        <i data-lucide="facebook" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_tiktok'])): ?>
                    <a href="<?= getSocialUrl('tiktok', $contact['social_tiktok']) ?>" target="_blank"
                        class="bg-black text-white">
                        <i data-lucide="video" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_linkedin'])): ?>
                    <a href="<?= getSocialUrl('linkedin', $contact['social_linkedin']) ?>" target="_blank"
                        class="bg-[#0077B5] text-white">
                        <i data-lucide="linkedin" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <?php if (!Auth::isMentor()): ?>
                    <a href="index.php?page=contact_edit&id=<?= $contact['id'] ?>"
                        class="bg-gray-200 text-gray-600">
                        <i data-lucide="edit-3" class="w-6 h-6"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Desktop Actions -->
            <div class="hidden md:flex gap-3 relative z-10 flex-wrap justify-end">
                <?php if (!empty($contact['phone'])): ?>
                    <a href="tel:<?= h($contact['phone']) ?>" target="_blank"
                        class="p-3 bg-green-500 hover:bg-green-600 text-white rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        title="Anrufen">
                        <i data-lucide="phone" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['whatsapp']) || !empty($contact['phone'])):
                    $wa = !empty($contact['whatsapp']) ? $contact['whatsapp'] : $contact['phone'];
                ?>
                    <a href="https://wa.me/<?= h(preg_replace('/[^0-9]/', '', $wa)) ?>" target="_blank"
                        class="p-3 bg-[#25D366] hover:bg-green-600 text-white rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        title="WhatsApp">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['email'])): ?>
                    <a href="mailto:<?= h($contact['email']) ?>"
                        class="p-3 bg-blue-500 hover:bg-blue-600 text-white rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        title="E-Mail">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_instagram'])): ?>
                    <a href="<?= getSocialUrl('instagram', $contact['social_instagram']) ?>" target="_blank"
                        class="p-3 bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 text-white rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        title="Instagram">
                        <i data-lucide="instagram" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_facebook'])): ?>
                    <a href="<?= getSocialUrl('facebook', $contact['social_facebook']) ?>" target="_blank"
                        class="p-3 bg-[#1877F2] text-white rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        title="Facebook">
                        <i data-lucide="facebook" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_tiktok'])): ?>
                    <a href="<?= getSocialUrl('tiktok', $contact['social_tiktok']) ?>" target="_blank"
                        class="p-3 bg-black text-white rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        title="TikTok">
                        <i data-lucide="video" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($contact['social_linkedin'])): ?>
                    <a href="<?= getSocialUrl('linkedin', $contact['social_linkedin']) ?>" target="_blank"
                        class="p-3 bg-[#0077B5] text-white rounded-xl shadow-md transition-all transform hover:-translate-y-0.5"
                        title="LinkedIn">
                        <i data-lucide="linkedin" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- LEFT COLUMN: WORKFLOW & INFO -->
            <div class="space-y-8 lg:col-span-1">

                <!-- WORKFLOW CARD -->
                <div id="workflow-container">
                    <?php include __DIR__ . '/_workflow_ui.php'; ?>
                </div>

                <!-- Contact Core Info -->
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800">Kontaktdaten</h3>
                        <?php if (!Auth::isMentor()): ?>
                            <a href="index.php?page=contact_edit&id=<?= $contact['id'] ?>"
                                class="text-xs text-primary font-medium hover:underline">Bearbeiten</a>
                        <?php endif; ?>
                    </div>

                    <ul class="space-y-4 text-sm">
                        <li class="flex items-start">
                            <div
                                class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center mr-3 text-gray-400 shrink-0">
                                <i data-lucide="phone" class="w-4 h-4"></i>
                            </div>
                            <span
                                class="text-gray-700 font-medium py-1.5"><?= htmlspecialchars($contact['phone'] ?? '-') ?></span>
                        </li>
                        <li class="flex items-start">
                            <div
                                class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center mr-3 text-gray-400 shrink-0">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </div>
                            <a href="mailto:<?= htmlspecialchars($contact['email'] ?? '') ?>"
                                class="text-gray-700 hover:text-primary truncate py-1.5"><?= htmlspecialchars($contact['email'] ?? '-') ?></a>
                        </li>
                        <li class="flex items-start">
                            <div
                                class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center mr-3 text-gray-400 shrink-0">
                                <i data-lucide="map-pin" class="w-4 h-4"></i>
                            </div>
                            <span
                                class="text-gray-700 py-1.5"><?= htmlspecialchars($contact['address'] ?? '-') ?></span>
                        </li>
                        <li class="flex items-start">
                            <div
                                class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center mr-3 text-gray-400 shrink-0">
                                <i data-lucide="cake" class="w-4 h-4"></i>
                            </div>
                            <span class="text-gray-700 py-1.5">
                                <?= $contact['birthday'] ? date('d.m.Y', strtotime($contact['birthday'])) : '-' ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- PRODUCT FAVORITES -->
                <div class="glass-card p-6 border-t-4 border-green-500">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center font-display">
                        <i data-lucide="package" class="w-5 h-5 mr-2 text-green-600"></i> Lieblingsprodukte
                    </h3>

                    <?php if (!empty($assignedProducts)): ?>
                        <ul class="space-y-3 mb-4">
                            <?php foreach ($assignedProducts as $prod): ?>
                                <li class="flex items-center justify-between group">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($prod['image_url']): ?>
                                            <img src="<?= h($prod['image_url']) ?>" alt="Produkt"
                                                class="w-8 h-8 rounded object-cover border border-gray-100">
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center"><i
                                                    data-lucide="box" class="w-4 h-4 text-gray-400"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= h($prod['product_url']) ?>" target="_blank"
                                                class="text-sm font-bold text-gray-700 hover:text-primary hover:underline block truncate max-w-[150px]">
                                                <?= h($prod['name']) ?>
                                            </a>
                                        </div>
                                    </div>
                                    <form action="index.php?page=contact_remove_product" method="POST"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity">
                                        <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                        <button class="p-1 text-gray-300 hover:text-red-500 rounded"><i data-lucide="x"
                                                class="w-4 h-4"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-xs text-gray-400 mb-4 italic">Noch keine Produkte zugewiesen.</p>
                    <?php endif; ?>

                    <!-- Assign Form -->
                    <form action="index.php?page=contact_assign_product" method="POST" class="relative">
                        <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                        <input list="products_list" name="product_name" placeholder="Produkt suchen..."
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-400 outline-none">
                        <datalist id="products_list">
                            <?php foreach ($allProducts as $p): ?>
                                <option value="<?= h($p['name']) ?>">
                                <?php endforeach; ?>
                        </datalist>
                        <button type="submit"
                            class="absolute right-1 top-1 p-1 text-green-600 hover:bg-green-50 rounded">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>

            </div>

            <!-- RIGHT COLUMN: ACTIVITY & NOTES -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Notes (New Layout) -->
                <div class="glass-card p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center font-display">
                        <i data-lucide="sticky-note" class="w-5 h-5 mr-2 text-highlight"></i> Notizen
                    </h3>

                    <div class="flex gap-4">
                        <div class="flex-1">
                            <form action="index.php?page=contact_add_note&id=<?= $contact['id'] ?>" method="POST"
                                class="relative">
                                <textarea name="content"
                                    class="w-full bg-yellow-50/50 border border-yellow-100 rounded-xl p-4 pr-12 text-sm text-gray-700 focus:ring-2 focus:ring-highlight outline-none resize-none shadow-inner"
                                    placeholder="<?= Auth::isMentor() ? 'Nur Lesezugriff' : 'Schreibe eine Notiz...' ?>"
                                    rows="2" <?= Auth::isMentor() ? 'disabled' : '' ?>></textarea>
                                <?php if (!Auth::isMentor()): ?>
                                    <button type="submit"
                                        class="absolute right-2 bottom-2 p-2 bg-highlight hover:bg-yellow-400 text-white rounded-lg transition-colors shadow-sm">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                        <?php foreach ($notes as $note): ?>
                            <div class="flex gap-4 group">
                                <div class="w-8 flex flex-col items-center">
                                    <div class="w-2 h-2 rounded-full bg-yellow-200 mb-1"></div>
                                    <div class="w-0.5 flex-1 bg-yellow-50 group-last:hidden"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <p class="text-xs text-gray-400 mb-1 font-medium">
                                        <?= date('d.m.Y, H:i', strtotime($note['created_at'])) ?> Uhr
                                    </p>
                                    <div
                                        class="bg-gray-50 rounded-xl p-3 text-gray-700 text-sm leading-relaxed border border-gray-100 group-hover:border-yellow-100 transition-colors">
                                        <?= nl2br(htmlspecialchars($note['content'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tasks Section -->
                <div class="glass-card p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                        <i data-lucide="check-square" class="w-32 h-32"></i>
                    </div>

                    <div class="flex justify-between items-center mb-6 relative z-10">
                        <h3 class="font-bold text-gray-800 flex items-center font-display">
                            <i data-lucide="check-circle-2" class="w-5 h-5 mr-2 text-secondary"></i> Aufgaben
                        </h3>
                        <?php if (!Auth::isMentor()): ?>
                            <button
                                onclick="window.location='index.php?page=task_create&contact_id=<?= $contact['id'] ?>&redirect_to=' + encodeURIComponent(window.location.href)"
                                class="text-sm bg-secondary text-white px-4 py-2 rounded-xl font-bold shadow hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center">
                                <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Aufgabe
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3 relative z-10">
                        <?php foreach ($tasks as $t): ?>
                            <div
                                class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-xl hover:shadow-md transition-all group">
                                <a href="<?= Auth::isMentor() ? '#' : 'index.php?page=task_toggle&id=' . $t['id'] ?>"
                                    class="w-6 h-6 border-2 <?= $t['completed'] ? 'bg-secondary border-secondary' : 'border-gray-200 group-hover:border-secondary' ?> rounded-lg flex items-center justify-center transition-all cursor-pointer">
                                    <?php if ($t['completed']): ?><i data-lucide="check"
                                            class="w-4 h-4 text-white"></i><?php endif; ?>
                                </a>

                                <div class="flex-1 min-w-0">
                                    <p
                                        class="font-medium <?= $t['completed'] ? 'line-through text-gray-400' : 'text-gray-800' ?> truncate">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </p>
                                    <div class="flex gap-3 text-xs text-gray-400 mt-0.5">
                                        <?php if ($t['due_date']): ?>
                                            <span class="flex items-center text-orange-400"><i data-lucide="calendar"
                                                    class="w-3 h-3 mr-1"></i>
                                                <?= date('d.m.', strtotime($t['due_date'])) ?></span>
                                        <?php endif; ?>
                                        <?php if ($t['priority'] == 'high'): ?><span class="text-red-500 font-bold">!
                                                Wichtig</span><?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!Auth::isMentor()): ?>
                                    <a href="index.php?page=task_delete&id=<?= $t['id'] ?>"
                                        class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($tasks)): ?>
                            <div class="text-center py-8">
                                <p class="text-gray-400 text-sm">Keine offenen Aufgaben.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="glass-card p-6">
                    <?php
                    // Activity Type Configuration
                    $activityConfig = [
                        'status_change' => [
                            'icon' => 'arrow-right-circle',
                            'color' => 'bg-blue-500',
                            'label' => 'Status'
                        ],
                        'note_added' => [
                            'icon' => 'sticky-note',
                            'color' => 'bg-yellow-400',
                            'label' => 'Notiz'
                        ],
                        'contact_created' => [
                            'icon' => 'user-plus',
                            'color' => 'bg-green-500',
                            'label' => 'Erstellt'
                        ],
                        'product_assigned' => [
                            'icon' => 'package',
                            'color' => 'bg-green-600',
                            'label' => 'Produkt'
                        ],
                        'product_removed' => [
                            'icon' => 'package-minus',
                            'color' => 'bg-red-500',
                            'label' => 'Produkt'
                        ],
                        'auto_task_created' => [
                            'icon' => 'calendar-plus',
                            'color' => 'bg-purple-500',
                            'label' => 'Automatisierung'
                        ],
                        'auto_task_rollback' => [
                            'icon' => 'undo',
                            'color' => 'bg-purple-400',
                            'label' => 'Automatisierung'
                        ],
                        'phase_change' => [
                            'icon' => 'layers',
                            'color' => 'bg-secondary',
                            'label' => 'Phase'
                        ],
                        'task_completed' => [
                            'icon' => 'check-square',
                            'color' => 'bg-green-500',
                            'label' => 'Aufgabe'
                        ]
                    ];

                    $totalActivities = count($activities);
                    $showCollapse = $totalActivities > 5;
                    ?>

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-800 flex items-center font-display">
                            <i data-lucide="history" class="w-5 h-5 mr-2 text-gray-400"></i>
                            Historie
                            <span class="ml-2 text-xs font-normal text-gray-400">(<?= $totalActivities ?>
                                <?= $totalActivities === 1 ? 'Eintrag' : 'Einträge' ?>)</span>
                        </h3>
                    </div>

                    <div class="border-l-2 border-gray-100 space-y-6 ml-2">
                        <?php foreach ($activities as $index => $act):
                            $config = $activityConfig[$act['type']] ?? [
                                'icon' => 'circle-dot',
                                'color' => 'bg-gray-400',
                                'label' => 'System'
                            ];

                            // Enhanced descriptions
                            $description = '';
                            switch ($act['type']) {
                                case 'status_change':
                                    $description = 'Status geändert';
                                    if ($act['old_value'] && $act['new_value']) {
                                        $description .= ' von <strong class="text-gray-700">' . htmlspecialchars($act['old_value']) . '</strong> → <strong class="text-secondary">' . htmlspecialchars($act['new_value']) . '</strong>';
                                    }
                                    break;

                                case 'phase_change':
                                    $description = 'Phase geändert';
                                    if ($act['old_value'] && $act['new_value']) {
                                        $description .= ' von <strong class="text-gray-700">' . htmlspecialchars($act['old_value']) . '</strong> → <strong class="text-secondary">' . htmlspecialchars($act['new_value']) . '</strong>';
                                    }
                                    break;

                                case 'task_completed':
                                    $description = htmlspecialchars($act['description']);
                                    break;

                                case 'product_assigned':
                                    $productName = $act['product_name'] ?? 'Unbekanntes Produkt';
                                    $description = 'Produkt <strong class="text-gray-700">' . htmlspecialchars($productName) . '</strong> zugewiesen';
                                    break;

                                case 'product_removed':
                                    $productName = $act['product_name'] ?? 'Unbekanntes Produkt';
                                    $description = 'Produkt <strong class="text-gray-700">' . htmlspecialchars($productName) . '</strong> entfernt';
                                    break;

                                case 'note_added':
                                    $noteContent = $act['new_value'] ?? '';
                                    if ($noteContent) {
                                        // Truncate long notes
                                        $maxLength = 100;
                                        $shortContent = strlen($noteContent) > $maxLength
                                            ? substr($noteContent, 0, $maxLength) . '...'
                                            : $noteContent;
                                        $description = 'Notiz hinzugefügt: «<em class="text-gray-600">' . htmlspecialchars($shortContent) . '</em>»';
                                    } else {
                                        $description = 'Notiz hinzugefügt';
                                    }
                                    break;

                                case 'contact_created':
                                    $description = 'Kontakt erstellt';
                                    break;

                                case 'auto_task_created':
                                    $description = htmlspecialchars($act['description']);
                                    break;

                                case 'auto_task_rollback':
                                    $description = htmlspecialchars($act['description']);
                                    break;

                                default:
                                    $description = htmlspecialchars($act['description']);
                            }

                            // Collapse logic - hide entries after index 4 if more than 5 total
                            $hideClass = ($showCollapse && $index >= 5) ? 'history-hidden hidden' : '';
                            ?>
                            <div class="relative pl-8 pb-2 <?= $hideClass ?>" data-history-entry>
                                <!-- Timeline Dot with Icon -->
                                <div
                                    class="absolute -left-[9px] top-1 w-5 h-5 rounded-full <?= $config['color'] ?> border-2 border-white shadow-sm flex items-center justify-center">
                                    <i data-lucide="<?= $config['icon'] ?>" class="w-3 h-3 text-white"></i>
                                </div>

                                <!-- Activity Content -->
                                <div
                                    class="bg-gray-50 rounded-lg p-3 border border-gray-100 hover:border-gray-200 transition-colors">
                                    <div class="flex justify-between items-start gap-3">
                                        <div class="flex-1">
                                            <div class="text-sm text-gray-700">
                                                <?= $description ?>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs text-gray-400">
                                                    <i data-lucide="clock" class="w-3 h-3 inline mr-1"></i>
                                                    <?= date('d.m.Y, H:i', strtotime($act['created_at'])) ?> Uhr
                                                </span>
                                            </div>
                                        </div>
                                        <span
                                            class="text-xs px-2 py-0.5 rounded-full bg-white border border-gray-200 text-gray-500 font-medium whitespace-nowrap">
                                            <?= $config['label'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($activities)): ?>
                            <div class="text-center py-8">
                                <p class="text-gray-400 text-sm">Noch keine Aktivitäten.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($showCollapse): ?>
                        <div class="mt-4 text-center">
                            <button id="toggleHistory"
                                class="text-sm text-primary hover:text-secondary font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center mx-auto"
                                onclick="toggleHistoryEntries()">
                                <i data-lucide="chevron-down" class="w-4 h-4 mr-1" id="historyChevron"></i>
                                <span id="historyButtonText">Alle <?= $totalActivities ?> Einträge anzeigen</span>
                            </button>
                        </div>

                        <script>
                            let historyExpanded = false;

                            function toggleHistoryEntries() {
                                const hiddenEntries = document.querySelectorAll('.history-hidden');
                                const button = document.getElementById('toggleHistory');
                                const buttonText = document.getElementById('historyButtonText');
                                const chevron = document.getElementById('historyChevron');

                                historyExpanded = !historyExpanded;

                                hiddenEntries.forEach(entry => {
                                    if (historyExpanded) {
                                        entry.classList.remove('hidden');
                                    } else {
                                        entry.classList.add('hidden');
                                    }
                                });

                                if (historyExpanded) {
                                    buttonText.textContent = 'Weniger anzeigen';
                                    chevron.setAttribute('data-lucide', 'chevron-up');
                                } else {
                                    buttonText.textContent = 'Alle <?= $totalActivities ?> Einträge anzeigen';
                                    chevron.setAttribute('data-lucide', 'chevron-down');
                                }

                                // Re-initialize lucide icons for the changed chevron
                                lucide.createIcons();
                            }

                            function toggleTagEdit(show) {
                                const display = document.getElementById('tags-display-container');
                                const edit = document.getElementById('tags-edit-container');
                                if (show) {
                                    display.classList.add('hidden');
                                    edit.classList.remove('hidden');
                                    edit.querySelector('input').focus();
                                } else {
                                    display.classList.remove('hidden');
                                    edit.classList.add('hidden');
                                }
                            }
                        </script>
                    <?php endif; ?>
                </div>


            </div>

        </div>

    </main>

    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>