<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

requirePermission('reservations', 'read');
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

// Récupérer les autres réservations pour ce livre
$autres_reservations = $controller->getReservationsByLivre($reservation['livre_id']);

// Messages de succès/erreur
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

$pageTitle = 'Détails Réservation #' . str_pad($reservation['id'], 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation #<?php echo str_pad($reservation['id'], 5, '0', STR_PAD_LEFT); ?> - Bibliothèque UN JOUR NOUVEAU
    </title>
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
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="index"
                            class="flex items-center text-library-600 hover:text-library-700 dark:text-library-400 dark:hover:text-library-300 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Retour à la liste
                        </a>
                    </div>
                    <div class="text-right">
                        <h1 class="text-2xl font-fredoka font-bold text-gray-900 dark:text-white">
                            Réservation #<?php echo str_pad($reservation['id'], 5, '0', STR_PAD_LEFT); ?>
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400">
                            Créée le <?php echo date('d/m/Y à H:i', strtotime($reservation['created_at'])); ?>
                        </p>
                    </div>
                </div>

                <!-- Messages -->
                <?php if ($success_message): ?>
                <div class="mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Informations principales -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Statut et actions rapides -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-bookmark text-library-600 mr-2"></i>
                                Statut de la Réservation
                            </h2>

                            <!-- Statut badge -->
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
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium <?php echo $status_classes[$reservation['statut']]; ?>">
                                <?php echo $status_labels[$reservation['statut']]; ?>
                            </span>
                        </div>

                        <!-- Informations temporelles -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Date de réservation</div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Date d'expiration</div>
                                <div
                                    class="font-medium <?php echo $reservation['jours_restants'] <= 3 ? 'text-red-600' : 'text-gray-900 dark:text-white'; ?>">
                                    <?php echo date('d/m/Y', strtotime($reservation['date_expiration'])); ?>
                                </div>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Temps restant</div>
                                <div
                                    class="font-medium <?php echo $reservation['jours_restants'] <= 3 ? 'text-red-600' : 'text-gray-900 dark:text-white'; ?>">
                                    <?php if ($reservation['jours_restants'] > 0): ?>
                                    <?php echo $reservation['jours_restants']; ?> jour(s)
                                    <?php elseif ($reservation['jours_restants'] == 0): ?>
                                    Expire aujourd'hui
                                    <?php else: ?>
                                    Expirée (<?php echo abs($reservation['jours_restants']); ?> jour(s))
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Actions rapides -->
                        <?php if ($reservation['statut'] === 'active'): ?>
                        <div class="flex flex-wrap gap-3">
                            <?php if ($reservation['quantite_disponible'] > 0): ?>
                            <button onclick="creerEmprunt(<?php echo $reservation['id']; ?>)"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Créer un emprunt
                            </button>
                            <?php endif; ?>

                            <button onclick="changerStatut(<?php echo $reservation['id']; ?>, 'annulee')"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Annuler
                            </button>

                            <a href="edit?id=<?php echo $reservation['id']; ?>"
                                class="bg-library-600 hover:bg-library-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-edit mr-2"></i>
                                Modifier
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Informations sur le livre -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-book text-library-600 mr-2"></i>
                            Livre Réservé
                        </h2>

                        <div class="flex items-start space-x-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    <?php echo htmlspecialchars($reservation['livre_titre']); ?>
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Code:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1">
                                            <?php echo htmlspecialchars($reservation['code_livre']); ?>
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Auteur:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1">
                                            <?php echo htmlspecialchars($reservation['auteur']); ?>
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Catégorie:</span>
                                        <span class="font-medium text-gray-900 dark:text-white ml-1">
                                            <?php echo htmlspecialchars($reservation['categorie_nom']); ?>
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Disponibilité:</span>
                                        <span
                                            class="font-medium ml-1 <?php echo $reservation['quantite_disponible'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $reservation['quantite_disponible'] > 0 ? $reservation['quantite_disponible'] . ' disponible(s)' : 'Non disponible'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations sur l'abonné -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-user text-library-600 mr-2"></i>
                            Abonné
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Nom complet:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1">
                                    <?php echo htmlspecialchars($reservation['abonne_nom']); ?>
                                </span>
                            </div>

                            <div>
                                <span class="text-gray-500 dark:text-gray-400">N° d'abonné:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1">
                                    <?php echo htmlspecialchars($reservation['numero_abonne']); ?>
                                </span>
                            </div>

                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Classe:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1">
                                    <?php echo htmlspecialchars($reservation['classe']); ?>
                                </span>
                            </div>

                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Statut:</span>
                                <span
                                    class="font-medium ml-1 <?php echo $reservation['abonne_statut'] === 'actif' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo ucfirst($reservation['abonne_statut']); ?>
                                </span>
                            </div>

                            <?php if ($reservation['telephone_parent']): ?>
                            <div class="md:col-span-2">
                                <span class="text-gray-500 dark:text-gray-400">Téléphone parent:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1">
                                    <?php echo htmlspecialchars($reservation['telephone_parent']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notes -->
                    <?php if ($reservation['notes']): ?>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-sticky-note text-library-600 mr-2"></i>
                            Notes
                        </h2>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                <?php echo htmlspecialchars($reservation['notes']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">

                    <!-- Position dans la file d'attente -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-list-ol text-library-600 mr-2"></i>
                            Position dans la file
                        </h3>

                        <div class="text-center">
                            <div class="text-3xl font-bold text-library-600 mb-2">
                                #<?php echo $reservation['priorite']; ?>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Position dans la file d'attente
                            </div>
                        </div>
                    </div>

                    <!-- Autres réservations pour ce livre -->
                    <?php if (count($autres_reservations) > 1): ?>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-users text-library-600 mr-2"></i>
                            File d'attente
                        </h3>

                        <div class="space-y-3">
                            <?php foreach ($autres_reservations as $index => $autre): ?>
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg <?php echo $autre['id'] == $reservation['id'] ? 'ring-2 ring-library-600' : ''; ?>">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">
                                        <?php echo htmlspecialchars($autre['abonne_nom']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo $autre['classe']; ?>
                                    </div>
                                </div>
                                <div class="text-sm font-medium text-library-600">
                                    #<?php echo $autre['priorite']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Informations de création -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-info text-library-600 mr-2"></i>
                            Informations
                        </h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Créée par:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($reservation['created_by_name']); ?>
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Créée le:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?>
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Modifiée le:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y H:i', strtotime($reservation['updated_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
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