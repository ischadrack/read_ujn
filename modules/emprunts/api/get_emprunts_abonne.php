<?php
require_once '../../../config/config.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['abonne_id']) || !is_numeric($_GET['abonne_id'])) {
    echo json_encode([]);
    exit;
}

$abonne_id = (int)$_GET['abonne_id'];

try {
    $sql = "SELECT e.id, e.date_emprunt, e.date_retour_prevue, e.statut,
                   l.titre as livre_titre, l.code_livre
            FROM emprunts e
            JOIN livres l ON e.livre_id = l.id
            WHERE e.abonne_id = ? 
            AND e.statut IN ('en_cours', 'en_retard')
            ORDER BY e.date_emprunt DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$abonne_id]);
    $emprunts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les dates pour l'affichage
    foreach ($emprunts as &$emprunt) {
        $emprunt['date_emprunt'] = date('d/m/Y', strtotime($emprunt['date_emprunt']));
        $emprunt['date_retour_prevue'] = date('d/m/Y', strtotime($emprunt['date_retour_prevue']));
    }
    
    echo json_encode($emprunts);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>