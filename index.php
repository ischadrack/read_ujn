<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$user = getUserData();

// Récupérer les statistiques du dashboard
try {
    // Statistiques des livres
    $stmt = $db->query("SELECT 
        COUNT(*) as total_livres,
        SUM(quantite_stock) as stock_total,
        SUM(quantite_disponible) as disponible_total,
        SUM(quantite_empruntee) as emprunte_total
        FROM livres WHERE statut = 'actif'");
    $stats_livres = $stmt->fetch();
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
    // Statistiques des abonnés
    $stmt = $db->query("SELECT 
        COUNT(*) as total_abonnes,
        COUNT(CASE WHEN statut = 'actif' THEN 1 END) as abonnes_actifs,
        COUNT(CASE WHEN statut = 'suspendu' THEN 1 END) as abonnes_suspendus,
        COUNT(CASE WHEN date_expiration < CURDATE() AND statut = 'actif' THEN 1 END) as abonnes_expires
        FROM abonnes");
    $stats_abonnes = $stmt->fetch();

    // Statistiques des emprunts
    $stmt = $db->query("SELECT 
        COUNT(*) as total_emprunts,
        COUNT(CASE WHEN statut = 'en_cours' THEN 1 END) as emprunts_cours,
        COUNT(CASE WHEN statut = 'en_retard' OR (statut = 'en_cours' AND date_retour_prevue < CURDATE()) THEN 1 END) as emprunts_retard,
        COUNT(CASE WHEN statut = 'rendu' AND MONTH(date_retour_effective) = MONTH(CURDATE()) THEN 1 END) as retours_mois
        FROM emprunts");
    $stats_emprunts = $stmt->fetch();

    // Statistiques des amendes
    $stmt = $db->query("SELECT 
        COUNT(*) as total_amendes,
        SUM(montant) as montant_total,
        COUNT(CASE WHEN statut = 'impayee' THEN 1 END) as amendes_impayees,
        SUM(CASE WHEN statut = 'impayee' THEN montant ELSE 0 END) as montant_impaye
        FROM amendes_pertes");
    $stats_amendes = $stmt->fetch();

    // Emprunts récents
    $stmt = $db->query("SELECT e.*, 
        l.titre, l.auteur,
        a.nom, a.prenom, a.numero_abonne,
        u.first_name as created_by_name
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.id
        JOIN abonnes a ON e.abonne_id = a.id
        JOIN users u ON e.created_by = u.id
        WHERE e.statut IN ('en_cours', 'en_retard')
        ORDER BY e.created_at DESC
        LIMIT 10");
    $emprunts_recents = $stmt->fetchAll();

    // Livres les plus empruntés
    $stmt = $db->query("SELECT l.titre, l.auteur, COUNT(e.id) as nb_emprunts
        FROM livres l
        LEFT JOIN emprunts e ON l.id = e.livre_id
        GROUP BY l.id
        ORDER BY nb_emprunts DESC
        LIMIT 5");
    $livres_populaires = $stmt->fetchAll();

    // Notifications importantes
    $notifications = [];
    
    // Emprunts en retard
    $stmt = $db->query("SELECT COUNT(*) as count FROM emprunts WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()");
    $retards = $stmt->fetchColumn();
    if ($retards > 0) {
        $notifications[] = [
            'type' => 'warning',
            'icon' => 'fas fa-clock',
            'title' => 'Emprunts en retard',
            'message' => "$retards emprunt(s) en retard nécessitent votre attention",
            'link' => 'modules/emprunts/views/index?statut=en_retard'
        ];
    }

    // Abonnements expirés
    $stmt = $db->query("SELECT COUNT(*) as count FROM abonnes WHERE statut = 'actif' AND date_expiration < CURDATE()");
    $expires = $stmt->fetchColumn();
    if ($expires > 0) {
        $notifications[] = [
            'type' => 'danger',
            'icon' => 'fas fa-user-times',
            'title' => 'Abonnements expirés',
            'message' => "$expires abonnement(s) expiré(s) à renouveler",
            'link' => 'modules/abonnes/views/index?statut=expire'
        ];
    }

    // Stock faible
    $stmt = $db->query("SELECT COUNT(*) as count FROM livres WHERE quantite_disponible = 0 AND statut = 'actif'");
    $stock_vide = $stmt->fetchColumn();
    if ($stock_vide > 0) {
        $notifications[] = [
            'type' => 'info',
            'icon' => 'fas fa-box-open',
            'title' => 'Stock épuisé',
            'message' => "$stock_vide livre(s) non disponible(s)",
            'link' => 'modules/livres/views/index?disponible=0'
        ];
    }

} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des statistiques : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - <?php echo SITE_NAME; ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&family=Fredoka:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    'ubuntu': ['Ubuntu', 'sans-serif'],
                    'fredoka': ['Fredoka', 'sans-serif'],
                },
                colors: {
                    'library': {
                        50: '#f0f9ff',
                        100: '#e0f2fe',
                        200: '#bae6fd',
                        300: '#7dd3fc',
                        400: '#38bdf8',
                        500: '#0ea5e9',
                        600: '#0284c7',
                        700: '#0369a1',
                        800: '#075985',
                        900: '#0c4a6e',
                    }
                }
            }
        }
    }
    </script>
</head>

<body
    class="font-ubuntu bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-900 dark:to-gray-800 min-h-screen">

     <!-- Sidebar -->
    <?php require_once 'modules/includes/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Header -->
        <header
            class="sticky top-0 z-50 bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-800 dark:to-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between h-16 px-6">
                <div class="flex items-center space-x-4">
                    <button id="sidebarToggle"
                        class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Tableau de Bord</h1>
                </div>

                <div class="flex items-center space-x-4">
                    
                    <!-- Notifications -->
                    <?php require_once 'notify.php'; ?>

                    <button id="themeToggle"
                        class="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-moon dark:hidden text-lg"></i>
                        <i class="fas fa-sun hidden dark:block text-lg"></i>
                    </button>

                    <div class="relative">
                        <button id="userDropdown"
                            class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <?php if (!empty($user['photo'])): ?>
                            <img src="<?= BASE_URL ?>/assets/uploads/users/<?= htmlspecialchars($current_user['photo']) ?>"
                                class="w-8 h-8 rounded-full object-cover" alt="Profile">
                            <?php else: ?>
                            <div
                                class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-sm text-gray-500 dark:text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <span class="hidden md:block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400"></i>
                        </button>

                        <div id="userDropdownMenu"
                            class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </p>
                            </div>
                            <div class="py-2">
                                <a href="profile.php"
                                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-user mr-2"></i>Mon Profil
                                </a>
                                <a href="logout.php"
                                    class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white">Tableau de Bord</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Bienvenue,
                        <?php echo htmlspecialchars($user['first_name']); ?> !</p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <?php echo date('l j F Y'); ?>
                </div>
            </div>            

            <!-- Statistiques principales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Livres -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Livres</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats_livres['total_livres']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Stock: <?php echo number_format($stats_livres['stock_total']); ?>
                            </p>
                        </div>
                        <div
                            class="h-12 w-12 bg-library-100 dark:bg-library-900 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-library-600 dark:text-library-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Abonnés -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Abonnés</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats_abonnes['total_abonnes']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Actifs: <?php echo number_format($stats_abonnes['abonnes_actifs']); ?>
                            </p>
                        </div>
                        <div
                            class="h-12 w-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Emprunts en Cours -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Emprunts en Cours</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats_emprunts['emprunts_cours']); ?>
                            </p>
                            <p class="text-xs text-red-500">
                                En retard: <?php echo number_format($stats_emprunts['emprunts_retard']); ?>
                            </p>
                        </div>
                        <div class="h-12 w-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Amendes Impayées -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Amendes Impayées</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats_amendes['amendes_impayees']); ?>
                            </p>
                            <p class="text-xs text-red-500">
                                <?php echo number_format($stats_amendes['montant_impaye'], 2); ?> €
                            </p>
                        </div>
                        <div class="h-12 w-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Emprunts Récents -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Emprunts Récents</h3>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($emprunts_recents)): ?>
                        <div class="space-y-4">
                            <?php foreach ($emprunts_recents as $emprunt): ?>
                            <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-book text-library-600 dark:text-library-400"></i>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($emprunt['titre']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        par <?php echo htmlspecialchars($emprunt['nom'] . ' ' . $emprunt['prenom']); ?>
                                        • Retour:
                                        <?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php if ($emprunt['date_retour_prevue'] < date('Y-m-d')): ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        En retard
                                    </span>
                                    <?php else: ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        En cours
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4">
                            <a href="modules/emprunts/views/index"
                                class="text-library-600 hover:text-library-700 text-sm font-medium">
                                Voir tous les emprunts →
                            </a>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-400">Aucun emprunt récent.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Livres Populaires -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Livres les Plus Empruntés</h3>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($livres_populaires)): ?>
                        <div class="space-y-4">
                            <?php foreach ($livres_populaires as $index => $livre): ?>
                            <div class="flex items-center">
                                <div
                                    class="flex-shrink-0 w-8 h-8 bg-library-100 dark:bg-library-900 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-semibold text-library-600 dark:text-library-400">
                                        <?php echo $index + 1; ?>
                                    </span>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($livre['titre']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($livre['auteur']); ?>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="text-sm text-library-600 dark:text-library-400 font-medium">
                                        <?php echo $livre['nb_emprunts']; ?> emprunts
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4">
                            <a href="modules/livres/views/index"
                                class="text-library-600 hover:text-library-700 text-sm font-medium">
                                Voir tous les livres →
                            </a>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-500 dark:text-gray-400">Aucune donnée disponible.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Graphique des Emprunts -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 h-80">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Évolution des Emprunts</h3>
                    <canvas id="empruntsChart" height="200"></canvas>
                </div>

                <!-- Graphique des Catégories -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 h-80">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Répartition par Catégories</h3>
                    <canvas id="categoriesChart" height="200"></canvas>
                </div>
            </div>

            <!-- Actions Rapides -->
            <div class="mt-8">
                <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Actions Rapides</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="modules/emprunts/views/add"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 text-center hover:shadow-xl transition-shadow group">
                        <i
                            class="fas fa-plus-circle text-3xl text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300"></i>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nouvel Emprunt</p>
                    </a>

                    <a href="modules/abonnes/views/add"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 text-center hover:shadow-xl transition-shadow group">
                        <i
                            class="fas fa-user-plus text-3xl text-green-600 dark:text-green-400 group-hover:text-green-700 dark:group-hover:text-green-300"></i>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nouvel Abonné</p>
                    </a>

                    <a href="modules/livres/views/add"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 text-center hover:shadow-xl transition-shadow group">
                        <i
                            class="fas fa-book-medical text-3xl text-purple-600 dark:text-purple-400 group-hover:text-purple-700 dark:group-hover:text-purple-300"></i>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nouveau Livre</p>
                    </a>

                    <a href="modules/emprunts/views/index?statut=en_retard"
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 text-center hover:shadow-xl transition-shadow group">
                        <i
                            class="fas fa-clock text-3xl text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300"></i>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Retards</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Sidebar functionality
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });

    document.getElementById('sidebarClose').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });

    document.getElementById('sidebarOverlay').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });

    // User Dropdown
    const userDropdown = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    userDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdownMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        userDropdownMenu.classList.add('hidden');
    });

    // Dark Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    const currentTheme = localStorage.getItem('theme') || 'light';
    html.classList.toggle('dark', currentTheme === 'dark');

    themeToggle.addEventListener('click', () => {
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });

    // Charts
    document.addEventListener('DOMContentLoaded', function() {
        // Chart.js configuration for Emprunts
        const empruntsCtx = document.getElementById('empruntsChart').getContext('2d');
        const empruntsChart = new Chart(empruntsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Emprunts',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100 // valeur max adaptée à tes données
                    }
                }
            }
        });

        // Chart.js configuration for Categories
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Romans', 'Documentaires', 'BD', 'Contes', 'Poésie'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(139, 92, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
    </script>
</body>

</html>