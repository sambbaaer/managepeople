<?php
// templates/contacts/list.php
$statuses = ['Alle', 'Offen', 'Interessent', 'Kundin', 'Partnerin', 'Stillgelegt'];
$sortOptions = [
    'updated_at_desc' => 'Zuletzt bearbeitet',
    'created_at_desc' => 'Neueste zuerst',
    'name_asc' => 'Name (A-Z)',
    'name_desc' => 'Name (Z-A)',
];
$currentSort = $_GET['sort'] ?? 'updated_at_desc';
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
        <header
            class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 md:mb-8 gap-3 md:gap-4">
            <div class="flex items-center justify-between w-full md:w-auto">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold font-display text-gray-800 relative z-10 w-max">
                        Kontakte
                        <?php echo getDoodle('Kontakte', 'doodle doodle-blue w-10 h-10 -top-4 -right-6 -rotate-12 opacity-80 hidden md:block'); ?>
                    </h1>
                    <p class="text-gray-500 text-sm">
                        <?php echo $total; ?> gefunden
                    </p>
                </div>
                <?php if (!Auth::isMentor()): ?>
                    <!-- Mobile: Icon-only buttons -->
                    <div class="flex gap-2 md:hidden">
                        <button onclick="openQuickCreateModal()"
                            class="w-10 h-10 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center shadow-sm">
                            <i data-lucide="zap" class="w-5 h-5"></i>
                        </button>
                        <a href="index.php?page=contact_create"
                            class="w-10 h-10 bg-primary text-white rounded-xl flex items-center justify-center shadow-lg">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!Auth::isMentor()): ?>
                <!-- Desktop: Full buttons -->
                <div class="hidden md:flex gap-3">
                    <form class="relative doodle-container flex-1" method="GET" action="index.php">
                        <input type="hidden" name="page" value="contacts">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Suchen..."
                            class="pl-10 pr-4 py-2 border-none rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-secondary outline-none w-full md:w-64">
                        <i data-lucide="search" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                    </form>
                    <?php if (Auth::isOwner()): ?>
                        <a href="index.php?page=export_contacts"
                            class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-xl font-medium shadow-sm border border-gray-200 transition-all flex items-center justify-center"
                            title="Alle Kontakte als CSV exportieren">
                            <i data-lucide="download" class="w-5 h-5 mr-2"></i> CSV-Export
                        </a>
                    <?php endif; ?>
                    <button onclick="openQuickCreateModal()"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl font-medium shadow-sm transition-all flex items-center justify-center">
                        <i data-lucide="zap" class="w-5 h-5 mr-2"></i> Schnellerfassung
                    </button>
                    <a href="index.php?page=contact_create"
                        class="bg-primary hover:bg-red-500 text-white px-4 py-2 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all flex items-center justify-center">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Neu
                    </a>
                </div>
            <?php endif; ?>

            <!-- Mobile: Search bar -->
            <form class="relative w-full md:hidden" method="GET" action="index.php">
                <input type="hidden" name="page" value="contacts">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Kontakt suchen..."
                    class="pl-10 pr-4 py-2.5 border-none rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-secondary outline-none w-full text-base">
                <i data-lucide="search" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
            </form>

            <?php if (Auth::isMentor()): ?>
                <div class="hidden md:flex gap-3">
                    <form class="relative doodle-container flex-1" method="GET" action="index.php">
                        <input type="hidden" name="page" value="contacts">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Suchen..."
                            class="pl-10 pr-4 py-2 border-none rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-secondary outline-none w-full md:w-64">
                        <i data-lucide="search" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                    </form>
                </div>
            <?php endif; ?>
        </header>

        <!-- Status Filter Pills -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 md:gap-4 mb-4 md:mb-6">
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide w-full md:w-auto">
                <?php
                foreach ($statuses as $status):
                    $isActive = ($filterStatus === $status) || ($status === 'Alle' && empty($filterStatus));
                    $urlStatus = $status === 'Alle' ? '' : $status;
                    ?>
                    <a href="index.php?page=contacts&status=<?= $urlStatus ?>&q=<?= $search ?>&sort=<?= $currentSort ?>"
                        class="px-3 md:px-4 py-1.5 md:py-2 rounded-full text-xs md:text-sm font-medium whitespace-nowrap transition-colors flex-shrink-0
                              <?= $isActive ? 'bg-secondary text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-100 border border-transparent hover:border-gray-200' ?>">
                        <?= $status ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Sort Dropdown (Desktop only) -->
            <form method="GET" class="relative group hidden md:block">
                <input type="hidden" name="page" value="contacts">
                <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                <select name="sort" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-gray-200 text-gray-700 py-2 pl-4 pr-8 rounded-xl leading-tight focus:outline-none focus:bg-white focus:border-gray-500 cursor-pointer">
                    <?php foreach ($sortOptions as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $currentSort === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                </div>
            </form>
        </div>

        <!-- ===== MOBILE CARD VIEW ===== -->
        <div class="md:hidden">
            <?php if (empty($contacts)): ?>
                <div class="text-center py-12 text-gray-400">
                    <i data-lucide="users" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                    <p>Keine Kontakte gefunden.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <?php foreach ($contacts as $contact):
                        $statusBadgeClass = match ($contact['status']) {
                            'Partnerin' => 'bg-purple-100 text-purple-700',
                            'Kundin' => 'bg-green-100 text-green-700',
                            'Interessent' => 'bg-orange-100 text-orange-700',
                            'Stillgelegt' => 'bg-gray-100 text-gray-500',
                            default => 'bg-blue-50 text-blue-600'
                        };
                        ?>
                        <div class="mobile-contact-card">
                            <!-- Avatar + Info (tappable -> detail) -->
                            <a href="index.php?page=contact_detail&id=<?= $contact['id'] ?>"
                                class="flex items-center flex-1 min-w-0 no-underline">
                                <div class="card-avatar bg-blue-100 text-blue-600">
                                    <?= substr($contact['name'], 0, 1) ?>
                                </div>
                                <div class="card-info">
                                    <div class="card-name"><?= htmlspecialchars($contact['name']) ?></div>
                                    <div class="card-meta">
                                        <button
                                            onclick="event.preventDefault(); event.stopPropagation(); openWorkflowModal('<?= $contact['id'] ?>')"
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold <?= $statusBadgeClass ?> inline-flex items-center">
                                            <?= htmlspecialchars($contact['status'] ?? 'Offen') ?>
                                        </button>
                                        <?php if (!empty($contact['phase'])): ?>
                                            <span
                                                class="text-[10px] text-gray-400 truncate max-w-[80px]"><?= htmlspecialchars($contact['phase']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>

                            <!-- Quick Actions (always visible) -->
                            <div class="card-actions" onclick="event.stopPropagation();">
                                <?php if (!empty($contact['whatsapp']) || !empty($contact['phone'])):
                                    $wa = !empty($contact['whatsapp']) ? $contact['whatsapp'] : $contact['phone'];
                                    ?>
                                    <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $wa)) ?>"
                                        target="_blank" class="bg-[#25D366] text-white" title="WhatsApp">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($contact['social_instagram'])): ?>
                                    <a href="<?= getSocialUrl('instagram', $contact['social_instagram']) ?>" target="_blank"
                                        class="bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 text-white"
                                        title="Instagram">
                                        <i data-lucide="instagram" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($contact['phone']) && empty($contact['whatsapp']) && empty($contact['social_instagram'])): ?>
                                    <a href="tel:<?= htmlspecialchars($contact['phone']) ?>" class="bg-green-500 text-white"
                                        title="Anrufen">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Mobile Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="p-4 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="index.php?page=contacts&p=<?= $i ?>&q=<?= $search ?>&status=<?= $filterStatus ?>&sort=<?= $currentSort ?>"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-colors
                              <?= $page == $i ? 'bg-secondary text-white' : 'bg-gray-100 text-gray-600' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== DESKTOP TABLE VIEW ===== -->
        <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50/50 border-b border-gray-100 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-5 font-semibold w-1/4">Name</th>
                            <th class="px-6 py-5 font-semibold w-40">Beziehung</th>
                            <th class="px-6 py-5 font-semibold w-48">Status</th>
                            <th class="px-6 py-5 font-semibold hidden lg:table-cell">Zuletzt kontaktiert / Verlauf</th>
                            <th class="px-6 py-5 font-semibold text-right w-32">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if (empty($contacts)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <div class="mb-2"><i data-lucide="users" class="w-10 h-10 mx-auto opacity-20"></i></div>
                                    Keine Kontakte gefunden.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $contact): ?>
                                <tr class="hover:bg-gray-50/100 transition-all group cursor-pointer"
                                    onclick="window.location='index.php?page=contact_detail&id=<?= $contact['id'] ?>'">
                                    <td class="px-6 py-6">
                                        <div class="flex items-center">
                                            <div
                                                class="w-11 h-11 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold mr-4 shadow-sm">
                                                <?= substr($contact['name'], 0, 1) ?>
                                            </div>
                                            <div class="font-bold text-gray-900 text-base">
                                                <?= htmlspecialchars($contact['name']) ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-6">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md font-medium">
                                            <?= htmlspecialchars($contact['beziehung'] ?? '-') ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-6">
                                        <?php
                                        $statusBadgeClass = match ($contact['status']) {
                                            'Partnerin' => 'bg-purple-100 text-purple-700',
                                            'Kundin' => 'bg-green-100 text-green-700',
                                            'Interessent' => 'bg-orange-100 text-orange-700',
                                            'Stillgelegt' => 'bg-gray-100 text-gray-500',
                                            default => 'bg-blue-50 text-blue-600'
                                        };
                                        ?>
                                        <div class="flex flex-col gap-1.5 items-start">
                                            <button
                                                onclick="event.stopPropagation(); openWorkflowModal('<?= $contact['id'] ?>')"
                                                class="px-4 py-1.5 rounded-full text-xs font-bold transition-all hover:scale-105 active:scale-95 shadow-sm <?= $statusBadgeClass ?>"
                                                title="Status & Phase bearbeiten">
                                                <?= htmlspecialchars($contact['status'] ?? 'Offen') ?>
                                            </button>
                                            <?php if (!empty($contact['phase'])): ?>
                                                <div class="flex items-center text-[10px] text-gray-500 font-medium ml-2">
                                                    <i data-lucide="layers" class="w-3 h-3 mr-1 text-secondary/60"></i>
                                                    <span
                                                        class="truncate max-w-[120px]"><?= htmlspecialchars($contact['phase']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-6 hidden lg:table-cell">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium text-sm text-gray-700 line-clamp-2"
                                                title="<?= htmlspecialchars($contact['last_activity_desc'] ?? '') ?>">
                                                <?= htmlspecialchars($contact['last_activity_desc'] ?? '-') ?>
                                            </span>
                                            <div class="flex items-center text-[11px] text-gray-400">
                                                <i data-lucide="calendar" class="w-3 h-3 mr-1"></i>
                                                <?= $contact['last_activity_at'] ? date('d.m.Y', strtotime($contact['last_activity_at'])) : ($contact['last_contacted_at'] ? date('d.m.Y', strtotime($contact['last_contacted_at'])) : '-') ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-6 text-right" onclick="event.stopPropagation();">
                                        <div
                                            class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                            <?php if (!empty($contact['whatsapp']) || !empty($contact['phone'])):
                                                $wa = !empty($contact['whatsapp']) ? $contact['whatsapp'] : $contact['phone'];
                                                ?>
                                                <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $wa)) ?>"
                                                    target="_blank"
                                                    class="p-2 bg-green-50 text-[#25D366] hover:bg-[#25D366] hover:text-white rounded-xl transition-all shadow-sm border border-green-100"
                                                    title="WhatsApp">
                                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($contact['social_instagram'])): ?>
                                                <a href="<?= getSocialUrl('instagram', $contact['social_instagram']) ?>"
                                                    target="_blank"
                                                    class="p-2 bg-pink-50 text-pink-600 hover:bg-gradient-to-tr hover:from-yellow-400 hover:via-red-500 hover:to-purple-500 hover:text-white rounded-xl transition-all shadow-sm border border-pink-100"
                                                    title="Instagram">
                                                    <i data-lucide="instagram" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($contact['email'])): ?>
                                                <a href="mailto:<?= htmlspecialchars($contact['email']) ?>"
                                                    class="p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl transition-all shadow-sm border border-blue-100"
                                                    title="E-Mail">
                                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($contact['phone']): ?>
                                                <a href="tel:<?= htmlspecialchars($contact['phone']) ?>"
                                                    class="p-2 bg-gray-50 text-gray-500 hover:bg-green-600 hover:text-white rounded-xl transition-all shadow-sm border border-gray-100"
                                                    title="Anrufen">
                                                    <i data-lucide="phone" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="index.php?page=contact_detail&id=<?= $contact['id'] ?>"
                                                class="p-2 bg-gray-50 text-gray-500 hover:bg-primary hover:text-white rounded-xl transition-all shadow-sm border border-gray-100"
                                                title="Details öffnen">
                                                <i data-lucide="maximize-2" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Desktop Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="p-4 border-t border-gray-100 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="index.php?page=contacts&p=<?= $i ?>&q=<?= $search ?>&status=<?= $filterStatus ?>&sort=<?= $currentSort ?>"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-colors
                              <?= $page == $i ? 'bg-secondary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Modal: Schnellerfassung -->
    <div id="quickCreateModal"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i data-lucide="zap" class="w-5 h-5 mr-2 text-yellow-500"></i> Schnellerfassung
                </h2>
                <button onclick="closeQuickCreateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form id="quickCreateForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" required placeholder="Name des Kontakts"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Beziehung</label>
                    <input type="text" name="beziehung" value="Bekannte" placeholder="z.B. Bekannte, Kollege..."
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none transition-all">
                            <?php foreach (['Offen', 'Interessent', 'Kundin', 'Partnerin'] as $st): ?>
                                <option value="<?= $st ?>"><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Letzter Kontakt</label>
                        <input type="date" name="last_contact" value="<?= date('Y-m-d') ?>"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Notiz</label>
                    <textarea name="note" rows="3" placeholder="Zusammenfassung des Gesprächs..."
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none transition-all resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tags</label>
                    <input type="text" name="tags" placeholder="Tag1, Tag2..."
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary outline-none transition-all">
                </div>

                <div id="quickCreateSuccess"
                    class="hidden p-3 bg-green-50 text-green-700 text-sm rounded-xl border border-green-100 mb-4 animate-in fade-in slide-in-from-top-2">
                    <div class="flex items-center">
                        <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
                        Kontakt erfolgreich erstellt!
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeQuickCreateModal()"
                        class="flex-1 px-6 py-3 border border-gray-200 text-gray-600 rounded-xl font-bold hover:bg-gray-50 transition-all">
                        Abbrechen
                    </button>
                    <button type="submit" id="quickCreateSubmit"
                        class="flex-1 px-6 py-3 bg-primary text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center">
                        <span>Speichern & Weiter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let hasCreatedContacts = false;

        function openQuickCreateModal() {
            hasCreatedContacts = false;
            document.getElementById('quickCreateModal').classList.remove('hidden');
            document.getElementById('quickCreateForm').querySelector('input[name="name"]').focus();
            document.getElementById('quickCreateSuccess').classList.add('hidden');
        }

        function closeQuickCreateModal() {
            if (hasCreatedContacts) {
                window.location.reload();
            } else {
                document.getElementById('quickCreateModal').classList.add('hidden');
            }
        }

        document.getElementById('quickCreateForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('quickCreateSubmit');
            const successMsg = document.getElementById('quickCreateSuccess');
            const originalContent = btn.innerHTML;

            successMsg.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();

            const formData = new FormData(this);
            fetch('index.php?page=contact_quick_create', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        hasCreatedContacts = true;
                        // Show success feedback
                        successMsg.classList.remove('hidden');
                        if (typeof lucide !== 'undefined') lucide.createIcons();

                        // Reset form but stay in modal
                        this.reset();
                        this.querySelector('input[name="beziehung"]').value = 'Bekannte';
                        this.querySelector('input[name="last_contact"]').value = '<?= date("Y-m-d") ?>';
                        this.querySelector('input[name="name"]').focus();

                        // Hide success message after 3 seconds
                        setTimeout(() => {
                            successMsg.classList.add('hidden');
                        }, 3000);
                    } else {
                        alert('Fehler: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ein technischer Fehler ist aufgetreten.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
        });

        // Close on backdrop click
        document.getElementById('quickCreateModal').addEventListener('click', function (e) {
            if (e.target === this) closeQuickCreateModal();
        });
    </script>

    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>