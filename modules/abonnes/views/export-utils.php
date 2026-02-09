<?php
/**
 * Utilitaires pour l'export PDF - Bibliothèque UN JOUR NOUVEAU
 */

class ExportUtils {
    
    /**
     * Génère un QR Code pour l'abonné (placeholder)
     */
    public static function generateQRCode($data) {
        // Pour une vraie implémentation, utiliser une bibliothèque comme phpqrcode
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg width="60" height="60" xmlns="http://www.w3.org/2000/svg">
                <rect width="60" height="60" fill="#e5e7eb"/>
                <text x="30" y="35" text-anchor="middle" font-size="8" fill="#6b7280">QR</text>
            </svg>
        ');
    }
    
    /**
     * Formatte une date française
     */
    public static function formatDateFr($date) {
        if (!$date) return '';
        
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
        ];
        
        $timestamp = strtotime($date);
        $day = date('d', $timestamp);
        $month = $months[(int)date('n', $timestamp)];
        $year = date('Y', $timestamp);
        
        return $day . ' ' . $month . ' ' . $year;
    }
    
    /**
     * Calcule l'âge depuis une date de naissance
     */
    public static function calculateAge($birthDate) {
        if (!$birthDate) return null;
        
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birth);
        
        return $age->y;
    }
    
    /**
     * Détermine le statut d'affichage avec vérification d'expiration
     */
    public static function getDisplayStatus($abonne) {
        $statut = $abonne['statut'];
        
        // Vérifier si l'abonnement est expiré
        if ($statut == 'actif' && $abonne['date_expiration'] < date('Y-m-d')) {
            return 'expire';
        }
        
        return $statut;
    }
    
    /**
     * Génère les couleurs pour un statut
     */
    public static function getStatusColors($status) {
        $colors = [
            'actif' => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => '✓'],
            'suspendu' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => '⚠'],
            'expire' => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => '⏰'],
            'archive' => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => '📁']
        ];
        
        return $colors[$status] ?? $colors['actif'];
    }
    
    /**
     * Formate un numéro de téléphone
     */
    public static function formatPhone($phone) {
        if (!$phone) return '';
        
        // Supprimer tout ce qui n'est pas un chiffre
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Formater selon la longueur
        if (strlen($phone) == 10) {
            return substr($phone, 0, 2) . ' ' . substr($phone, 2, 2) . ' ' . 
                   substr($phone, 4, 2) . ' ' . substr($phone, 6, 2) . ' ' . 
                   substr($phone, 8, 2);
        }
        
        return $phone;
    }
    
    /**
     * Génère les statistiques pour une liste d'abonnés
     */
    public static function generateStats($abonnes) {
        $stats = [
            'total' => count($abonnes),
            'actifs' => 0,
            'expires' => 0,
            'suspendus' => 0,
            'archives' => 0,
            'emprunts_total' => 0,
            'emprunts_retard' => 0
        ];
        
        foreach ($abonnes as $abonne) {
            $status = self::getDisplayStatus($abonne);
            
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
            
            $stats['emprunts_total'] += $abonne['emprunts_actifs'] ?? 0;
            $stats['emprunts_retard'] += $abonne['emprunts_retard'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * Génère le CSS pour l'impression
     */
    public static function getPrintCSS() {
        return file_get_contents(__DIR__ . '/../../../assets/css/print-styles.css');
    }
    
    /**
     * Nettoie le texte pour l'export
     */
    public static function cleanText($text) {
        if (!$text) return '';
        
        // Remplacer les retours à la ligne par des espaces
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        
        // Supprimer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Nettoyer les caractères spéciaux HTML
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        return trim($text);
    }
    
    /**
     * Génère un identifiant unique pour le fichier
     */
    public static function generateFileId($prefix = 'export') {
        return $prefix . '_' . date('Y-m-d_H-i-s') . '_' . uniqid();
    }
    
    /**
     * Valide les données d'abonné
     */
    public static function validateAbonneData($abonne) {
        $required = ['nom', 'prenom', 'numero_abonne', 'statut', 'date_inscription', 'date_expiration'];
        
        foreach ($required as $field) {
            if (empty($abonne[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Log des exports pour audit
     */
    public static function logExport($type, $user_id, $count = 1, $filters = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'user_id' => $user_id,
            'count' => $count,
            'filters' => json_encode($filters),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $log_file = __DIR__ . '/../../../../logs/exports.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}

/**
 * Classe pour générer des templates d'export personnalisés
 */
class ExportTemplates {
    
    /**
     * Template pour carte d'abonnement moderne
     */
    public static function modernSubscriptionCard($abonne) {
        $utils = new ExportUtils();
        $status = $utils->getDisplayStatus($abonne);
        $colors = $utils->getStatusColors($status);
        $age = $utils->calculateAge($abonne['date_naissance']);
        
        return '
        <div class="modern-card">
            <div class="card-header">
                <div class="school-logo">📚</div>
                <h1 class="school-name">BIBLIOTHÈQUE UN JOUR NOUVEAU</h1>
                <p class="card-subtitle">CARTE D\'ABONNEMENT SCOLAIRE</p>
            </div>
            
            <div class="student-section">
                <div class="student-avatar">
                    ' . strtoupper(substr($abonne['nom'], 0, 1) . substr($abonne['prenom'], 0, 1)) . '
                </div>
                <div>
                    <h2 class="student-name">' . htmlspecialchars($abonne['nom'] . ' ' . $abonne['prenom']) . '</h2>
                    <span class="student-number">N° ' . htmlspecialchars($abonne['numero_abonne']) . '</span>
                    <span class="status-badge status-' . $status . '">' . $colors['icon'] . ' ' . strtoupper($status) . '</span>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-section">
                    <h3 class="section-title">Informations Élève</h3>
                    <div class="info-row">
                        <span class="info-label">Sexe:</span>
                        <span class="info-value">' . ($abonne['sexe'] == 'M' ? 'Masculin' : 'Féminin') . '</span>
                    </div>
                    ' . ($age ? '<div class="info-row">
                        <span class="info-label">Âge:</span>
                        <span class="info-value">' . $age . ' ans</span>
                    </div>' : '') . '
                    <div class="info-row">
                        <span class="info-label">Niveau:</span>
                        <span class="info-value">' . ucfirst($abonne['niveau']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Classe:</span>
                        <span class="info-value">' . htmlspecialchars($abonne['classe']) . '</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3 class="section-title">Abonnement</h3>
                    <div class="info-row">
                        <span class="info-label">Inscription:</span>
                        <span class="info-value">' . date('d/m/Y', strtotime($abonne['date_inscription'])) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Expiration:</span>
                        <span class="info-value">' . date('d/m/Y', strtotime($abonne['date_expiration'])) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Limite emprunts:</span>
                        <span class="info-value">' . $abonne['nb_emprunts_max'] . ' livres</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Actuellement:</span>
                        <span class="info-value">' . ($abonne['emprunts_actifs'] ?? 0) . '/' . $abonne['nb_emprunts_max'] . '</span>
                    </div>
                </div>
                
                <div class="info-section full-width">
                    <h3 class="section-title">Contact Parent/Tuteur</h3>
                    <div class="info-row">
                        <span class="info-label">Nom:</span>
                        <span class="info-value">' . htmlspecialchars($abonne['nom_parent']) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Téléphone:</span>
                        <span class="info-value">' . $utils->formatPhone($abonne['telephone_parent']) . '</span>
                    </div>
                    ' . (!empty($abonne['email_parent']) ? '<div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">' . htmlspecialchars($abonne['email_parent']) . '</span>
                    </div>' : '') . '
                </div>
            </div>
            
            ' . (($abonne['emprunts_retard'] ?? 0) > 0 ? '
            <div class="warning-box">
                ⚠️ Attention: ' . $abonne['emprunts_retard'] . ' emprunt(s) en retard
            </div>' : '') . '
            
            ' . ($status === 'expire' ? '
            <div class="warning-box">
                ⏰ Abonnement expiré le ' . $utils->formatDateFr($abonne['date_expiration']) . '
            </div>' : '') . '
            
            ' . (!empty($abonne['notes']) ? '
            <div class="notes-section">
                <h3 class="section-title">Notes</h3>
                <p>' . htmlspecialchars($utils->cleanText($abonne['notes'])) . '</p>
            </div>' : '') . '
            
            <div class="card-footer">
                <p><strong>Cette carte est personnelle et non cessible</strong></p>
                <p>En cas de perte, veuillez contacter la bibliothèque</p>
                <p>Généré le ' . $utils->formatDateFr(date('Y-m-d')) . ' à ' . date('H:i') . '</p>
            </div>
        </div>';
    }
    
    /**
     * Template pour liste complète
     */
    public static function completeList($abonnes, $filters = []) {
        $utils = new ExportUtils();
        $stats = $utils->generateStats($abonnes);
        
        $html = '
        <div class="list-header">
            <h1 class="list-title">BIBLIOTHÈQUE UN JOUR NOUVEAU</h1>
            <h2 class="list-subtitle">Liste des Abonnés</h2>
            <p>Document généré le ' . $utils->formatDateFr(date('Y-m-d')) . ' à ' . date('H:i') . '</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['total']) . '</div>
                <div class="stat-label">Total Abonnés</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['actifs']) . '</div>
                <div class="stat-label">Actifs</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['expires']) . '</div>
                <div class="stat-label">Expirés</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['suspendus']) . '</div>
                <div class="stat-label">Suspendus</div>
            </div>
        </div>';
        
        // Afficher les filtres appliqués
        if (!empty($filters)) {
            $html .= '<div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h4 style="margin: 0 0 10px 0; color: #374151;">Filtres appliqués:</h4>';
            
            foreach ($filters as $key => $value) {
                if ($value) {
                    $html .= '<span style="background: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-right: 8px;">' . ucfirst($key) . ': ' . htmlspecialchars($value) . '</span>';
                }
            }
            
            $html .= '</div>';
        }
        
        $html .= '
        <table class="data-table">
            <thead>
                <tr>
                    <th>N° Abonné</th>
                    <th>Nom Complet</th>
                    <th>Classe</th>
                    <th>Statut</th>
                    <th>Emprunts</th>
                    <th>Parent/Tuteur</th>
                    <th>Téléphone</th>
                    <th>Expiration</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($abonnes as $abonne) {
            $status = $utils->getDisplayStatus($abonne);
            $colors = $utils->getStatusColors($status);
            
            $html .= '<tr class="avoid-break">
                <td><strong>' . htmlspecialchars($abonne['numero_abonne']) . '</strong></td>
                <td class="student-name">' . htmlspecialchars($abonne['nom'] . ' ' . $abonne['prenom']) . '</td>
                <td>' . htmlspecialchars($abonne['classe']) . '</td>
                <td>
                    <span style="background: ' . $colors['bg'] . '; color: ' . $colors['color'] . '; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">
                        ' . $colors['icon'] . ' ' . strtoupper($status) . '
                    </span>
                </td>
                <td>' . ($abonne['emprunts_actifs'] ?? 0) . '/' . $abonne['nb_emprunts_max'] . '</td>
                <td>' . htmlspecialchars($abonne['nom_parent']) . '</td>
                <td>' . $utils->formatPhone($abonne['telephone_parent']) . '</td>
                <td>' . date('d/m/Y', strtotime($abonne['date_expiration'])) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }
}

?>