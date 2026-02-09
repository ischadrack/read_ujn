<?php
require_once '../../../config/config.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['livre_id']) || !is_numeric($_GET['livre_id'])) {
    echo json_encode([]);
    exit;
}

$livre_id = (int)$_GET['livre_id'];

try {
    $sql = "SELECT r.id, r.date_reservation, r.date_expiration, r.priorite,
                   CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                   a.numero_abonne, a.classe
            FROM reservations r
            JOIN abonnes a ON r.abonne_id = a.id
            WHERE r.livre_id = ? 
            AND r.statut = 'active'
            ORDER BY r.priorite ASC, r.date_reservation ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$livre_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les dates pour l'affichage
    foreach ($reservations as &$reservation) {
        $reservation['date_reservation'] = date('d/m/Y', strtotime($reservation['date_reservation']));
        $reservation['date_expiration'] = date('d/m/Y', strtotime($reservation['date_expiration']));
    }
    
    echo json_encode($reservations);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>