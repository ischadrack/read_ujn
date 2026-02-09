<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();
requireAdmin();

$controller = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['bulk_action'] ?? '';
    $selected_ids = $_POST['selected_users'] ?? [];
    
    if (empty($selected_ids) || !is_array($selected_ids)) {
        header('Location: index?error=' . urlencode('Aucun utilisateur sélectionné'));
        exit;
    }
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    switch ($action) {
        case 'activate':
            foreach ($selected_ids as $id) {
                $user = $controller->getById($id);
                if ($user) {
                    $user['status'] = 'active';
                    $result = $controller->update($id, $user);
                    if ($result['success']) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Utilisateur ID $id: " . $result['message'];
                    }
                }
            }
            $message = "$success_count utilisateur(s) activé(s)";
            break;
            
        case 'deactivate':
            foreach ($selected_ids as $id) {
                $user = $controller->getById($id);
                if ($user) {
                    $user['status'] = 'inactive';
                    $result = $controller->update($id, $user);
                    if ($result['success']) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Utilisateur ID $id: " . $result['message'];
                    }
                }
            }
            $message = "$success_count utilisateur(s) désactivé(s)";
            break;
            
        case 'delete':
            foreach ($selected_ids as $id) {
                if ($id != $_SESSION['user_id']) {
                    $result = $controller->delete($id);
                    if ($result['success']) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Utilisateur ID $id: " . $result['message'];
                    }
                } else {
                    $error_count++;
                    $errors[] = "Impossible de supprimer votre propre compte";
                }
            }
            $message = "$success_count utilisateur(s) supprimé(s)";
            break;
            
        default:
            header('Location: index?error=' . urlencode('Action non reconnue'));
            exit;
    }
    
    if ($error_count > 0) {
        $message .= " avec $error_count erreur(s)";
    }
    
    if ($success_count > 0) {
        header('Location: index?success=' . urlencode($message));
    } else {
        header('Location: index?error=' . urlencode('Aucune action effectuée: ' . implode(', ', $errors)));
    }
    exit;
}

header('Location: index');
exit;
?>