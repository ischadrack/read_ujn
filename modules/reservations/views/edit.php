<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

requirePermission('reservations', 'update');
requireLogin();

$user = getUserData();
$controller = new ReservationController();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index?error=Réservation introuvable');
    exit;
}

$reservation = $controller->find($id);
if (!$reservation) {
    header('Location: index?error=Réservation introuvable');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->update($id, $_POST);
    if ($result['success']) {
        header('Location: view?id=' . $id . '&success=' . urlencode($result['message']));
        exit;
    } else {
        $error_message = $result['message'];
    }
}

$pageTitle = 'Modifier Réservation #' . str_pad($reservation['id'], 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la Réservation - Bibliothèque UN JOUR NOUVEAU</title>
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
                            <?php echo $pageTitle; ?>
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
                    <a href="view?id=<?php echo $reservation['id']; ?>"
                        class="flex items-center text-library-600 hover:text-library-700 dark:text-library-400 dark:hover:text-library-300 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour aux détails
                    </a>
                </div>
                <div class="text-right">
                    <h1 class="text-2xl font-fredoka font-bold text-gray-900 dark:text-white">Modifier la Réservation
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">N°
                        <?php echo str_pad($reservation['id'], 5, '0', STR_PAD_LEFT); ?></p>
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
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Abonné</label>
                                <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-xl">
                                    <div class="text-gray-900 dark:text-white font-medium">
                                        <?php echo htmlspecialchars($reservation['abonne_nom']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        N° <?php echo htmlspecialchars($reservation['numero_abonne']); ?> -
                                        <?php echo htmlspecialchars($reservation['classe']); ?>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Livre
                                    réservé</label>
                                <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-xl">
                                    <div class="text-gray-900 dark:text-white font-medium">
                                        <?php echo htmlspecialchars($reservation['livre_titre']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Code: <?php echo htmlspecialchars($reservation['code_livre']); ?> -
                                        <?php echo htmlspecialchars($reservation['auteur']); ?>
                                    </div>
                                    <div class="text-sm mt-1">
                                        <?php if ($reservation['quantite_disponible'] > 0): ?>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200">
                                            Disponible (<?php echo $reservation['quantite_disponible']; ?>)
                                        </span>
                                        <?php else: ?>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200">
                                            Non disponible
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                                        de réservation</label>
                                    <input type="date" value="<?php echo $reservation['date_reservation']; ?>" readonly
                                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white cursor-not-allowed">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date
                                        d'expiration</label>
                                    <input type="date" value="<?php echo $reservation['date_expiration']; ?>" readonly
                                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white cursor-not-allowed">
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                                <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-xl">
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
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium <?php echo $status_classes[$reservation['statut']]; ?>">
                                        <?php echo $status_labels[$reservation['statut']]; ?>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes ou
                                    observations *</label>
                                <textarea name="notes" rows="6" required
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white resize-none placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Ajoutez des notes ou des observations sur cette réservation..."><?php echo htmlspecialchars($reservation['notes']); ?></textarea>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Les notes permettent de garder un historique des modifications et des
                                    communications.
                                </p>
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

                            <!-- Priorité dans la file d'attente -->
                            <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Position dans la file
                                    d'attente</h4>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-blue-600 mb-1">
                                        #<?php echo $reservation['priorite']; ?>
                                    </div>
                                    <div class="text-sm text-blue-800 dark:text-blue-200">
                                        Position actuelle
                                    </div>
                                </div>
                            </div>

                            <!-- Temps restant -->
                            <div
                                class="bg-<?php echo $reservation['jours_restants'] <= 3 ? 'red' : 'gray'; ?>-50 dark:bg-<?php echo $reservation['jours_restants'] <= 3 ? 'red' : 'gray'; ?>-900/30 rounded-lg p-4">
                                <h4
                                    class="font-medium text-<?php echo $reservation['jours_restants'] <= 3 ? 'red' : 'gray'; ?>-900 dark:text-<?php echo $reservation['jours_restants'] <= 3 ? 'red' : 'gray'; ?>-100 mb-2">
                                    Temps restant</h4>
                                <div
                                    class="text-sm text-<?php echo $reservation['jours_restants'] <= 3 ? 'red' : 'gray'; ?>-800 dark:text-<?php echo $reservation['jours_restants'] <= 3 ? 'red' : 'gray'; ?>-200">
                                    <?php if ($reservation['jours_restants'] > 0): ?>
                                    <i class="fas fa-clock mr-2"></i>
                                    <?php echo $reservation['jours_restants']; ?> jour(s) restant(s)
                                    <?php elseif ($reservation['jours_restants'] == 0): ?>
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Expire aujourd'hui
                                    <?php else: ?>
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Expirée depuis <?php echo abs($reservation['jours_restants']); ?> jour(s)
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Disponibilité du livre -->
                            <div
                                class="bg-<?php echo $reservation['quantite_disponible'] > 0 ? 'green' : 'orange'; ?>-50 dark:bg-<?php echo $reservation['quantite_disponible'] > 0 ? 'green' : 'orange'; ?>-900/30 rounded-lg p-4">
                                <h4
                                    class="font-medium text-<?php echo $reservation['quantite_disponible'] > 0 ? 'green' : 'orange'; ?>-900 dark:text-<?php echo $reservation['quantite_disponible'] > 0 ? 'green' : 'orange'; ?>-100 mb-2">
                                    Disponibilité du livre</h4>
                                <div
                                    class="text-sm text-<?php echo $reservation['quantite_disponible'] > 0 ? 'green' : 'orange'; ?>-800 dark:text-<?php echo $reservation['quantite_disponible'] > 0 ? 'green' : 'orange'; ?>-200">
                                    <?php if ($reservation['quantite_disponible'] > 0): ?>
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <?php echo $reservation['quantite_disponible']; ?> exemplaire(s) disponible(s)
                                    <?php if ($reservation['statut'] === 'active' && $reservation['priorite'] == 1): ?>
                                    <br><strong>Vous pouvez créer l'emprunt maintenant !</strong>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <i class="fas fa-hourglass-half mr-2"></i>
                                    Livre non disponible - En attente
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions recommandées -->
                            <?php if ($reservation['statut'] === 'active'): ?>
                            <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-4">
                                <h4 class="font-medium text-yellow-900 dark:text-yellow-100 mb-2">Actions recommandées
                                </h4>
                                <div class="space-y-2 text-sm text-yellow-800 dark:text-yellow-200">
                                    <?php if ($reservation['quantite_disponible'] > 0 && $reservation['priorite'] == 1): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-star text-yellow-600 mr-2"></i>
                                        <span>Créer un emprunt (livre disponible)</span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($reservation['jours_restants'] <= 3): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone text-yellow-600 mr-2"></i>
                                        <span>Contacter l'abonné (expiration proche)</span>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($reservation['jours_restants'] < 0): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-times text-red-600 mr-2"></i>
                                        <span>Marquer comme expirée</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Historique -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3">Historique</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Créée par:</span>
                                        <span
                                            class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($reservation['created_by_name']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Créée le:</span>
                                        <span
                                            class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Modifiée le:</span>
                                        <span
                                            class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($reservation['updated_at'])); ?></span>
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
                                Seules les notes peuvent être modifiées
                            </div>

                            <div class="flex space-x-4">
                                <a href="view?id=<?php echo $reservation['id']; ?>"
                                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Annuler
                                </a>
                                <button type="submit"
                                    class="px-8 py-3 bg-library-600 hover:bg-library-700 text-white rounded-lg font-medium transition-colors">
                                    <i class="fas fa-save mr-2"></i>
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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
        const notesField = document.querySelector('textarea[name="notes"]');

        if (!notesField.value.trim()) {
            e.preventDefault();
            notesField.classList.add('border-red-500');
            alert('Veuillez ajouter des notes pour expliquer la modification.');
            notesField.focus();
        } else {
            notesField.classList.remove('border-red-500');
        }
    });
    </script>
</body>

</html>