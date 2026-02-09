<?php
require_once '../../../config/config.php';
require_once '../controller.php';


$controller = new UserController();

// Récupération des filtres
$filters = [];
if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
if (isset($_GET['role'])) $filters['role'] = $_GET['role'];
if (isset($_GET['status'])) $filters['status'] = $_GET['status'];

// Récupération de tous les utilisateurs (sans pagination pour l'export)
$result = $controller->index($filters, 1, 10000);
$users = $result['data'];

$export_type = $_GET['export'] ?? 'excel';

if ($export_type === 'excel') {
    // Export Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="utilisateurs_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM pour Excel UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes
    fputcsv($output, [
        'ID',
        'Prénom',
        'Nom',
        'Nom d\'utilisateur',
        'Email',
        'Rôle',
        'Statut',
        'Téléphone',
        'Spécialité',
        'Date de création',
        'Dernière connexion'
    ], ';');
    
    // Données
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['first_name'],
            $user['last_name'],
            $user['username'],
            $user['email'],
            ucfirst($user['role']),
            ucfirst($user['status']),
            $user['telephone'] ?? '',
            $user['specialite'] ?? '',
            date('d/m/Y H:i', strtotime($user['created_at'])),
            $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais'
        ], ';');
    }
    
    fclose($output);
    exit;

} elseif ($export_type === 'pdf') {
    // Export PDF - Simple HTML to PDF conversion
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Liste des Utilisateurs</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h1 { text-align: center; color: #333; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .role-admin { background-color: #ffebee; }
            .role-bibliothecaire { background-color: #e3f2fd; }
            .role-assistant { background-color: #e8f5e8; }
            .status-active { color: #2e7d32; }
            .status-inactive { color: #d32f2f; }
        </style>
    </head>
    <body>
        <h1>Liste des Utilisateurs - Bibliothèque UN JOUR NOUVEAU</h1>
        <p>Généré le ' . date('d/m/Y à H:i') . '</p>
        
        <table>
            <thead>
                <tr>
                    <th>Nom complet</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Téléphone</th>
                    <th>Date création</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($users as $user) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($user['email']) . '</td>';
        $html .= '<td class="role-' . $user['role'] . '">' . ucfirst($user['role']) . '</td>';
        $html .= '<td class="status-' . $user['status'] . '">' . ucfirst($user['status']) . '</td>';
        $html .= '<td>' . htmlspecialchars($user['telephone'] ?? '-') . '</td>';
        $html .= '<td>' . date('d/m/Y', strtotime($user['created_at'])) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>
    </body>
    </html>';

    // Si vous avez une bibliothèque PDF comme TCPDF ou DOMPDF, utilisez-la ici
    // Sinon, on retourne du HTML qui peut être imprimé en PDF par le navigateur
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="utilisateurs_' . date('Y-m-d_H-i-s') . '.html"');
    echo $html;
    exit;
}

// Si type non reconnu, redirection
header('Location: index?error=' . urlencode('Type d\'export non supporté'));
exit;
?>