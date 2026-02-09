<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new AmendePerteController();
$pageTitle = "Créer une Nouvelle Amende";

// Récupérer les abonnés actifs
$abonnes_stmt = $db->prepare("SELECT id, numero_abonne, nom, prenom, classe FROM abonnes WHERE statut = 'actif' ORDER BY nom, prenom");
$abonnes_stmt->execute();
$abonnes = $abonnes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les livres
$livres_stmt = $db->prepare("SELECT id, code_livre, titre, prix_unitaire FROM livres WHERE statut = 'actif' ORDER BY titre");
$livres_stmt->execute();
$livres = $livres_stmt->fetchAll(PDO::FETCH_ASSOC);

// Si un abonné est spécifié dans l'URL
$abonne_preselect = isset($_GET['abonne_id']) ? (int)$_GET['abonne_id'] : 0;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->create($_POST);
    if ($result['success']) {
        header('Location: index.php?success=' . urlencode($result['message']));
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
    <title>Créer une Nouvelle Amende - Bibliothèque UN JOUR NOUVEAU</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

            <!-- Messages d'erreur -->
            <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 animate-pulse">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                <form method="POST" class="p-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Informations de base -->
                        <div class="space-y-6">
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <i class="fas fa-exclamation-triangle text-library-600 mr-2"></i>
                                    Informations de l'Amende
                                </h3>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Abonné concerné *</label>
                                <select name="abonne_id" id="abonne_select" required class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                    <option value="">Sélectionner un abonné...</option>
                                    <?php foreach ($abonnes as $abonne): ?>
                                    <option value="<?php echo $abonne['id']; ?>" <?php echo $abonne_preselect == $abonne['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($abonne['numero_abonne'] . ' - ' . $abonne['nom'] . ' ' . $abonne['prenom'] . ' (' . $abonne['classe'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type d'amende *</label>
                                    <select name="type" id="type_amende" required class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                        <option value="">Sélectionner...</option>
                                        <option value="retard">Retard de restitution</option>
                                        <option value="perte">Perte de livre</option>
                                        <option value="deterioration">Détérioration</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de l'amende *</label>
                                    <input type="date" name="date_amende" value="<?php echo date('Y-m-d'); ?>" required
                                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Livre concerné (optionnel)</label>
                                <select name="livre_id" id="livre_select" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                    <option value="">Sélectionner un livre...</option>
                                    <?php foreach ($livres as $livre): ?>
                                    <option value="<?php echo $livre['id']; ?>" data-prix="<?php echo $livre['prix_unitaire']; ?>">
                                        <?php echo htmlspecialchars($livre['code_livre'] . ' - ' . $livre['titre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Montant de l'amende (FC) *</label>
                                <input type="number" name="montant" id="montant_amende" min="0" step="1" required
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200"
                                    placeholder="Montant en Francs Congolais">
                            </div>

                            <!-- Suggestions de montant -->
                            <div id="montant_suggestions" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suggestions de montant</label>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" onclick="setMontant(500)" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-sm transition-colors">500 FC</button>
                                    <button type="button" onclick="setMontant(1000)" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-sm transition-colors">1000 FC</button>
                                    <button type="button" onclick="setMontant(2000)" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-sm transition-colors">2000 FC</button>
                                    <button type="button" onclick="setMontant(5000)" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 text-sm transition-colors">5000 FC</button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description détaillée *</label>
                                <textarea name="description" rows="4" required
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white resize-none placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200"
                                    placeholder="Décrivez la raison de l'amende, les circonstances, etc."></textarea>
                            </div>
                        </div>

                        <!-- Informations complémentaires -->
                        <div class="space-y-6">
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <i class="fas fa-cog text-library-600 mr-2"></i>
                                    Configuration
                                </h3>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Emprunt associé (optionnel)</label>
                                <select name="emprunt_id" id="emprunt_select" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                    <option value="">Aucun emprunt associé</option>
                                </select>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sélectionnez d'abord un abonné pour voir ses emprunts</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut de l'amende</label>
                                <select name="statut" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                    <option value="impayee" selected>Impayée</option>
                                    <option value="payee">Payée</option>
                                    <option value="remise">Remise</option>
                                    <option value="annulee">Annulée</option>
                                </select>
                            </div>

                            <!-- Barème des amendes -->
                            <div class="bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                                    <i class="fas fa-calculator text-library-600 mr-2"></i>
                                    Barème indicatif des amendes
                                </h4>
                                <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                    <div class="flex justify-between p-2 bg-white dark:bg-gray-800 rounded">
                                        <span>Retard (par jour):</span>
                                        <span class="font-medium text-orange-600">100 FC</span>
                                    </div>
                                    <div class="flex justify-between p-2 bg-white dark:bg-gray-800 rounded">
                                        <span>Perte de livre:</span>
                                        <span class="font-medium text-red-600">80% du prix</span>
                                    </div>
                                    <div class="flex justify-between p-2 bg-white dark:bg-gray-800 rounded">
                                        <span>Détérioration légère:</span>
                                        <span class="font-medium text-yellow-600">30% du prix</span>
                                    </div>
                                    <div class="flex justify-between p-2 bg-white dark:bg-gray-800 rounded">
                                        <span>Détérioration grave:</span>
                                        <span class="font-medium text-red-600">60% du prix</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Calculateur automatique -->
                            <div id="calculateur" class="hidden bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-3 flex items-center">
                                    <i class="fas fa-calculator text-blue-600 mr-2"></i>
                                    Calculateur automatique
                                </h4>
                                <div id="calcul_retard" class="hidden">
                                    <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Nombre de jours de retard</label>
                                    <input type="number" id="jours_retard" min="1" class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-blue-300 dark:border-blue-600 rounded-md text-blue-900 dark:text-blue-100 mb-2">
                                    <button type="button" onclick="calculerRetard()" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-calculator mr-1"></i>
                                        Calculer
                                    </button>
                                </div>
                                <div id="calcul_perte" class="hidden">
                                    <div class="text-sm text-blue-800 dark:text-blue-200 mb-2">Prix du livre: <span id="prix_livre" class="font-bold">0</span> FC</div>
                                    <button type="button" onclick="calculerPerte()" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-calculator mr-1"></i>
                                        Calculer 80% du prix
                                    </button>
                                </div>
                                <div id="calcul_deterioration" class="hidden">
                                    <div class="text-sm text-blue-800 dark:text-blue-200 mb-2">Prix du livre: <span id="prix_livre_det" class="font-bold">0</span> FC</div>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="calculerDeterioration(30)" class="px-3 py-1 bg-yellow-600 text-white rounded-md text-sm hover:bg-yellow-700 transition-colors">
                                            Légère (30%)
                                        </button>
                                        <button type="button" onclick="calculerDeterioration(60)" class="px-3 py-1 bg-red-600 text-white rounded-md text-sm hover:bg-red-700 transition-colors">
                                            Grave (60%)
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations sur l'abonné sélectionné -->
                            <div id="info_abonne" class="hidden bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-4">
                                <h4 class="font-medium text-yellow-900 dark:text-yellow-100 mb-2 flex items-center">
                                    <i class="fas fa-user text-yellow-600 mr-2"></i>
                                    Informations sur l'abonné
                                </h4>
                                <div id="abonne_details" class="text-sm text-yellow-800 dark:text-yellow-200">
                                    <!-- Rempli via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Les champs marqués d'un * sont obligatoires
                            </div>
                            
                            <div class="flex space-x-4">
                                <a href="index.php" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                                    <i class="fas fa-times mr-2"></i>
                                    Annuler
                                </a>
                                <button type="submit" class="px-8 py-3 bg-library-600 hover:bg-library-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fas fa-save mr-2"></i>
                                    Créer l'amende
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

    // Gestion du type d'amende
    document.getElementById('type_amende').addEventListener('change', function() {
        const type = this.value;
        const calculateur = document.getElementById('calculateur');
        const suggestions = document.getElementById('montant_suggestions');
        
        // Cacher tous les calculateurs
        document.getElementById('calcul_retard').classList.add('hidden');
        document.getElementById('calcul_perte').classList.add('hidden');
        document.getElementById('calcul_deterioration').classList.add('hidden');
        
        if (type === 'retard') {
            calculateur.classList.remove('hidden');
            document.getElementById('calcul_retard').classList.remove('hidden');
            suggestions.classList.remove('hidden');
        } else if (type === 'perte') {
            calculateur.classList.remove('hidden');
            document.getElementById('calcul_perte').classList.remove('hidden');
            suggestions.classList.add('hidden');
        } else if (type === 'deterioration') {
            calculateur.classList.remove('hidden');
            document.getElementById('calcul_deterioration').classList.remove('hidden');
            suggestions.classList.add('hidden');
        } else {
            calculateur.classList.add('hidden');
            suggestions.classList.remove('hidden');
        }
    });

    // Gestion de la sélection de livre
    document.getElementById('livre_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const prix = selectedOption.getAttribute('data-prix') || 0;
        
        document.getElementById('prix_livre').textContent = prix;
        document.getElementById('prix_livre_det').textContent = prix;
    });

    // Gestion de la sélection d'abonné
    document.getElementById('abonne_select').addEventListener('change', function() {
        const abonneId = this.value;
        
        if (abonneId) {
            // Charger les emprunts de cet abonné
            fetch(`../../emprunts/api/get_emprunts_abonne.php?abonne_id=${abonneId}`)
            .then(response => response.json())
            .then(emprunts => {
                const empruntSelect = document.getElementById('emprunt_select');
                empruntSelect.innerHTML = '<option value="">Aucun emprunt associé</option>';
                
                emprunts.forEach(emprunt => {
                    const option = document.createElement('option');
                    option.value = emprunt.id;
                    option.textContent = `${emprunt.livre_titre} (Emprunté le ${emprunt.date_emprunt})`;
                    if (emprunt.statut === 'en_retard') {
                        option.textContent += ' - EN RETARD';
                        option.style.color = 'red';
                    }
                    empruntSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des emprunts:', error);
            });

            // Afficher les infos de l'abonné
            const abonne = abonnesData.find(a => a.id == abonneId);
            if (abonne) {
                document.getElementById('abonne_details').innerHTML = `
                    <div class="space-y-1">
                        <div><span class="font-medium">Nom:</span> ${abonne.nom} ${abonne.prenom}</div>
                        <div><span class="font-medium">Classe:</span> ${abonne.classe}</div>
                        <div><span class="font-medium">N° d'abonné:</span> ${abonne.numero_abonne}</div>
                    </div>
                `;
                document.getElementById('info_abonne').classList.remove('hidden');
            }
        } else {
            document.getElementById('info_abonne').classList.add('hidden');
            document.getElementById('emprunt_select').innerHTML = '<option value="">Aucun emprunt associé</option>';
        }
    });

    // Fonctions de calcul
    function setMontant(montant) {
        document.getElementById('montant_amende').value = montant;
    }

    function calculerRetard() {
        const jours = parseInt(document.getElementById('jours_retard').value) || 0;
        const montant = jours * 100;
        document.getElementById('montant_amende').value = montant;
        
        if (jours > 0) {
            document.querySelector('textarea[name="description"]').value = 
                `Amende pour retard de restitution de ${jours} jour(s) (${montant} FC à 100 FC par jour)`;
        }
    }

    function calculerPerte() {
        const prix = parseInt(document.getElementById('prix_livre').textContent) || 0;
        const montant = Math.round(prix * 0.8);
        document.getElementById('montant_amende').value = montant;
        
        if (prix > 0) {
            document.querySelector('textarea[name="description"]').value = 
                `Amende pour perte de livre (80% du prix d'achat: ${montant} FC sur ${prix} FC)`;
        }
    }

    function calculerDeterioration(pourcentage) {
        const prix = parseInt(document.getElementById('prix_livre_det').textContent) || 0;
        const montant = Math.round(prix * (pourcentage / 100));
        document.getElementById('montant_amende').value = montant;
        
        if (prix > 0) {
            const type = pourcentage === 30 ? 'légère' : 'grave';
            document.querySelector('textarea[name="description"]').value = 
                `Amende pour détérioration ${type} de livre (${pourcentage}% du prix d'achat: ${montant} FC sur ${prix} FC)`;
        }
    }

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

    // Si un abonné est présélectionné, charger ses emprunts
    if (<?php echo $abonne_preselect; ?> > 0) {
        document.getElementById('abonne_select').dispatchEvent(new Event('change'));
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