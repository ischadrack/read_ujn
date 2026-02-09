<?php
require_once '../../../config/config.php';
require_once '../controller.php';

$controller = new UserController();
$current_user = $controller->getById($_SESSION['user_id']);

// Récupérer l'ID de l'utilisateur
$user_id = $_GET['id'] ?? 0;
$user_data = $controller->getById($user_id);

if (!$user_data) {
    header('Location: index?error=' . urlencode('Utilisateur non trouvé'));
    exit;
}

// Récupérer les statistiques et activités de l'utilisateur
$user_stats = $controller->getUserStatistics($user_id);
$recent_activities = $controller->getUserActivities($user_id, 10);

// Messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Utilisateur - Bibliothèque UN JOUR NOUVEAU</title>
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
    <?php require_once '../../../includes/sidebar.php'; ?>

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-800 dark:to-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between h-16 px-6">
                <div class="flex items-center space-x-4">
                    <button id="sidebarToggle" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Détails de l'Utilisateur</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <button id="themeToggle" class="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-moon dark:hidden text-lg"></i>
                        <i class="fas fa-sun hidden dark:block text-lg"></i>
                    </button>

                    <div class="relative">
                        <button id="userDropdown" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <?php if (!empty($current_user['photo'])): ?>
                                    <img src="../../../assets/uploads/users/<?php echo $current_user['photo']; ?>" alt="Photo" class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-user text-sm text-gray-500 dark:text-gray-400"></i>
                                <?php endif; ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400"></i>
                        </button>

                        <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo htmlspecialchars($current_user['email']); ?>
                                </p>
                            </div>
                            <div class="py-2">
                                <a href="../../../profile.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-user mr-2"></i>Mon Profil
                                </a>
                                <a href="../../../logout.php" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
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

            <!-- Breadcrumb -->
            <nav class="flex mb-8" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="../../../index" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-library-600 dark:text-gray-400 dark:hover:text-white">
                            <i class="fas fa-home mr-2"></i>
                            Accueil
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="index" class="ml-1 text-sm font-medium text-gray-700 hover:text-library-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Utilisateurs</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Détails</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header avec actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($user_data['photo'])): ?>
                            <img src="../../../assets/uploads/users/<?php echo $user_data['photo']; ?>" alt="Photo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-2xl text-gray-400 dark:text-gray-500"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="edit?id=<?php echo $user_data['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-edit mr-2"></i>Modifier
                    </a>
                    <a href="index" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Informations principales -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Informations Personnelles</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nom complet</label>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nom d'utilisateur</label>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    @<?php echo htmlspecialchars($user_data['username']); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Email</label>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    <?php echo htmlspecialchars($user_data['email']); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Rôle</label>
                                <?php
                                $role_classes = [
                                    'admin' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'bibliothecaire' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'assistant' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                ];
                                $role_icons = [
                                    'admin' => 'fas fa-user-shield',
                                    'bibliothecaire' => 'fas fa-user-graduate',
                                    'assistant' => 'fas fa-user-tie'
                                ];
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $role_classes[$user_data['role']]; ?>">
                                    <i class="<?php echo $role_icons[$user_data['role']]; ?> mr-2"></i>
                                    <?php echo ucfirst($user_data['role']); ?>
                                </span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Statut</label>
                                <?php
                                $status_classes = [
                                    'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'inactive' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                ];
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $status_classes[$user_data['status']]; ?>">
                                    <i class="fas fa-circle mr-2 text-xs"></i>
                                    <?php echo ucfirst($user_data['status']); ?>
                                </span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Téléphone</label>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    <?php echo htmlspecialchars($user_data['telephone'] ?? 'Non renseigné'); ?>
                                </p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Spécialité</label>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    <?php echo htmlspecialchars($user_data['specialite'] ?? 'Non renseignée'); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de création</label>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    <?php echo date('d/m/Y à H:i', strtotime($user_data['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Dernière connexion</label>
                                <p class="text-gray-900 dark:text-white font-medium">
                                    <?php echo $user_data['last_login'] ? date('d/m/Y à H:i', strtotime($user_data['last_login'])) : 'Jamais'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Activités récentes -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Activités Récentes</h2>
                        
                        <?php if (empty($recent_activities)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-history text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">Aucune activité récente</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <?php echo date('d/m/Y à H:i', strtotime($activity['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="space-y-6">
                    <!-- Statistiques générales -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statistiques</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-book text-green-600 mr-3"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Emprunts créés</span>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo $user_stats['emprunts_crees']; ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-undo text-blue-600 mr-3"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Retours traités</span>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo $user_stats['retours_traites']; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions rapides -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions Rapides</h3>
                        
                        <div class="space-y-3">
                            <a href="edit?id=<?php echo $user_data['id']; ?>" class="w-full bg-library-600 hover:bg-library-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center">
                                <i class="fas fa-edit mr-2"></i>Modifier
                            </a>
                            
                            <?php if ($user_data['status'] == 'active'): ?>
                            <button onclick="toggleStatus(<?php echo $user_data['id']; ?>, 'active')" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center">
                                <i class="fas fa-pause mr-2"></i>Désactiver
                            </button>
                            <?php else: ?>
                            <button onclick="toggleStatus(<?php echo $user_data['id']; ?>, 'inactive')" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center">
                                <i class="fas fa-play mr-2"></i>Activer
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteUser(<?php echo $user_data['id']; ?>)" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center">
                                <i class="fas fa-trash mr-2"></i>Supprimer
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleStatus(id, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'activer' : 'désactiver';
        
        if (confirm(`Êtes-vous sûr de vouloir ${action} cet utilisateur ?`)) {
            fetch('../controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=toggle_status&id=${id}&status=${currentStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erreur lors du changement de statut');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du changement de statut');
            });
        }
    }

    function deleteUser(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
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