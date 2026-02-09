<?php
// Fonctions utilitaires pour le système de bibliothèque

function formatDate($date) {
    if (empty($date)) return '';
    return date('d/m/Y H:i', strtotime($date));
}

function formatDateOnly($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

function getStockStatus($quantite_disponible, $seuil_alerte) {
    if ($quantite_disponible <= 0) {
        return [
            'text' => 'Épuisé',
            'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
        ];
    } elseif ($quantite_disponible <= $seuil_alerte) {
        return [
            'text' => 'Stock bas',
            'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
        ];
    } else {
        return [
            'text' => 'Disponible',
            'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        ];
    }
}

function calculateAge($date_naissance) {
    if (empty($date_naissance)) return null;
    
    $birthday = new DateTime($date_naissance);
    $today = new DateTime();
    $age = $today->diff($birthday);
    
    return $age->y;
}

function isSubscriptionExpired($date_expiration) {
    return $date_expiration < date('Y-m-d');
}

function calculateLateFee($date_retour_prevue, $date_retour_effective = null, $tarif_par_jour = 100) {
    $date_retour = $date_retour_effective ?? date('Y-m-d');
    
    if ($date_retour <= $date_retour_prevue) {
        return 0;
    }
    
    $jours_retard = (strtotime($date_retour) - strtotime($date_retour_prevue)) / 86400;
    return floor($jours_retard) * $tarif_par_jour;
}

function generateBarcode($text, $type = 'code128') {
    // Fonction pour générer un code-barres simple (à implémenter avec une librairie)
    return strtoupper($text);
}

function sendNotification($type, $destinataire_id, $titre, $message) {
    global $db;
    
    try {
        $stmt = $db->prepare("INSERT INTO notifications (destinataire_type, destinataire_id, type, titre, message) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute(['abonne', $destinataire_id, $type, $titre, $message]);
    } catch (Exception $e) {
        error_log("Erreur envoi notification: " . $e->getMessage());
        return false;
    }
}

function logActivity($action, $details = '', $user_id = null) {
    global $db;
    
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Erreur log activité: " . $e->getMessage());
        return false;
    }
}

function renderPagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav class="flex items-center justify-between">';
    $html .= '<div class="flex-1 flex justify-between sm:hidden">';
    
    // Mobile: Précédent/Suivant uniquement
    if ($current_page > 1) {
        $prev_params = array_merge($params, ['page' => $current_page - 1]);
        $html .= '<a href="' . $base_url . '?' . http_build_query($prev_params) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Précédent</a>';
    }
    
    if ($current_page < $total_pages) {
        $next_params = array_merge($params, ['page' => $current_page + 1]);
        $html .= '<a href="' . $base_url . '?' . http_build_query($next_params) . '" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Suivant</a>';
    }
    
    $html .= '</div>';
    $html .= '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
    $html .= '<div><p class="text-sm text-gray-700">Page ' . $current_page . ' sur ' . $total_pages . '</p></div>';
    $html .= '<div><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">';
    
    // Desktop: Numéros de pages
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $page_params = array_merge($params, ['page' => $i]);
        $active_class = $i == $current_page ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
        $html .= '<a href="' . $base_url . '?' . http_build_query($page_params) . '" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ' . $active_class . '">' . $i . '</a>';
    }
    
    $html .= '</nav></div></div></nav>';
    
    return $html;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>