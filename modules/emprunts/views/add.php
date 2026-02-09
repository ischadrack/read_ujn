<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new EmpruntController();

// Récupérer les livres disponibles et les abonnés actifs
$livres_disponibles = $controller->getAvailableBooks();
$abonnes_actifs = $controller->getActiveMembers();

// Pre-selection si des paramètres sont passés
$selected_livre_id = $_GET['livre_id'] ?? '';
$selected_abonne_id = $_GET['abonne_id'] ?? '';

// Messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->create($_POST);
    
    if ($result['success']) {
        header("Location: index.php?success=" . urlencode($result['message']));
        exit;
    } else {
        $error_message = $result['message'];
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
                            <?php echo $pageTitle ?? 'Gestion des Emprunts'; ?></h1>
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

            <!-- Formulaire -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-fredoka font-bold text-gray-900 dark:text-white">Enregistrer un Nouvel Emprunt</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Sélectionnez le livre et l'abonné pour créer un emprunt</p>
                </div>

                <form method="POST" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Sélection du livre -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Livre à emprunter <span class="text-red-500">*</span>
                            </label>
                            <select name="livre_id" required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-300">
                                <option value="">Sélectionner un livre</option>
                                <?php foreach ($livres_disponibles as $livre): ?>
                                <option value="<?php echo $livre['id']; ?>" <?php echo $selected_livre_id == $livre['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($livre['titre']); ?> 
                                    (<?php echo htmlspecialchars($livre['code_livre']); ?>) 
                                    - <?php echo htmlspecialchars($livre['auteur']); ?>
                                    <?php if ($livre['quantite_disponible'] > 0): ?>
                                    - <?php echo $livre['quantite_disponible']; ?> disponible(s)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($livres_disponibles)): ?>
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Aucun livre disponible pour l'emprunt.
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Sélection de l'abonné -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Abonné <span class="text-red-500">*</span>
                            </label>
                            <select name="abonne_id" required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-300">
                                <option value="">Sélectionner un abonné</option>
                                <?php foreach ($abonnes_actifs as $abonne): ?>
                                <option value="<?php echo $abonne['id']; ?>" <?php echo $selected_abonne_id == $abonne['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($abonne['nom_complet']); ?> 
                                    (N° <?php echo htmlspecialchars($abonne['numero_abonne']); ?>) 
                                    - <?php echo htmlspecialchars($abonne['classe']); ?>
                                    - Emprunts: <?php echo $abonne['nb_emprunts_actuel']; ?>/<?php echo $abonne['nb_emprunts_max']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($abonnes_actifs)): ?>
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Aucun abonné actif disponible.
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Date d'emprunt -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date d'emprunt <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_emprunt" value="<?php echo date('Y-m-d'); ?>" required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-300">
                        </div>

                        <!-- Durée d'emprunt -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Durée d'emprunt (jours)
                            </label>
                            <select name="duree_jours"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-300">
                                <option value="7">7 jours</option>
                                <option value="14" selected>14 jours (standard)</option>
                                <option value="21">21 jours</option>
                                <option value="30">30 jours</option>
                            </select>
                        </div>

                        <!-- État du livre à l'emprunt -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                État du livre à l'emprunt
                            </label>
                            <select name="etat_livre_emprunt"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-300">
                                <option value="neuf">Neuf</option>
                                <option value="bon" selected>Bon état</option>
                                <option value="use">Usé</option>
                                <option value="deteriore">Détérioré</option>
                            </select>
                        </div>

                        <!-- Observations -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Observations
                            </label>
                            <textarea name="observations_emprunt" rows="3"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-300"
                                placeholder="Notes ou commentaires sur cet emprunt..."></textarea>
                        </div>
                    </div>

                    <!-- Résumé -->
                    <div class="mt-6 p-4 bg-library-50 dark:bg-library-900/30 rounded-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            <i class="fas fa-info-circle mr-2 text-library-600"></i>
                            Résumé de l'emprunt
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p><strong>Date de retour prévue:</strong> <span id="date_retour_preview">-</span></p>
                                <p><strong>Durée totale:</strong> <span id="duree_preview">14 jours</span></p>
                            </div>
                            <div>
                                <p><strong>Renouvellements autorisés:</strong> 2 maximum</p>
                                <p><strong>Amende en cas de retard:</strong> 100 FC/jour</p>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Annuler
                        </a>
                        
                        <div class="flex gap-3">
                            <button type="submit" class="bg-library-600 hover:bg-library-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                                <i class="fas fa-save mr-2"></i>Créer l'emprunt
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Mettre à jour le résumé en temps réel
    function updateSummary() {
        const dateEmprunt = document.querySelector('input[name="date_emprunt"]').value;
        const dureeJours = document.querySelector('select[name="duree_jours"]').value;
        
        if (dateEmprunt && dureeJours) {
            const dateRetour = new Date(dateEmprunt);
            dateRetour.setDate(dateRetour.getDate() + parseInt(dureeJours));
            
            document.getElementById('date_retour_preview').textContent = dateRetour.toLocaleDateString('fr-FR');
            document.getElementById('duree_preview').textContent = dureeJours + ' jours';
        }
    }

    // Event listeners pour mise à jour du résumé
    document.querySelector('input[name="date_emprunt"]').addEventListener('change', updateSummary);
    document.querySelector('select[name="duree_jours"]').addEventListener('change', updateSummary);

    // Initialiser le résumé
    updateSummary();

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