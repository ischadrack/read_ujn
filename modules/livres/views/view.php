<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new LivreController();

// Récupérer l'ID du livre
$livre_id = $_GET['id'] ?? 0;
$livre = $controller->find($livre_id);

if (!$livre) {
    header("Location: index?error=" . urlencode("Livre introuvable."));
    exit;
}

// Récupérer l'historique des emprunts
$emprunts_history = $controller->getEmpruntsHistory($livre_id);

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
                            <?php echo $pageTitle ?? 'Gestion des Livres'; ?></h1>
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

            <!-- Actions rapides -->
            <div class="flex flex-wrap gap-3 mb-6">
                <a href="edit?id=<?php echo $livre['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-edit mr-2"></i>Modifier
                </a>
                <?php if ($livre['quantite_disponible'] > 0): ?>
                <a href="../../emprunts/views/add?livre_id=<?php echo $livre['id']; ?>" class="bg-library-600 hover:bg-library-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-hand-holding mr-2"></i>Nouvel Emprunt
                </a>
                <?php endif; ?>
                <a href="index" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Informations principales -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="flex items-start space-x-6">
                            <!-- Image du livre -->
                            <div class="flex-shrink-0">
                                <?php if (!empty($livre['photo'])): ?>
                                <img src="../../../../assets/uploads/livres/<?php echo htmlspecialchars($livre['photo']); ?>" class="w-32 h-44 rounded-lg object-cover shadow-md" alt="Couverture du livre">
                                <?php else: ?>
                                <div class="w-32 h-44 rounded-lg bg-library-100 dark:bg-library-900 flex items-center justify-center shadow-md">
                                    <i class="fas fa-book text-4xl text-library-600 dark:text-library-400"></i>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Informations du livre -->
                            <div class="flex-1">
                                <div class="mb-4">
                                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white mb-2">
                                        <?php echo htmlspecialchars($livre['titre']); ?>
                                    </h1>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                        <span><strong>Code:</strong> <?php echo htmlspecialchars($livre['code_livre']); ?></span>
                                        <?php if ($livre['isbn']): ?>
                                        <span><strong>ISBN:</strong> <?php echo htmlspecialchars($livre['isbn']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Auteur & Publication</h3>
                                        <p class="text-gray-600 dark:text-gray-400">
                                            <?php echo htmlspecialchars($livre['auteur']) ?: 'Non spécifié'; ?>
                                        </p>
                                        <?php if ($livre['editeur']): ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-500">
                                            Éditeur: <?php echo htmlspecialchars($livre['editeur']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if ($livre['annee_publication']): ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-500">
                                            Année: <?php echo $livre['annee_publication']; ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Catégorie</h3>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" style="background-color: <?php echo htmlspecialchars($livre['categorie_color']); ?>20; color: <?php echo htmlspecialchars($livre['categorie_color']); ?>;">
                                            <?php echo htmlspecialchars($livre['categorie_nom']); ?>
                                        </span>
                                        <?php if ($livre['niveau_classe']): ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                            Niveau: <?php echo htmlspecialchars($livre['niveau_classe']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($livre['description']): ?>
                                <div class="mb-6">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Description</h3>
                                    <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                        <?php echo nl2br(htmlspecialchars($livre['description'])); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des emprunts -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mt-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-history mr-2 text-library-600"></i>
                            Historique des Emprunts
                        </h2>

                        <?php if (!empty($emprunts_history)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Abonné</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dates</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php foreach ($emprunts_history as $emprunt): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($emprunt['abonne_nom']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                N° <?php echo htmlspecialchars($emprunt['numero_abonne']); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                Emprunté: <?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                Prévu: <?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?>
                                            </div>
                                            <?php if ($emprunt['date_retour_effective']): ?>
                                            <div class="text-sm text-green-600 dark:text-green-400">
                                                Rendu: <?php echo date('d/m/Y', strtotime($emprunt['date_retour_effective'])); ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php
                                            $statut_classes = [
                                                'en_cours' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                'rendu' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'en_retard' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'perdu' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                            ];
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statut_classes[$emprunt['statut']] ?? $statut_classes['en_cours']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $emprunt['statut'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>Aucun emprunt enregistré pour ce livre.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar avec informations détaillées -->
                <div class="space-y-6">
                    <!-- Statut et disponibilité -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-info-circle mr-2 text-library-600"></i>
                            Statut & Disponibilité
                        </h2>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Statut:</span>
                                <?php
                                $statut_classes = [
                                    'actif' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'inactif' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'archive' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                ];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statut_classes[$livre['statut']]; ?>">
                                    <?php echo ucfirst($livre['statut']); ?>
                                </span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Stock total:</span>
                                <span class="font-semibold text-gray-900 dark:text-white"><?php echo $livre['quantite_stock']; ?></span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Disponibles:</span>
                                <span class="font-semibold <?php echo $livre['quantite_disponible'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <?php echo $livre['quantite_disponible']; ?>
                                </span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Empruntés:</span>
                                <span class="font-semibold text-orange-600 dark:text-orange-400"><?php echo $livre['quantite_empruntee']; ?></span>
                            </div>

                            <?php if ($livre['quantite_perdue'] > 0): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Perdus:</span>
                                <span class="font-semibold text-red-600 dark:text-red-400"><?php echo $livre['quantite_perdue']; ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($livre['reservations_actives'] > 0): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Réservations:</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400"><?php echo $livre['reservations_actives']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Détails techniques -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-cog mr-2 text-library-600"></i>
                            Détails Techniques
                        </h2>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Langue:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($livre['langue']); ?></span>
                            </div>

                            <?php if ($livre['nombre_pages'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Pages:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo $livre['nombre_pages']; ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">État:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo ucfirst($livre['etat']); ?></span>
                            </div>

                            <?php if ($livre['prix_unitaire'] > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Prix:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo number_format($livre['prix_unitaire'], 2); ?> FC</span>
                            </div>
                            <?php endif; ?>

                            <?php if ($livre['date_acquisition']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Acquistion:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo date('d/m/Y', strtotime($livre['date_acquisition'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Informations système -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-database mr-2 text-library-600"></i>
                            Informations Système
                        </h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Ajouté par:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($livre['created_by_name']); ?></span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Date d'ajout:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($livre['created_at'])); ?></span>
                            </div>

                            <?php if ($livre['updated_at'] != $livre['created_at']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Dernière modif:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($livre['updated_at'])); ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Total emprunts:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo $livre['total_emprunts']; ?></span>
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