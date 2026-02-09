<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new EmpruntController();

// Pagination et filtres
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(10, min(100, (int)$_GET['limit'])) : 20;
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$filters = [];
if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
if (isset($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (isset($_GET['abonne_id'])) $filters['abonne_id'] = $_GET['abonne_id'];
if (isset($_GET['livre_id'])) $filters['livre_id'] = $_GET['livre_id'];
if (isset($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
if (isset($_GET['date_fin'])) $filters['date_fin'] = $_GET['date_fin'];
if (isset($_GET['en_retard'])) $filters['en_retard'] = $_GET['en_retard'];

$result = $controller->index($filters, $page, $limit, $sort, $order);
$emprunts = $result['data'];
$total_pages = $result['pages'];
$total_records = $result['total'];

$stats = $controller->getStats();

// Messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel Abonné - Bibliothèque UN JOUR NOUVEAU</title>
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
                            <?php echo $pageTitle ?? 'Gestion des Emprunts'; ?></h1>
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
                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white">Gestion des Emprunts</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Suivi des prêts de livres et retours</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button onclick="exportData('excel')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                    <button onclick="exportData('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </button>
                    <a href="add.php" class="bg-library-600 hover:bg-library-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i>Nouvel Emprunt
                    </a>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-hand-holding text-library-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Emprunts en Cours</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo $stats['en_cours']; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-red-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">En Retard</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo $stats['en_retard']; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-plus-circle text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nouveaux ce Mois</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo $stats['nouveaux_mois']; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-undo text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Retours ce Mois</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo $stats['retours_mois']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Filtres de recherche</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Livre, abonné..."
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                        <select name="statut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Tous les statuts</option>
                            <option value="en_cours" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                            <option value="rendu" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'rendu' ? 'selected' : ''; ?>>Rendu</option>
                            <option value="en_retard" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'en_retard' ? 'selected' : ''; ?>>En retard</option>
                            <option value="perdu" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'perdu' ? 'selected' : ''; ?>>Perdu</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date début</label>
                        <input type="date" name="date_debut" value="<?php echo htmlspecialchars($_GET['date_debut'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date fin</label>
                        <input type="date" name="date_fin" value="<?php echo htmlspecialchars($_GET['date_fin'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">En retard</label>
                        <select name="en_retard" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Tous</option>
                            <option value="1" <?php echo isset($_GET['en_retard']) && $_GET['en_retard'] == '1' ? 'selected' : ''; ?>>En retard seulement</option>
                        </select>
                    </div>

                    <div class="md:col-span-6 flex gap-3">
                        <button type="submit" class="bg-library-600 hover:bg-library-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-redo mr-2"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tableau des emprunts -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Livre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Abonné</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dates</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Retard</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($emprunts as $emprunt): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($emprunt['livre_titre']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($emprunt['code_livre']); ?>
                                    </div>
                                    <?php if ($emprunt['auteur']): ?>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        <?php echo htmlspecialchars($emprunt['auteur']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($emprunt['abonne_nom']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        N° <?php echo htmlspecialchars($emprunt['numero_abonne']); ?>
                                    </div>
                                    <?php if ($emprunt['classe']): ?>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        <?php echo htmlspecialchars($emprunt['classe']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        Emprunté: <?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Retour prévu: <?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?>
                                    </div>
                                    <?php if ($emprunt['date_retour_effective']): ?>
                                    <div class="text-sm text-green-600 dark:text-green-400">
                                        Rendu: <?php echo date('d/m/Y', strtotime($emprunt['date_retour_effective'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statut_classes = [
                                        'en_cours' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'rendu' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'en_retard' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'perdu' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    ];
                                    
                                    // Déterminer le statut d'affichage
                                    $display_statut = $emprunt['statut'];
                                    if ($emprunt['statut'] == 'en_cours' && $emprunt['jours_retard'] > 0) {
                                        $display_statut = 'en_retard';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statut_classes[$display_statut]; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $display_statut)); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($emprunt['statut'] == 'en_cours' && $emprunt['jours_retard'] > 0): ?>
                                    <div class="text-sm text-red-600 dark:text-red-400 font-medium">
                                        <?php echo $emprunt['jours_retard']; ?> jour(s)
                                    </div>
                                    <?php elseif ($emprunt['statut'] == 'en_cours'): ?>
                                    <div class="text-sm text-green-600 dark:text-green-400">
                                        À temps
                                    </div>
                                    <?php else: ?>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        -
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-3">
                                        <a href="view.php?id=<?php echo $emprunt['id']; ?>" class="text-library-600 hover:text-library-900 dark:text-library-400 dark:hover:text-library-300 transition-colors" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($emprunt['statut'] == 'en_cours'): ?>
                                        <button onclick="renewEmprunt(<?php echo $emprunt['id']; ?>)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors" title="Renouveler">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        <button onclick="returnBook(<?php echo $emprunt['id']; ?>)" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors" title="Retourner le livre">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Précédent
                        </a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Suivant
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Affichage de <span class="font-medium"><?php echo (($page - 1) * $limit) + 1; ?></span>
                                à <span class="font-medium"><?php echo min($page * $limit, $total_records); ?></span>
                                sur <span class="font-medium"><?php echo $total_records; ?></span> résultats
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" class="<?php echo $i == $page ? 'bg-library-50 border-library-500 text-library-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
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

    <!-- Modal de retour -->
    <div id="returnModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Retourner le livre</h3>
                <form id="returnForm">
                    <input type="hidden" id="returnEmpruntId" name="id">
                    <div class="mt-4 px-7 py-3 text-left">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de retour</label>
                        <input type="date" name="date_retour_effective" value="<?php echo date('Y-m-d'); ?>" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">État du livre au retour</label>
                        <select name="etat_livre_retour" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                            <option value="bon">Bon état</option>
                            <option value="use">Usé</option>
                            <option value="deteriore">Détérioré</option>
                        </select>
                        
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">Observations</label>
                        <textarea name="observations_retour" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white" placeholder="Commentaires sur le retour..."></textarea>
                    </div>
                    <div class="flex items-center px-4 py-3">
                        <button type="button" onclick="closeReturnModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 mr-2">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Confirmer le retour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function renewEmprunt(id) {
        if (confirm('Voulez-vous renouveler cet emprunt pour 14 jours supplémentaires ?')) {
            fetch('../controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=renew&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php?success=' + encodeURIComponent(data.message);
                } else {
                    alert(data.message || 'Erreur lors du renouvellement');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du renouvellement');
            });
        }
    }

    function returnBook(id) {
        document.getElementById('returnEmpruntId').value = id;
        document.getElementById('returnModal').classList.remove('hidden');
    }

    function closeReturnModal() {
        document.getElementById('returnModal').classList.add('hidden');
    }

    document.getElementById('returnForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'return');

        fetch('../controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php?success=' + encodeURIComponent(data.message);
            } else {
                alert(data.message || 'Erreur lors du retour');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du retour');
        });
    });

    function exportData(type) {
        const params = new URLSearchParams(window.location.search);
        params.set('export', type);
        window.location.href = 'export.php?' + params.toString();
    }

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