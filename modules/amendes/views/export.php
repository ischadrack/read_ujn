<?php
require_once '../../../config/config.php';
require_once '../controller.php';
requireLogin();

$controller = new AmendePerteController();

// Récupérer les filtres de l'URL
$filters = [];
if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
if (isset($_GET['type'])) $filters['type'] = $_GET['type'];
if (isset($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (isset($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
if (isset($_GET['date_fin'])) $filters['date_fin'] = $_GET['date_fin'];

$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$export_type = $_GET['export'] ?? 'excel';

// Récupérer toutes les amendes (pas de pagination pour l'export)
$result = $controller->index($filters, 1, 10000, $sort, $order);
$amendes = $result['data'];

if ($export_type === 'excel') {
    // Export Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="amendes_pertes_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'N° Amende',
        'Date Amende',
        'Type',
        'Statut',
        'Abonné',
        'N° Abonné',
        'Classe',
        'Livre',
        'Code Livre',
        'Montant (FC)',
        'Description',
        'Date Paiement',
        'Mode Paiement',
        'N° Reçu',
        'Créée par',
        'Date Création'
    ]);
    
    // Données
    foreach ($amendes as $amende) {
        fputcsv($output, [
            str_pad($amende['id'], 6, '0', STR_PAD_LEFT),
            date('d/m/Y', strtotime($amende['date_amende'])),
            ucfirst($amende['type']),
            ucfirst($amende['statut']),
            $amende['abonne_nom'],
            $amende['numero_abonne'],
            $amende['classe'],
            $amende['livre_titre'] ?: 'Non spécifié',
            $amende['code_livre'] ?: '',
            number_format($amende['montant'], 0, ',', ' '),
            $amende['description'],
            $amende['date_paiement'] ? date('d/m/Y', strtotime($amende['date_paiement'])) : '',
            $amende['mode_paiement'] ?: '',
            $amende['recu_numero'] ?: '',
            $amende['created_by_name'],
            date('d/m/Y H:i', strtotime($amende['created_at']))
        ]);
    }
    
    fclose($output);
    
} elseif ($export_type === 'pdf') {
    // Export PDF simple (HTML vers PDF)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Rapport des Amendes & Pertes</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .stats {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
                background-color: #f5f5f5;
                padding: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 10px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #4a90e2;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .footer {
                margin-top: 20px;
                text-align: center;
                font-size: 10px;
                color: #666;
            }
            .status {
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 9px;
            }
            .status-impayee { background-color: #ffebee; color: #c62828; }
            .status-payee { background-color: #e8f5e8; color: #2e7d32; }
            .status-annulee { background-color: #f5f5f5; color: #666; }
            .status-remise { background-color: #e3f2fd; color: #1976d2; }
            .type-retard { background-color: #fff3e0; color: #f57c00; }
            .type-perte { background-color: #ffebee; color: #d32f2f; }
            .type-deterioration { background-color: #fffde7; color: #f9a825; }
            .type-autre { background-color: #f5f5f5; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Bibliothèque UN JOUR NOUVEAU</h1>
            <h2>Rapport des Amendes & Pertes</h2>
            <p>Généré le <?php echo date('d/m/Y à H:i'); ?></p>
        </div>

        <?php
        $stats = $controller->getStats();
        ?>
        <div class="stats">
            <div><strong>Total Impayées:</strong> <?php echo number_format($stats['total_impayees'], 0, ',', ' '); ?> FC</div>
            <div><strong>Payées ce mois:</strong> <?php echo number_format($stats['payees_mois'], 0, ',', ' '); ?> FC</div>
            <div><strong>Retards:</strong> <?php echo $stats['retards']; ?></div>
            <div><strong>Pertes:</strong> <?php echo $stats['pertes']; ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 8%">N° Amende</th>
                    <th style="width: 10%">Date</th>
                    <th style="width: 10%">Type</th>
                    <th style="width: 20%">Abonné</th>
                    <th style="width: 15%">Livre</th>
                    <th style="width: 10%">Montant</th>
                    <th style="width: 8%">Statut</th>
                    <th style="width: 10%">Paiement</th>
                    <th style="width: 9%">Reçu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($amendes as $amende): ?>
                <tr>
                    <td><?php echo str_pad($amende['id'], 6, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($amende['date_amende'])); ?></td>
                    <td>
                        <span class="status type-<?php echo $amende['type']; ?>">
                            <?php echo ucfirst($amende['type']); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($amende['abonne_nom']); ?><br>
                        <small><?php echo htmlspecialchars($amende['numero_abonne'] . ' - ' . $amende['classe']); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($amende['livre_titre'] ?: 'Non spécifié'); ?><br>
                        <small><?php echo htmlspecialchars($amende['code_livre'] ?: ''); ?></small>
                    </td>
                    <td><strong><?php echo number_format($amende['montant'], 0, ',', ' '); ?> FC</strong></td>
                    <td>
                        <span class="status status-<?php echo $amende['statut']; ?>">
                            <?php echo ucfirst($amende['statut']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($amende['date_paiement']): ?>
                        <?php echo date('d/m/Y', strtotime($amende['date_paiement'])); ?><br>
                        <small><?php echo htmlspecialchars($amende['mode_paiement']); ?></small>
                        <?php else: ?>
                        <span style="color: #999;">Non payée</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($amende['recu_numero'] ?: ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer flex justify-between mt-4">
            <p><strong>Nombre total d'amendes:</strong> <?php echo count($amendes); ?></p>
            <p><strong>Total des montants:</strong> <?php echo number_format(array_sum(array_column($amendes, 'montant')), 0, ',', ' '); ?> FC</p>
            <p><strong>Total payé:</strong> <?php echo number_format(array_sum(array_column(array_filter($amendes, function($a) { return $a['statut'] == 'payee'; }), 'montant')), 0, ',', ' '); ?> FC</p>
        </div>

        <script>
            // Auto-print when PDF export is requested
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>
    <?php
}
?>