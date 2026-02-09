<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new AmendePerteController();
$pageTitle = "Détails de l'Amende";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.php?error=Amende introuvable');
    exit;
}

$amende = $controller->find($id);
if (!$amende) {
    header('Location: index.php?error=Amende introuvable');
    exit;
}

$success_message = $_GET['success'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Abonnés - Bibliothèque UN JOUR NOUVEAU</title>
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

        <div class="p-6">
            <!-- Messages -->
            <?php if ($success_message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 animate-pulse">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Informations principales -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Détails de l'amende -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-exclamation-triangle text-library-600 mr-3"></i>
                                Informations de l'Amende
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Type
                                    d'amende</label>
                                <?php
                                $type_classes = [
                                    'retard' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'perte' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'deterioration' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'autre' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                ];
                                $type_icons = [
                                    'retard' => 'fas fa-clock',
                                    'perte' => 'fas fa-book-dead',
                                    'deterioration' => 'fas fa-tools',
                                    'autre' => 'fas fa-question'
                                ];
                                ?>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $type_classes[$amende['type']]; ?>">
                                    <i class="<?php echo $type_icons[$amende['type']]; ?> mr-2"></i>
                                    <?php echo ucfirst($amende['type']); ?>
                                </span>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Montant</label>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?php echo number_format($amende['montant'], 0, ',', ' '); ?> FC
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de
                                    l'amende</label>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y', strtotime($amende['date_amende'])); ?>
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Statut</label>
                                <?php
                                $statut_classes = [
                                    'impayee' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'payee' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'annulee' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                    'remise' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                ];
                                ?>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statut_classes[$amende['statut']]; ?>">
                                    <?php echo ucfirst($amende['statut']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($amende['description']): ?>
                        <div class="mt-6">
                            <label
                                class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</label>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-gray-900 dark:text-white whitespace-pre-wrap">
                                    <?php echo htmlspecialchars($amende['description']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Informations de paiement -->
                    <?php if ($amende['statut'] == 'payee'): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-money-bill-wave text-green-600 mr-3"></i>
                                Informations de Paiement
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de
                                    paiement</label>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y', strtotime($amende['date_paiement'])); ?>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Mode de
                                    paiement</label>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($amende['mode_paiement'] ?: 'Non spécifié'); ?>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Numéro de
                                    reçu</label>
                                <p class="text-lg text-gray-900 dark:text-white font-mono">
                                    <?php echo htmlspecialchars($amende['recu_numero'] ?: 'Non spécifié'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Traité
                                par</label>
                            <p class="text-lg text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($amende['processed_by_name'] ?: 'Non spécifié'); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Informations sur l'emprunt (si applicable) -->
                    <?php if ($amende['emprunt_id']): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-exchange-alt text-library-600 mr-3"></i>
                                Informations sur l'Emprunt
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date
                                    d'emprunt</label>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y', strtotime($amende['date_emprunt'])); ?>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de
                                    retour prévue</label>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y', strtotime($amende['date_retour_prevue'])); ?>
                                </p>
                            </div>

                            <?php if ($amende['date_retour_effective']): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de
                                    retour effective</label>
                                <p class="text-lg text-gray-900 dark:text-white">
                                    <?php echo date('d/m/Y', strtotime($amende['date_retour_effective'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">

                    <!-- Informations sur l'abonné -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-user text-library-600 mr-2"></i>
                                Abonné
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nom
                                    complet</label>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($amende['abonne_nom']); ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Numéro
                                    d'abonné</label>
                                <p class="font-mono text-library-600 dark:text-library-400">
                                    <?php echo htmlspecialchars($amende['numero_abonne']); ?></p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Classe</label>
                                <p class="text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($amende['classe']); ?></p>
                            </div>

                            <?php if ($amende['telephone_parent']): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Téléphone
                                    parent</label>
                                <p class="text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($amende['telephone_parent']); ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="pt-4">
                                <a href="../../abonnes/views/view.php?id=<?php echo $amende['abonne_id']; ?>"
                                    class="inline-flex items-center text-library-600 hover:text-library-700 dark:text-library-400 dark:hover:text-library-300 transition-colors">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Voir le profil complet
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Informations sur le livre -->
                    <?php if ($amende['livre_id']): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-book text-library-600 mr-2"></i>
                                Livre Concerné
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Titre</label>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($amende['livre_titre']); ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Code
                                    livre</label>
                                <p class="font-mono text-library-600 dark:text-library-400">
                                    <?php echo htmlspecialchars($amende['code_livre']); ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Prix
                                    unitaire</label>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    <?php echo number_format($amende['prix_unitaire'], 0, ',', ' '); ?> FC
                                </p>
                            </div>

                            <div class="pt-4">
                                <a href="../../livres/views/view.php?id=<?php echo $amende['livre_id']; ?>"
                                    class="inline-flex items-center text-library-600 hover:text-library-700 dark:text-library-400 dark:hover:text-library-300 transition-colors">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Voir les détails du livre
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Actions</h3>
                        </div>

                        <div class="space-y-3">
                            <?php if ($amende['statut'] == 'impayee'): ?>
                            <button onclick="payerAmende(<?php echo $amende['id']; ?>)"
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-money-bill-wave mr-2"></i>
                                Marquer comme payée
                            </button>
                            <?php endif; ?>

                            <a href="edit.php?id=<?php echo $amende['id']; ?>"
                                class="block w-full bg-library-600 hover:bg-library-700 text-white px-4 py-2 rounded-lg font-medium transition-colors text-center">
                                <i class="fas fa-edit mr-2"></i>
                                Modifier
                            </a>

                            <button onclick="imprimerRecu()"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-print mr-2"></i>
                                Imprimer le reçu
                            </button>

                            <button onclick="deleteAmende()"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer l'amende
                            </button>
                        </div>
                    </div>

                    <!-- Informations de suivi -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Suivi</h3>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Créée par:</span>
                                <span
                                    class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($amende['created_by_name']); ?></span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Créée le:</span>
                                <span
                                    class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($amende['created_at'])); ?></span>
                            </div>

                            <?php if ($amende['processed_by_name']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Traitée par:</span>
                                <span
                                    class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($amende['processed_by_name']); ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Modifiée le:</span>
                                <span
                                    class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($amende['updated_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de paiement -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>
                    Enregistrer le paiement
                </h3>
                <form id="paymentForm">
                    <input type="hidden" id="amende_id" name="id" value="<?php echo $amende['id']; ?>">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de
                            paiement</label>
                        <input type="date" name="date_paiement" value="<?php echo date('Y-m-d'); ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-library-500 focus:border-library-500 transition-all duration-200">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mode de
                            paiement</label>
                        <select name="mode_paiement" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-library-500 focus:border-library-500 transition-all duration-200">
                            <option value="">Sélectionner...</option>
                            <option value="especes">Espèces</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="carte">Carte bancaire</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Numéro de
                            reçu</label>
                        <input type="text" name="recu_numero"
                            placeholder="Ex: REC-<?php echo date('Y'); ?>-<?php echo str_pad($amende['id'], 3, '0', STR_PAD_LEFT); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-library-500 focus:border-library-500 font-mono transition-all duration-200">
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="closePaymentModal()"
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Annuler
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            Confirmer le paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function payerAmende(id) {
        document.getElementById('paymentModal').classList.remove('hidden');
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    }

    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'payer');

        fetch('../controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'view.php?id=<?php echo $amende['id']; ?>&success=' +
                        encodeURIComponent(data.message);
                } else {
                    alert(data.message || 'Erreur lors de l\'enregistrement du paiement');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'enregistrement du paiement');
            });
    });

    function imprimerRecu() {
        window.print();
    }

    function deleteAmende() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette amende ? Cette action est irréversible.')) {
            fetch('../controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=delete&id=<?php echo $amende['id']; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index.php?success=' + encodeURIComponent(data.message);
                    } else {
                        alert(data.message || 'Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression');
                });
        }
    }// Sidebar functionality
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

    // Auto-submit form on filter change
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("form");
        
        form.querySelectorAll("select").forEach(select => {
            select.addEventListener("change", () => {
                form.submit();
            });
        });

        const searchInput = form.querySelector("input[name='search']");
        let timer;
        if (searchInput) {
            searchInput.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    form.submit();
                }, 500);
            });
        }
    });
    </script>
</body>

</html>