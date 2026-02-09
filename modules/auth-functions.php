<?php
// Fonctions d'authentification et d'autorisation

function requireAdmin() {
    requireLogin(); // S'assurer que l'utilisateur est connecté
    
    global $db;
    
    // Vérifier le rôle de l'utilisateur
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        // Rediriger vers une page d'erreur ou le tableau de bord
        header('Location: ../index?error=' . urlencode('Accès non autorisé. Privilèges administrateur requis.'));
        exit;
    }
}

function isLoggedIn() {
    session_start();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}


function logUserActivity($action, $description = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO user_logs (user_id, action, description, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $description
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement de l'activité: " . $e->getMessage());
        return false;
    }
}

// Fonction pour déconnecter un utilisateur
function logout() {
    session_start();
    
    // Enregistrer l'activité de déconnexion
    logUserActivity('logout', 'Déconnexion utilisateur');
    
    // Détruire toutes les données de session
    $_SESSION = array();
    
    // Détruire le cookie de session si il existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
}
?>