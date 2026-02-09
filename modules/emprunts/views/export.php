<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

requireLogin();

$controller = new EmpruntController();

// Récupération des filtres
$filters = [];
if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
if (isset($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (isset($_GET['abonne_id'])) $filters['abonne_id'] = $_GET['abonne_id'];
if (isset($_GET['livre_id'])) $filters['livre_id'] = $_GET['livre_id'];
if (isset($_GET['date_debut'])) $filters['date_debut'] = $_GET['date_debut'];
if (isset($_GET['date_fin'])) $filters['date_fin'] = $_GET['date_fin'];
if (isset($_GET['en_retard'])) $filters['en_retard'] = $_GET['en_retard'];

$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$export_type = $_GET['export'] ?? 'excel';

// Récupération de tous les emprunts (sans pagination pour l'export)
$result = $controller->index($filters, 1, 10000, $sort, $order);
$emprunts = $result['data'];

if ($export_type === 'pdf') {
    generatePDFList($emprunts, $filters);
} else {
    generateExcelExport($emprunts);
}

function generateExcelExport($emprunts) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="emprunts_bibliotheque_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // BOM for UTF-8
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    $headers = [
        'Livre', 'Code Livre', 'Auteur', 'Abonné', 'Numéro Abonné', 'Classe',
        'Date Emprunt', 'Date Retour Prévue', 'Date Retour Effective', 'Statut',
        'Jours de Retard', 'Durée (jours)', 'État Livre Emprunt', 'État Livre Retour',
        'Amende', 'Observations Emprunt', 'Observations Retour', 'Créé par', 'Traité par'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Données
    foreach ($emprunts as $emprunt) {
        $row = [
            $emprunt['livre_titre'] ?? '',
            $emprunt['code_livre'] ?? '',
            $emprunt['auteur'] ?? '',
            $emprunt['abonne_nom'] ?? '',
            $emprunt['numero_abonne'] ?? '',
            $emprunt['classe'] ?? '',
            date('d/m/Y', strtotime($emprunt['date_emprunt'])),
            date('d/m/Y', strtotime($emprunt['date_retour_prevue'])),
            $emprunt['date_retour_effective'] ? date('d/m/Y', strtotime($emprunt['date_retour_effective'])) : '',
            ucfirst($emprunt['statut']),
            max(0, $emprunt['jours_retard'] ?? 0),
            $emprunt['duree_jours'] ?? 14,
            ucfirst($emprunt['etat_livre_emprunt'] ?? 'bon'),
            ucfirst($emprunt['etat_livre_retour'] ?? ''),
            $emprunt['amende'] ?? 0,
            $emprunt['observations_emprunt'] ?? '',
            $emprunt['observations_retour'] ?? '',
            $emprunt['created_by_name'] ?? '',
            $emprunt['processed_by_name'] ?? ''
        ];
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
}

function generatePDFList($emprunts, $filters = []) {
    $stats = calculateStats($emprunts);
    
    header('Content-Type: text/html; charset=utf-8');
    echo getModernHTMLTemplate('Rapport des Emprunts');
    echo generateListContent($emprunts, $stats, $filters);
    echo getHTMLFooter();
}

function calculateStats($emprunts) {
    $stats = [
        'total' => count($emprunts),
        'en_cours' => 0,
        'rendus' => 0,
        'en_retard' => 0,
        'amendes_total' => 0
    ];
    
    foreach ($emprunts as $emprunt) {
        switch ($emprunt['statut']) {
            case 'en_cours':
                $stats['en_cours']++;
                if (($emprunt['jours_retard'] ?? 0) > 0) {
                    $stats['en_retard']++;
                }
                break;
            case 'rendu':
                $stats['rendus']++;
                break;
        }
        
        $stats['amendes_total'] += $emprunt['amende'] ?? 0;
    }
    
    return $stats;
}

function getModernHTMLTemplate($title = 'Rapport des Emprunts') {
    return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&family=Fredoka+One:wght@400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&family=Fredoka+One:wght@400&display=swap");
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Ubuntu", sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            padding: 0;
        }

        .fredoka-font {
            font-family: "Fredoka One", cursive;
        }

        @media print {
            @page {
                size: A4;
                margin: 8mm;
            }
            
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
                break-before: page;
            }
            
            .document-card {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }

        @media screen {
            body {
                padding: 10mm;
                background: #f5f5f5;
            }
            
            .print-controls {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                display: flex;
                gap: 10px;
            }
            
            .print-btn {
                background: #059669;
                color: #fff;
                border: none;
                padding: 12px 24px;
                cursor: pointer;
                font-family: "Ubuntu", sans-serif;
                font-size: 12px;
                font-weight: 500;
                border-radius: 6px;
                transition: all 0.2s;
                box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
            }
            
            .print-btn:hover {
                background: #047857;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(5, 150, 105, 0.3);
            }
        }

        .document-card {
            width: 190mm;
            max-width: 190mm;
            margin: 0 auto 20px;
            background: #fff;
            padding: 10mm;
            position: relative;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8mm;
            padding-bottom: 4mm;
            border-bottom: 2px solid #059669;
        }

        .header-left {
            text-align: left;
            flex: 1;
        }

        .header-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .school-name {
            font-family: "Fredoka One", cursive;
            font-size: 18px;
            font-weight: 400;
            text-transform: uppercase;
            margin-bottom: 2mm;
            color: #059669;
            letter-spacing: 1px;
        }

        .school-subtitle {
            font-family: "Ubuntu", sans-serif;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .school-address {
            font-family: "Ubuntu", sans-serif;
            font-size: 10px;
            font-weight: 400;
            margin-bottom: 1mm;
            line-height: 1.4;
            color: #6b7280;
        }

        .document-title {
            font-family: "Fredoka One", cursive;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 4mm 0;
            text-align: center;
            color: #1f2937;
            letter-spacing: 2px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #059669, #10b981);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3mm;
            font-family: "Fredoka One", cursive;
            font-size: 9px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
            text-align: center;
            line-height: 1.2;
        }

        .document-number {
            font-family: "Ubuntu", sans-serif;
            font-size: 10px;
            font-weight: 700;
            color: #374151;
            background: #f3f4f6;
            padding: 2mm;
            border-radius: 4px;
        }

        .separator-line {
            text-align: center;
            margin: 4mm 0;
            font-size: 12px;
            font-family: "Ubuntu", monospace;
            letter-spacing: 1px;
            color: #9ca3af;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4mm;
            margin: 4mm 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            padding: 3mm;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #bbf7d0;
        }

        .stat-card.urgent {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #fca5a5;
        }

        .stat-number {
            font-family: "Fredoka One", cursive;
            font-size: 18px;
            font-weight: 400;
            color: #059669;
            margin-bottom: 1mm;
        }

        .stat-number.urgent {
            color: #dc2626;
        }

        .stat-label {
            font-family: "Ubuntu", sans-serif;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            color: #064e3b;
            letter-spacing: 0.5px;
        }

        .stat-label.urgent {
            color: #7f1d1d;
        }

        .info-section {
            margin-bottom: 5mm;
            background: #fafafa;
            padding: 4mm;
            border-radius: 6px;
            border-left: 4px solid #059669;
        }

        .section-title {
            font-family: "Ubuntu", sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 3mm;
            color: #1f2937;
            letter-spacing: 1px;
        }

        .table-container {
            overflow-x: auto;
            margin: 4mm 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .data-table th {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff;
            padding: 3mm 2mm;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 2mm;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .data-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .data-table tr:hover {
            background: #f1f5f9;
        }

        .status-badge {
            display: inline-block;
            padding: 1mm 2mm;
            border-radius: 4px;
            font-family: "Ubuntu", sans-serif;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-en_cours {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-rendu {
            background: #dcfce7;
            color: #166534;
        }

        .status-en_retard {
            background: #fee2e2;
            color: #991b1b;
        }

        .retard-badge {
            background: #fef2f2;
            color: #dc2626;
            font-weight: 700;
            padding: 1mm;
            border-radius: 3px;
            font-size: 8px;
        }

        .amende-badge {
            background: #fff7ed;
            color: #ea580c;
            font-weight: 700;
            padding: 1mm;
            border-radius: 3px;
            font-size: 8px;
        }

        .footer-info {
            text-align: center;
            margin-top: 6mm;
            font-family: "Ubuntu", sans-serif;
            font-size: 9px;
            line-height: 1.5;
            color: #6b7280;
            border-top: 2px solid #e5e7eb;
            padding-top: 4mm;
        }

        .generation-info {
            font-weight: 600;
            color: #374151;
            margin-bottom: 2mm;
        }

        .contact-info {
            color: #059669;
            font-weight: 500;
        }

    </style>
</head>
<body>';
}

function generateListContent($emprunts, $stats, $filters) {
    $html = '<div class="document-card">
        <div class="document-header">
            <div class="header-left">
                <div class="school-name">UN JOUR NOUVEAU</div>
                <div class="school-subtitle">Bibliothèque Scolaire</div>
                <div class="school-address">N° 140, Av. La Frontière, Q. Katindo</div>
                <div class="school-address">GOMA - NIF: A1207713Y.CA 90740</div>
                <div class="school-address">Tél: +243 822 01 74 80</div>
                <div class="school-address contact-info">ujn@gunjournouveau.org</div>
            </div>
            <div class="header-right">
                <div class="logo">
                    <div>GESTION<br>EMPRUNTS</div>
                </div>
                <div class="document-number">
                    DOC N° EMP-' . date('Ymd-His') . '
                </div>
            </div>
        </div>
        
        <div class="document-title">RAPPORT DES EMPRUNTS</div>
        
        <div class="separator-line">═══════════════════════════════════════════════════════════════════════════════════════</div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">' . $stats['total'] . '</div>
                <div class="stat-label">Total Emprunts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $stats['en_cours'] . '</div>
                <div class="stat-label">En Cours</div>
            </div>
            <div class="stat-card ' . ($stats['en_retard'] > 0 ? 'urgent' : '') . '">
                <div class="stat-number ' . ($stats['en_retard'] > 0 ? 'urgent' : '') . '">' . $stats['en_retard'] . '</div>
                <div class="stat-label ' . ($stats['en_retard'] > 0 ? 'urgent' : '') . '">En Retard</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . number_format($stats['amendes_total'], 0, ',', ' ') . '</div>
                <div class="stat-label">Amendes (FC)</div>
            </div>
        </div>
        
        <div class="separator-line">───────────────────────────────────────────────────────────────────────────────────────</div>';
        
    if (!empty($filters)) {
        $html .= '<div class="info-section">
            <div class="section-title">Filtres Appliqués</div>';
        
        if (!empty($filters['search'])) {
            $html .= '<div style="margin-bottom: 1mm;">Recherche: "' . htmlspecialchars($filters['search']) . '"</div>';
        }
        if (!empty($filters['statut'])) {
            $html .= '<div style="margin-bottom: 1mm;">Statut: ' . ucfirst($filters['statut']) . '</div>';
        }
        if (!empty($filters['date_debut']) || !empty($filters['date_fin'])) {
            $html .= '<div style="margin-bottom: 1mm;">Période: ';
            if (!empty($filters['date_debut'])) {
                $html .= 'du ' . date('d/m/Y', strtotime($filters['date_debut']));
            }
            if (!empty($filters['date_fin'])) {
                $html .= ' au ' . date('d/m/Y', strtotime($filters['date_fin']));
            }
            $html .= '</div>';
        }
        if (!empty($filters['en_retard'])) {
            $html .= '<div style="margin-bottom: 1mm;">Filtre: Emprunts en retard uniquement</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '<div class="info-section">
            <div class="section-title">Détail des Emprunts</div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Livre & Abonné</th>
                            <th>Dates</th>
                            <th>Statut</th>
                            <th>Retard</th>
                            <th>Amende</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
    foreach ($emprunts as $emprunt) {
        $status_class = 'status-' . str_replace(' ', '_', $emprunt['statut']);
        $jours_retard = max(0, $emprunt['jours_retard'] ?? 0);
        
        if ($emprunt['statut'] === 'en_cours' && $jours_retard > 0) {
            $status_class = 'status-en_retard';
        }
        
        $html .= '<tr>
            <td>
                <div style="font-weight: 700; margin-bottom: 1mm;">' . htmlspecialchars($emprunt['livre_titre'] ?? 'Livre non spécifié') . '</div>
                <div style="font-size: 8px; color: #6b7280; margin-bottom: 1mm;">Code: ' . htmlspecialchars($emprunt['code_livre'] ?? 'N/A') . '</div>
                <div style="font-weight: 600;">' . htmlspecialchars($emprunt['abonne_nom'] ?? 'Abonné non spécifié') . '</div>
                <div style="font-size: 8px; color: #6b7280;">N° ' . htmlspecialchars($emprunt['numero_abonne'] ?? 'N/A') . ' - ' . htmlspecialchars($emprunt['classe'] ?? 'N/A') . '</div>
            </td>
            <td>
                <div style="margin-bottom: 1mm;"><strong>Emprunt:</strong><br>' . date('d/m/Y', strtotime($emprunt['date_emprunt'])) . '</div>
                <div style="margin-bottom: 1mm;"><strong>Retour prévu:</strong><br>' . date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) . '</div>';
                
        if ($emprunt['date_retour_effective']) {
            $html .= '<div><strong>Retour effectif:</strong><br>' . date('d/m/Y', strtotime($emprunt['date_retour_effective'])) . '</div>';
        }
        
        $html .= '</td>
            <td><span class="status-badge ' . $status_class . '">' . ucfirst($emprunt['statut']) . '</span></td>
            <td>';
            
        if ($jours_retard > 0) {
            $html .= '<span class="retard-badge">' . $jours_retard . ' jour(s)</span>';
        } else {
            $html .= '<span style="color: #059669;">À jour</span>';
        }
        
        $html .= '</td>
            <td>';
            
        if (($emprunt['amende'] ?? 0) > 0) {
            $html .= '<span class="amende-badge">' . number_format($emprunt['amende'], 0, ',', ' ') . ' FC</span>';
        } else {
            $html .= '<span style="color: #6b7280;">-</span>';
        }
        
        $html .= '</td>
        </tr>';
    }
    
    $html .= '</tbody>
                </table>
            </div>
        </div>
        
        <div class="separator-line">═══════════════════════════════════════════════════════════════════════════════════════</div>
        
        <div class="footer-info">
            <div class="generation-info">
                DOCUMENT GÉNÉRÉ LE ' . strtoupper(date('d/m/Y')) . ' À ' . date('H:i') . '
            </div>
            <div>Système de Gestion Bibliothèque - UN JOUR NOUVEAU</div>
            <div class="contact-info">Pour toute question: ujn@gunjournouveau.org</div>
        </div>
    </div>';
    
    return $html;
}

function getHTMLFooter() {
    return '
    <div class="print-controls no-print">
        <button onclick="window.print()" class="print-btn">
            📄 IMPRIMER
        </button>
        <button onclick="exportToExcel()" class="print-btn">
            📊 EXCEL
        </button>
        <a href="index.php">
            <button class="print-btn">
                ← RETOUR
            </button>
        </a>
    </div>

    <script>
        function exportToExcel() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set("export", "excel");
            window.location.href = currentUrl.toString();
        }

        document.addEventListener("keydown", function(e) {
            if (e.ctrlKey && e.key === "p") {
                e.preventDefault();
                window.print();
            }
            if (e.key === "Escape") {
                window.history.back();
            }
        });

        window.addEventListener("beforeprint", function() {
            document.querySelectorAll(".document-card").forEach(card => {
                card.style.pageBreakAfter = "always";
                card.style.pageBreakInside = "avoid";
            });
        });
    </script>
</body>
</html>';
}
?>