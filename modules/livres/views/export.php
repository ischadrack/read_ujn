<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

requireLogin();

$controller = new LivreController();

// Récupération des filtres
$filters = [];
if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
if (isset($_GET['categorie_id'])) $filters['categorie_id'] = $_GET['categorie_id'];
if (isset($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (isset($_GET['niveau_classe'])) $filters['niveau_classe'] = $_GET['niveau_classe'];
if (isset($_GET['langue'])) $filters['langue'] = $_GET['langue'];
if (isset($_GET['disponible'])) $filters['disponible'] = $_GET['disponible'];

$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$export_type = $_GET['export'] ?? 'excel';

// Récupération de tous les livres (sans pagination pour l'export)
$result = $controller->index($filters, 1, 10000, $sort, $order);
$livres = $result['data'];

if ($export_type === 'pdf') {
    generatePDFList($livres, $filters);
} else {
    generateExcelExport($livres);
}

function generateExcelExport($livres) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="livres_bibliotheque_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // BOM for UTF-8
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    $headers = [
        'Code Livre', 'Titre', 'Auteur', 'Éditeur', 'ISBN', 'Catégorie',
        'Niveau Classe', 'Langue', 'Année Publication', 'Nombre Pages',
        'Quantité Stock', 'Quantité Disponible', 'Prix Unitaire', 'État',
        'Statut', 'Date Création', 'Créé par'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Données
    foreach ($livres as $livre) {
        $row = [
            $livre['code_livre'],
            $livre['titre'],
            $livre['auteur'] ?? '',
            $livre['editeur'] ?? '',
            $livre['isbn'] ?? '',
            $livre['categorie_nom'] ?? '',
            $livre['niveau_classe'] ?? '',
            $livre['langue'] ?? 'Français',
            $livre['annee_publication'] ?? '',
            $livre['nombre_pages'] ?? 0,
            $livre['quantite_stock'] ?? 0,
            $livre['quantite_disponible'] ?? 0,
            $livre['prix_unitaire'] ?? 0,
            ucfirst($livre['etat'] ?? 'bon'),
            ucfirst($livre['statut'] ?? 'actif'),
            date('d/m/Y H:i', strtotime($livre['created_at'])),
            $livre['created_by_name'] ?? ''
        ];
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
}

function generatePDFList($livres, $filters = []) {
    $stats = calculateStats($livres);
    
    header('Content-Type: text/html; charset=utf-8');
    echo getModernHTMLTemplate('Liste des Livres');
    echo generateListContent($livres, $stats, $filters);
    echo getHTMLFooter();
}

function calculateStats($livres) {
    $stats = [
        'total' => count($livres),
        'disponibles' => 0,
        'empruntes' => 0,
        'reserves' => 0,
        'inactifs' => 0
    ];
    
    foreach ($livres as $livre) {
        if ($livre['statut'] === 'inactif') {
            $stats['inactifs']++;
        } elseif ($livre['quantite_disponible'] > 0) {
            $stats['disponibles']++;
        } else {
            $stats['empruntes']++;
        }
        
        if (($livre['reservations_actives'] ?? 0) > 0) {
            $stats['reserves']++;
        }
    }
    
    return $stats;
}

function getModernHTMLTemplate($title = 'Liste des Livres') {
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
                background: #2563eb;
                color: #fff;
                border: none;
                padding: 12px 24px;
                cursor: pointer;
                font-family: "Ubuntu", sans-serif;
                font-size: 12px;
                font-weight: 500;
                border-radius: 6px;
                transition: all 0.2s;
                box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
            }
            
            .print-btn:hover {
                background: #1d4ed8;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
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
            border-bottom: 2px solid #2563eb;
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
            color: #2563eb;
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
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3mm;
            font-family: "Fredoka One", cursive;
            font-size: 10px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            padding: 3mm;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-number {
            font-family: "Fredoka One", cursive;
            font-size: 18px;
            font-weight: 400;
            color: #2563eb;
            margin-bottom: 1mm;
        }

        .stat-label {
            font-family: "Ubuntu", sans-serif;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }

        .info-section {
            margin-bottom: 5mm;
            background: #fafafa;
            padding: 4mm;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
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

        .book-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2mm 0;
            border-bottom: 1px dotted #d1d5db;
            margin-bottom: 2mm;
        }

        .book-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .book-info {
            flex: 1;
        }

        .book-title {
            font-family: "Ubuntu", sans-serif;
            font-size: 11px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1mm;
        }

        .book-details {
            font-family: "Ubuntu", sans-serif;
            font-size: 9px;
            color: #6b7280;
            line-height: 1.3;
        }

        .book-status {
            text-align: right;
            min-width: 25mm;
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

        .status-disponible {
            background: #dcfce7;
            color: #166534;
        }

        .status-emprunte {
            background: #fef3c7;
            color: #92400e;
        }

        .status-reserve {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-inactif {
            background: #fee2e2;
            color: #991b1b;
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
            color: #2563eb;
            font-weight: 500;
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
            background: linear-gradient(135deg, #2563eb, #3b82f6);
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

    </style>
</head>
<body>';
}

function generateListContent($livres, $stats, $filters) {
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
                    <div>BIBLIO<br>THÈQUE</div>
                </div>
                <div class="document-number">
                    DOC N° LIV-' . date('Ymd-His') . '
                </div>
            </div>
        </div>
        
        <div class="document-title">INVENTAIRE DES LIVRES</div>
        
        <div class="separator-line">═══════════════════════════════════════════════════════════════════════════════════════</div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">' . $stats['total'] . '</div>
                <div class="stat-label">Total Livres</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $stats['disponibles'] . '</div>
                <div class="stat-label">Disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $stats['empruntes'] . '</div>
                <div class="stat-label">Empruntés</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $stats['reserves'] . '</div>
                <div class="stat-label">Réservés</div>
            </div>
        </div>
        
        <div class="separator-line">───────────────────────────────────────────────────────────────────────────────────────</div>';
        
    if (!empty($filters)) {
        $html .= '<div class="info-section">
            <div class="section-title">Filtres Appliqués</div>';
        
        if (!empty($filters['search'])) {
            $html .= '<div class="book-details">Recherche: "' . htmlspecialchars($filters['search']) . '"</div>';
        }
        if (!empty($filters['categorie_id'])) {
            $html .= '<div class="book-details">Catégorie: ID ' . htmlspecialchars($filters['categorie_id']) . '</div>';
        }
        if (!empty($filters['statut'])) {
            $html .= '<div class="book-details">Statut: ' . ucfirst($filters['statut']) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '<div class="info-section">
            <div class="section-title">Catalogue Détaillé</div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Titre & Auteur</th>
                            <th>Catégorie</th>
                            <th>Stock</th>
                            <th>Disponible</th>
                            <th>État</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
    foreach ($livres as $livre) {
        $status_class = 'status-disponible';
        $status_text = 'Disponible';
        
        if ($livre['statut'] === 'inactif') {
            $status_class = 'status-inactif';
            $status_text = 'Inactif';
        } elseif ($livre['quantite_disponible'] == 0) {
            $status_class = 'status-emprunte';
            $status_text = 'Emprunté';
        } elseif (($livre['reservations_actives'] ?? 0) > 0) {
            $status_class = 'status-reserve';
            $status_text = 'Réservé';
        }
        
        $html .= '<tr>
            <td><strong>' . htmlspecialchars($livre['code_livre']) . '</strong></td>
            <td>
                <div class="book-title">' . htmlspecialchars($livre['titre']) . '</div>
                <div class="book-details">
                    ' . htmlspecialchars($livre['auteur'] ?? 'Auteur non spécifié') . '
                    ' . (!empty($livre['editeur']) ? ' - ' . htmlspecialchars($livre['editeur']) : '') . '
                    ' . (!empty($livre['annee_publication']) ? ' (' . $livre['annee_publication'] . ')' : '') . '
                </div>
            </td>
            <td>' . htmlspecialchars($livre['categorie_nom'] ?? 'Non classé') . '</td>
            <td><strong>' . ($livre['quantite_stock'] ?? 0) . '</strong></td>
            <td><strong>' . ($livre['quantite_disponible'] ?? 0) . '</strong></td>
            <td>' . ucfirst($livre['etat'] ?? 'bon') . '</td>
            <td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>
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
        <a href="index">
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