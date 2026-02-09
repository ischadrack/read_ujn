<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

requirePermission('reservations', 'read');
requireLogin();

$controller = new ReservationController();

// Récupérer les filtres de la session ou de l'URL
$filters = [];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];
if (!empty($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (!empty($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
if (!empty($_GET['date_fin'])) $filters['date_fin'] = $_GET['date_fin'];

$reservations = $controller->exportData($filters);
$export_type = $_GET['export'] ?? 'excel';

if ($export_type === 'excel') {
    // Export Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="reservations_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Date Réservation</th>';
    echo '<th>Date Expiration</th>';
    echo '<th>Abonné</th>';
    echo '<th>N° Abonné</th>';
    echo '<th>Classe</th>';
    echo '<th>Livre</th>';
    echo '<th>Code Livre</th>';
    echo '<th>Auteur</th>';
    echo '<th>Catégorie</th>';
    echo '<th>Priorité</th>';
    echo '<th>Statut</th>';
    echo '<th>Créé par</th>';
    echo '<th>Date création</th>';
    echo '</tr>';
    
    foreach ($reservations as $reservation) {
        echo '<tr>';
        echo '<td>' . $reservation['id'] . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($reservation['date_reservation'])) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($reservation['date_expiration'])) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['abonne_nom']) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['numero_abonne']) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['classe']) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['livre_titre']) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['code_livre']) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['auteur']) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['categorie_nom']) . '</td>';
        echo '<td>' . $reservation['priorite'] . '</td>';
        echo '<td>' . ucfirst($reservation['statut']) . '</td>';
        echo '<td>' . htmlspecialchars($reservation['created_by_name']) . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($reservation['created_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} else {
    // Export CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="reservations_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    fputcsv($output, [
        'ID',
        'Date Réservation',
        'Date Expiration', 
        'Abonné',
        'N° Abonné',
        'Classe',
        'Livre',
        'Code Livre',
        'Auteur',
        'Catégorie',
        'Priorité',
        'Statut',
        'Créé par',
        'Date création'
    ]);
    
    // Données
    foreach ($reservations as $reservation) {
        fputcsv($output, [
            $reservation['id'],
            date('d/m/Y', strtotime($reservation['date_reservation'])),
            date('d/m/Y', strtotime($reservation['date_expiration'])),
            $reservation['abonne_nom'],
            $reservation['numero_abonne'],
            $reservation['classe'],
            $reservation['livre_titre'],
            $reservation['code_livre'],
            $reservation['auteur'],
            $reservation['categorie_nom'],
            $reservation['priorite'],
            ucfirst($reservation['statut']),
            $reservation['created_by_name'],
            date('d/m/Y H:i', strtotime($reservation['created_at']))
        ]);
    }
    
    fclose($output);
}
exit;
?>