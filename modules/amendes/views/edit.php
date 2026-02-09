<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$user = getUserData();
$controller = new AmendePerteController();
$pageTitle = "Modifier l'Amende";

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
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
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
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Abonné
                                concerné</label>
                            <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-xl">
                                <div class="text-gray-900 dark:text-white font-medium">
                                    <?php echo htmlspecialchars($amende['abonne_nom']); ?>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    N° <?php echo htmlspecialchars($amende['numero_abonne']); ?> -
                                    <?php echo htmlspecialchars($amende['classe']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type
                                    d'amende *</label>
                                <select name="type" required
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                    <option value="retard" <?php echo $amende['type'] == 'retard' ? 'selected' : ''; ?>>
                                        Retard de restitution</option>
                                    <option value="perte" <?php echo $amende['type'] == 'perte' ? 'selected' : ''; ?>>
                                        Perte de livre</option>
                                    <option value="deterioration"
                                        <?php echo $amende['type'] == 'deterioration' ? 'selected' : ''; ?>>
                                        Détérioration</option>
                                    <option value="autre" <?php echo $amende['type'] == 'autre' ? 'selected' : ''; ?>>
                                        Autre</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Montant
                                    de l'amende (FC) *</label>
                                <input type="number" name="montant" value="<?php echo $amende['montant']; ?>" min="0"
                                    step="1" required
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description
                                détaillée *</label>
                            <textarea name="description" rows="4" required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white resize-none transition-all duration-200"><?php echo htmlspecialchars($amende['description']); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut de
                                l'amende *</label>
                            <select name="statut" id="statut_select" required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                <option value="impayee" <?php echo $amende['statut'] == 'impayee' ? 'selected' : ''; ?>>
                                    Impayée</option>
                                <option value="payee" <?php echo $amende['statut'] == 'payee' ? 'selected' : ''; ?>>
                                    Payée</option>
                                <option value="remise" <?php echo $amende['statut'] == 'remise' ? 'selected' : ''; ?>>
                                    Remise</option>
                                <option value="annulee" <?php echo $amende['statut'] == 'annulee' ? 'selected' : ''; ?>>
                                    Annulée</option>
                            </select>
                        </div>
                    </div>

                    <!-- Informations de paiement -->
                    <div class="space-y-6">
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-money-bill-wave text-library-600 mr-2"></i>
                                Informations de Paiement
                            </h3>
                        </div>

                        <div id="paiement_fields"
                            class="<?php echo $amende['statut'] != 'payee' ? 'hidden' : ''; ?> space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date de
                                    paiement</label>
                                <input type="date" name="date_paiement" value="<?php echo $amende['date_paiement']; ?>"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mode de
                                    paiement</label>
                                <select name="mode_paiement"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white transition-all duration-200">
                                    <option value="">Sélectionner...</option>
                                    <option value="especes"
                                        <?php echo $amende['mode_paiement'] == 'especes' ? 'selected' : ''; ?>>Espèces
                                    </option>
                                    <option value="mobile_money"
                                        <?php echo $amende['mode_paiement'] == 'mobile_money' ? 'selected' : ''; ?>>
                                        Mobile Money</option>
                                    <option value="carte"
                                        <?php echo $amende['mode_paiement'] == 'carte' ? 'selected' : ''; ?>>Carte
                                        bancaire</option>
                                    <option value="cheque"
                                        <?php echo $amende['mode_paiement'] == 'cheque' ? 'selected' : ''; ?>>Chèque
                                    </option>
                                    <option value="virement"
                                        <?php echo $amende['mode_paiement'] == 'virement' ? 'selected' : ''; ?>>Virement
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Numéro de
                                    reçu</label>
                                <input type="text" name="recu_numero"
                                    value="<?php echo htmlspecialchars($amende['recu_numero']); ?>"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-library-500 focus:border-transparent text-gray-900 dark:text-white font-mono transition-all duration-200"
                                    placeholder="Ex: REC-<?php echo date('Y'); ?>-<?php echo str_pad($amende['id'], 3, '0', STR_PAD_LEFT); ?>">
                            </div>

                            <?php if ($amende['processed_by_name']): ?>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Paiement traité par</div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($amende['processed_by_name']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Informations sur le livre et l'emprunt -->
                        <?php if ($amende['livre_id'] || $amende['emprunt_id']): ?>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                                <i class="fas fa-book text-library-600 mr-2"></i>
                                Livre et Emprunt Associés
                            </h3>

                            <?php if ($amende['livre_titre']): ?>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($amende['livre_titre']); ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Code:
                                    <?php echo htmlspecialchars($amende['code_livre']); ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Prix:
                                    <?php echo number_format($amende['prix_unitaire'], 0, ',', ' '); ?> FC</div>
                            </div>
                            <?php endif; ?>

                            <?php if ($amende['emprunt_id']): ?>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Emprunt du
                                    <?php echo date('d/m/Y', strtotime($amende['date_emprunt'])); ?></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Retour prévu le
                                    <?php echo date('d/m/Y', strtotime($amende['date_retour_prevue'])); ?></div>
                                <?php if ($amende['date_retour_effective']): ?>
                                <div class="text-sm text-green-600 dark:text-green-400">Retourné le
                                    <?php echo date('d/m/Y', strtotime($amende['date_retour_effective'])); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Historique des modifications -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center mb-4">
                                <i class="fas fa-history text-library-600 mr-2"></i>
                                Historique
                            </h3>

                            <div class="space-y-3">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">Créée par
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo htmlspecialchars($amende['created_by_name']); ?></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                <?php echo date('d/m/Y', strtotime($amende['created_at'])); ?></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <?php echo date('H:i', strtotime($amende['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">Dernière
                                                modification</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Système</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                <?php echo date('d/m/Y', strtotime($amende['updated_at'])); ?></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <?php echo date('H:i', strtotime($amende['updated_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>
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
                            <a href="view.php?id=<?php echo $amende['id']; ?>"
                                class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Annuler
                            </a>
                            <button type="submit"
                                class="px-8 py-3 bg-library-600 hover:bg-library-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-0.5">
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
    // Gestion de l'affichage des champs de paiement
    document.getElementById('statut_select').addEventListener('change', function() {
        const paiementFields = document.getElementById('paiement_fields');
        const datePaiement = document.querySelector('input[name="date_paiement"]');

        if (this.value === 'payee') {
            paiementFields.classList.remove('hidden');
            if (!datePaiement.value) {
                datePaiement.value = '<?php echo date('Y-m-d'); ?>';
            }
        } else {
            paiementFields.classList.add('hidden');
            datePaiement.value = '';
            document.querySelector('select[name="mode_paiement"]').value = '';
            document.querySelector('input[name="recu_numero"]').value = '';
        }
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

        // Validation spécifique pour les paiements
        const statut = document.getElementById('statut_select').value;
        if (statut === 'payee') {
            const datePaiement = document.querySelector('input[name="date_paiement"]');
            const modePaiement = document.querySelector('select[name="mode_paiement"]');

            if (!datePaiement.value) {
                datePaiement.classList.add('border-red-500');
                hasError = true;
            }

            if (!modePaiement.value) {
                modePaiement.classList.add('border-red-500');
                hasError = true;
            }
        }

        if (hasError) {
            e.preventDefault();
            alert(
                'Veuillez remplir tous les champs obligatoires et les informations de paiement si applicable.'
                );
        }
    });
    /// Sidebar functionality
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