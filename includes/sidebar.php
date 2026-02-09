<?php
/**
 * ASIDEBAR PRINCIPAL
 * - Gestion par rôles (admin | bibliothecaire | assistant)
 * - Permissions via permissions.php
 * - Chemins propres avec BASE_URL
 */

require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/../config/config.php';

$current_user = getUserData();

/**
 * Générateur de lien sidebar
 */
// Fonction pour générer les liens de sidebar
function renderSidebarLink($user, $module, $title, $icon, $href) {
    $canAccess = canUserAccess($user, $module);
    $classes = getLinkClasses($user, $module);
    $active = (strpos($_SERVER['REQUEST_URI'], "/$module/") !== false && $canAccess)
        ? 'bg-library-100 dark:bg-library-900 text-library-600 dark:text-library-400'
        : '';
    $onclick = $canAccess ? '' : 'onclick="return false;" title="Accès non autorisé"';

    // Lien propre sans .php grâce au .htaccess
    echo "<a href='" . ($canAccess ? BASE_URL . "/$href" : '#') . "' class='$classes $active' $onclick>
            <i class='$icon mr-3 text-lg " . ($canAccess ? 'group-hover:scale-110 transition-transform' : '') . "'></i>
            <span class='font-medium'>$title</span>"
            . (!$canAccess ? '<i class="fas fa-lock ml-auto text-xs"></i>' : '') .
         "</a>";
}
?>

<!-- SIDEBAR -->
<div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64
            bg-gradient-to-br from-library-50 to-blue-100
            dark:from-gray-800 dark:to-gray-900
            shadow-xl transform -translate-x-full lg:translate-x-0
            transition-transform duration-300">

    <!-- HEADER -->
    <div class="flex items-center justify-between h-16 px-6
                border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3">
            <span class="font-bold text-lg text-library-800 dark:text-library-200 hidden xl:block">
                <img class="w-20" src="<?php echo BASE_URL; ?>/assets/logo/logoujn.png" alt="Logo UN JOUR NOUVEAU">
            </span>
        </div>

        <button id="sidebarClose" class="lg:hidden text-library-700 dark:text-library-300 hover:text-library-900">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- NAVIGATION -->
    <nav class="mt-6 px-4 pb-28 overflow-y-auto h-[calc(100vh-64px)]">
        <div class="space-y-2">

            <?php
            renderSidebarLink($current_user, 'dashboard', 'Dashboard', 'fas fa-tachometer-alt', '/index');

            renderSidebarLink($current_user, 'abonnes', 'Abonnés', 'fas fa-users', '/modules/abonnes/views/index');
            renderSidebarLink($current_user, 'livres', 'Livres', 'fas fa-book', '/modules/livres/views/index');
            renderSidebarLink($current_user, 'emprunts', 'Emprunts', 'fas fa-exchange-alt', '/modules/emprunts/views/index');
            renderSidebarLink($current_user, 'amendes', 'Amendes & Pertes', 'fas fa-exclamation-triangle', '/modules/amendes/views/index');
            renderSidebarLink($current_user, 'reservations', 'Réservations', 'fas fa-bookmark', '/modules/reservations/views/index');
            renderSidebarLink($current_user, 'categories', 'Catégories', 'fas fa-tags', '/modules/categories_livres/views/index');
            ?>

            <!-- RAPPORTS -->
            <?php if (canUserAccess($current_user, 'reports')): ?>
            <div class="border-t border-gray-200 dark:border-gray-700 mt-6 pt-4">
                <h3 class="px-4 py-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                    Rapports
                </h3>
                <?php
                renderSidebarLink(
                    $current_user,
                    'reports',
                    'Statistiques',
                    'fas fa-chart-bar',
                    '/modules/reports/views/index'
                );
                ?>
            </div>
            <?php endif; ?>

            <!-- ADMINISTRATION -->
            <?php if (canUserAccess($current_user, 'users') || canUserAccess($current_user, 'settings')): ?>
            <div class="border-t border-gray-200 dark:border-gray-700 mt-6 pt-4">
                <h3 class="px-4 py-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                    Administration
                </h3>

                <?php
                renderSidebarLink(
                    $current_user,
                    'users',
                    'Utilisateurs',
                    'fas fa-user-cog',
                    '/modules/users/views/index'
                );

                // renderSidebarLink(
                //     $current_user,
                //     'settings',
                //     'Paramètres',
                //     'fas fa-cogs',
                //     '/admin/settings/index'
                // );
                ?>
            </div>
            <?php endif; ?>

        </div>
    </nav>

    <!-- PROFIL UTILISATEUR -->
    <div class="absolute bottom-0 left-0 right-0 p-4
                border-t border-gray-200 dark:border-gray-700
                bg-gradient-to-br from-library-50 to-blue-100
                dark:from-gray-800 dark:to-gray-900">

        <div class="flex items-center space-x-3">
            <?php if (!empty($current_user['photo'])): ?>
            <img src="<?php echo BASE_URL; ?><?php echo BASE_URL; ?>/assets/uploads/<?= htmlspecialchars($user['photo'], ENT_QUOTES) ?>"
                class="w-9 h-9 rounded-full object-cover border-2 border-library-200 dark:border-library-600"
                alt="Profil">
            <?php else: ?>
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-library-500 to-purple-600
                            flex items-center justify-center border-2 border-library-200">
                <span class="text-white text-xs font-semibold">
                    <?= strtoupper(substr($current_user['first_name'],0,1) . substr($current_user['last_name'],0,1)) ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    <?= htmlspecialchars($current_user['first_name'].' '.$current_user['last_name']) ?>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    <?= ucfirst($current_user['role']) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- OVERLAY MOBILE -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>