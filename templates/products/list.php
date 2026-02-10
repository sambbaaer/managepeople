<?php
$currentFilter = $filter ?? 'active';
$currentSearch = $search ?? '';
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/../layout/head.php'; ?>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/../layout/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <!-- HEADER -->
        <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div class="doodle-container relative">
                <h1 class="text-3xl font-bold font-display text-gray-800 relative z-10">Produkte</h1>
                <p class="text-gray-500 mt-1 relative z-10">
                    <?= count($products) ?> Produkt<?= count($products) !== 1 ? 'e' : '' ?>
                    <?php if ($currentFilter === 'archived'): ?> archiviert<?php endif; ?>
                </p>
                <?php echo getDoodle('Diamant', 'doodle doodle-blue w-10 h-10 -top-4 -right-8 rotate-12 opacity-80'); ?>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                <!-- Search -->
                <form action="index.php" method="GET" class="relative">
                    <input type="hidden" name="page" value="products">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($currentFilter) ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($currentSearch) ?>"
                        placeholder="Produkt suchen..."
                        class="pl-10 pr-4 py-2 border-none rounded-full glass-card focus:ring-2 focus:ring-secondary outline-none w-full md:w-56 transition-shadow">
                    <i data-lucide="search" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                </form>
                <!-- Export -->
                <a href="index.php?page=product_export"
                    class="inline-flex items-center justify-center px-4 py-2 bg-secondary hover:bg-teal-500 text-white rounded-xl font-medium shadow-md hover:shadow-lg transition-all text-sm">
                    <i data-lucide="download" class="w-4 h-4 mr-2"></i> JSON Export
                </a>
                <!-- New Product Toggle -->
                <button onclick="document.getElementById('newProductForm').classList.toggle('hidden')"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary hover:bg-red-500 text-white rounded-xl font-medium shadow-md hover:shadow-lg transition-all text-sm">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Neues Produkt
                </button>
            </div>
        </header>

        <!-- SUCCESS/ERROR MESSAGES -->
        <?php if ($msg): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm flex items-center">
                <i data-lucide="check-circle-2" class="w-5 h-5 mr-2 shrink-0"></i>
                <?php
                $messages = [
                    'product_created' => 'Produkt erfolgreich erstellt.',
                    'product_archived' => 'Produkt archiviert.',
                    'product_unarchived' => 'Produkt wiederhergestellt.',
                    'contact_assigned' => 'Kunde zugewiesen.',
                    'contact_removed' => 'Kunde entfernt.',
                ];
                echo $messages[$msg] ?? 'Aktion erfolgreich.';
                ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2 shrink-0"></i>
                <?php
                $errors = [
                    'name_required' => 'Bitte gib einen Produktnamen ein.',
                ];
                echo $errors[$error] ?? htmlspecialchars($error);
                ?>
            </div>
        <?php endif; ?>

        <!-- NEW PRODUCT FORM (Hidden by default) -->
        <div id="newProductForm" class="hidden mb-8">
            <form action="index.php?page=product_create" method="POST"
                class="glass-card p-6 border-t-4 border-secondary">
                <h3 class="text-lg font-bold font-display text-gray-800 mb-4 flex items-center">
                    <i data-lucide="package-plus" class="w-5 h-5 mr-2 text-secondary"></i> Neues Produkt erfassen
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" required placeholder="Produktname"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bild-URL</label>
                        <input type="url" name="image_url" placeholder="https://..."
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Produkt-URL</label>
                        <input type="url" name="product_url" placeholder="https://..."
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-secondary outline-none">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('newProductForm').classList.add('hidden')"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors">
                        Abbrechen
                    </button>
                    <button type="submit"
                        class="px-6 py-2 bg-secondary hover:bg-teal-500 text-white rounded-xl font-medium shadow-md hover:shadow-lg transition-all">
                        Speichern
                    </button>
                </div>
            </form>
        </div>

        <!-- FILTER TABS -->
        <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
            <?php
            $filters = [
                'active' => 'Aktiv',
                'archived' => 'Archiviert',
                'all' => 'Alle',
            ];
            foreach ($filters as $key => $label):
                $isActive = ($currentFilter === $key);
                ?>
                <a href="index.php?page=products&filter=<?= $key ?><?= $currentSearch ? '&q=' . urlencode($currentSearch) : '' ?>"
                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors <?= $isActive ? 'bg-secondary text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- PRODUCT LIST -->
        <?php if (empty($products)): ?>
            <div class="glass-card p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="package-x" class="w-8 h-8 text-gray-400"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-600 font-display mb-2">Keine Produkte gefunden</h3>
                <p class="text-gray-400 text-sm">
                    <?php if ($currentFilter === 'archived'): ?>
                        Es gibt keine archivierten Produkte.
                    <?php elseif ($currentSearch): ?>
                        Keine Produkte gefunden für "<?= htmlspecialchars($currentSearch) ?>".
                    <?php else: ?>
                        Erstelle ein neues Produkt oder importiere Produkte via JSON in den Einstellungen.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($products as $product):
                    $contacts = $productContacts[$product['id']] ?? [];
                    $isArchived = (int) ($product['archived'] ?? 0) === 1;
                    ?>
                    <div class="glass-card overflow-hidden <?= $isArchived ? 'opacity-70' : '' ?>">
                        <!-- Product Header -->
                        <div class="p-4 md:p-5 flex items-center gap-4 cursor-pointer product-toggle"
                            data-product-id="<?= $product['id'] ?>">
                            <!-- Product Image -->
                            <?php if (!empty($product['image_url'])): ?>
                                <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 shrink-0 shadow-sm">
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover"
                                        onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><i data-lucide=\'package\' class=\'w-6 h-6 text-gray-400\'></i></div>';lucide.createIcons();">
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                                    <i data-lucide="package" class="w-6 h-6 text-secondary"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Product Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-gray-800 truncate"><?= htmlspecialchars($product['name']) ?></h3>
                                    <?php if ($isArchived): ?>
                                        <span
                                            class="text-xs px-2 py-0.5 bg-gray-200 text-gray-500 rounded-full font-medium">Archiviert</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($product['product_url'])): ?>
                                    <a href="<?= htmlspecialchars($product['product_url']) ?>" target="_blank" rel="noopener"
                                        class="text-xs text-secondary hover:underline flex items-center mt-0.5"
                                        onclick="event.stopPropagation();">
                                        <i data-lucide="external-link" class="w-3 h-3 mr-1"></i> Produktseite
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Customer Count Badge -->
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 rounded-full">
                                    <i data-lucide="users" class="w-4 h-4 text-primary"></i>
                                    <span class="text-sm font-bold text-primary"><?= (int) $product['customer_count'] ?></span>
                                </div>

                                <!-- Archive/Unarchive -->
                                <?php if ($isArchived): ?>
                                    <form action="index.php?page=product_unarchive&filter=<?= $currentFilter ?>" method="POST"
                                        onclick="event.stopPropagation();">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="p-2 hover:bg-green-100 rounded-lg transition-colors"
                                            title="Wiederherstellen">
                                            <i data-lucide="archive-restore" class="w-4 h-4 text-green-600"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="index.php?page=product_archive&filter=<?= $currentFilter ?>" method="POST"
                                        onclick="event.stopPropagation();">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="p-2 hover:bg-orange-100 rounded-lg transition-colors"
                                            title="Archivieren">
                                            <i data-lucide="archive" class="w-4 h-4 text-orange-500"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Expand Arrow -->
                                <div class="expand-arrow transition-transform duration-200">
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Expandable Contact Section -->
                        <div class="product-details hidden border-t border-gray-100" data-details-for="<?= $product['id'] ?>">
                            <div class="p-4 md:p-5 bg-gray-50/50">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-bold text-gray-600 uppercase tracking-wider flex items-center">
                                        <i data-lucide="users" class="w-4 h-4 mr-1.5 text-primary"></i>
                                        Zugewiesene Kunden (<?= count($contacts) ?>)
                                    </h4>
                                </div>

                                <?php if (empty($contacts)): ?>
                                    <p class="text-sm text-gray-400 italic mb-4">Noch keine Kunden zugewiesen.</p>
                                <?php else: ?>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mb-4">
                                        <?php foreach ($contacts as $contact): ?>
                                            <div
                                                class="flex items-center justify-between bg-white rounded-lg p-2.5 group hover:shadow-sm transition-all">
                                                <a href="index.php?page=contact_detail&id=<?= $contact['id'] ?>"
                                                    class="flex items-center gap-2 min-w-0 flex-1 no-underline">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                                                        <?= mb_substr($contact['name'], 0, 1) ?>
                                                    </div>
                                                    <span
                                                        class="text-sm text-gray-700 truncate group-hover:text-primary transition-colors font-medium">
                                                        <?= htmlspecialchars($contact['name']) ?>
                                                    </span>
                                                </a>
                                                <form action="index.php?page=product_remove_contact" method="POST"
                                                    class="shrink-0 ml-2">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                                                    <input type="hidden" name="filter" value="<?= $currentFilter ?>">
                                                    <button type="submit"
                                                        class="p-1 opacity-0 group-hover:opacity-100 hover:bg-red-100 rounded transition-all"
                                                        title="Entfernen">
                                                        <i data-lucide="x" class="w-3.5 h-3.5 text-red-400"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Assign Contact Form -->
                                <form action="index.php?page=product_assign_contact" method="POST"
                                    class="flex items-center gap-2 mt-2">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= $currentFilter ?>">
                                    <div class="relative flex-1">
                                        <i data-lucide="user-plus" class="absolute left-3 top-2.5 w-4 h-4 text-gray-400"></i>
                                        <input type="text" name="contact_name" list="contacts_list_<?= $product['id'] ?>"
                                            placeholder="Kunde zuweisen..."
                                            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none text-sm bg-white">
                                        <datalist id="contacts_list_<?= $product['id'] ?>">
                                            <?php foreach ($allContacts as $c): ?>
                                                <option value="<?= htmlspecialchars($c['name']) ?>">
                                                <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                    <button type="submit"
                                        class="px-4 py-2 bg-primary hover:bg-red-500 text-white rounded-lg font-medium text-sm transition-all shadow-sm hover:shadow-md">
                                        Zuweisen
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <script src="assets/js/products.js"></script>
    <?php include __DIR__ . '/../layout/footer.php'; ?>
</body>

</html>