<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new AbonneController();

// Récupérer l'ID de l'abonné
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index?error=Abonné introuvable');
    exit;
}

// Récupérer les détails de l'abonné
$abonne = $controller->find($id);

if (!$abonne) {
    header('Location: index?error=Abonné introuvable');
    exit;
}

// Récupérer l'historique des emprunts
global $db;
$emprunts_stmt = $db->prepare("
    SELECT e.*, l.titre, l.code_livre, l.auteur,
           CASE 
               WHEN e.statut = 'en_cours' AND e.date_retour_prevue < CURDATE() THEN 'en_retard'
               ELSE e.statut
           END as statut_display
    FROM emprunts e
    LEFT JOIN livres l ON e.livre_id = l.id
    WHERE e.abonne_id = ?
    ORDER BY e.date_emprunt DESC
    LIMIT 20
");
$emprunts_stmt->execute([$id]);
$emprunts = $emprunts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les amendes
$amendes_stmt = $db->prepare("
    SELECT ap.*, l.titre as livre_titre
    FROM amendes_pertes ap
    LEFT JOIN livres l ON ap.livre_id = l.id
    WHERE ap.abonne_id = ?
    ORDER BY ap.date_amende DESC
");
$amendes_stmt->execute([$id]);
$amendes = $amendes_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <button
                        class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>

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

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 p-6">
            <!-- Colonne principale - Informations de l'abonné -->
            <div class="xl:col-span-2 space-y-8">
                <!-- Fiche d'identité -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-library-600 to-library-700 text-white p-6">
                        <div class="flex items-center space-x-6">
                            <?php if (!empty($abonne['photo'])): ?>
                            <img src="../../../../assets/uploads/abonnes/<?php echo htmlspecialchars($abonne['photo']); ?>"
                                class="w-24 h-24 rounded-full border-4 border-white object-cover" alt="Photo">
                            <?php else: ?>
                            <div
                                class="w-24 h-24 rounded-full border-4 border-white bg-library-500 flex items-center justify-center">
                                <i class="fas fa-user text-3xl text-white"></i>
                            </div>
                            <?php endif; ?>

                            <div>
                                <h1 class="text-3xl font-fredoka font-bold">
                                    <?php echo htmlspecialchars($abonne['nom'] . ' ' . $abonne['prenom']); ?></h1>
                                <p class="text-library-100 text-lg">Abonné N°
                                    <?php echo htmlspecialchars($abonne['numero_abonne']); ?></p>
                                <div class="flex items-center mt-2">
                                    <?php
                                        $statut_classes = [
                                            'actif' => 'bg-green-500',
                                            'suspendu' => 'bg-red-500',
                                            'expire' => 'bg-orange-500',
                                            'archive' => 'bg-gray-500'
                                        ];
                                        
                                        $display_statut = $abonne['statut'];
                                        if ($abonne['statut'] == 'actif' && $abonne['date_expiration'] < date('Y-m-d')) {
                                            $display_statut = 'expire';
                                        }
                                        ?>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white <?php echo $statut_classes[$display_statut]; ?>">
                                        <?php echo ucfirst($display_statut); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations
                                    personnelles</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-library-600 w-5"></i>
                                        <span class="ml-3 text-gray-700 dark:text-gray-300">
                                            <?php if ($abonne['date_naissance']): ?>
                                            Né(e) le <?php echo date('d/m/Y', strtotime($abonne['date_naissance'])); ?>
                                            <?php else: ?>
                                            Date de naissance non renseignée
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-venus-mars text-library-600 w-5"></i>
                                        <span class="ml-3 text-gray-700 dark:text-gray-300">
                                            <?php echo $abonne['sexe'] == 'M' ? 'Masculin' : 'Féminin'; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-graduation-cap text-library-600 w-5"></i>
                                        <span class="ml-3 text-gray-700 dark:text-gray-300">
                                            <?php echo ucfirst($abonne['niveau']); ?> -
                                            <?php echo htmlspecialchars($abonne['classe']); ?>
                                        </span>
                                    </div>
                                    <?php if ($abonne['adresse']): ?>
                                    <div class="flex items-start">
                                        <i class="fas fa-map-marker-alt text-library-600 w-5 mt-1"></i>
                                        <span class="ml-3 text-gray-700 dark:text-gray-300">
                                            <?php echo nl2br(htmlspecialchars($abonne['adresse'])); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact
                                    parent/tuteur</h3>
                                <div class="space-y-3">
                                    <?php if ($abonne['nom_parent']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-user-friends text-library-600 w-5"></i>
                                        <span class="ml-3 text-gray-700 dark:text-gray-300">
                                            <?php echo htmlspecialchars($abonne['nom_parent']); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($abonne['telephone_parent']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone text-library-600 w-5"></i>
                                        <span class="ml-3 text-gray-700 dark:text-gray-300">
                                            <a href="tel:<?php echo htmlspecialchars($abonne['telephone_parent']); ?>"
                                                class="hover:text-library-600">
                                                <?php echo htmlspecialchars($abonne['telephone_parent']); ?>
                                            </a>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($abonne['email_parent']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope text-library-600 w-5"></i>
                                        <span class="ml-3 text-gray-700 dark:text-gray-300">
                                            <a href="mailto:<?php echo htmlspecialchars($abonne['email_parent']); ?>"
                                                class="hover:text-library-600">
                                                <?php echo htmlspecialchars($abonne['email_parent']); ?>
                                            </a>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($abonne['notes']): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Notes et observations
                            </h3>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-gray-700 dark:text-gray-300">
                                    <?php echo nl2br(htmlspecialchars($abonne['notes'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historique des emprunts -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Historique des emprunts</h2>
                    </div>

                    <?php if (empty($emprunts)): ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-book text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400">Aucun emprunt enregistré pour cet abonné.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Livre</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Date emprunt</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Retour prévu</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Statut</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Amende</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($emprunts as $emprunt): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($emprunt['titre']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($emprunt['code_livre']); ?>
                                                <?php if ($emprunt['auteur']): ?>
                                                - <?php echo htmlspecialchars($emprunt['auteur']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        <?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        <?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?>
                                        <?php if ($emprunt['statut_display'] == 'en_retard'): ?>
                                        <span class="text-red-600 dark:text-red-400 text-xs block">
                                            <?php 
                                                $jours_retard = floor((time() - strtotime($emprunt['date_retour_prevue'])) / 86400);
                                                echo $jours_retard . ' jour(s) de retard';
                                                ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                            $statut_classes = [
                                                'en_cours' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                'rendu' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'en_retard' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'perdu' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                            ];
                                            $statut_labels = [
                                                'en_cours' => 'En cours',
                                                'rendu' => 'Rendu',
                                                'en_retard' => 'En retard',
                                                'perdu' => 'Perdu'
                                            ];
                                            ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statut_classes[$emprunt['statut_display']]; ?>">
                                            <?php echo $statut_labels[$emprunt['statut_display']]; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        <?php if ($emprunt['amende'] > 0): ?>
                                        <span class="text-red-600 dark:text-red-400 font-semibold">
                                            <?php echo number_format($emprunt['amende'], 0, ',', ' '); ?> FC
                                        </span>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Colonne latérale - Statistiques et infos rapides -->
            <div class="space-y-8">
                <!-- Statistiques de l'abonné -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statistiques d'abonnement</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-library-50 dark:bg-library-900 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-check text-library-600 mr-3"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Inscrit depuis</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                <?php echo date('d/m/Y', strtotime($abonne['date_inscription'])); ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-book-open text-green-600 mr-3"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Emprunts
                                    actuels</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                <?php echo ($abonne['emprunts_actifs'] ?? 0) . '/' . $abonne['nb_emprunts_max']; ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-history text-blue-600 mr-3"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total emprunts</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                <?php echo $abonne['total_emprunts'] ?? 0; ?>
                            </span>
                        </div>

                        <?php if (($abonne['emprunts_retard'] ?? 0) > 0): ?>
                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">En retard</span>
                            </div>
                            <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                <?php echo $abonne['emprunts_retard']; ?> emprunt(s)
                            </span>
                        </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-orange-600 mr-3"></i>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Expire le</span>
                            </div>
                            <span
                                class="text-sm font-semibold <?php echo $abonne['date_expiration'] < date('Y-m-d') ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'; ?>">
                                <?php echo date('d/m/Y', strtotime($abonne['date_expiration'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Amendes et pénalités -->
                <?php
                        $total_amendes_impayees = $abonne['total_amendes_impayees'] ?? 0; // valeur depuis la DB ou 0 par défaut

                        if (!empty($amendes) && is_array($amendes)):
                ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Amendes et pénalités</h3>
                    <div class="space-y-3">
                        <?php foreach ($amendes as $amende): ?>
                        <div
                            class="flex items-center justify-between p-3 <?php echo $amende['statut'] == 'impayee' ? 'bg-red-50 dark:bg-red-900' : 'bg-gray-50 dark:bg-gray-700'; ?> rounded-lg">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo ucfirst($amende['type']); ?>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo date('d/m/Y', strtotime($amende['date_amende'])); ?>
                                    <?php if (!empty($amende['livre_titre'])): ?>
                                    - <?php echo htmlspecialchars($amende['livre_titre']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="text-sm font-semibold <?php echo $amende['statut'] == 'impayee' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'; ?>">
                                    <?php echo number_format($amende['montant'], 0, ',', ' '); ?> FC
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo ucfirst($amende['statut']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if ($total_amendes_impayees > 0): ?>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex items-center justify-between p-3 bg-red-100 dark:bg-red-800 rounded-lg">
                                <span class="font-semibold text-red-800 dark:text-red-200">Total impayé</span>
                                <span class="font-bold text-red-800 dark:text-red-200">
                                    <?php echo number_format($total_amendes_impayees, 0, ',', ' '); ?> FC
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions rapides -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions rapides</h3>
                    <div class="space-y-3">
                        <a href="../../emprunts/views/add?abonne_id=<?php echo $abonne['id']; ?>"
                            class="w-full flex items-center justify-center px-4 py-3 bg-library-600 hover:bg-library-700 text-white rounded-lg font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Nouvel emprunt
                        </a>

                        <a href="edit?id=<?php echo $abonne['id']; ?>"
                            class="w-full flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            Modifier l'abonné
                        </a>

                        <button onclick="renewSubscription(<?php echo $abonne['id']; ?>)"
                            class="w-full flex items-center justify-center px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">
                            <i class="fas fa-refresh mr-2"></i>
                            Renouveler abonnement
                        </button>

                        <?php if ($total_amendes_impayees > 0): ?>
                        <button onclick="payFines(<?php echo $abonne['id']; ?>)"
                            class="w-full flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            <i class="fas fa-money-bill mr-2"></i>
                            Payer amendes
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
    // Dark Mode
    const html = document.documentElement;
    const currentTheme = localStorage.getItem('theme') || 'light';
    html.classList.toggle('dark', currentTheme === 'dark');

    function renewSubscription(id) {
        if (confirm('Êtes-vous sûr de vouloir renouveler cet abonnement pour une année supplémentaire ?')) {
            // Implémentation du renouvellement
            alert('Fonctionnalité en cours de développement');
        }
    }

    function payFines(id) {
        if (confirm('Marquer toutes les amendes comme payées ?')) {
            // Implémentation du paiement des amendes
            alert('Fonctionnalité en cours de développement');
        }
    }
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