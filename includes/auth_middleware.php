<?php
/**
 * Middleware d'authentification et d'autorisation
 */

require_once 'permissions.php';

/**
 * Vérifie l'authentification de l'utilisateur
 */
function requireAuth() {
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_data'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /auth/login.php');
        exit();
    }
}

/**
 * Vérifie les permissions pour accéder à une page
 * @param string $module - Le module requis
 * @param string $action - L'action requise (par défaut: 'read')
 */
function requirePermission($module, $action = 'read') {
    requireAuth();
    
    $user = $_SESSION['user_data'];
    
    if (!canUserAccess($user, $module, $action)) {
        http_response_code(403);
        include_once '../../../includes/403.php';
        exit();
    }
}

/**
 * Vérifie si l'utilisateur est administrateur
 */
function requireAdmin() {
    requireAuth();
    
    $user = $_SESSION['user_data'];
    
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        include_once '../../../includes/403.php';
        exit();
    }
}

/**
 * Obtient les données de l'utilisateur connecté
 * @return array|null
 */
// function getUserData() {
//     session_start();
//     return isset($_SESSION['user_data']) ? $_SESSION['user_data'] : null;
// }

/**
 * Vérifie si l'utilisateur peut effectuer une action sur un enregistrement
 * @param array $user - Données utilisateur
 * @param array $record - Enregistrement à vérifier
 * @param string $action - Action à effectuer
 * @return bool
 */
function canEditRecord($user, $record, $action = 'edit') {
    // L'admin peut tout faire
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Les autres utilisateurs ne peuvent modifier que leurs propres créations
    if (isset($record['created_by']) && $record['created_by'] == $user['id']) {
        return true;
    }
    
    return false;
}
?>