<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new CategoriesLivresController();

$id = $_GET['id'] ?? 0;
$categorie = $controller->find($id);

if (!$categorie) {
    header('Location: index?error=' . urlencode('Catégorie non trouvée'));
    exit;
}

// Récupérer les livres de cette catégorie
$livres_stmt = $db->prepare("
    SELECT l.*, 
           CONCAT(u.first_name, ' ', u.last_name) as created_by_name
    FROM livres l
    LEFT JOIN users u ON l.created_by = u.id
    WHERE l.categorie_id = ? AND l.statut = 'actif'
    ORDER BY l.titre
    LIMIT 10
");
$livres_stmt->execute([$id]);
$livres = $livres_stmt->fetchAll(PDO::FETCH_ASSOC);

// Message de succès
$success_message = $_GET['success'] ?? '';
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
                            <?php echo $pageTitle ?? 'Gestion des Abonnés'; ?></h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 hidden sm:block">Système de Gestion de
                            Bibliothèque</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
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
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-6 h-6 rounded-full" style="background-color: <?php echo $categorie['color']; ?>"></div>
                <div>
                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($categorie['nom']); ?></h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        Détails et livres de la catégorie 
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 ml-2">
                            <i class="fas fa-child mr-1"></i>
                            <?php echo $categorie['age_minimum']; ?>-<?php echo $categorie['age_maximum']; ?> ans
                        </span>
                    </p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="index" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
                <a href="edit?id=<?php echo $categorie['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
            </div>
        </div>

        <!-- Message de succès -->
        <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Informations principales -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Détails de la catégorie -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>Informations générales
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nom</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($categorie['nom']); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Couleur</label>
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 rounded-full border border-gray-300" style="background-color: <?php echo $categorie['color']; ?>"></div>
                                <span class="text-gray-900 dark:text-white font-mono text-sm"><?php echo $categorie['color']; ?></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tranche d'âge</label>
                            <p class="text-gray-900 dark:text-white"><?php echo $categorie['age_minimum']; ?> - <?php echo $categorie['age_maximum']; ?> ans</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Créée le</label>
                            <p class="text-gray-900 dark:text-white"><?php echo formatDate($categorie['created_at']); ?></p>
                        </div>
                    </div>

                    <?php if ($categorie['description']): ?>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Description</label>
                        <p class="text-gray-900 dark:text-white"><?php echo nl2br(htmlspecialchars($categorie['description'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Livres de cette catégorie -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-books mr-2"></i>Livres de cette catégorie
                        </h3>
                        <a href="../../livres/views/index?categorie_id=<?php echo $categorie['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Voir tous <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($livres)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-inbox text-gray-400 text-4xl mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-400">Aucun livre dans cette catégorie</p>
                                <a href="../../livres/views/add?categorie_id=<?php echo $categorie['id']; ?>" class="inline-block mt-3 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    Ajouter le premier livre
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($livres as $livre): ?>
                                <div class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                                <i class="fas fa-book text-indigo-600 dark:text-indigo-400"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($livre['titre']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Code: <?php echo htmlspecialchars($livre['code_livre']); ?>
                                                <?php if ($livre['auteur']): ?>
                                                    - <?php echo htmlspecialchars($livre['auteur']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4 text-sm">
                                        <div class="text-center">
                                            <p class="font-medium text-gray-900 dark:text-white"><?php echo $livre['quantite_disponible']; ?></p>
                                            <p class="text-gray-500 dark:text-gray-400">Disponible</p>
                                        </div>
                                        <a href="../../livres/views/view?id=<?php echo $livre['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar des statistiques -->
            <div class="space-y-6">
                <!-- Statistiques -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Statistiques</h3>
                    
                    <div class="space-y-4">
                        <div class="text-center p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400"><?php echo $categorie['livres_count']; ?></p>
                            <p class="text-sm text-indigo-600 dark:text-indigo-400">Livres</p>
                        </div>

                        <?php
                        // Calculer les stats de stock pour cette catégorie
                        $stock_stmt = $db->prepare("
                            SELECT 
                                SUM(quantite_stock) as stock_total,
                                SUM(quantite_disponible) as stock_disponible,
                                SUM(quantite_empruntee) as stock_emprunte,
                                COUNT(CASE WHEN quantite_disponible <= 0 THEN 1 END) as rupture,
                                AVG(nombre_pages) as pages_moyennes
                            FROM livres 
                            WHERE categorie_id = ? AND statut = 'actif'
                        ");
                        $stock_stmt->execute([$categorie['id']]);
                        $stats = $stock_stmt->fetch();
                        ?>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="text-center p-3 bg-green-50 dark:bg-green-900/30 rounded">
                                <p class="font-bold text-green-600 dark:text-green-400"><?php echo $stats['stock_total'] ?: 0; ?></p>
                                <p class="text-green-600 dark:text-green-400">Stock total</p>
                            </div>
                            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded">
                                <p class="font-bold text-blue-600 dark:text-blue-400"><?php echo $stats['stock_disponible'] ?: 0; ?></p>
                                <p class="text-blue-600 dark:text-blue-400">Disponible</p>
                            </div>
                            <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded">
                                <p class="font-bold text-yellow-600 dark:text-yellow-400"><?php echo $stats['stock_emprunte'] ?: 0; ?></p>
                                <p class="text-yellow-600 dark:text-yellow-400">Emprunté</p>
                            </div>
                            <div class="text-center p-3 bg-red-50 dark:bg-red-900/30 rounded">
                                <p class="font-bold text-red-600 dark:text-red-400"><?php echo $stats['rupture'] ?: 0; ?></p>
                                <p class="text-red-600 dark:text-red-400">Rupture</p>
                            </div>
                        </div>

                        <?php if ($stats['pages_moyennes']): ?>
                        <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/30 rounded">
                            <p class="font-bold text-purple-600 dark:text-purple-400"><?php echo round($stats['pages_moyennes']); ?></p>
                            <p class="text-purple-600 dark:text-purple-400 text-xs">Pages moyennes</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Actions</h3>
                    
                    <div class="space-y-3">
                        <a href="../../livres/views/add?categorie_id=<?php echo $categorie['id']; ?>" class="flex items-center p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                            <i class="fas fa-plus text-indigo-600 dark:text-indigo-400 text-lg mr-3"></i>
                            <div>
                                <p class="font-medium text-indigo-900 dark:text-indigo-100">Ajouter Livre</p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400">Dans cette catégorie</p>
                            </div>
                        </a>

                        <a href="../../livres/views/index?categorie_id=<?php echo $categorie['id']; ?>" class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-list text-gray-600 dark:text-gray-400 text-lg mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">Voir Livres</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Tous les livres</p>
                            </div>
                        </a>

                        <a href="../../emprunts/views/index?categorie_id=<?php echo $categorie['id']; ?>" class="flex items-center p-3 bg-orange-50 dark:bg-orange-900/30 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/50 transition-colors">
                            <i class="fas fa-exchange-alt text-orange-600 dark:text-orange-400 text-lg mr-3"></i>
                            <div>
                                <p class="font-medium text-orange-900 dark:text-orange-100">Emprunts</p>
                                <p class="text-xs text-orange-600 dark:text-orange-400">Voir les emprunts</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recommandations d'âge -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-child mr-2"></i>Tranche d'âge
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Âge minimum</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo $categorie['age_minimum']; ?> ans</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Âge maximum</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?php echo $categorie['age_maximum']; ?> ans</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-3">
                            <div class="bg-blue-600 h-2 rounded-full" 
                                 style="width: <?php echo (($categorie['age_maximum'] - $categorie['age_minimum']) / 18) * 100; ?>%; margin-left: <?php echo ($categorie['age_minimum'] / 18) * 100; ?>%;"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>0 ans</span>
                            <span>18 ans</span>
                        </div>
                    </div>
                </div>
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