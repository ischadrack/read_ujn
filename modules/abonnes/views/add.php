<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new AbonneController();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->create($_POST);
    if ($result['success']) {
        header('Location: index?success=' . urlencode($result['message']));
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Générer un nouveau numéro d'abonné
$nouveau_numero = $controller->generateNumeroAbonne();
$pageTitle = "Nouvel Abonné";
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

        <div class="pY-6 lg:p-8">
            <!-- Messages d'erreur -->
            <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="p-6">
                
            <!-- Navigation de retour -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-4">
                    <a href="index"
                        class="flex items-center text-library-600 hover:text-library-700 dark:text-library-400 dark:hover:text-library-300 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour à la liste
                    </a>
                </div>
                <div class="text-right">
                    <h1 class="text-2xl font-fredoka font-bold text-gray-900 dark:text-white">Nouvel Abonné</h1>
                    <p class="text-gray-600 dark:text-gray-400">Inscription d'un nouvel élève</p>
                </div>
            </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                    <form method="POST" enctype="multipart/form-data" class="p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                            <!-- Informations personnelles -->
                            <div class="space-y-6">
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                        <i class="fas fa-user text-library-600 mr-2"></i>
                                        Informations de l'élève
                                    </h3>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Numéro
                                            d'abonné *</label>
                                        <input type="text" name="numero_abonne"
                                            value="<?php echo htmlspecialchars($nouveau_numero); ?>" readonly
                                            class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white font-mono">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sexe
                                            *</label>
                                        <select name="sexe" required
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                            <option value="">Sélectionner...</option>
                                            <option value="M">Masculin</option>
                                            <option value="F">Féminin</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom
                                            *</label>
                                        <input type="text" name="nom" required
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                            placeholder="Nom de famille">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prénom
                                            *</label>
                                        <input type="text" name="prenom" required
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                            placeholder="Prénom">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                                        de naissance</label>
                                    <input type="date" name="date_naissance"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Niveau
                                            *</label>
                                        <select name="niveau" required
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                            <option value="">Sélectionner...</option>
                                            <option value="maternelle">Maternelle</option>
                                            <option value="primaire">Primaire</option>
                                            <option value="secondaire">Secondaire</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Classe</label>
                                        <input type="text" name="classe"
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                            placeholder="Ex: CP1, CE1, 6ème...">
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Adresse</label>
                                    <textarea name="adresse" rows="3"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none"
                                        placeholder="Adresse complète de l'élève"></textarea>
                                </div>
                            </div>

                            <!-- Informations parent/tuteur et configuration -->
                            <div class="space-y-6">
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                        <i class="fas fa-user-friends text-library-600 mr-2"></i>
                                        Informations du parent/tuteur
                                    </h3>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom
                                        du parent/tuteur</label>
                                    <input type="text" name="nom_parent"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                        placeholder="Nom complet du parent ou tuteur">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Téléphone</label>
                                        <input type="tel" name="telephone_parent"
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                            placeholder="+243 XXX XXX XXX">
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                                        <input type="email" name="email_parent"
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                            placeholder="email@exemple.com">
                                    </div>
                                </div>

                                <!-- Configuration de l'abonnement -->
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                    <h3
                                        class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                                        <i class="fas fa-cogs text-library-600 mr-2"></i>
                                        Configuration de l'abonnement
                                    </h3>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                                                d'inscription *</label>
                                            <input type="date" name="date_inscription"
                                                value="<?php echo date('Y-m-d'); ?>" required
                                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Limite
                                                d'emprunts</label>
                                            <select name="nb_emprunts_max"
                                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                                <option value="2">2 livres maximum</option>
                                                <option value="3" selected>3 livres maximum</option>
                                                <option value="4">4 livres maximum</option>
                                                <option value="5">5 livres maximum</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Photo -->
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Photo
                                        de l'élève</label>
                                    <div class="flex items-center justify-center w-full">
                                        <label for="photo"
                                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6"
                                                id="uploadContent">
                                                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span
                                                        class="font-semibold">Cliquez pour télécharger</span> une photo
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG ou JPEG
                                                    (MAX. 2MB)</p>
                                            </div>
                                            <input type="file" id="photo" name="photo" accept="image/*"
                                                class="hidden" />
                                        </label>
                                    </div>
                                    <div id="imagePreview" class="hidden mt-4"></div>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes
                                        et observations</label>
                                    <textarea name="notes" rows="4"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none"
                                        placeholder="Notes particulières, allergies, restrictions..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Les champs marqués d'un * sont obligatoires
                                </div>

                                <div class="flex space-x-4">
                                    <a href="index"
                                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        Annuler
                                    </a>
                                    <button type="submit"
                                        class="px-8 py-3 bg-library-600 hover:bg-library-700 text-white rounded-lg font-medium transition-colors">
                                        <i class="fas fa-save mr-2"></i>
                                        Inscrire l'abonné
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Dark Mode Toggle
    const html = document.documentElement;
    const currentTheme = localStorage.getItem('theme') || 'light';
    html.classList.toggle('dark', currentTheme === 'dark');

    // Preview de l'image
    document.getElementById('photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const uploadContent = document.getElementById('uploadContent');
        const imagePreview = document.getElementById('imagePreview');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <img src="${e.target.result}" class="w-20 h-20 rounded-full object-cover border-2 border-library-200" alt="Aperçu">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${file.name}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                        </div>
                    </div>
                `;
                imagePreview.classList.remove('hidden');
                uploadContent.innerHTML = `
                    <i class="fas fa-check text-2xl text-green-500 mb-2"></i>
                    <p class="mb-2 text-sm text-green-600 dark:text-green-400">Image sélectionnée</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cliquez pour changer</p>
                `;
            };
            reader.readAsDataURL(file);
        }
    });

    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const required = this.querySelectorAll('[required]');
        let hasError = false;

        required.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                hasError = true;
            } else {
                input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            }
        });

        if (hasError) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
        }
    });

    // Sidebar et autres fonctionnalités
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion du sidebar et des dropdowns
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle && sidebar && overlay) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }

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

        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const isDark = html.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        }
    });
    </script>
</body>

</html>