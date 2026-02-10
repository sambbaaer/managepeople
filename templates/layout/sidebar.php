<?php
$user_name = $_SESSION['user_name'] ?? 'User';
$first_name = explode(' ', trim($user_name))[0];
$current_page = $page ?? '';

$hour = (int) date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Guten Morgen';
    $greeting_icon = 'sunrise';
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = 'Hallo';
    $greeting_icon = 'sun';
} elseif ($hour >= 18 && $hour < 22) {
    $greeting = 'Guten Abend';
    $greeting_icon = 'sunset';
} else {
    $greeting = 'Hallo Nachteule';
    $greeting_icon = 'moon';
}
?>

<!-- DESKTOP SIDEBAR -->
<aside
    class="desktop-sidebar w-64 bg-white border-r border-gray-200 flex-col sticky top-0 h-screen hidden md:flex z-40">
    <div class="p-6 doodle-container">
        <h2 class="text-2xl font-bold text-gray-800 leading-tight relative z-10 font-display">
            <?= htmlspecialchars($greeting) ?>, <br> <span
                class="text-primary"><?= htmlspecialchars($first_name) ?>!</span>
        </h2>
        <?php echo getDoodle('Herz1', 'doodle doodle-coral w-12 h-12 -top-2 -right-2 rotate-12'); ?>
    </div>

    <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
        <a href="index.php?page=dashboard" class="sidebar-item <?= $current_page == 'dashboard' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="index.php?page=statistics" class="sidebar-item <?= $current_page == 'statistics' ? 'active' : '' ?>">
            <i data-lucide="pie-chart"></i>
            <span class="font-medium">Statistik</span>
        </a>

        <div class="pt-4 pb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">Kontakte</p>
            <a href="index.php?page=contacts"
                class="sidebar-item <?= $current_page == 'contacts' && !isset($_GET['smart_list_id']) ? 'active' : '' ?> group">
                <i data-lucide="users" class="group-hover:text-primary transition-colors"></i>
                <span>Alle Kontakte</span>
            </a>

            <!-- Smart Lists -->
            <div class="ml-8 space-y-1 mt-1">
                <?php
                // Fetch smart lists directly here (simplest for include structure)
                require_once __DIR__ . '/../../core/models/SmartList.php';
                try {
                    $smartListModel = new SmartList();
                    $sidebarSmartLists = $smartListModel->getAll();

                    foreach ($sidebarSmartLists as $sl):
                        $isActive = isset($_GET['smart_list_id']) && $_GET['smart_list_id'] == $sl['id'];
                        ?>
                        <a href="index.php?page=contacts&smart_list_id=<?= $sl['id'] ?>"
                            class="block p-2 text-sm rounded transition-colors <?= $isActive ? 'text-primary font-bold bg-gray-50' : 'text-gray-500 hover:text-secondary' ?>">
                            <?= htmlspecialchars($sl['name']) ?>
                        </a>
                    <?php endforeach;
                } catch (Exception $e) { /* ignore in view */
                }
                ?>
            </div>
        </div>

        <div class="pt-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">Apps</p>
            <a href="index.php?page=tasks" class="sidebar-item <?= $current_page == 'tasks' ? 'active' : '' ?>">
                <i data-lucide="check-square"></i>
                <span>To-Do's</span>
            </a>
            <a href="index.php?page=calendar" class="sidebar-item <?= $current_page == 'calendar' ? 'active' : '' ?>">
                <i data-lucide="calendar"></i>
                <span>Kalender</span>
            </a>
            <a href="index.php?page=workflows" class="sidebar-item <?= $current_page == 'workflows' ? 'active' : '' ?>">
                <i data-lucide="git-branch"></i>
                <span>Abläufe</span>
            </a>
            <a href="index.php?page=automation"
                class="sidebar-item <?= $current_page == 'automation' ? 'active' : '' ?>">
                <i data-lucide="zap"></i>
                <span>Automation</span>
            </a>
            <a href="index.php?page=products" class="sidebar-item <?= $current_page == 'products' ? 'active' : '' ?>">
                <i data-lucide="package"></i>
                <span>Produkte</span>
            </a>
        </div>
    </nav>

    <!-- USER SECTION BOTTOM -->
    <div class="p-4 border-t border-gray-100">
        <a href="index.php?page=settings"
            class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-xl cursor-pointer transition-colors group">
            <div
                class="w-10 h-10 rounded-full bg-accent flex items-center justify-center text-white font-bold shadow-sm">
                <?= substr($user_name, 0, 1) ?>
            </div>
            <div class="flex-1 overflow-hidden">
                <p class="text-sm font-semibold truncate text-gray-700 group-hover:text-primary transition-colors">
                    <?= htmlspecialchars($user_name) ?>
                </p>
                <span class="text-xs text-gray-400">Mein Profil</span>
            </div>
            <i data-lucide="settings" class="w-4 h-4 text-gray-400 group-hover:text-primary transition-colors"></i>
        </a>
        <div class="text-center mt-2">
            <a href="index.php?page=logout" class="text-xs text-red-400 hover:text-red-500 hover:underline">Abmelden</a>
        </div>
    </div>
</aside>

<!-- MOBILE BOTTOM NAVIGATION -->
<nav class="mobile-nav md:hidden">
    <a href="index.php?page=dashboard" class="mobile-nav-item <?= $current_page == 'dashboard' ? 'active' : '' ?>">
        <i data-lucide="home" class="w-6 h-6 mb-1"></i>
        <span>Home</span>
    </a>
    <a href="index.php?page=contacts" class="mobile-nav-item <?= $current_page == 'contacts' ? 'active' : '' ?>">
        <i data-lucide="users" class="w-6 h-6 mb-1"></i>
        <span>Kontakte</span>
    </a>

    <!-- Central Floating Action Button (Quick Create) -->
    <?php if (!Auth::isMentor()): ?>
        <div class="relative w-1/5 flex justify-center">
            <button class="mobile-floating-action-btn" id="mobileFabBtn" onclick="handleMobileFab()">
                <i data-lucide="plus" class="w-8 h-8"></i>
            </button>
        </div>
    <?php endif; ?>

    <a href="index.php?page=search" class="mobile-nav-item <?= $current_page == 'search' ? 'active' : '' ?>">
        <i data-lucide="search" class="w-6 h-6 mb-1"></i>
        <span>Suche</span>
    </a>
    <button class="mobile-nav-item" onclick="toggleMobileMore()" id="mobileMoreBtn">
        <i data-lucide="menu" class="w-6 h-6 mb-1"></i>
        <span>Mehr</span>
    </button>
</nav>