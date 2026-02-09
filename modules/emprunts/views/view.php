<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new EmpruntController();

// Récupérer l'ID de l'emprunt
$emprunt_id = $_GET['id'] ?? 0;
$emprunt = $controller->find($emprunt_id);

if (!$emprunt) {
    header("Location: index.php?error=" . urlencode("Emprunt introuvable."));
    exit;
}

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
                            <?php echo $pageTitle ?? 'Gestion des Emprunts'; ?></h1>
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

            <!-- Actions rapides -->
            <div class="flex flex-wrap gap-3 mb-6">
                <?php if ($emprunt['statut'] == 'en_cours'): ?>
                <button onclick="renewEmprunt(<?php echo $emprunt['id']; ?>)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-redo mr-2"></i>Renouveler
                </button>
                <button onclick="returnBook(<?php echo $emprunt['id']; ?>)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-undo mr-2"></i>Retourner le livre
                </button>
                <?php endif; ?>
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Informations principales -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Informations du livre -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-book mr-2 text-library-600"></i>
                            Livre Emprunté
                        </h2>

                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <?php if (!empty($emprunt['livre_photo'])): ?>
                                <img src="../../../../assets/uploads/livres/<?php echo htmlspecialchars($emprunt['livre_photo']); ?>" class="w-20 h-28 rounded-lg object-cover shadow-md" alt="Couverture">
                                <?php else: ?>
                                <div class="w-20 h-28 rounded-lg bg-library-100 dark:bg-library-900 flex items-center justify-center shadow-md">
                                    <i class="fas fa-book text-2xl text-library-600 dark:text-library-400"></i>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    <?php echo htmlspecialchars($emprunt['livre_titre']); ?>
                                </h3>
                                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <p><strong>Code:</strong> <?php echo htmlspecialchars($emprunt['code_livre']); ?></p>
                                    <?php if ($emprunt['auteur']): ?>
                                    <p><strong>Auteur:</strong> <?php echo htmlspecialchars($emprunt['auteur']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations de l'abonné -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-user mr-2 text-library-600"></i>
                            Abonné
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Informations personnelles</h3>
                                <div class="space-y-1 text-sm">
                                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($emprunt['abonne_nom']); ?></p>
                                    <p><strong>Numéro:</strong> <?php echo htmlspecialchars($emprunt['numero_abonne']); ?></p>
                                    <?php if ($emprunt['classe']): ?>
                                    <p><strong>Classe:</strong> <?php echo htmlspecialchars($emprunt['classe']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($emprunt['telephone_parent']): ?>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Contact</h3>
                                <div class="space-y-1 text-sm">
                                    <p><strong>Téléphone parent:</strong> <?php echo htmlspecialchars($emprunt['telephone_parent']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Observations -->
                    <?php if ($emprunt['observations_emprunt'] || $emprunt['observations_retour']): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-comment mr-2 text-library-600"></i>
                            Observations
                        </h2>

                        <?php if ($emprunt['observations_emprunt']): ?>
                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">À l'emprunt</h3>
                            <p class="text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                <?php echo nl2br(htmlspecialchars($emprunt['observations_emprunt'])); ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if ($emprunt['observations_retour']): ?>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Au retour</h3>
                            <p class="text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                <?php echo nl2br(htmlspecialchars($emprunt['observations_retour'])); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar avec détails -->
                <div class="space-y-6">
                    <!-- Statut et dates -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-calendar mr-2 text-library-600"></i>
                            Statut & Dates
                        </h2>

                        <div class="space-y-4">
                            <div class="text-center">
                                <?php
                                $statut_classes = [
                                    'en_cours' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'rendu' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'en_retard' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'perdu' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                ];
                                
                                $display_statut = $emprunt['statut'];
                                if ($emprunt['statut'] == 'en_cours' && $emprunt['jours_retard'] > 0) {
                                    $display_statut = 'en_retard';
                                }
                                ?>
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium <?php echo $statut_classes[$display_statut]; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $display_statut)); ?>
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Date d'emprunt:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        <?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?>
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Retour prévu:</span>
                                    <span class="font-semibold <?php echo $emprunt['jours_retard'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'; ?>">
                                        <?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?>
                                    </span>
                                </div>

                                <?php if ($emprunt['date_retour_effective']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Retour effectif:</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">
                                        <?php echo date('d/m/Y', strtotime($emprunt['date_retour_effective'])); ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Durée:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        <?php echo $emprunt['duree_jours']; ?> jours
                                    </span>
                                </div>

                                <?php if ($emprunt['statut'] == 'en_cours'): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Retard:</span>
                                    <span class="font-semibold <?php echo $emprunt['jours_retard'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'; ?>">
                                        <?php if ($emprunt['jours_retard'] > 0): ?>
                                            <?php echo $emprunt['jours_retard']; ?> jour(s)
                                        <?php else: ?>
                                            À temps
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- État du livre -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-clipboard-check mr-2 text-library-600"></i>
                            État du Livre
                        </h2>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">À l'emprunt:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo ucfirst($emprunt['etat_livre_emprunt']); ?>
                                </span>
                            </div>

                            <?php if ($emprunt['etat_livre_retour']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Au retour:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo ucfirst($emprunt['etat_livre_retour']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Renouvellements -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-redo mr-2 text-library-600"></i>
                            Renouvellements
                        </h2>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Effectués:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo $emprunt['nb_renouvellements']; ?>
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Maximum:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo $emprunt['max_renouvellements']; ?>
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Restants:</span>
                                <span class="font-semibold <?php echo ($emprunt['max_renouvellements'] - $emprunt['nb_renouvellements']) > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                    <?php echo max(0, $emprunt['max_renouvellements'] - $emprunt['nb_renouvellements']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Amendes -->
                    <?php if ($emprunt['amende'] > 0): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                            Amendes
                        </h2>

                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                <?php echo number_format($emprunt['amende'], 0); ?> FC
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Amende pour retard
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Informations système -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-database mr-2 text-library-600"></i>
                            Informations Système
                        </h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Créé par:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($emprunt['created_by_name']); ?></span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Date création:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($emprunt['created_at'])); ?></span>
                            </div>

                            <?php if ($emprunt['processed_by_name']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Traité par:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($emprunt['processed_by_name']); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($emprunt['updated_at'] != $emprunt['created_at']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Dernière modif:</span>
                                <span class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($emprunt['updated_at'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de retour -->
    <div id="returnModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Retourner le livre</h3>
                <form id="returnForm">
                    <input type="hidden" id="returnEmpruntId" name="id" value="<?php echo $emprunt['id']; ?>">
                    <div class="mt-4 px-7 py-3 text-left">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de retour</label>
                        <input type="date" name="date_retour_effective" value="<?php echo date('Y-m-d'); ?>" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">État du livre au retour</label>
                        <select name="etat_livre_retour" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                            <option value="<?php echo $emprunt['etat_livre_emprunt']; ?>" selected><?php echo ucfirst($emprunt['etat_livre_emprunt']); ?> (identique)</option>
                            <option value="bon">Bon état</option>
                            <option value="use">Usé</option>
                            <option value="deteriore">Détérioré</option>
                        </select>
                        
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-2">Observations</label>
                        <textarea name="observations_retour" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white" placeholder="Commentaires sur le retour..."></textarea>
                    </div>
                    <div class="flex items-center px-4 py-3">
                        <button type="button" onclick="closeReturnModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 mr-2">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Confirmer le retour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function renewEmprunt(id) {
        if (confirm('Voulez-vous renouveler cet emprunt pour 14 jours supplémentaires ?')) {
            fetch('../controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=renew&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'view.php?id=' + id + '&success=' + encodeURIComponent(data.message);
                } else {
                    alert(data.message || 'Erreur lors du renouvellement');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du renouvellement');
            });
        }
    }

    function returnBook(id) {
        document.getElementById('returnModal').classList.remove('hidden');
    }

    function closeReturnModal() {
        document.getElementById('returnModal').classList.add('hidden');
    }

    document.getElementById('returnForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'return');

        fetch('../controller.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php?success=' + encodeURIComponent(data.message);
            } else {
                alert(data.message || 'Erreur lors du retour');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du retour');
        });
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
    </script>
</body>
</html>