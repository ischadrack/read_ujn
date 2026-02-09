<?php
require_once '../../../config/config.php';
require_once '../controller.php';
require_once '../../../includes/auth_middleware.php';

requireLogin();

$controller = new AbonneController();

// Get filters from session or URL
$filters = [];
if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
if (isset($_GET['statut'])) $filters['statut'] = $_GET['statut'];
if (isset($_GET['niveau'])) $filters['niveau'] = $_GET['niveau'];
if (isset($_GET['classe'])) $filters['classe'] = $_GET['classe'];

$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$export_type = $_GET['export'] ?? 'excel';

// For individual subscription card
if ($export_type === 'fiche' && isset($_GET['id'])) {
    $abonne = $controller->find($_GET['id']);
    if (!$abonne) {
        die('Abonné non trouvé');
    }
    generateSinglePageCard($abonne);
    exit;
}

// For bulk subscription cards export
if ($export_type === 'bulk_cards') {
    $result = $controller->index($filters, 1, 10000, $sort, $order);
    $abonnes = $result['data'];
    generateBulkSinglePageCards($abonnes);
    exit;
}

// Get all subscribers (without pagination for export)
$result = $controller->index($filters, 1, 10000, $sort, $order);
$abonnes = $result['data'];

if ($export_type === 'pdf') {
    generatePDFList($abonnes, $filters);
} else {
    generateExcelExport($abonnes);
}

function generateExcelExport($abonnes) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="abonnes_bibliotheque_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF"; // BOM for UTF-8

    $output = fopen('php://output', 'w');

    // Headers
    $headers = [
        'Numéro Abonné', 'Nom', 'Prénom', 'Sexe', 'Date de Naissance',
        'Niveau', 'Classe', 'Statut', 'Date Inscription', 'Date Expiration',
        'Emprunts Maximum', 'Emprunts Actuels', 'Emprunts en Retard',
        'Nom Parent/Tuteur', 'Téléphone Parent', 'Email Parent', 'Adresse', 'Notes'
    ];

    fputcsv($output, $headers, ';');

    // Data
    foreach ($abonnes as $abonne) {
        $statut_display = $abonne['statut'];
        if ($abonne['statut'] == 'actif' && $abonne['date_expiration'] < date('Y-m-d')) {
            $statut_display = 'expiré';
        }

        $row = [
            $abonne['numero_abonne'],
            $abonne['nom'],
            $abonne['prenom'],
            $abonne['sexe'] == 'M' ? 'Masculin' : 'Féminin',
            $abonne['date_naissance'] ? date('d/m/Y', strtotime($abonne['date_naissance'])) : '',
            ucfirst($abonne['niveau']),
            $abonne['classe'],
            ucfirst($statut_display),
            date('d/m/Y', strtotime($abonne['date_inscription'])),
            date('d/m/Y', strtotime($abonne['date_expiration'])),
            $abonne['nb_emprunts_max'],
            $abonne['emprunts_actifs'] ?? 0,
            $abonne['emprunts_retard'] ?? 0,
            $abonne['nom_parent'],
            $abonne['telephone_parent'],
            $abonne['email_parent'],
            str_replace(["\r\n", "\r", "\n"], ' ', $abonne['adresse']),
            str_replace(["\r\n", "\r", "\n"], ' ', $abonne['notes'] ?? '')
        ];

        fputcsv($output, $row, ';');
    }

    fclose($output);
}

function getEmpruntsActifs($abonne_id) {
    global $db;
    $sql = "SELECT e.*, l.titre, l.code_livre, l.auteur
            FROM emprunts e
            LEFT JOIN livres l ON e.livre_id = l.id
            WHERE e.abonne_id = ? AND e.statut IN ('en_cours', 'en_retard')
            ORDER BY e.date_emprunt DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$abonne_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateSinglePageCard($abonne) {
    $emprunts = getEmpruntsActifs($abonne['id']);
    header('Content-Type: text/html; charset=utf-8');
    echo getMinimalHTMLTemplate('Fiche Abonnement - ' . $abonne['nom'] . ' ' . $abonne['prenom']);
    echo generateMinimalCardContent($abonne, $emprunts);
    echo getHTMLFooter();
}

function generateBulkSinglePageCards($abonnes) {
    header('Content-Type: text/html; charset=utf-8');
    echo getMinimalHTMLTemplate('Fiches Abonnement - ' . count($abonnes) . ' abonnés');

    foreach ($abonnes as $index => $abonne) {
        $emprunts = getEmpruntsActifs($abonne['id']);
        echo generateMinimalCardContent($abonne, $emprunts);
        if ($index < count($abonnes) - 1) {
            echo '<div class="page-break"></div>';
        }
    }

    echo getHTMLFooter();
}

function generatePDFList($abonnes, $filters = []) {
    $stats = calculateStats($abonnes);

    header('Content-Type: text/html; charset=utf-8');
    echo getMinimalHTMLTemplate('Liste des Abonnés');
    echo generateListContent($abonnes, $stats, $filters);
    echo getHTMLFooter();
}

function calculateStats($abonnes) {
    $stats = [
        'total' => count($abonnes),
        'actifs' => 0,
        'expires' => 0,
        'suspendus' => 0,
        'archives' => 0
    ];

    foreach ($abonnes as $abonne) {
        $status = getDisplayStatus($abonne);

        switch ($status) {
            case 'actif':
                $stats['actifs']++;
                break;
            case 'expire':
                $stats['expires']++;
                break;
            case 'suspendu':
                $stats['suspendus']++;
                break;
            case 'archive':
                $stats['archives']++;
                break;
        }
    }

    return $stats;
}

function getDisplayStatus($abonne) {
    $statut = $abonne['statut'];

    // Vérifier si l'abonnement est expiré
    if ($statut == 'actif' && $abonne['date_expiration'] < date('Y-m-d')) {
        return 'expire';
    }

    return $statut;
}

function formatPhone($phone) {
    if (!$phone) return '';

    // Supprimer tout ce qui n'est pas un chiffre
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Formater selon la longueur
    if (strlen($phone) == 10) {
        return substr($phone, 0, 2) . '.' . substr($phone, 2, 2) . '.' .
               substr($phone, 4, 2) . '.' . substr($phone, 6, 2) . '.' .
               substr($phone, 8, 2);
    }

    return $phone;
}

function calculateAge($birthDate) {
    if (!$birthDate) return null;

    $birth = new DateTime($birthDate);
    $today = new DateTime();
    $age = $today->diff($birth);

    return $age->y;
}

function getMinimalHTMLTemplate($title = 'Fiche Abonnement') {
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
            line-height: 1.2;
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

            .receipt-card {
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
                background: #000;
                color: #fff;
                border: none;
                padding: 10px 20px;
                cursor: pointer;
                font-family: "Ubuntu", sans-serif;
                font-size: 12px;
                font-weight: 500;
                border-radius: 4px;
                transition: background 0.2s;
            }

            .print-btn:hover {
                background: #333;
            }
        }

        .receipt-card {
            width: 180mm;
            max-width: 180mm;
            margin: 0 auto 20px;
            background: #fff;
            padding: 8mm;
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6mm;
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
            font-size: 14px;
            font-weight: 400;
            text-transform: uppercase;
            margin-bottom: 2mm;
            color: #000;
        }

        .school-address {
            font-family: "Ubuntu", sans-serif;
            font-size: 9px;
            font-weight: 400;
            margin-bottom: 1mm;
            line-height: 1.3;
        }

        .receipt-title {
            font-family: "Fredoka One", cursive;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 3mm 0;
            text-align: center;
        }

        .logo {
            width: 70px;
            height: 70px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3mm;
            font-family: "Fredoka One", cursive;
            font-size: 8px;
            color: #666;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        .receipt-number {
            font-family: "Ubuntu", sans-serif;
            font-size: 9px;
            font-weight: 700;
        }

        .separator-line {
            text-align: center;
            margin: 3mm 0;
            font-size: 10px;
            font-family: "Ubuntu", monospace;
            letter-spacing: 0.5px;
        }

        .student-name {
            text-align: center;
            font-family: "Fredoka One", cursive;
            font-size: 14px;
            font-weight: 400;
            text-transform: uppercase;
            margin: 3mm 0;
            letter-spacing: 1px;
        }

        .student-number {
            text-align: center;
            font-family: "Ubuntu", sans-serif;
            font-size: 12px;
            font-weight: 700;
            margin: 2mm 0;
        }

        .status-line {
            text-align: center;
            margin: 3mm 0;
            font-family: "Ubuntu", sans-serif;
            font-size: 11px;
            font-weight: 700;
            padding: 2mm;
            border-radius: 4px;
        }

        .status-actif {
            background: #e8f5e8;
            color: #2d5a2d;
            text-transform: uppercase;
        }

        .status-expire {
            background: #ffe8e8;
            color: #8b0000;
            text-transform: uppercase;
        }

        .status-suspendu {
            background: #fff3cd;
            color: #856404;
            text-transform: uppercase;
        }

        .two-column {
            display: flex;
            gap: 8mm;
            margin: 3mm 0;
        }

        .column {
            flex: 1;
        }

        .info-section {
            margin-bottom: 4mm;
        }

        .section-title {
            font-family: "Ubuntu", sans-serif;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2mm;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 1mm;
        }

        .info-line {
            justify-content: space-between; /* texte gauche / droite */
            align-items: center;            /* centré verticalement */
            flex-direction: column;      /* empilement vertical */
            margin-bottom: 4px;
            font-size: 13px;
                }

        .info-label {
             font-weight: bold;
            color: #333;
                }

        .info-value {
            width: 55%;
            font-family: "Ubuntu", sans-serif;
            font-weight: 700;
            color: #000;
            word-wrap: break-word;
        }

        .alert-box {
            text-align: center;
            margin: 3mm 0;
            padding: 2mm;
            font-family: "Ubuntu", sans-serif;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            border-radius: 4px;
        }

        .alert-urgent {
            background: #ffebee;
            border: 2px solid #f44336;
            color: #c62828;
        }

        .alert-warning {
            background: #fff3e0;
            border: 2px solid #ff9800;
            color: #e65100;
        }

        .notes-section {
            margin-top: 3mm;
            font-size: 9px;
            background: #f9f9f9;
            padding: 2mm;
            border-radius: 4px;
        }

        .notes-title {
            font-family: "Ubuntu", sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1mm;
            color: #333;
        }

        .notes-text {
            font-family: "Ubuntu", sans-serif;
            font-size: 8px;
            line-height: 1.4;
            color: #555;
        }

        .footer-info {
            text-align: center;
            margin-top: 4mm;
            font-family: "Ubuntu", sans-serif;
            font-size: 8px;
            line-height: 1.4;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 2mm;
        }

        .dotted-line {
            text-align: center;
            margin: 1mm 0;
            font-family: "Ubuntu", monospace;
            font-size: 8px;
            color: #999;
        }

        .livre-emprunt {              
                border: 1px solid #ccc;
                text-align: left;
                padding: 6px 10px;
                margin-bottom: 8px;
                border-radius: 6px;
                background: #fdfdfd;
            }
        .infos {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                margin-top: 4px;
            }

        .livre-retard {
            background: #ffebee;
            border-left: 3px solid #f44336;
        }

        .text-retard {
            color: red;
            font-weight: bold;
        }

    </style>
</head>
<body>';
}

function generateMinimalCardContent($abonne, $emprunts = []) {
    $status = getDisplayStatus($abonne);
    $age = calculateAge($abonne['date_naissance']);

    $statusLabels = [
        'actif' => 'ACTIF',
        'suspendu' => 'SUSPENDU',
        'expire' => 'EXPIRÉ'
    ];

    $statusClass = 'status-' . $status;
    $statusLabel = $statusLabels[$status] ?? 'INCONNU';

    $html = '<div class="receipt-card">
        <div class="receipt-header">
            <!-- Partie gauche -->
            <div class="header-left">
                <div class="school-name">UN JOUR NOUVEAU</div>
                <div class="school-address">N° 140, Av. La Frontière, Q. Katindo</div>
                <div class="school-address">GOMA-NIF: A1207713Y.CA 90740</div>
                <div class="school-address">+243 822 01 74 80</div>
                <div class="school-address" style="color:green">ujn@gunjournouveau.org</div>
            </div>
            <!-- Partie droite -->
            <div class="header-right">
                <div class="logo"><img src="../../../assets/logo/ecole.png" alt="Logo"></div>
                <div class="student-number">Fiche N° ' . htmlspecialchars($abonne['numero_abonne']) . '</div>
            </div>
        </div>

        <div class="receipt-title">FICHE D\'ABONNEMENT</div>

        <div class="separator-line">=========================================================================================</div>

        <div class="status-line ' . $statusClass . '">
            <div class="student-name">' . htmlspecialchars(strtoupper($abonne['nom'] . ' ' . $abonne['prenom'])) . '</div>
        </div>

        <div class="separator-line">-------------------------------</div>

        <div class="two-column">
            <div class="column">
                <div class="info-section">
                    <div class="section-title">ÉLÈVE</div>
                    <div class="info-line">
                        <span class="info-label">SEXE:</span>
                        <span class="info-value">' . ($abonne['sexe'] == 'M' ? 'MASCULIN' : 'FÉMININ') . '</span>
                    </div>';

    if ($age) {
        $html .= '<div class="info-line">
            <span class="info-label">ÂGE:</span>
            <span class="info-value">' . $age . ' ANS</span>
        </div>';
    }

    $html .= '<div class="info-line">
                        <span class="info-label">NIVEAU:</span>
                        <span class="info-value">' . strtoupper($abonne['niveau']) . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">CLASSE:</span>
                        <span class="info-value">' . htmlspecialchars(strtoupper($abonne['classe'])) . '</span>
                    </div>
                </div>

                <div class="info-section">
                    <div class="section-title">ABONNEMENT</div>
                    <div class="info-line">
                        <span class="info-label">INSCRIPTION:</span>
                        <span class="info-value">' . date('d/m/Y', strtotime($abonne['date_inscription'])) . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">EXPIRATION:</span>
                        <span class="info-value">' . date('d/m/Y', strtotime($abonne['date_expiration'])) . '</span>
                    </div>
                </div>
            </div>

            <div class="column">
                <div class="info-section">
                    <div class="section-title">PARENT/TUTEUR</div>
                    <div class="info-line">
                        <span class="info-label">NOM:</span>
                        <span class="info-value">' . htmlspecialchars(strtoupper($abonne['nom_parent'])) . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">TÉL:</span>
                        <span class="info-value">' . formatPhone($abonne['telephone_parent']) . '</span>
                    </div>';

    if (!empty($abonne['email_parent'])) {
        $html .= '<div class="info-line">
            <span class="info-label">EMAIL:</span>
            <span class="info-value" style="font-size: 8px;">' . htmlspecialchars($abonne['email_parent']) . '</span>
        </div>';
    }

    $html .= '</div>

                <div class="info-section">
                    <div class="section-title">EMPRUNTS</div>
                    <div class="info-line">
                        <span class="info-label">LIMITE:</span>
                        <span class="info-value">' . $abonne['nb_emprunts_max'] . '</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">ACTUELS:</span>
                        <span class="info-value">' . count($emprunts) . '</span>
                    </div>
                </div>
            </div>
        </div>';

    // Section des livres empruntés
    if (count($emprunts) > 0) {
        $html .= '<div class="separator-line">-------------------------------</div>
                        <div class="info-section">
                            <div class="section-title">📚 LIVRES EMPRUNTÉS (' . count($emprunts) . ')</div>
                            <div class="emprunts-container">';

                        foreach ($emprunts as $index => $emprunt) {
                            $date_retour_prevue = date('d/m/Y', strtotime($emprunt['date_retour_prevue']));
                            $date_emprunt = date('d/m/Y', strtotime($emprunt['date_emprunt']));
                            $is_retard = strtotime($emprunt['date_retour_prevue']) < time();

                            $html .= '<div class="livre-emprunt' . ($is_retard ? ' livre-retard' : '') . '">
                                <div class="info-line">
                                    <span class="info-label">Titre:</span>
                                    <span class="info-value">' . htmlspecialchars(strtoupper($emprunt['titre'])) . '</span>
                                </div>';
                            $html .= '
                                <div class="infos">
                                    <div class="info-line">
                                        <span class="info-label">Date emprunt:</span>
                                        <span class="info-value">' . $date_emprunt . '</span>
                                    </div>
                                    <div class="info-line">
                                        <span class="info-label">Retour prévu:</span>
                                        <span class="info-value' . ($is_retard ? ' text-retard' : '') . '">' . $date_retour_prevue . ($is_retard ? ' ⚠
                                            EN RETARD' : '') . '</span>
                                    </div>
                                </div>
                        </div>';

            if ($index < count($emprunts) - 1) {
                $html .= '<div class="dotted-line">...................................</div>';
            }
        }

        $html .= '</div></div>';
    }

    // Alertes importantes
    if (($abonne['emprunts_retard'] ?? 0) > 0) {
        $html .= '<div class="separator-line">-------------------------------</div>
        <div class="alert-box alert-urgent">
            ⚠ URGENT - ' . $abonne['emprunts_retard'] . ' EMPRUNT(S) EN RETARD
        </div>';
    }

    if ($status === 'expire') {
        $html .= '<div class="separator-line">-------------------------------</div>
        <div class="alert-box alert-warning">
            ⚠ ABONNEMENT EXPIRÉ LE ' . strtoupper(date('d/m/Y', strtotime($abonne['date_expiration']))) . '
        </div>';
    }

    // Notes compactes
    if (!empty($abonne['notes'])) {
        $notes = strlen($abonne['notes']) > 150 ? substr($abonne['notes'], 0, 147) . '...' : $abonne['notes'];
        $html .= '<div class="separator-line">-------------------------------</div>
        <div class="notes-section">
            <div class="notes-title">📝 NOTES:</div>
            <div class="notes-text">' . nl2br(htmlspecialchars($notes)) . '</div>
        </div>';
    }

    $html .= '<div class="separator-line">===============================</div>

        <div class="footer-info">
            <div>CARTE PERSONNELLE ET NON CESSIBLE</div>
            <div class="dotted-line">...................................</div>
            <div>PERTE/VOL: CONTACTER LA BIBLIOTHÈQUE</div>
            <div>GÉNÉRÉ LE ' . strtoupper(date('d/m/Y')) . ' À ' . date('H:i') . '</div>
        </div>
    </div>';

    return $html;
}

function generateListContent($abonnes, $stats, $filters) {
    $html = '<div class="receipt-card">
        <div class="receipt-header">
            <div class="header-left">
                <div class="school-name">UN JOUR NOUVEAU</div>
                <div class="school-address">LYCÉE BILINGUE PRIVÉ</div>
                <div class="school-address">TEL: +243 822 01 74 80</div>
                <div class="receipt-title">LISTE DES ABONNÉS</div>
            </div>
        </div>

        <div class="separator-line">===============================</div>

        <div class="info-section">
            <div class="section-title">STATISTIQUES</div>
            <div class="info-line">
                <span class="info-label">TOTAL:</span>
                <span class="info-value">' . $stats['total'] . '</span>
            </div>
            <div class="info-line">
                <span class="info-label">ACTIFS:</span>
                <span class="info-value">' . $stats['actifs'] . '</span>
            </div>
            <div class="info-line">
                <span class="info-label">EXPIRÉS:</span>
                <span class="info-value">' . $stats['expires'] . '</span>
            </div>
            <div class="info-line">
                <span class="info-label">SUSPENDUS:</span>
                <span class="info-value">' . $stats['suspendus'] . '</span>
            </div>
        </div>

        <div class="separator-line">-------------------------------</div>

        <div class="info-section">
            <div class="section-title">LISTE DÉTAILLÉE</div>';

    foreach ($abonnes as $index => $abonne) {
        $status = getDisplayStatus($abonne);
        $statusLabel = strtoupper($status);

        if ($index > 0) {
            $html .= '<div class="dotted-line">...................................</div>';
        }

        $html .= '<div class="info-line">
            <span class="info-label">' . htmlspecialchars($abonne['numero_abonne']) . '</span>
            <span class="info-value">' . htmlspecialchars(strtoupper($abonne['nom'] . ' ' . $abonne['prenom'])) . '</span>
        </div>
        <div class="info-line">
            <span class="info-label">CLASSE:</span>
            <span class="info-value">' . htmlspecialchars(strtoupper($abonne['niveau'] . ' - ' . $abonne['classe'])) . '</span>
        </div>
        <div class="info-line">
            <span class="info-label">STATUT:</span>
            <span class="info-value">' . $statusLabel . '</span>
        </div>';
    }

    $html .= '</div>

        <div class="separator-line">===============================</div>

        <div class="footer-info">
            <div>DOCUMENT GÉNÉRÉ LE ' . strtoupper(date('d/m/Y')) . ' À ' . date('H:i') . '</div>
        </div>
    </div>';

    return $html;
}

function getHTMLFooter() {
    return '
    <div class="print-controls no-print">
        <button onclick="window.print()" class="print-btn">
            🖨️ IMPRIMER
        </button>
        <a href="index">
            <button class="print-btn">
                ✖️ FERMER
            </button>
        </a>
    </div>

    <script>
        document.addEventListener("keydown", function(e) {
            if (e.ctrlKey && e.key === "p") {
                e.preventDefault();
                window.print();
            }
            if (e.key === "Escape") {
                window.location.href = "index";
            }
        });

        window.addEventListener("beforeprint", function() {
            document.querySelectorAll(".receipt-card").forEach(card => {
                card.style.pageBreakAfter = "always";
                card.style.pageBreakInside = "avoid";
            });
        });
    </script>
</body>
</html>';
}
?>
<style>

</style>