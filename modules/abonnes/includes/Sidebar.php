<?php
// Sidebar component for navigation
$currentModule = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out lg:static lg:inset-0">
    <div class="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-library-600 to-library-700">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                <i class="fas fa-book text-library-600 text-lg"></i>
            </div>
            <span class="text-white font-fredoka font-bold text-lg">Bibliothèque</span>
        </div>
        <button id="sidebarClose" class="lg:hidden text-white hover:bg-library-700 p-1 rounded">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="h-full overflow-y-auto bg-white dark:bg-gray-800">
        <nav class="p-4 space-y-2">
            <!-- Dashboard -->
            <a href="../../../dashboard.php"
                class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 p-3 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-tachometer-alt text-library-600"></i>
                <span class="font-medium">Tableau de bord</span>
            </a>

            <!-- Gestion des Livres -->
            <div class="space-y-1">
                <div
                    class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 p-3 rounded-lg bg-gray-100 dark:bg-gray-700">
                    <i class="fas fa-book text-library-600"></i>
                    <span class="font-medium">Gestion des Livres</span>
                </div>
                <div class="ml-6 space-y-1">
                    <a href="../../livres/views/index"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>Liste des livres</span>
                    </a>
                    <a href="../../livres/views/add"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Ajouter un livre</span>
                    </a>
                    <a href="../../categories/views/index"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-tags w-4"></i>
                        <span>Catégories</span>
                    </a>
                </div>
            </div>

            <!-- Gestion des Abonnés -->
            <div class="space-y-1">
                <div class="flex items-center space-x-3 text-white p-3 rounded-lg bg-library-600">
                    <i class="fas fa-users text-white"></i>
                    <span class="font-medium">Gestion des Abonnés</span>
                </div>
                <div class="ml-6 space-y-1">
                    <a href="index"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'index' ? 'bg-library-50 text-library-600' : ''; ?>">
                        <i class="fas fa-list w-4"></i>
                        <span>Liste des abonnés</span>
                    </a>
                    <a href="add"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'add' ? 'bg-library-50 text-library-600' : ''; ?>">
                        <i class="fas fa-user-plus w-4"></i>
                        <span>Nouvel abonné</span>
                    </a>
                    <a href="../../emprunts/views/index"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-handshake w-4"></i>
                        <span>Emprunts</span>
                    </a>
                </div>
            </div>

            <!-- Amendes & Pertes -->
            <div class="space-y-1">
                <div
                    class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 p-3 rounded-lg bg-gray-100 dark:bg-gray-700">
                    <i class="fas fa-exclamation-triangle text-library-600"></i>
                    <span class="font-medium">Amendes & Pertes</span>
                </div>
                <div class="ml-6 space-y-1">
                    <a href="../../amendes/views/index"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-list w-4"></i>
                        <span>Liste des amendes</span>
                    </a>
                    <a href="../../amendes/views/add"
                        class="flex items-center space-x-3 text-gray-600 dark:text-gray-400 p-2 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-plus w-4"></i>
                        <span>Nouvelle amende</span>
                    </a>
                </div>
            </div>

            <!-- Rapports -->
            <a href="../../rapports/index"
                class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 p-3 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-chart-bar text-library-600"></i>
                <span class="font-medium">Rapports</span>
            </a>

            <!-- Paramètres -->
            <a href="../../parametres/index"
                class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 p-3 rounded-lg hover:bg-library-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-cog text-library-600"></i>
                <span class="font-medium">Paramètres</span>
            </a>
        </nav>

        <!-- User info at bottom -->
        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gray-50 dark:bg-gray-700">
            <div class="flex items-center space-x-3">
                <?php if (!empty($current_user['photo'])): ?>
                <img src="<?php echo BASE_URL; ?>/assets/uploads/<?= htmlspecialchars($user['photo'], ENT_QUOTES) ?>"
                    class="w-8 h-8 rounded-full object-cover border-2 border-library-200 dark:border-library-600"
                    alt="Profile">
                <?php else: ?>
                <div class="w-10 h-10 bg-library-600 rounded-full flex items-center justify-center">
                    <span class="text-white font-semibold text-sm">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        <?php echo htmlspecialchars($user['role']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar overlay for mobile -->
<div id="sidebarOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40 lg:hidden hidden"></div>