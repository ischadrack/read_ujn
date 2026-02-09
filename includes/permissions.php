<?php
/**
 * Système de gestion des permissions
 */

// Configuration des permissions par rôle
$permissions = [
    'admin' => [
        'dashboard' => true,
        'abonnes' => ['read', 'create', 'edit', 'delete'],
        'livres' => ['read', 'create', 'edit', 'delete'],
        'emprunts' => ['read', 'create', 'edit', 'delete'],
        'amendes' => ['read', 'create', 'edit', 'delete'],
        'reservations' => ['read', 'create', 'edit', 'delete'],
        'categories' => ['read', 'create', 'edit', 'delete'],
        'reports' => ['read'],
        'users' => ['read', 'create', 'edit', 'delete'],
        'settings' => ['read', 'edit']
    ],
    'bibliothecaire' => [
        'dashboard' => true,
        'abonnes' => ['read', 'create', 'edit'],
        'livres' => ['read', 'create', 'edit'],
        'emprunts' => false,
        'amendes' => ['read', 'create', 'edit'],
        'reservations' => ['read', 'create', 'edit'],
        'categories' => ['read', 'create', 'edit'],
        'reports' => ['read'],
        'users' => false,
        'settings' => false
    ],
    'assistant' => [
        'dashboard' => true,
        'abonnes' => ['read'],
        'livres' => ['read'],
        'emprunts' => ['read', 'create'],
        'amendes' => ['read'],
        'reservations' => ['read', 'create'],
        'categories' => ['read'],
        'reports' => false,
        'users' => false,
        'settings' => false
    ]
];

/**
 * Vérifie si un utilisateur a l'autorisation pour un module
 * @param string $userRole - Le rôle de l'utilisateur
 * @param string $module - Le module à vérifier
 * @param string $action - L'action spécifique (optionnel)
 * @return bool
 */
function hasPermission($userRole, $module, $action = null) {
    global $permissions;
    
    // Si le rôle n'existe pas, pas d'autorisation
    if (!isset($permissions[$userRole])) {
        return false;
    }
    
    $rolePermissions = $permissions[$userRole];
    
    // Si le module n'existe pas dans les permissions du rôle
    if (!isset($rolePermissions[$module])) {
        return false;
    }
    
    $modulePermission = $rolePermissions[$module];
    
    // Si la permission est boolean false
    if ($modulePermission === false) {
        return false;
    }
    
    // Si la permission est boolean true (accès complet)
    if ($modulePermission === true) {
        return true;
    }
    
    // Si aucune action spécifique n'est demandée, vérifier l'accès général
    if ($action === null) {
        return is_array($modulePermission) && !empty($modulePermission);
    }
    
    // Vérifier l'action spécifique
    return is_array($modulePermission) && in_array($action, $modulePermission);
}

/**
 * Obtient toutes les permissions d'un rôle
 * @param string $userRole - Le rôle de l'utilisateur
 * @return array
 */
function getRolePermissions($userRole) {
    global $permissions;
    return isset($permissions[$userRole]) ? $permissions[$userRole] : [];
}

/**
 * Vérifie si un utilisateur peut effectuer une action sur un module
 * @param array $user - Les données de l'utilisateur
 * @param string $module - Le module
 * @param string $action - L'action
 * @return bool
 */
function canUserAccess($user, $module, $action = 'read') {
    if (!$user || !isset($user['role'])) {
        return false;
    }
    
    return hasPermission($user['role'], $module, $action);
}

/**
 * Génère les classes CSS pour un lien selon les permissions
 * @param array $user - Les données de l'utilisateur
 * @param string $module - Le module
 * @return string
 */
function getLinkClasses($user, $module) {
    $baseClasses = "flex items-center px-4 py-3 rounded-lg transition-all duration-200 group ";
    
    if (canUserAccess($user, $module)) {
        return $baseClasses . "text-gray-700 dark:text-gray-300 hover:bg-library-100 dark:hover:bg-library-900/30 hover:text-library-600 dark:hover:text-library-400";
    } else {
        return $baseClasses . "text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-50";
    }
}

/**
 * Génère l'attribut href selon les permissions
 * @param array $user - Les données de l'utilisateur
 * @param string $module - Le module
 * @param string $defaultHref - Le lien par défaut
 * @return string
 */
function getLinkHref($user, $module, $defaultHref) {
    return canUserAccess($user, $module) ? $defaultHref : '#';
}

/**
 * Middleware de vérification des permissions pour les pages
 * @param array $user - Les données de l'utilisateur
 * @param string $module - Le module requis
 * @param string $action - L'action requise
 * @param string $redirectUrl - URL de redirection en cas d'accès refusé
 */
function checkPagePermission($user, $module, $action = 'read', $redirectUrl = '/index') {
    if (!canUserAccess($user, $module, $action)) {
        $_SESSION['error_message'] = "Vous n'avez pas l'autorisation d'accéder à cette page.";
        header("Location: " . $redirectUrl);
        exit();
    }
}

/**
 * Affiche un message d'erreur pour accès refusé
 */
function showAccessDenied() {
    return '
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="max-w-md mx-auto text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                <i class="fas fa-lock text-red-600 dark:text-red-400"></i>
            </div>
            <h1 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">Accès refusé</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Vous n\'avez pas l\'autorisation d\'accéder à cette ressource.
            </p>
            <div class="mt-6">
                <a href="/index" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-library-600 hover:bg-library-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>';
}
?>