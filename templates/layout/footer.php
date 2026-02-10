<!-- MOVED FROM SIDEBAR: MOBILE "MEHR" SLIDE-UP SHEET -->
<div class="mobile-more-overlay md:hidden" id="mobileMoreOverlay" onclick="closeMobileMore()" style="display: none;">
    <div class="mobile-more-sheet" onclick="event.stopPropagation()">
        <div class="sheet-handle"></div>

        <!-- User Info -->
        <div class="flex items-center px-6 pb-4 mb-2 border-b border-gray-100">
            <div
                class="w-11 h-11 rounded-full bg-accent flex items-center justify-center text-white font-bold shadow-sm mr-3">
                <?= substr($_SESSION['user_name'] ?? 'User', 0, 1) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 truncate">
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></p>
                <p class="text-xs text-gray-400">Hallo!</p>
            </div>
        </div>

        <!-- Navigation Links -->
        <a href="index.php?page=tasks" class="sheet-item <?= ($page ?? '') == 'tasks' ? 'active' : '' ?>">
            <i data-lucide="check-square"></i>
            <span>Aufgaben</span>
        </a>
        <a href="index.php?page=calendar" class="sheet-item <?= ($page ?? '') == 'calendar' ? 'active' : '' ?>">
            <i data-lucide="calendar"></i>
            <span>Kalender</span>
        </a>
        <a href="index.php?page=products" class="sheet-item <?= ($page ?? '') == 'products' ? 'active' : '' ?>">
            <i data-lucide="package"></i>
            <span>Produkte</span>
        </a>

        <div class="sheet-divider"></div>

        <a href="index.php?page=workflows" class="sheet-item">
            <i data-lucide="git-branch"></i>
            <span>Abläufe</span>
        </a>
        <a href="index.php?page=automation" class="sheet-item">
            <i data-lucide="zap"></i>
            <span>Automation</span>
        </a>

        <div class="sheet-divider"></div>

        <a href="index.php?page=settings" class="sheet-item">
            <i data-lucide="settings"></i>
            <span>Einstellungen</span>
        </a>
        <a href="index.php?page=logout" class="sheet-item" style="color: #ef4444;">
            <i data-lucide="log-out" style="color: #ef4444;"></i>
            <span>Abmelden</span>
        </a>

        <div style="height: 20px;"></div>
    </div>
</div>

<script>
    function toggleMobileMore() {
        const el = document.getElementById('mobileMoreOverlay');
        if (el.style.display === 'none' || el.style.display === '') {
            el.style.display = 'block';
            // Force reflow
            void el.offsetWidth;
            el.classList.add('open');
        } else {
            closeMobileMore();
        }
        // Re-init icons in sheet if needed
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    function closeMobileMore() {
        const el = document.getElementById('mobileMoreOverlay');
        el.classList.remove('open');
        setTimeout(() => {
            el.style.display = 'none';
        }, 250); // wait for fade out
    }
    function handleMobileFab() {
        // If on contacts page and quickCreateModal exists, open it
        if (typeof openQuickCreateModal === 'function') {
            openQuickCreateModal();
        } else {
            // Navigate to contact create page
            window.location = 'index.php?page=contact_create';
        }
    }
</script>

<script>
    // Initialize Icons
    lucide.createIcons();

    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(() => { });
    }
</script>
<?php include __DIR__ . '/phase_modal.php'; ?>

<!-- Globales Workflow-Modal Container -->
<div id="workflowModal" class="fixed inset-0 bg-black/50 z-[60] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative">
        <button onclick="closeWorkflowModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 z-10">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
        <div id="workflow-modal-content" class="max-h-[85vh] overflow-y-auto custom-scrollbar">
            <!-- Content will be loaded via AJAX -->
            <div class="p-12 text-center text-gray-500">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-current border-t-transparent text-secondary rounded-full mb-4"
                    role="status"></div>
                <p>Lade Workflow...</p>
            </div>
        </div>
    </div>
</div>
</body>

</html>