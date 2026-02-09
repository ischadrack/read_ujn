<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

// Vérifier les permissions pour ce module
requirePermission('abonnes', 'read');
requireLogin();

$user = getUserData();
$controller = new AbonneController();

// Pagination et filtres
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(10, min(100, (int)$_GET['limit'])) : 20;
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$filters = [];
if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
if (isset($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (isset($_GET['niveau'])) $filters['niveau'] = $_GET['niveau'];
if (isset($_GET['classe'])) $filters['classe'] = $_GET['classe'];

$result = $controller->index($filters, $page, $limit, $sort, $order);
$abonnes = $result['data'];
$total_pages = $result['pages'];
$total_records = $result['total'];

$stats = $controller->getStats();

// Messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// Récupérer les classes pour les filtres
$classes_stmt = $db->prepare("SELECT DISTINCT classe FROM abonnes WHERE classe != '' ORDER BY classe");
$classes_stmt->execute();
$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Abonnés - Bibliothèque UN JOUR NOUVEAU</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&family=Fredoka:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
    <?php require_once '../../includes/sidebar.php'; ?>
    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">

        <!-- Header -->
        <header
            class="sticky top-0 z-50 bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-800 dark:to-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between h-16 px-6">
                <div class="flex items-center space-x-4">
                    <button id="sidebarToggle"
                        class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-800 dark:text-white">
                            <?php echo $pageTitle ?? 'Gestion des Abonnés'; ?></h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">Système de Gestion de
                            Bibliothèque</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <?php require_once '../../../notify.php'; ?>

                    <!-- Dark Mode Toggle -->
                    <button id="themeToggle"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-moon dark:hidden text-lg"></i>
                        <i class="fas fa-sun hidden dark:block text-lg"></i>
                    </button>

                    <!-- User Dropdown -->
                    <div class="relative">
                        <button id="userDropdown"
                            class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <?php if (!empty($user['photo'])): ?>
                           <img src="<?php echo BASE_URL; ?>/assets/uploads/<?= htmlspecialchars($user['photo'], ENT_QUOTES) ?>"
                                class="w-8 h-8 rounded-full object-cover border-2 border-library-200 dark:border-library-600"
                                alt="Profile">
                            <?php else: ?>
                            <div
                                class="w-8 h-8 bg-gradient-to-br from-library-500 to-purple-600 rounded-full flex items-center justify-center border-2 border-library-200 dark:border-library-600">
                                <span class="text-white text-sm font-semibold">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                </span>
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
                                <a href="../../../profile.php"
                                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-user mr-2"></i>Mon Profil
                                </a>
                                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                <a href="../../../logout.php"
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
            <!-- Messages -->
            <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white">Abonnés de la Bibliothèque
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Gestion des abonnements et suivi des élèves</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <!-- Dropdown Export -->
                    <div class="relative">
                        <button id="exportDropdown"
                            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                            <i class="fas fa-download"></i>
                            Exporter
                            <i class="fas fa-chevron-down text-sm ml-1"></i>
                        </button>
                        <div id="exportDropdownMenu"
                            class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                            <div class="py-2">
                                <button onclick="exportData('excel')"
                                    class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-3">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-file-excel text-green-600 dark:text-green-400"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Export Excel</div>
                                        <div class="text-xs text-gray-500">Liste complète (.xlsx)</div>
                                    </div>
                                </button>
                                <button onclick="exportData('pdf')"
                                    class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-red-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-3">
                                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-file-pdf text-red-600 dark:text-red-400"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Export PDF</div>
                                        <div class="text-xs text-gray-500">Liste des abonnés (.pdf)</div>
                                    </div>
                                </button>
                                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                <button onclick="exportBulkCards()"
                                    class="w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-3">
                                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-id-card text-purple-600 dark:text-purple-400"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Cartes en lot</div>
                                        <div class="text-xs text-gray-500">Toutes les fiches (.pdf)</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <a href="add"
                        class="bg-gradient-to-r from-library-600 to-library-700 hover:from-library-700 hover:to-library-800 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        Nouvel Abonné
                    </a>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-200 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-library-100 dark:bg-library-900 rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-library-600 dark:text-library-400 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Abonnés</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['total_abonnes']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-200 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center">
                                <i class="fas fa-user-plus text-green-600 dark:text-green-400 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nouveaux ce Mois</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['nouveaux_mois']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-200 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock text-orange-600 dark:text-orange-400 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Abonnements Expirés</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['expires']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-200 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-xl flex items-center justify-center">
                                <i class="fas fa-ban text-red-600 dark:text-red-400 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Suspendus</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['suspendus']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-filter text-library-600"></i>
                    Filtres de recherche
                </h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            placeholder="Nom, prénom, numéro..."
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                        <select name="statut"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Tous les statuts</option>
                            <option value="actif"
                                <?php echo isset($_GET['statut']) && $_GET['statut'] == 'actif' ? 'selected' : ''; ?>>
                                Actif</option>
                            <option value="suspendu"
                                <?php echo isset($_GET['statut']) && $_GET['statut'] == 'suspendu' ? 'selected' : ''; ?>>
                                Suspendu</option>
                            <option value="expire"
                                <?php echo isset($_GET['statut']) && $_GET['statut'] == 'expire' ? 'selected' : ''; ?>>
                                Expiré</option>
                            <option value="archive"
                                <?php echo isset($_GET['statut']) && $_GET['statut'] == 'archive' ? 'selected' : ''; ?>>
                                Archivé</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Niveau</label>
                        <select name="niveau"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Tous les niveaux</option>
                            <option value="maternelle"
                                <?php echo isset($_GET['niveau']) && $_GET['niveau'] == 'maternelle' ? 'selected' : ''; ?>>
                                Maternelle</option>
                            <option value="primaire"
                                <?php echo isset($_GET['niveau']) && $_GET['niveau'] == 'primaire' ? 'selected' : ''; ?>>
                                Primaire</option>
                            <option value="secondaire"
                                <?php echo isset($_GET['niveau']) && $_GET['niveau'] == 'secondaire' ? 'selected' : ''; ?>>
                                Secondaire</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Classe</label>
                        <select name="classe"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($classes as $classe): ?>
                            <option value="<?php echo htmlspecialchars($classe['classe']); ?>"
                                <?php echo isset($_GET['classe']) && $_GET['classe'] == $classe['classe'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe['classe']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Trier par</label>
                        <select name="sort"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Date
                                d'inscription</option>
                            <option value="nom" <?php echo $sort == 'nom' ? 'selected' : ''; ?>>Nom</option>
                            <option value="classe" <?php echo $sort == 'classe' ? 'selected' : ''; ?>>Classe</option>
                            <option value="date_expiration" <?php echo $sort == 'date_expiration' ? 'selected' : ''; ?>>
                                Date d'expiration</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ordre</label>
                        <select name="order"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                            <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                        </select>
                    </div>

                    <div class="md:col-span-6 flex gap-3">
                        <button type="submit"
                            class="bg-library-600 hover:bg-library-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                            <i class="fas fa-search"></i>Rechercher
                        </button>
                        <a href="index"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                            <i class="fas fa-redo"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tableau des abonnés -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Abonné</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Classe</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Statut</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Emprunts</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Parent</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Expiration</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($abonnes as $abonne): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <?php if (!empty($abonne['photo'])): ?>
                                            <img src="../../../../assets/uploads/abonnes/<?php echo htmlspecialchars($abonne['photo']); ?>"
                                                class="h-12 w-12 rounded-full object-cover border-2 border-library-200 dark:border-library-600" alt="Photo">
                                            <?php else: ?>
                                            <div
                                                class="h-12 w-12 rounded-full bg-gradient-to-br from-library-100 to-library-200 dark:from-library-900 dark:to-library-800 flex items-center justify-center border-2 border-library-200 dark:border-library-600">
                                                <i class="fas fa-user text-library-600 dark:text-library-400 text-lg"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($abonne['nom'] . ' ' . $abonne['prenom']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                                <span class="bg-library-100 dark:bg-library-900 px-2 py-1 rounded-full text-xs font-medium text-library-700 dark:text-library-300">
                                                    N° <?php echo htmlspecialchars($abonne['numero_abonne']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($abonne['classe']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo ucfirst($abonne['niveau']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statut_classes = [
                                        'actif' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'suspendu' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'expire' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'archive' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    ];
                                    
                                    // Vérifier si l'abonnement est expiré
                                    $display_statut = $abonne['statut'];
                                    if ($abonne['statut'] == 'actif' && $abonne['date_expiration'] < date('Y-m-d')) {
                                        $display_statut = 'expire';
                                    }
                                    ?>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $statut_classes[$display_statut]; ?>">
                                        <?php echo ucfirst($display_statut); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo $abonne['emprunts_actifs']; ?>/<?php echo $abonne['nb_emprunts_max']; ?>
                                        </div>
                                        <?php if ($abonne['emprunts_retard'] > 0): ?>
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            <i class="fas fa-exclamation-triangle mr-1 text-xs"></i>
                                            <?php echo $abonne['emprunts_retard']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($abonne['nom_parent']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-phone text-xs"></i>
                                        <?php echo htmlspecialchars($abonne['telephone_parent']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo date('d/m/Y', strtotime($abonne['date_expiration'])); ?>
                                    </div>
                                    <?php if ($abonne['date_expiration'] < date('Y-m-d')): ?>
                                    <div class="text-sm text-red-600 dark:text-red-400 font-medium flex items-center gap-1">
                                        <i class="fas fa-clock text-xs"></i>
                                        Expiré
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <div class="relative">
                                            <button onclick="toggleActionMenu(<?php echo $abonne['id']; ?>)"
                                                class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-2 rounded-lg transition-colors">
                                                <i class="fas fa-ellipsis-v text-gray-600 dark:text-gray-300"></i>
                                            </button>
                                            <div id="actionMenu<?php echo $abonne['id']; ?>"
                                                class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                                                <div class="py-2">
                                                    <a href="view?id=<?php echo $abonne['id']; ?>"
                                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                                        <i class="fas fa-eye text-library-600 dark:text-library-400 w-4"></i>
                                                        Voir détails
                                                    </a>
                                                    <button onclick="exportFiche(<?php echo $abonne['id']; ?>)"
                                                        class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                                        <i class="fas fa-id-card text-purple-600 dark:text-purple-400 w-4"></i>
                                                        Fiche d'abonnement
                                                    </button>
                                                    <a href="edit?id=<?php echo $abonne['id']; ?>"
                                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                                        <i class="fas fa-edit text-green-600 dark:text-green-400 w-4"></i>
                                                        Modifier
                                                    </a>
                                                    <a href="../../emprunts/views/add?abonne_id=<?php echo $abonne['id']; ?>"
                                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                                        <i class="fas fa-book text-blue-600 dark:text-blue-400 w-4"></i>
                                                        Nouvel emprunt
                                                    </a>
                                                    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                                    <button onclick="deleteAbonne(<?php echo $abonne['id']; ?>)"
                                                        class="w-full text-left block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 flex items-center gap-3">
                                                        <i class="fas fa-trash w-4"></i>
                                                        Supprimer
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div
                    class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-600">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Précédent
                        </a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Suivant
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Affichage de <span class="font-medium"><?php echo (($page - 1) * $limit) + 1; ?></span>
                                à <span class="font-medium"><?php echo min($page * $limit, $total_records); ?></span>
                                sur <span class="font-medium"><?php echo number_format($total_records); ?></span> résultats
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"
                                    class="<?php echo $i == $page ? 'bg-library-50 border-library-500 text-library-600 dark:bg-library-900 dark:text-library-300' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function deleteAbonne(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet abonné ? Cette action est irréversible.')) {
            fetch('../controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=delete&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index?success=' + encodeURIComponent(data.message);
                    } else {
                        alert(data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression');
                });
        }
    }

    function exportData(type) {
        const params = new URLSearchParams(window.location.search);
        params.set('export', type);
        window.location.href = 'export.php?' + params.toString();
    }

    function exportFiche(id) {
        window.open('export.php?export=fiche&id=' + id, '_blank');
    }

    function exportBulkCards() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'bulk_cards');
        window.location.href = 'export.php?' + params.toString();
    }

    function toggleActionMenu(id) {
        // Fermer tous les autres menus
        document.querySelectorAll('[id^="actionMenu"]').forEach(menu => {
            if (menu.id !== `actionMenu${id}`) {
                menu.classList.add('hidden');
            }
        });
        
        // Toggler le menu courant
        const menu = document.getElementById(`actionMenu${id}`);
        menu.classList.toggle('hidden');
    }

    // Export Dropdown
    const exportDropdown = document.getElementById('exportDropdown');
    const exportDropdownMenu = document.getElementById('exportDropdownMenu');

    exportDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
        exportDropdownMenu.classList.toggle('hidden');
    });

    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (!exportDropdown.contains(e.target)) {
            exportDropdownMenu.classList.add('hidden');
        }
        
        // Fermer les menus d'actions
        if (!e.target.closest('[id^="actionMenu"]') && !e.target.closest('button[onclick*="toggleActionMenu"]')) {
            document.querySelectorAll('[id^="actionMenu"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

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

    // Auto-submit form on filter change
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("form");

        form.querySelectorAll("select").forEach(select => {
            select.addEventListener("change", () => {
                form.submit();
            });
        });

        const searchInput = form.querySelector("input[name='search']");
        let timer;
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    form.submit();
                }, 500);
            });
        }
    });
    </script>
</body>

</html>