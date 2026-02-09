<?php
require_once '../../../config/config.php';

class ReportController {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function getDashboardStats() {
        $stats = [];
        
        // Statistiques générales
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM abonnes WHERE statut = 'actif'");
        $stmt->execute();
        $stats['total_abonnes'] = $stmt->fetch()['total'];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM livres WHERE statut = 'disponible'");
        $stmt->execute();
        $stats['total_livres'] = $stmt->fetch()['total'];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM emprunts WHERE statut = 'en_cours'");
        $stmt->execute();
        $stats['emprunts_en_cours'] = $stmt->fetch()['total'];
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM emprunts WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()");
        $stmt->execute();
        $stats['emprunts_retard'] = $stmt->fetch()['total'];
        
        $stmt = $this->db->prepare("SELECT SUM(montant) as total FROM amendes_pertes WHERE statut = 'impayee'");
        $stmt->execute();
        $stats['amendes_impayees'] = $stmt->fetch()['total'] ?? 0;
        
        return $stats;
    }
    
    public function getEmpruntsByMonth($year = null) {
        if (!$year) $year = date('Y');
        
        $sql = "SELECT 
                    MONTH(date_emprunt) as mois,
                    COUNT(*) as total_emprunts,
                    COUNT(CASE WHEN statut = 'retourne' THEN 1 END) as emprunts_retournes,
                    COUNT(CASE WHEN statut = 'en_cours' AND date_retour_prevue < CURDATE() THEN 1 END) as emprunts_retard
                FROM emprunts 
                WHERE YEAR(date_emprunt) = ? 
                GROUP BY MONTH(date_emprunt)
                ORDER BY mois";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    }
    
    public function getTopLivres($limit = 10) {
        $sql = "SELECT 
                    l.titre,
                    l.auteur,
                    l.isbn,
                    COUNT(e.id) as nb_emprunts,
                    AVG(DATEDIFF(e.date_retour_effective, e.date_emprunt)) as duree_moyenne
                FROM livres l
                LEFT JOIN emprunts e ON l.id = e.livre_id
                WHERE e.statut = 'retourne'
                GROUP BY l.id
                ORDER BY nb_emprunts DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getTopAbonnes($limit = 10) {
        $sql = "SELECT 
                    a.nom,
                    a.prenom,
                    a.numero_abonne,
                    a.classe,
                    COUNT(e.id) as nb_emprunts,
                    COUNT(CASE WHEN e.statut = 'en_cours' THEN 1 END) as emprunts_actifs,
                    COUNT(CASE WHEN e.statut = 'en_cours' AND e.date_retour_prevue < CURDATE() THEN 1 END) as emprunts_retard
                FROM abonnes a
                LEFT JOIN emprunts e ON a.id = e.abonne_id
                GROUP BY a.id
                ORDER BY nb_emprunts DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
   public function getEmpruntsByCategorie() {
    $sql = "SELECT 
                c.nom AS categorie,
                c.color AS color,
                COUNT(e.id) AS nb_emprunts,
                COUNT(CASE WHEN e.statut = 'en_cours' THEN 1 END) AS emprunts_actifs
            FROM categories_livres c
            LEFT JOIN livres l ON c.id = l.categorie_id
            LEFT JOIN emprunts e ON l.id = e.livre_id
            GROUP BY c.id, c.nom, c.color
            ORDER BY nb_emprunts DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    public function getEmpruntsByClasse() {
        $sql = "SELECT 
                    a.classe,
                    a.niveau,
                    COUNT(e.id) as nb_emprunts,
                    COUNT(CASE WHEN e.statut = 'en_cours' THEN 1 END) as emprunts_actifs,
                    COUNT(DISTINCT a.id) as nb_abonnes
                FROM abonnes a
                LEFT JOIN emprunts e ON a.id = e.abonne_id
                WHERE a.classe IS NOT NULL AND a.classe != ''
                GROUP BY a.classe, a.niveau
                ORDER BY nb_emprunts DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getRetardStats() {
        $sql = "SELECT 
                    COUNT(*) as total_retards,
                    AVG(DATEDIFF(CURDATE(), date_retour_prevue)) as retard_moyen,
                    MAX(DATEDIFF(CURDATE(), date_retour_prevue)) as retard_max,
                    COUNT(CASE WHEN DATEDIFF(CURDATE(), date_retour_prevue) <= 7 THEN 1 END) as retards_1_semaine,
                    COUNT(CASE WHEN DATEDIFF(CURDATE(), date_retour_prevue) > 7 AND DATEDIFF(CURDATE(), date_retour_prevue) <= 30 THEN 1 END) as retards_1_mois,
                    COUNT(CASE WHEN DATEDIFF(CURDATE(), date_retour_prevue) > 30 THEN 1 END) as retards_plus_1_mois
                FROM emprunts 
                WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function generateReport($type, $filters = []) {
        switch ($type) {
            case 'emprunts':
                return $this->generateEmpruntReport($filters);
            case 'abonnes':
                return $this->generateAbonneReport($filters);
            case 'livres':
                return $this->generateLivreReport($filters);
            case 'amendes':
                return $this->generateAmendeReport($filters);
            case 'retards':
                return $this->generateRetardReport($filters);
            default:
                return [];
        }
    }
    
    private function generateEmpruntReport($filters) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['date_debut'])) {
            $where_conditions[] = "e.date_emprunt >= ?";
            $params[] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where_conditions[] = "e.date_emprunt <= ?";
            $params[] = $filters['date_fin'];
        }
        
        if (!empty($filters['statut'])) {
            $where_conditions[] = "e.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['classe'])) {
            $where_conditions[] = "a.classe = ?";
            $params[] = $filters['classe'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT 
                    e.id,
                    e.date_emprunt,
                    e.date_retour_prevue,
                    e.date_retour_effective,
                    e.statut,
                    a.nom as abonne_nom,
                    a.prenom as abonne_prenom,
                    a.numero_abonne,
                    a.classe,
                    l.titre,
                    l.auteur,
                    l.isbn,
                    CASE 
                        WHEN e.statut = 'en_cours' AND e.date_retour_prevue < CURDATE() 
                        THEN DATEDIFF(CURDATE(), e.date_retour_prevue)
                        ELSE 0
                    END as jours_retard
                FROM emprunts e
                JOIN abonnes a ON e.abonne_id = a.id
                JOIN livres l ON e.livre_id = l.id
                $where_clause
                ORDER BY e.date_emprunt DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    private function generateAbonneReport($filters) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['statut'])) {
            $where_conditions[] = "a.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['niveau'])) {
            $where_conditions[] = "a.niveau = ?";
            $params[] = $filters['niveau'];
        }
        
        if (!empty($filters['classe'])) {
            $where_conditions[] = "a.classe = ?";
            $params[] = $filters['classe'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT 
                    a.*,
                    COUNT(e.id) as total_emprunts,
                    COUNT(CASE WHEN e.statut = 'en_cours' THEN 1 END) as emprunts_actifs,
                    COUNT(CASE WHEN e.statut = 'en_cours' AND e.date_retour_prevue < CURDATE() THEN 1 END) as emprunts_retard,
                    SUM(am.montant) as total_amendes
                FROM abonnes a
                LEFT JOIN emprunts e ON a.id = e.abonne_id
                LEFT JOIN amendes am ON a.id = am.abonne_id AND am.statut = 'impayee'
                $where_clause
                GROUP BY a.id
                ORDER BY a.nom, a.prenom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    private function generateLivreReport($filters) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['categorie_id'])) {
            $where_conditions[] = "l.categorie_id = ?";
            $params[] = $filters['categorie_id'];
        }
        
        if (!empty($filters['statut'])) {
            $where_conditions[] = "l.statut = ?";
            $params[] = $filters['statut'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT 
                    l.*,
                    c.nom as categorie_nom,
                    COUNT(e.id) as total_emprunts,
                    COUNT(CASE WHEN e.statut = 'en_cours' THEN 1 END) as emprunts_actifs,
                    AVG(CASE WHEN e.statut = 'retourne' THEN DATEDIFF(e.date_retour_effective, e.date_emprunt) END) as duree_moyenne_emprunt
                FROM livres l
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                LEFT JOIN emprunts e ON l.id = e.livre_id
                $where_clause
                GROUP BY l.id
                ORDER BY l.titre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    private function generateAmendeReport($filters) {
        $where_conditions = [];
        $params = [];
        
        if (!empty($filters['date_debut'])) {
            $where_conditions[] = "am.date_creation >= ?";
            $params[] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where_conditions[] = "am.date_creation <= ?";
            $params[] = $filters['date_fin'];
        }
        
        if (!empty($filters['statut'])) {
            $where_conditions[] = "am.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['type'])) {
            $where_conditions[] = "am.type = ?";
            $params[] = $filters['type'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $sql = "SELECT 
                    am.*,
                    a.nom as abonne_nom,
                    a.prenom as abonne_prenom,
                    a.numero_abonne,
                    a.classe,
                    l.titre as livre_titre,
                    l.auteur as livre_auteur
                FROM amendes_pertes am
                JOIN abonnes a ON am.abonne_id = a.id
                LEFT JOIN livres l ON am.livre_id = l.id
                $where_clause
                ORDER BY am.date_creation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    private function generateRetardReport($filters) {
        $where_conditions = ["e.statut = 'en_cours'", "e.date_retour_prevue < CURDATE()"];
        $params = [];
        
        if (!empty($filters['classe'])) {
            $where_conditions[] = "a.classe = ?";
            $params[] = $filters['classe'];
        }
        
        if (!empty($filters['jours_retard_min'])) {
            $where_conditions[] = "DATEDIFF(CURDATE(), e.date_retour_prevue) >= ?";
            $params[] = $filters['jours_retard_min'];
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "SELECT 
                    e.*,
                    a.nom as abonne_nom,
                    a.prenom as abonne_prenom,
                    a.numero_abonne,
                    a.classe,
                    a.telephone_parent,
                    l.titre,
                    l.auteur,
                    DATEDIFF(CURDATE(), e.date_retour_prevue) as jours_retard
                FROM emprunts e
                JOIN abonnes a ON e.abonne_id = a.id
                JOIN livres l ON e.livre_id = l.id
                $where_clause
                ORDER BY jours_retard DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

// Traitement des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireLogin();
    
    $controller = new ReportController();
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    
    switch ($_POST['action']) {
        case 'generate_report':
            if (isset($_POST['type'])) {
                $filters = $_POST['filters'] ?? [];
                $data = $controller->generateReport($_POST['type'], $filters);
                $response = ['success' => true, 'data' => $data];
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>