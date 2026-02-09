<?php
require_once '../../../config/config.php';
require_once '../controller.php';
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
global $db;
$classes_stmt = $db->prepare("SELECT DISTINCT classe FROM abonnes WHERE classe != '' ORDER BY classe");
$classes_stmt->execute();
$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Gestion des Abonnés";
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Abonnés - Bibliothèque UN JOUR NOUVEAU</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

<body class="font-ubuntu bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-900 dark:to-gray-800 min-h-screen">

    <!-- Sidebar -->
    <?php require_once '../../components/Sidebar.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        
        <!-- Header -->
        <?php require_once '../../components/Header.php'; ?>

        <div class="p-6">
            <!-- Messages -->
            <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 animate-pulse">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 animate-pulse">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-users text-library-600 mr-3"></i>
                        Abonnés de la Bibliothèque
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Gestion des abonnements et suivi des élèves</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button onclick="exportData('excel')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                    <button onclick="exportData('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </button>
                    <a href="add" class="bg-library-600 hover:bg-library-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-0.5">
                        <i class="fas fa-plus mr-2"></i>Nouvel Abonné
                    </a>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-library-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-library-100 dark:bg-library-900/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-library-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Abonnés</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo $stats['total_abonnes']; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-plus text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nouveaux ce Mois</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo $stats['nouveaux_mois']; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-orange-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-orange-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Abonnements Expirés</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo $stats['expires']; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-ban text-red-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Suspendus</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo $stats['suspendus']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-filter text-library-600 mr-2"></i>
                    Filtres de recherche
                </h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Nom, prénom, numéro..."
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                        <select name="statut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'actif' ? 'selected' : ''; ?>>Actif</option>
                            <option value="suspendu" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                            <option value="expire" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'expire' ? 'selected' : ''; ?>>Expiré</option>
                            <option value="archive" <?php echo isset($_GET['statut']) && $_GET['statut'] == 'archive' ? 'selected' : ''; ?>>Archivé</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Niveau</label>
                        <select name="niveau" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Tous les niveaux</option>
                            <option value="maternelle" <?php echo isset($_GET['niveau']) && $_GET['niveau'] == 'maternelle' ? 'selected' : ''; ?>>Maternelle</option>
                            <option value="primaire" <?php echo isset($_GET['niveau']) && $_GET['niveau'] == 'primaire' ? 'selected' : ''; ?>>Primaire</option>
                            <option value="secondaire" <?php echo isset($_GET['niveau']) && $_GET['niveau'] == 'secondaire' ? 'selected' : ''; ?>>Secondaire</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Classe</label>
                        <select name="classe" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($classes as $classe): ?>
                            <option value="<?php echo htmlspecialchars($classe['classe']); ?>" <?php echo isset($_GET['classe']) && $_GET['classe'] == $classe['classe'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($classe['classe']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Trier par</label>
                        <select name="sort" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Date d'inscription</option>
                            <option value="nom" <?php echo $sort == 'nom' ? 'selected' : ''; ?>>Nom</option>
                            <option value="classe" <?php echo $sort == 'classe' ? 'selected' : ''; ?>>Classe</option>
                            <option value="date_expiration" <?php echo $sort == 'date_expiration' ? 'selected' : ''; ?>>Date d'expiration</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ordre</label>
                        <select name="order" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                            <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                            <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                        </select>
                    </div>

                    <div class="md:col-span-6 flex gap-3">
                        <button type="submit" class="bg-library-600 hover:bg-library-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                        <a href="index" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-redo mr-2"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tableau des abonnés -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Abonné</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Classe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Emprunts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Expiration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($abonnes as $abonne): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if (!empty($abonne['photo'])): ?>
                                            <img src="../../../assets/uploads/abonnes/<?php echo htmlspecialchars($abonne['photo']); ?>" class="h-10 w-10 rounded-full object-cover" alt="Photo">
                                            <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-library-100 dark:bg-library-900 flex items-center justify-center">
                                                <i class="fas fa-user text-library-600 dark:text-library-400"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($abonne['nom'] . ' ' . $abonne['prenom']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                N° <?php echo htmlspecialchars($abonne['numero_abonne']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statut_classes[$display_statut]; ?>">
                                        <?php echo ucfirst($display_statut); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo $abonne['emprunts_actifs']; ?>/<?php echo $abonne['nb_emprunts_max']; ?>
                                        <?php if (($abonne['emprunts_retard'] ?? 0) > 0): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">
                                            <?php echo $abonne['emprunts_retard']; ?> en retard
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($abonne['nom_parent'] ?: ''); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($abonne['telephone_parent'] ?: ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?php echo date('d/m/Y', strtotime($abonne['date_expiration'])); ?>
                                    </div>
                                    <?php if ($abonne['date_expiration'] < date('Y-m-d')): ?>
                                    <div class="text-sm text-red-600 dark:text-red-400">
                                        Expiré
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-3">
                                        <a href="view?id=<?php echo $abonne['id']; ?>" class="text-library-600 hover:text-library-900 dark:text-library-400 dark:hover:text-library-300 transition-colors" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit?id=<?php echo $abonne['id']; ?>" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../../emprunts/views/add?abonne_id=<?php echo $abonne['id']; ?>" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors" title="Nouvel emprunt">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                        <button onclick="deleteAbonne(<?php echo $abonne['id']; ?>)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($abonnes)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
                                        <p class="text-lg font-medium text-gray-500 dark:text-gray-400">Aucun abonné trouvé</p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Essayez de modifier vos critères de recherche</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
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

    <script>
    function deleteAbonne(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet abonné ? Cette action est irréversible.')) {
            fetch('../controller.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const sidebarClose = document.getElementById('sidebarClose');

        if (sidebarToggle && sidebar && overlay) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });

            if (sidebarClose) {
                sidebarClose.addEventListener('click', function() {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }

            overlay.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }

        // User Dropdown
        const userDropdown = document.getElementById('userDropdown');
        const userDropdownMenu = document.getElementById('userDropdownMenu');

        if (userDropdown && userDropdownMenu) {
            userDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', () => {
                userDropdownMenu.classList.add('hidden');
            });
        }

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

        // Auto-submit form on filter change
        const form = document.querySelector("form");
        if (form) {
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
        }
    });
    </script>
</body>
</html>