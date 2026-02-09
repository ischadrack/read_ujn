<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new CategoriesLivresController();

// Traitement du formulaire
if ($_POST) {
    $data = [
        'nom' => sanitizeInput($_POST['nom']),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'age_minimum' => (int)($_POST['age_minimum'] ?? 3),
        'age_maximum' => (int)($_POST['age_maximum'] ?? 18),
        'color' => $_POST['color'] ?? '#3b82f6'
    ];

    $result = $controller->create($data);
    if ($result['success']) {
        header('Location: view?id=' . $result['categorie_id'] . '&success=' . urlencode($result['message']));
        exit;
    } else {
        $error = $result['message'];
    }
}
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
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white">Nouvelle Catégorie</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Créer une nouvelle catégorie de livres avec tranche d'âge</p>
            </div>
            <a href="index" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </a>
        </div>

        <!-- Message d'erreur -->
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="max-w-2xl mx-auto">
            <form method="POST" id="categorieForm" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom de la catégorie *</label>
                    <input type="text" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                           placeholder="Ex: Contes et Histoires, Romans Jeunesse..."
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea name="description" rows="4" placeholder="Description de la catégorie..."
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <!-- Tranche d'âge -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Âge minimum *</label>
                        <input type="number" name="age_minimum" required min="1" max="18" 
                               value="<?php echo $_POST['age_minimum'] ?? 3; ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Âge minimum recommandé en années</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Âge maximum *</label>
                        <input type="number" name="age_maximum" required min="1" max="18" 
                               value="<?php echo $_POST['age_maximum'] ?? 18; ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Âge maximum recommandé en années</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Couleur</label>
                    <div class="flex items-center space-x-4">
                        <input type="color" name="color" value="<?php echo $_POST['color'] ?? '#3b82f6'; ?>" 
                               class="w-16 h-12 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
                            foreach ($colors as $color):
                            ?>
                                <button type="button" onclick="setColor('<?php echo $color; ?>')" 
                                        class="w-8 h-8 rounded-full border-2 border-white shadow-md hover:scale-110 transition-transform"
                                        style="background-color: <?php echo $color; ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Choisissez une couleur pour identifier la catégorie</p>
                </div>

                <!-- Aperçu -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Aperçu</h4>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div id="colorPreview" class="w-4 h-4 rounded-full" style="background-color: <?php echo $_POST['color'] ?? '#3b82f6'; ?>"></div>
                            <span id="namePreview" class="font-medium text-gray-900 dark:text-white">Nom de la catégorie</span>
                        </div>
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            <i class="fas fa-child mr-1"></i>
                            <span id="agePreview">3-18 ans</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="index" class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Créer la catégorie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setColor(color) {
        document.querySelector('input[name="color"]').value = color;
        document.getElementById('colorPreview').style.backgroundColor = color;
    }

    function updatePreview() {
        // Update name
        const nameInput = document.querySelector('input[name="nom"]');
        const namePreview = document.getElementById('namePreview');
        namePreview.textContent = nameInput.value || 'Nom de la catégorie';

        // Update age range
        const ageMin = document.querySelector('input[name="age_minimum"]').value || 3;
        const ageMax = document.querySelector('input[name="age_maximum"]').value || 18;
        document.getElementById('agePreview').textContent = `${ageMin}-${ageMax} ans`;
    }

    // Update preview
    document.querySelector('input[name="nom"]').addEventListener('input', updatePreview);
    document.querySelector('input[name="age_minimum"]').addEventListener('input', updatePreview);
    document.querySelector('input[name="age_maximum"]').addEventListener('input', updatePreview);

    document.querySelector('input[name="color"]').addEventListener('input', function() {
        document.getElementById('colorPreview').style.backgroundColor = this.value;
    });

    // Initialize preview
    updatePreview();

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

    // Form validation
    document.getElementById('categorieForm').addEventListener('submit', function(e) {
        const nom = document.querySelector('input[name="nom"]').value;
        const ageMin = parseInt(document.querySelector('input[name="age_minimum"]').value);
        const ageMax = parseInt(document.querySelector('input[name="age_maximum"]').value);
        
        if (!nom.trim()) {
            e.preventDefault();
            alert('Veuillez saisir le nom de la catégorie');
            return false;
        }

        if (ageMin >= ageMax) {
            e.preventDefault();
            alert('L\'âge minimum doit être inférieur à l\'âge maximum');
            return false;
        }
    });
</script>
</body>
</html>