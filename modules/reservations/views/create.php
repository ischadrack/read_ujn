<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

// Vérifier les permissions pour ce module
requirePermission('reservations', 'create');
requireLogin();

$user = getUserData();
$controller = new ReservationController();

// Récupérer les abonnés actifs
$abonnes_stmt = $db->prepare("SELECT id, numero_abonne, nom, prenom, classe FROM abonnes WHERE statut = 'actif' ORDER BY nom, prenom");
$abonnes_stmt->execute();
$abonnes = $abonnes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les livres actifs
$livres_stmt = $db->prepare("
    SELECT l.id, l.code_livre, l.titre, l.auteur, l.quantite_disponible, 
           c.nom as categorie_nom, c.color as categorie_color
    FROM livres l
    LEFT JOIN categories_livres c ON l.categorie_id = c.id
    WHERE l.statut = 'actif'
    ORDER BY l.titre
");
$livres_stmt->execute();
$livres = $livres_stmt->fetchAll(PDO::FETCH_ASSOC);

// Si des paramètres sont passés dans l'URL
$abonne_preselect = isset($_GET['abonne_id']) ? (int)$_GET['abonne_id'] : 0;
$livre_preselect = isset($_GET['livre_id']) ? (int)$_GET['livre_id'] : 0;

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

$pageTitle = 'Nouvelle Réservation';
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Réservation - Bibliothèque UN JOUR NOUVEAU</title>
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
                            <?php echo $pageTitle ?? 'Nouvelle Réservation'; ?>
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
            <!-- Header avec retour -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center space-x-4">
                    <a href="index"
                        class="flex items-center text-library-600 hover:text-library-700 dark:text-library-400 dark:hover:text-library-300 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour à la liste
                    </a>
                </div>
                <div class="text-right">
                    <h1 class="text-2xl font-fredoka font-bold text-gray-900 dark:text-white">Nouvelle Réservation</h1>
                    <p class="text-gray-600 dark:text-gray-400">Créer une nouvelle réservation de livre</p>
                </div>
            </div>

            <!-- Messages d'erreur -->
            <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                <form method="POST" class="p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                        <!-- Informations principales -->
                        <div class="space-y-6">
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <i class="fas fa-bookmark text-library-600 mr-2"></i>
                                    Informations de la Réservation
                                </h3>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Abonné
                                    *</label>
                                <select name="abonne_id" id="abonne_select" required
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                    <option value="">Sélectionner un abonné...</option>
                                    <?php foreach ($abonnes as $abonne): ?>
                                    <option value="<?php echo $abonne['id']; ?>"
                                        <?php echo $abonne_preselect == $abonne['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($abonne['numero_abonne'] . ' - ' . $abonne['nom'] . ' ' . $abonne['prenom'] . ' (' . $abonne['classe'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Livre à
                                    réserver *</label>
                                <select name="livre_id" id="livre_select" required
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                    <option value="">Sélectionner un livre...</option>
                                    <?php foreach ($livres as $livre): ?>
                                    <option value="<?php echo $livre['id']; ?>"
                                        data-disponible="<?php echo $livre['quantite_disponible']; ?>"
                                        data-categorie="<?php echo htmlspecialchars($livre['categorie_nom']); ?>"
                                        data-auteur="<?php echo htmlspecialchars($livre['auteur']); ?>"
                                        <?php echo $livre_preselect == $livre['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($livre['code_livre'] . ' - ' . $livre['titre'] . ' (' . $livre['auteur'] . ')'); ?>
                                        <?php if ($livre['quantite_disponible'] <= 0): ?>
                                        - NON DISPONIBLE
                                        <?php else: ?>
                                        - <?php echo $livre['quantite_disponible']; ?> disponible(s)
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                                        de réservation *</label>
                                    <input type="date" name="date_reservation" value="<?php echo date('Y-m-d'); ?>"
                                        required
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                                        d'expiration</label>
                                    <input type="date" name="date_expiration"
                                        value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>"
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Par défaut: dans 14 jours
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes ou
                                    observations</label>
                                <textarea name="notes" rows="4"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white resize-none placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Notes ou commentaires sur cette réservation..."></textarea>
                            </div>
                        </div>

                        <!-- Informations complémentaires -->
                        <div class="space-y-6">
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <i class="fas fa-info-circle text-library-600 mr-2"></i>
                                    Informations Complémentaires
                                </h3>
                            </div>

                            <!-- Informations sur l'abonné sélectionné -->
                            <div id="info_abonne" class="hidden bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Informations sur l'abonné
                                </h4>
                                <div id="abonne_details" class="text-sm text-blue-800 dark:text-blue-200">
                                    <!-- Rempli via JavaScript -->
                                </div>
                            </div>

                            <!-- Informations sur le livre sélectionné -->
                            <div id="info_livre" class="hidden bg-green-50 dark:bg-green-900/30 rounded-lg p-4">
                                <h4 class="font-medium text-green-900 dark:text-green-100 mb-2">Informations sur le
                                    livre</h4>
                                <div id="livre_details" class="text-sm text-green-800 dark:text-green-200">
                                    <!-- Rempli via JavaScript -->
                                </div>
                            </div>

                            <!-- Guide des réservations -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3">Guide des Réservations</h4>
                                <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-library-600 w-5"></i>
                                        <span class="ml-2">Durée par défaut: 14 jours</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-list-ol text-library-600 w-5"></i>
                                        <span class="ml-2">Priorité automatique selon l'ordre d'arrivée</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-bell text-library-600 w-5"></i>
                                        <span class="ml-2">Notification automatique quand le livre est disponible</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-orange-600 w-5"></i>
                                        <span class="ml-2">Réservation expirée si non retirée à temps</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerte livre non disponible -->
                            <div id="alerte_disponibilite"
                                class="hidden bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                                    <div class="text-sm text-red-700">
                                        <strong>Livre non disponible</strong><br>
                                        Ce livre n'est actuellement pas disponible. La réservation sera placée en file
                                        d'attente.
                                    </div>
                                </div>
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
                                <button type="submit" id="submit_btn"
                                    class="px-8 py-3 bg-library-600 hover:bg-library-700 text-white rounded-lg font-medium transition-colors">
                                    <i class="fas fa-bookmark mr-2"></i>
                                    Créer la réservation
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Données pour les calculs
    let abonnesData = <?php echo json_encode($abonnes); ?>;
    let livresData = <?php echo json_encode($livres); ?>;

    // Gestion de la sélection d'abonné
    document.getElementById('abonne_select').addEventListener('change', function() {
        const abonneId = this.value;

        if (abonneId) {
            const abonne = abonnesData.find(a => a.id == abonneId);
            if (abonne) {
                document.getElementById('abonne_details').innerHTML = `
                    <div><strong>Nom:</strong> ${abonne.nom} ${abonne.prenom}</div>
                    <div><strong>Classe:</strong> ${abonne.classe}</div>
                    <div><strong>N° d'abonné:</strong> ${abonne.numero_abonne}</div>
                `;
                document.getElementById('info_abonne').classList.remove('hidden');
            }
        } else {
            document.getElementById('info_abonne').classList.add('hidden');
        }
    });

    // Gestion de la sélection de livre
    document.getElementById('livre_select').addEventListener('change', function() {
        const livreId = this.value;
        const selectedOption = this.options[this.selectedIndex];

        if (livreId) {
            const disponible = parseInt(selectedOption.getAttribute('data-disponible'));
            const categorie = selectedOption.getAttribute('data-categorie');
            const auteur = selectedOption.getAttribute('data-auteur');

            document.getElementById('livre_details').innerHTML = `
                <div><strong>Auteur:</strong> ${auteur}</div>
                <div><strong>Catégorie:</strong> ${categorie}</div>
                <div><strong>Disponibilité:</strong> ${disponible > 0 ? `${disponible} exemplaire(s) disponible(s)` : 'Non disponible'}</div>
            `;
            document.getElementById('info_livre').classList.remove('hidden');

            // Afficher/cacher l'alerte de disponibilité
            const alerte = document.getElementById('alerte_disponibilite');
            if (disponible <= 0) {
                alerte.classList.remove('hidden');
            } else {
                alerte.classList.add('hidden');
            }
        } else {
            document.getElementById('info_livre').classList.add('hidden');
            document.getElementById('alerte_disponibilite').classList.add('hidden');
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

    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const required = this.querySelectorAll('[required]');
        let hasError = false;

        required.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('border-red-500');
                hasError = true;
            } else {
                input.classList.remove('border-red-500');
            }
        });

        if (hasError) {
            e.preventDefault();
            alert('Veuillez remplir tous les champs obligatoires.');
        }
    });

    // Si des éléments sont présélectionnés, déclencher les événements
    if (<?php echo $abonne_preselect; ?> > 0) {
        document.getElementById('abonne_select').dispatchEvent(new Event('change'));
    }

    if (<?php echo $livre_preselect; ?> > 0) {
        document.getElementById('livre_select').dispatchEvent(new Event('change'));
    }
    </script>
</body>

</html>