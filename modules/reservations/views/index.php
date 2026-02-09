<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

// Vérifier les permissions pour ce module
requirePermission('reservations', 'read');
requireLogin();

$user = getUserData();
$controller = new ReservationController();

// Paramètres de pagination et filtres
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(10, min(100, (int)$_GET['limit'])) : 20;
$sort = $_GET['sort'] ?? 'date_reservation';
$order = $_GET['order'] ?? 'DESC';

// Filtres
$filters = [];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
if (!empty($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (!empty($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
if (!empty($_GET['date_fin'])) $filters['date_fin'] = $_GET['date_fin'];
if (!empty($_GET['expires_soon'])) $filters['expires_soon'] = true;

// Récupérer les données
$result = $controller->index($filters, $page, $limit, $sort, $order);
$reservations = $result['data'];
$total_pages = $result['pages'];
$total_records = $result['total'];

// Statistiques
$stats = $controller->getStats();

// Messages de succès/erreur
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

$pageTitle = 'Gestion des Réservations';
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations - Bibliothèque UN JOUR NOUVEAU</title>
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
                            <?php echo $pageTitle ?? 'Gestion des Réservations'; ?>
                        </h1>
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
                           <img src="<?= BASE_URL ?>/assets/uploads/users/<?= htmlspecialchars($current_user['photo']) ?>"
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
                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-bookmark text-library-600 mr-3"></i>
                        Réservations
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Gestion des réservations de livres</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button onclick="exportData('excel')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                    <a href="create"
                        class="bg-library-600 hover:bg-library-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i>Nouvelle Réservation
                    </a>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bookmark text-library-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Actives</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['actives']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-orange-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expirent Bientôt</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['expirent_bientot']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expirées</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['expirees']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Satisfaites ce mois</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($stats['satisfaites_mois']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Filtres et Recherche</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            placeholder="Nom, livre, code..."
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                        <select name="statut"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Tous les statuts</option>
                            <option value="active"
                                <?php echo ($_GET['statut'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="satisfaite"
                                <?php echo ($_GET['statut'] ?? '') === 'satisfaite' ? 'selected' : ''; ?>>Satisfaite
                            </option>
                            <option value="expiree"
                                <?php echo ($_GET['statut'] ?? '') === 'expiree' ? 'selected' : ''; ?>>Expirée</option>
                            <option value="annulee"
                                <?php echo ($_GET['statut'] ?? '') === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                            début</label>
                        <input type="date" name="date_debut"
                            value="<?php echo htmlspecialchars($_GET['date_debut'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date fin</label>
                        <input type="date" name="date_fin"
                            value="<?php echo htmlspecialchars($_GET['date_fin'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full bg-library-600 hover:bg-library-700 text-white px-4 py-3 rounded-xl font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </div>

                    <div class="md:col-span-6 flex flex-wrap gap-3 mt-4">
                        <a href="?expires_soon=1"
                            class="text-orange-600 hover:text-orange-700 font-medium px-4 py-2 bg-orange-50 rounded-lg transition-colors">
                            <i class="fas fa-clock mr-1"></i>Expirent bientôt
                        </a>
                        <?php if ($stats['expirees'] > 0): ?>
                        <button onclick="marquerExpirees()"
                            class="text-red-600 hover:text-red-700 font-medium px-4 py-2 bg-red-50 rounded-lg transition-colors">
                            <i class="fas fa-times mr-1"></i>Marquer expirées (<?php echo $stats['expirees']; ?>)
                        </button>
                        <?php endif; ?>
                        <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                            <?php echo number_format($total_records); ?> réservation(s)
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table des réservations -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date_reservation', 'order' => $sort === 'date_reservation' && $order === 'DESC' ? 'ASC' : 'DESC'])); ?>"
                                        class="flex items-center hover:text-library-600">
                                        Réservation
                                        <?php if ($sort === 'date_reservation'): ?>
                                        <i
                                            class="fas fa-sort-<?php echo $order === 'DESC' ? 'down' : 'up'; ?> ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Livre
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Abonné
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'priorite', 'order' => $sort === 'priorite' && $order === 'DESC' ? 'ASC' : 'DESC'])); ?>"
                                        class="flex items-center hover:text-library-600">
                                        Priorité
                                        <?php if ($sort === 'priorite'): ?>
                                        <i
                                            class="fas fa-sort-<?php echo $order === 'DESC' ? 'down' : 'up'; ?> ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date_expiration', 'order' => $sort === 'date_expiration' && $order === 'DESC' ? 'ASC' : 'DESC'])); ?>"
                                        class="flex items-center hover:text-library-600">
                                        Expiration
                                        <?php if ($sort === 'date_expiration'): ?>
                                        <i
                                            class="fas fa-sort-<?php echo $order === 'DESC' ? 'down' : 'up'; ?> ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php if (!empty($reservations) && is_array($reservations)): ?>
                            <?php foreach ($reservations as $reservation): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            #<?php echo str_pad($reservation['id'], 5, '0', STR_PAD_LEFT); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($reservation['livre_titre']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($reservation['code_livre']); ?>
                                            <?php if ($reservation['quantite_disponible'] > 0): ?>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200 ml-2">
                                                Disponible (<?php echo $reservation['quantite_disponible']; ?>)
                                            </span>
                                            <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200 ml-2">
                                                Non disponible
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($reservation['abonne_nom']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo htmlspecialchars($reservation['numero_abonne'] . ' - ' . $reservation['classe']); ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                        Position <?php echo (int) $reservation['priorite']; ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            <?php echo date('d/m/Y', strtotime($reservation['date_expiration'])); ?>
                                        </div>
                                        <div
                                            class="text-sm <?php echo $reservation['jours_restants'] <= 3 ? 'text-red-600' : 'text-gray-500 dark:text-gray-400'; ?>">
                                            <?php if ($reservation['jours_restants'] > 0): ?>
                                            <?php echo $reservation['jours_restants']; ?> jour(s) restant(s)
                                            <?php elseif ($reservation['jours_restants'] == 0): ?>
                                            Expire aujourd'hui
                                            <?php else: ?>
                                            Expirée (<?php echo abs($reservation['jours_restants']); ?> jour(s))
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
            $status_classes = [
                'active' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200',
                'satisfaite' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200',
                'expiree' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200',
                'annulee' => 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200'
            ];
            $status_labels = [
                'active' => 'Active',
                'satisfaite' => 'Satisfaite',
                'expiree' => 'Expirée',
                'annulee' => 'Annulée'
            ];
            ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_classes[$reservation['statut']] ?? ''; ?>">
                                        <?php echo $status_labels[$reservation['statut']] ?? $reservation['statut']; ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-3">
                                        <a href="view?id=<?php echo $reservation['id']; ?>"
                                            class="text-library-600 hover:text-library-900 dark:text-library-400 dark:hover:text-library-300 transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if ($reservation['statut'] === 'active'): ?>
                                        <?php if ($reservation['quantite_disponible'] > 0): ?>
                                        <button onclick="creerEmprunt(<?php echo $reservation['id']; ?>)"
                                            class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                        <?php endif; ?>

                                        <button onclick="changerStatut(<?php echo $reservation['id']; ?>, 'annulee')"
                                            class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>

                                        <a href="edit?id=<?php echo $reservation['id']; ?>"
                                            class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Aucune réservation trouvée
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>

                <?php if (empty($reservations)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-bookmark text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune réservation</h3>
                    <p class="text-gray-500 dark:text-gray-400">Aucune réservation ne correspond aux critères
                        sélectionnés.</p>
                </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div
                    class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
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
                                sur <span class="font-medium"><?php echo $total_records; ?></span> résultats
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"
                                    class="<?php echo $i == $page ? 'bg-library-50 border-library-500 text-library-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
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
    function changerStatut(id, statut) {
        const messages = {
            'satisfaite': 'Marquer cette réservation comme satisfaite ?',
            'expiree': 'Marquer cette réservation comme expirée ?',
            'annulee': 'Annuler cette réservation ?',
            'active': 'Réactiver cette réservation ?'
        };

        if (confirm(messages[statut] || 'Changer le statut de cette réservation ?')) {
            const formData = new FormData();
            formData.append('action', 'change_status');
            formData.append('id', id);
            formData.append('statut', statut);

            fetch('../controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur lors de l\'opération');
                });
        }
    }

    function creerEmprunt(id) {
        if (confirm('Créer un emprunt à partir de cette réservation ?')) {
            const formData = new FormData();
            formData.append('action', 'create_emprunt');
            formData.append('id', id);

            fetch('../controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Emprunt créé avec succès !');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur lors de la création de l\'emprunt');
                });
        }
    }

    function marquerExpirees() {
        if (confirm('Marquer automatiquement toutes les réservations expirées ?')) {
            const formData = new FormData();
            formData.append('action', 'mark_expired');

            fetch('../controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur lors de l\'opération');
                });
        }
    }

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
    </script>
</body>

</html>