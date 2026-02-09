<?php
/**
 * Utilitaires pour l'export moderne - Bibliothèque UN JOUR NOUVEAU
 * Version sans dépendances externes
 */

class ModernExportUtils {
    
    /**
     * Génère un QR Code SVG simple
     */
    public static function generateSimpleQRCode($data, $size = 60) {
        // QR Code simplifié en SVG pour les fiches
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
                <rect width="' . $size . '" height="' . $size . '" fill="#f3f4f6" rx="8"/>
                <rect x="8" y="8" width="8" height="8" fill="#1f2937"/>
                <rect x="20" y="8" width="8" height="8" fill="#1f2937"/>
                <rect x="32" y="8" width="8" height="8" fill="#1f2937"/>
                <rect x="44" y="8" width="8" height="8" fill="#1f2937"/>
                <rect x="8" y="20" width="8" height="8" fill="#1f2937"/>
                <rect x="20" y="20" width="8" height="8" fill="#f3f4f6"/>
                <rect x="32" y="20" width="8" height="8" fill="#1f2937"/>
                <rect x="44" y="20" width="8" height="8" fill="#f3f4f6"/>
                <rect x="8" y="32" width="8" height="8" fill="#f3f4f6"/>
                <rect x="20" y="32" width="8" height="8" fill="#1f2937"/>
                <rect x="32" y="32" width="8" height="8" fill="#f3f4f6"/>
                <rect x="44" y="32" width="8" height="8" fill="#1f2937"/>
                <rect x="8" y="44" width="8" height="8" fill="#1f2937"/>
                <rect x="20" y="44" width="8" height="8" fill="#f3f4f6"/>
                <rect x="32" y="44" width="8" height="8" fill="#1f2937"/>
                <rect x="44" y="44" width="8" height="8" fill="#f3f4f6"/>
                <text x="30" y="38" text-anchor="middle" font-size="6" fill="#6b7280">QR</text>
            </svg>
        ');
    }
    
    /**
     * Formatte une date en français avec style
     */
    public static function formatDateFrench($date, $withTime = false) {
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
        
        $formatted = $day . ' ' . $month . ' ' . $year;
        
        if ($withTime) {
            $formatted .= ' à ' . date('H:i', $timestamp);
        }
        
        return $formatted;
    }
    
    /**
     * Calcule l'âge avec précision
     */
    public static function calculatePreciseAge($birthDate) {
        if (!$birthDate) return null;
        
        try {
            $birth = new DateTime($birthDate);
            $today = new DateTime();
            $age = $today->diff($birth);
            
            return [
                'years' => $age->y,
                'months' => $age->m,
                'days' => $age->d,
                'formatted' => $age->y . ' ans' . ($age->m > 0 ? ' et ' . $age->m . ' mois' : '')
            ];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Détermine le statut avec couleurs et icônes
     */
    public static function getStatusInfo($abonne) {
        $statut = $abonne['statut'];
        
        // Vérifier si l'abonnement est expiré
        if ($statut == 'actif' && $abonne['date_expiration'] < date('Y-m-d')) {
            $statut = 'expire';
        }
        
        $statusConfig = [
            'actif' => [
                'label' => 'ACTIF',
                'icon' => '✅',
                'emoji' => '🟢',
                'color' => '#065f46',
                'bg' => '#d1fae5',
                'border' => '#10b981',
                'priority' => 1
            ],
            'expire' => [
                'label' => 'EXPIRÉ',
                'icon' => '⏰',
                'emoji' => '🟡',
                'color' => '#92400e',
                'bg' => '#fef3c7',
                'border' => '#f59e0b',
                'priority' => 3
            ],
            'suspendu' => [
                'label' => 'SUSPENDU',
                'icon' => '⚠️',
                'emoji' => '🔴',
                'color' => '#991b1b',
                'bg' => '#fee2e2',
                'border' => '#ef4444',
                'priority' => 4
            ],
            'archive' => [
                'label' => 'ARCHIVÉ',
                'icon' => '📁',
                'emoji' => '⚫',
                'color' => '#374151',
                'bg' => '#f3f4f6',
                'border' => '#6b7280',
                'priority' => 2
            ]
        ];
        
        return $statusConfig[$statut] ?? $statusConfig['actif'];
    }
    
    /**
     * Formatte un numéro de téléphone avec style international
     */
    public static function formatPhoneNumber($phone) {
        if (!$phone) return '';
        
        // Nettoyer le numéro
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        
        // Formats congolais
        if (preg_match('/^(\+?243)?([0-9]{9})$/', $clean, $matches)) {
            $number = $matches[2];
            return '+243 ' . substr($number, 0, 3) . ' ' . substr($number, 3, 3) . ' ' . substr($number, 6, 3);
        }
        
        // Format standard pour 10 chiffres
        if (strlen($clean) == 10) {
            return substr($clean, 0, 2) . ' ' . substr($clean, 2, 2) . ' ' . 
                   substr($clean, 4, 2) . ' ' . substr($clean, 6, 2) . ' ' . 
                   substr($clean, 8, 2);
        }
        
        return $phone; // Retourner tel quel si format non reconnu
    }
    
    /**
     * Génère des statistiques avancées
     */
    public static function generateAdvancedStats($abonnes) {
        $stats = [
            'total' => count($abonnes),
            'actifs' => 0,
            'expires' => 0,
            'suspendus' => 0,
            'archives' => 0,
            'emprunts_total' => 0,
            'emprunts_retard' => 0,
            'par_niveau' => [],
            'par_classe' => [],
            'age_moyen' => 0,
            'nouveaux_ce_mois' => 0
        ];
        
        $ages = [];
        $ce_mois = date('Y-m');
        
        foreach ($abonnes as $abonne) {
            $status_info = self::getStatusInfo($abonne);
            
            // Compter par statut
            switch ($status_info['priority']) {
                case 1: $stats['actifs']++; break;
                case 2: $stats['archives']++; break;
                case 3: $stats['expires']++; break;
                case 4: $stats['suspendus']++; break;
            }
            
            // Statistiques d'emprunts
            $stats['emprunts_total'] += $abonne['emprunts_actifs'] ?? 0;
            $stats['emprunts_retard'] += $abonne['emprunts_retard'] ?? 0;
            
            // Par niveau et classe
            $niveau = $abonne['niveau'] ?? 'non_specifie';
            $classe = $abonne['classe'] ?? 'non_specifie';
            
            $stats['par_niveau'][$niveau] = ($stats['par_niveau'][$niveau] ?? 0) + 1;
            $stats['par_classe'][$classe] = ($stats['par_classe'][$classe] ?? 0) + 1;
            
            // Calcul âge
            if ($abonne['date_naissance']) {
                $age_info = self::calculatePreciseAge($abonne['date_naissance']);
                if ($age_info) {
                    $ages[] = $age_info['years'];
                }
            }
            
            // Nouveaux ce mois
            if (substr($abonne['date_inscription'], 0, 7) === $ce_mois) {
                $stats['nouveaux_ce_mois']++;
            }
        }
        
        // Age moyen
        if (!empty($ages)) {
            $stats['age_moyen'] = round(array_sum($ages) / count($ages), 1);
        }
        
        // Trier les classements
        arsort($stats['par_niveau']);
        arsort($stats['par_classe']);
        
        return $stats;
    }
    
    /**
     * Nettoie et formate le texte pour l'export
     */
    public static function cleanTextForExport($text) {
        if (!$text) return '';
        
        // Remplacer les retours à la ligne par des espaces
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        
        // Supprimer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Nettoyer les caractères spéciaux HTML
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Limiter la longueur si nécessaire
        if (strlen($text) > 200) {
            $text = substr($text, 0, 197) . '...';
        }
        
        return trim($text);
    }
    
    /**
     * Génère un identifiant unique pour les fichiers
     */
    public static function generateUniqueId($prefix = 'export', $suffix = '') {
        $timestamp = date('Y-m-d_H-i-s');
        $random = substr(md5(uniqid(mt_rand(), true)), 0, 6);
        
        return $prefix . '_' . $timestamp . '_' . $random . ($suffix ? '_' . $suffix : '');
    }
    
    /**
     * Valide les données d'un abonné
     */
    public static function validateAbonneData($abonne) {
        $required_fields = [
            'nom', 'prenom', 'numero_abonne', 'statut', 
            'date_inscription', 'date_expiration', 'niveau', 'classe'
        ];
        
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($abonne[$field])) {
                $errors[] = "Champ requis manquant: {$field}";
            }
        }
        
        // Validations spécifiques
        if (isset($abonne['date_naissance']) && $abonne['date_naissance']) {
            $age_info = self::calculatePreciseAge($abonne['date_naissance']);
            if (!$age_info || $age_info['years'] > 25 || $age_info['years'] < 3) {
                $errors[] = "Âge suspect: " . ($age_info ? $age_info['years'] . ' ans' : 'invalide');
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Génère un log d'export pour audit
     */
    public static function logExportActivity($type, $user_id, $count = 1, $filters = [], $format = 'html') {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'format' => $format,
            'user_id' => $user_id,
            'count' => $count,
            'filters' => $filters,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'unique_id' => self::generateUniqueId('log')
        ];
        
        $log_file = __DIR__ . '/../../../../logs/exports.log';
        $log_dir = dirname($log_file);
        
        // Créer le répertoire si nécessaire
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Écrire le log
        $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        return $log_entry['unique_id'];
    }
    
    /**
     * Génère des couleurs harmonieuses pour les graphiques
     */
    public static function generateColorPalette($count = 10) {
        $colors = [
            '#3b82f6', // Bleu
            '#10b981', // Vert
            '#f59e0b', // Ambre
            '#ef4444', // Rouge
            '#8b5cf6', // Violet
            '#06b6d4', // Cyan
            '#84cc16', // Lime
            '#f97316', // Orange
            '#ec4899', // Rose
            '#6b7280'  // Gris
        ];
        
        // Répéter et mélanger si on a besoin de plus de couleurs
        while (count($colors) < $count) {
            $colors = array_merge($colors, $colors);
        }
        
        return array_slice($colors, 0, $count);
    }
    
    /**
     * Convertit une couleur hex en rgba avec opacité
     */
    public static function hexToRgba($hex, $opacity = 1) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) == 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            return "rgba(59, 130, 246, {$opacity})"; // Fallback
        }
        
        return "rgba({$r}, {$g}, {$b}, {$opacity})";
    }
    
    /**
     * Génère des métadonnées pour le document
     */
    public static function generateDocumentMetadata($type, $count, $filters = []) {
        return [
            'title' => self::getDocumentTitle($type, $count),
            'description' => self::getDocumentDescription($type, $count, $filters),
            'keywords' => self::getDocumentKeywords($type),
            'author' => 'Bibliothèque UN JOUR NOUVEAU',
            'created' => date('c'),
            'format' => 'HTML/PDF',
            'language' => 'fr-FR',
            'version' => '2.0'
        ];
    }
    
    private static function getDocumentTitle($type, $count) {
        switch ($type) {
            case 'fiche':
                return 'Fiche d\'Abonnement - Bibliothèque UN JOUR NOUVEAU';
            case 'bulk_cards':
                return "Fiches d'Abonnement ({$count} abonnés) - Bibliothèque UN JOUR NOUVEAU";
            case 'liste':
            default:
                return "Liste des Abonnés ({$count} abonnés) - Bibliothèque UN JOUR NOUVEAU";
        }
    }
    
    private static function getDocumentDescription($type, $count, $filters) {
        $base = "Document généré automatiquement par le système de gestion de la Bibliothèque UN JOUR NOUVEAU. ";
        
        switch ($type) {
            case 'fiche':
                return $base . "Fiche d'abonnement individuelle avec toutes les informations de l'élève.";
            case 'bulk_cards':
                return $base . "Collection de {$count} fiches d'abonnement pour impression en lot.";
            case 'liste':
            default:
                $desc = $base . "Liste complète de {$count} abonnés de la bibliothèque.";
                if (!empty($filters)) {
                    $desc .= " Filtres appliqués: " . implode(', ', array_keys($filters)) . ".";
                }
                return $desc;
        }
    }
    
    private static function getDocumentKeywords($type) {
        $base = ['bibliothèque', 'abonnés', 'élèves', 'école', 'UN JOUR NOUVEAU'];
        
        switch ($type) {
            case 'fiche':
                return array_merge($base, ['fiche', 'abonnement', 'carte', 'individuel']);
            case 'bulk_cards':
                return array_merge($base, ['fiches', 'lot', 'impression', 'cartes']);
            case 'liste':
            default:
                return array_merge($base, ['liste', 'rapport', 'statistiques', 'export']);
        }
    }
}
?>