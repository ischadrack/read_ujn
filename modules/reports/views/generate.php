<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new ReportController();

// Récupérer les données pour les filtres
$stmt = $db->prepare("SELECT DISTINCT classe FROM abonnes WHERE classe IS NOT NULL AND classe != '' ORDER BY classe");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT id, nom FROM categories_livres ORDER BY nom");
$stmt->execute();
$categories_livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Avancées - Bibliothèque UN JOUR NOUVEAU</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <a href="index" class="ml-1 text-sm font-medium text-gray-700 hover:text-library-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Rapports</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Générer</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white">Générer des Rapports</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Créez des rapports personnalisés selon vos besoins</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="index" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                </div>
            </div>

            <!-- Types de rapports -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Rapport des emprunts -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer" onclick="selectReportType('emprunts')">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exchange-alt text-blue-600 text-3xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rapport des Emprunts</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Historique et statistiques des emprunts</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <ul class="space-y-1">
                            <li>• Emprunts par période</li>
                            <li>• Statut des emprunts</li>
                            <li>• Retards et retours</li>
                            <li>• Filtrage par classe/abonné</li>
                        </ul>
                    </div>
                </div>

                <!-- Rapport des abonnés -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer" onclick="selectReportType('abonnes')">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-green-600 text-3xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rapport des Abonnés</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Informations sur les abonnés</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <ul class="space-y-1">
                            <li>• Liste des abonnés</li>
                            <li>• Activité par abonné</li>
                            <li>• Statistiques par classe</li>
                            <li>• Abonnements expirés</li>
                        </ul>
                    </div>
                </div>

                <!-- Rapport des livres -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer" onclick="selectReportType('livres')">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-purple-600 text-3xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rapport des Livres</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Inventaire et popularité des livres</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <ul class="space-y-1">
                            <li>• Inventaire complet</li>
                            <li>• Livres les plus empruntés</li>
                            <li>• Disponibilité par catégorie</li>
                            <li>• Durée moyenne d'emprunt</li>
                        </ul>
                    </div>
                </div>

                <!-- Rapport des amendes -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer" onclick="selectReportType('amendes')">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rapport des Amendes</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Suivi des amendes et pénalités</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <ul class="space-y-1">
                            <li>• Amendes par période</li>
                            <li>• Montants impayés</li>
                            <li>• Historique des paiements</li>
                            <li>• Pertes de livres</li>
                        </ul>
                    </div>
                </div>

                <!-- Rapport des retards -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer" onclick="selectReportType('retards')">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-orange-600 text-3xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rapport des Retards</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Analyse des retards de retour</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <ul class="space-y-1">
                            <li>• Emprunts en retard</li>
                            <li>• Durée des retards</li>
                            <li>• Abonnés récidivistes</li>
                            <li>• Notifications à envoyer</li>
                        </ul>
                    </div>
                </div>

                <!-- Rapport personnalisé -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer" onclick="selectReportType('custom')">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <i class="fas fa-cogs text-gray-600 text-3xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rapport Personnalisé</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Créez votre propre rapport</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <ul class="space-y-1">
                            <li>• Critères personnalisés</li>
                            <li>• Combinaison de données</li>
                            <li>• Filtres avancés</li>
                            <li>• Format sur mesure</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Formulaire de génération -->
            <div id="reportForm" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Configuration du Rapport</h3>
                
                <form id="generateReportForm">
                    <input type="hidden" id="reportType" name="report_type" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Période -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de début</label>
                            <input type="date" name="date_debut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de fin</label>
                            <input type="date" name="date_fin" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                        </div>

                        <!-- Filtres spécifiques selon le type de rapport -->
                        <div id="specificFilters">
                            <!-- Les filtres spécifiques seront ajoutés ici par JavaScript -->
                        </div>
                    </div>

                    <!-- Format de sortie -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format de sortie</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="format" value="html" checked class="text-library-600 focus:ring-library-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Affichage HTML</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="format" value="pdf" class="text-library-600 focus:ring-library-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">PDF</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="format" value="excel" class="text-library-600 focus:ring-library-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Excel</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="format" value="csv" class="text-library-600 focus:ring-library-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">CSV</span>
                            </label>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex items-center justify-end space-x-4">
                        <button type="button" onclick="hideReportForm()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </button>
                        <button type="submit" class="bg-library-600 hover:bg-library-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            <i class="fas fa-file-alt mr-2"></i>Générer le Rapport
                        </button>
                    </div>
                </form>
            </div>

            <!-- Zone d'affichage des résultats -->
            <div id="reportResults" class="hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mt-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Résultats du Rapport</h3>
                    <div class="flex space-x-2">
                        <button onclick="exportReport('pdf')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </button>
                        <button onclick="exportReport('excel')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>Excel
                        </button>
                    </div>
                </div>
                <div id="reportContent">
                    <!-- Le contenu du rapport sera affiché ici -->
                </div>
            </div>
        </div>
    </div>

    <script>
    const reportFilters = {
        emprunts: `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <select name="statut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Tous les statuts</option>
                    <option value="en_cours">En cours</option>
                    <option value="retourne">Retourné</option>
                    <option value="perdu">Perdu</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Classe</label>
                <select name="classe" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $classe): ?>
                    <option value="<?php echo htmlspecialchars($classe['classe']); ?>"><?php echo htmlspecialchars($classe['classe']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        `,
        abonnes: `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <select name="statut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actif</option>
                    <option value="suspendu">Suspendu</option>
                    <option value="expire">Expiré</option>
                    <option value="archive">Archivé</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Niveau</label>
                <select name="niveau" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Tous les niveaux</option>
                    <option value="maternelle">Maternelle</option>
                    <option value="primaire">Primaire</option>
                    <option value="secondaire">Secondaire</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Classe</label>
                <select name="classe" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $classe): ?>
                    <option value="<?php echo htmlspecialchars($classe['classe']); ?>"><?php echo htmlspecialchars($classe['classe']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        `,
        livres: `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catégorie</label>
                <select name="categorie_id" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories_livres as $categorie): ?>
                    <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['nom']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <select name="statut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Tous les statuts</option>
                    <option value="disponible">Disponible</option>
                    <option value="emprunte">Emprunté</option>
                    <option value="perdu">Perdu</option>
                    <option value="endommage">Endommagé</option>
                </select>
            </div>
        `,
        amendes: `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                <select name="type" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Tous les types</option>
                    <option value="retard">Retard</option>
                    <option value="perte">Perte</option>
                    <option value="degradation">Dégradation</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                <select name="statut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Tous les statuts</option>
                    <option value="impayee">Impayée</option>
                    <option value="payee">Payée</option>
                    <option value="annulee">Annulée</option>
                </select>
            </div>
        `,
        retards: `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Classe</label>
                <select name="classe" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $classe): ?>
                    <option value="<?php echo htmlspecialchars($classe['classe']); ?>"><?php echo htmlspecialchars($classe['classe']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Retard minimum (jours)</label>
                <input type="number" name="jours_retard_min" min="1" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white" placeholder="Ex: 7">
            </div>
        `
    };

    function selectReportType(type) {
        document.getElementById('reportType').value = type;
        document.getElementById('specificFilters').innerHTML = reportFilters[type] || '';
        document.getElementById('reportForm').classList.remove('hidden');
        document.getElementById('reportResults').classList.add('hidden');
        
        // Scroll vers le formulaire
        document.getElementById('reportForm').scrollIntoView({ behavior: 'smooth' });
    }

    function hideReportForm() {
        document.getElementById('reportForm').classList.add('hidden');
        document.getElementById('reportResults').classList.add('hidden');
    }

    // Gestion du formulaire
    document.getElementById('generateReportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const format = formData.get('format');
        
        if (format === 'html') {
            generateHTMLReport(formData);
        } else {
            downloadReport(formData);
        }
    });

    function generateHTMLReport(formData) {
        const data = Object.fromEntries(formData);
        
        fetch('../controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=generate_report&type=' + data.report_type + '&filters=' + encodeURIComponent(JSON.stringify(data))
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayReportResults(result.data, data.report_type);
            } else {
                alert('Erreur lors de la génération du rapport: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la génération du rapport');
        });
    }

    function displayReportResults(data, type) {
        const resultsDiv = document.getElementById('reportResults');
        const contentDiv = document.getElementById('reportContent');
        
        let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">';
        
        if (data.length > 0) {
            // En-têtes
            html += '<thead class="bg-gray-50 dark:bg-gray-700"><tr>';
            Object.keys(data[0]).forEach(key => {
                html += `<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">${key}</th>`;
            });
            html += '</tr></thead>';
            
            // Données
            html += '<tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
            data.forEach(row => {
                html += '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">';
                Object.values(row).forEach(value => {
                    html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${value || '-'}</td>`;
                });
                html += '</tr>';
            });
            html += '</tbody>';
        } else {
            html += '<tbody><tr><td colspan="100%" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Aucune donnée trouvée</td></tr></tbody>';
        }
        
        html += '</table></div>';
        
        contentDiv.innerHTML = html;
        resultsDiv.classList.remove('hidden');
        resultsDiv.scrollIntoView({ behavior: 'smooth' });
    }

    function downloadReport(formData) {
        const data = Object.fromEntries(formData);
        const params = new URLSearchParams(data);
        window.open('export_report.php?' + params.toString(), '_blank');
    }

    function exportReport(format) {
        const formData = new FormData(document.getElementById('generateReportForm'));
        formData.set('format', format);
        downloadReport(formData);
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