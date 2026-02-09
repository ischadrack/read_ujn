<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$user = getUserData();
$message = '';
$error = '';

// Traitement du formulaire de mise à jour du profil
if ($_POST && !isset($_POST['password_change'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $telephone = sanitizeInput($_POST['telephone'] ?? '');
    $specialite = sanitizeInput($_POST['specialite'] ?? '');
    
    // Vérifier si l'email ou username existe déjà (autre que l'utilisateur actuel)
    $check_stmt = $db->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $check_stmt->execute([$email, $username, $_SESSION['user_id']]);
    
    if ($check_stmt->fetch()) {
        $error = "Cet email ou nom d'utilisateur est déjà utilisé.";
    } else {
        // Traitement de l'upload de photo
        $photo_path = $user['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= $max_size) {
                $upload_dir = __DIR__ . '/assets/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne photo si elle existe
                    if ($user['photo'] && file_exists(__DIR__ . '/assets/uploads/' . $user['photo'])) {
                        unlink(__DIR__ . '/assets/uploads/' . $user['photo']);
                    }
                    $photo_path = $new_filename;
                }
            } else {
                $error = "Format de fichier non autorisé ou fichier trop volumineux (max 5MB).";
            }
        }
        
        if (empty($error)) {
            // Mettre à jour le profil
            $update_stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, telephone = ?, specialite = ?, photo = ? WHERE id = ?");
            
            if ($update_stmt->execute([$first_name, $last_name, $username, $email, $telephone, $specialite, $photo_path, $_SESSION['user_id']])) {
                $message = "Profil mis à jour avec succès.";
                $user = getUserData(); // Recharger les données
            } else {
                $error = "Erreur lors de la mise à jour du profil.";
            }
        }
    }
}

// Traitement du changement de mot de passe
if ($_POST && isset($_POST['password_change'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($new_password) < 6) {
        $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Mot de passe actuel incorrect.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pwd_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($update_pwd_stmt->execute([$hashed_password, $_SESSION['user_id']])) {
            $message = "Mot de passe changé avec succès.";
        } else {
            $error = "Erreur lors du changement de mot de passe.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - <?php echo SITE_NAME; ?></title>
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
<body class="font-ubuntu bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-900 dark:to-gray-800 min-h-screen">
 <!-- Sidebar -->
    <?php require_once 'modules/includes/sidebar.php'; ?>
<!-- Main Content -->
<div class="lg:ml-64 min-h-screen">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-gradient-to-br from-library-50 to-blue-100 dark:from-gray-800 dark:to-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between h-16 px-6">
            <div class="flex items-center space-x-4">
                <button id="sidebarToggle" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Mon Profil</h1>
            </div>

            <div class="flex items-center space-x-4">
                <button id="themeToggle" class="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-moon dark:hidden text-lg"></i>
                    <i class="fas fa-sun hidden dark:block text-lg"></i>
                </button>

                <div class="relative">
                    <button id="userDropdown" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <?php if (!empty($user['photo'])): ?>
                            <img src="assets/uploads/<?php echo htmlspecialchars($user['photo']); ?>" class="w-8 h-8 rounded-full object-cover border-2 border-library-200 dark:border-library-600" alt="Profile">
                        <?php else: ?>
                            <div class="w-8 h-8 bg-gradient-to-br from-library-500 to-purple-600 rounded-full flex items-center justify-center border-2 border-library-200 dark:border-library-600">
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

                    <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                        </div>
                        <div class="py-2">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-library-600 dark:text-library-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-user mr-2"></i>Mon Profil
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
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
            <h1 class="text-3xl font-fredoka font-bold text-gray-900 dark:text-white">Mon Profil</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Gérer vos informations personnelles</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Informations personnelles -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Informations personnelles</h3>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Photo de profil -->
                    <div class="text-center">
                        <div class="relative inline-block">
                            <?php if (!empty($user['photo'])): ?>
                                <img id="profilePreview" src="assets/uploads/<?php echo htmlspecialchars($user['photo']); ?>" class="w-24 h-24 rounded-full object-cover border-4 border-library-200 dark:border-library-600" alt="Profile">
                            <?php else: ?>
                                <div id="profilePreview" class="w-24 h-24 bg-gradient-to-br from-library-500 to-purple-600 rounded-full flex items-center justify-center border-4 border-library-200 dark:border-library-600">
                                    <span class="text-white text-2xl font-bold">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="absolute bottom-0 right-0">
                                <label for="photo" class="bg-green-600 hover:bg-green-700 text-white p-2 rounded-full cursor-pointer transition-colors shadow-lg">
                                    <i class="fas fa-upload text-sm"></i>
                                </label>
                                <input type="file" id="photo" name="photo" accept="image/*" class="hidden" onchange="previewImage(this)">
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Laissez vide pour conserver la photo actuelle</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prénom *</label>
                            <input type="text" name="first_name" required value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom *</label>
                            <input type="text" name="last_name" required value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email *</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom d'utilisateur *</label>
                            <input type="text" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rôle</label>
                        <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-600 dark:text-gray-300">
                            <?php echo ucfirst($user['role']); ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dernière connexion</label>
                        <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-600 dark:text-gray-300">
                            <?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Jamais'; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Téléphone</label>
                            <input type="tel" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Spécialité</label>
                            <input type="text" name="specialite" value="<?php echo htmlspecialchars($user['specialite'] ?? ''); ?>"
                                   placeholder="Ex: Mathématiques, Français..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-library-600 hover:bg-library-700 text-white py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i>Mettre à jour le profil
                    </button>
                </form>
            </div>

            <!-- Changer le mot de passe -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-6 text-gray-900 dark:text-white">Changer le mot de passe</h3>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="password_change" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mot de passe actuel *</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nouveau mot de passe *</label>
                        <input type="password" name="new_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 caractères</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirmer le nouveau mot de passe *</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-library-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-key mr-2"></i>Changer le mot de passe
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Preview image
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('profilePreview');
                preview.innerHTML = `<img src="${e.target.result}" class="w-24 h-24 rounded-full object-cover border-4 border-library-200 dark:border-library-600" alt="Profile Preview">`;
            }
            reader.readAsDataURL(input.files[0]);
        }
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