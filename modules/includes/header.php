<?php
// Récupération des données utilisateur pour l'header
$header_user = getUserData();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Bibliothèque UN JOUR NOUVEAU';
?>
<!-- Header -->
<header
    class="sticky top-0 z-50 bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-800 dark:to-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between h-16 px-6">
        <div class="flex items-center space-x-4">
            <button id="sidebarToggle"
                class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-2 rounded-lg transition-colors">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white" id="pageTitle">
                <?php echo $pageTitle; ?>
            </h1>
        </div>

        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <div class="relative">
                <button id="notificationToggle"
                    class="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors relative">
                    <i class="fas fa-bell text-lg"></i>
                    <span
                        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                </button>

                <!-- Dropdown notifications -->
                <div id="notificationDropdown"
                    class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <div
                            class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-orange-500"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-white">Retard de restitution</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">3 livres en retard à récupérer
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">Il y a 2 heures</p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-plus text-green-500"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-white">Nouvel abonné</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Marie Dubois s'est inscrite</p>
                                    <p class="text-xs text-gray-400 mt-1">Il y a 1 jour</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                        <a href="<?php echo getBasePath(); ?>notifications.php"
                            class="text-sm text-library-600 hover:text-library-700 dark:text-library-400 dark:hover:text-library-300">
                            Voir toutes les notifications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dark Mode Toggle -->
            <button id="themeToggle"
                class="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-moon dark:hidden text-lg"></i>
                <i class="fas fa-sun hidden dark:block text-lg"></i>
            </button>

            <!-- User Dropdown -->
            <div class="relative">
                <button id="userDropdown"
                    class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <?php if (!empty($current_user['photo'])): ?>
                    <img src="../../../assets/uploads/<?php echo htmlspecialchars($current_user['photo']); ?>"
                        class="w-8 h-8 rounded-full object-cover border-2 border-library-200 dark:border-library-600"
                        alt="Profile">
                    <?php else: ?>
                    <div
                        class="w-8 h-8 bg-gradient-to-br from-library-500 to-purple-600 rounded-full flex items-center justify-center border-2 border-library-200 dark:border-library-600">
                        <span class="text-white text-sm font-semibold">
                            <?php echo strtoupper(substr($header_user['first_name'], 0, 1) . substr($header_user['last_name'], 0, 1)); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <span class="hidden md:block text-sm font-medium text-gray-700 dark:text-gray-300">
                        <?php echo htmlspecialchars($header_user['first_name'] . ' ' . $header_user['last_name']); ?>
                    </span>
                    <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400"></i>
                </button>

                <!-- User Dropdown Menu -->
                <div id="userDropdownMenu"
                    class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($header_user['first_name'] . ' ' . $header_user['last_name']); ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?php echo htmlspecialchars($header_user['email']); ?>
                        </p>
                        <p class="text-xs text-library-600 dark:text-library-400">
                            <?php echo ucfirst($header_user['role']); ?>
                        </p>
                    </div>
                    <div class="py-2">
                        <a href="<?php echo getBasePath(); ?>profile.php"
                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-user mr-2"></i>Mon Profil
                        </a>
                        <a href="<?php echo getBasePath(); ?>settings.php"
                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-cog mr-2"></i>Paramètres
                        </a>
                        <div class="border-t border-gray-200 dark:border-gray-700 mt-2 pt-2">
                            <a href="<?php echo getBasePath(); ?>logout.php"
                                class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    // User Dropdown
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdown && userDropdownMenu) {
        userDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('hidden');
            // Close notification dropdown if open
            const notificationDropdown = document.getElementById('notificationDropdown');
            if (notificationDropdown) notificationDropdown.classList.add('hidden');
        });
    }

    // Notification Dropdown
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (notificationToggle && notificationDropdown) {
        notificationToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
            // Close user dropdown if open
            if (userDropdownMenu) userDropdownMenu.classList.add('hidden');
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        if (userDropdownMenu) userDropdownMenu.classList.add('hidden');
        if (notificationDropdown) notificationDropdown.classList.add('hidden');
    });

    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    const currentTheme = localStorage.getItem('theme') || 'light';
    html.classList.toggle('dark', currentTheme === 'dark');

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    }
});
</script>