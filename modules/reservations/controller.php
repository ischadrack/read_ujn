<?php
require_once(__DIR__ . '/../../config/config.php');

requireLogin();

class ReservationController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function index($filters = [], $page = 1, $limit = 20, $sort = 'date_reservation', $order = 'DESC')
    {
        $where = "WHERE 1=1";
        $params = [];
        $offset = ($page - 1) * $limit;

        // Filtres
        if (!empty($filters['search'])) {
            $where .= " AND (a.nom LIKE ? OR a.prenom LIKE ? OR l.titre LIKE ? OR l.code_livre LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['statut'])) {
            $where .= " AND r.statut = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['abonne_id'])) {
            $where .= " AND r.abonne_id = ?";
            $params[] = $filters['abonne_id'];
        }

        if (!empty($filters['livre_id'])) {
            $where .= " AND r.livre_id = ?";
            $params[] = $filters['livre_id'];
        }

        if (!empty($filters['date_debut'])) {
            $where .= " AND r.date_reservation >= ?";
            $params[] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $where .= " AND r.date_reservation <= ?";
            $params[] = $filters['date_fin'];
        }

        if (!empty($filters['expires_soon'])) {
            $where .= " AND r.date_expiration <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND r.statut = 'active'";
        }

        // Validation du tri
        $allowed_sorts = ['date_reservation', 'date_expiration', 'priorite', 'statut'];
        if (!in_array($sort, $allowed_sorts)) $sort = 'date_reservation';
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) $order = 'DESC';

        // Compter le total
        $count_sql = "SELECT COUNT(*) as total 
                      FROM reservations r
                      LEFT JOIN abonnes a ON r.abonne_id = a.id
                      LEFT JOIN livres l ON r.livre_id = l.id
                      $where";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];

        // Récupérer les données avec pagination
        $sql = "SELECT r.*,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                       a.numero_abonne,
                       a.classe,
                       a.telephone_parent,
                       l.titre as livre_titre,
                       l.code_livre,
                       l.auteur,
                       l.quantite_disponible,
                       c.nom as categorie_nom,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       DATEDIFF(r.date_expiration, CURDATE()) as jours_restants
                FROM reservations r
                LEFT JOIN abonnes a ON r.abonne_id = a.id
                LEFT JOIN livres l ON r.livre_id = l.id
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                LEFT JOIN users u ON r.created_by = u.id
                $where
                ORDER BY 
                    CASE WHEN r.statut = 'active' THEN 1 ELSE 2 END,
                    r.priorite DESC,
                    r.$sort $order
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $reservations,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function create($data)
    {
        // Vérifier que l'abonné existe et est actif
        $check_abonne = $this->db->prepare("SELECT id, statut, nb_emprunts_actuel, nb_emprunts_max FROM abonnes WHERE id = ?");
        $check_abonne->execute([$data['abonne_id']]);
        $abonne = $check_abonne->fetch();
        
        if (!$abonne) {
            return ['success' => false, 'message' => "Abonné introuvable."];
        }
        
        if ($abonne['statut'] !== 'actif') {
            return ['success' => false, 'message' => "L'abonné n'est pas actif."];
        }

        // Vérifier que le livre existe et est actif
        $check_livre = $this->db->prepare("SELECT id, titre, quantite_disponible, statut FROM livres WHERE id = ?");
        $check_livre->execute([$data['livre_id']]);
        $livre = $check_livre->fetch();
        
        if (!$livre) {
            return ['success' => false, 'message' => "Livre introuvable."];
        }
        
        if ($livre['statut'] !== 'actif') {
            return ['success' => false, 'message' => "Le livre n'est pas disponible."];
        }

        // Vérifier qu'il n'y a pas déjà une réservation active pour ce livre par cet abonné
        $check_existing = $this->db->prepare("SELECT id FROM reservations WHERE livre_id = ? AND abonne_id = ? AND statut = 'active'");
        $check_existing->execute([$data['livre_id'], $data['abonne_id']]);
        if ($check_existing->fetch()) {
            return ['success' => false, 'message' => "Une réservation active existe déjà pour ce livre par cet abonné."];
        }

        // Déterminer la priorité (basée sur la date de réservation)
        $priorite_stmt = $this->db->prepare("SELECT COALESCE(MAX(priorite), 0) + 1 as nouvelle_priorite FROM reservations WHERE livre_id = ? AND statut = 'active'");
        $priorite_stmt->execute([$data['livre_id']]);
        $priorite = $priorite_stmt->fetch()['nouvelle_priorite'];

        // Date d'expiration (14 jours par défaut)
        $date_expiration = date('Y-m-d', strtotime('+14 days'));
        if (!empty($data['date_expiration'])) {
            $date_expiration = $data['date_expiration'];
        }

        $sql = "INSERT INTO reservations (
                    livre_id, abonne_id, date_reservation, date_expiration, 
                    priorite, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['livre_id'],
            $data['abonne_id'],
            $data['date_reservation'] ?? date('Y-m-d'),
            $date_expiration,
            $priorite,
            $data['notes'] ?? '',
            $_SESSION['user_id']
        ]);

        if ($ok) {
            return [
                'success' => true,
                'message' => "Réservation créée avec succès. Position dans la file d'attente: $priorite",
                'reservation_id' => $this->db->lastInsertId()
            ];
        }

        return [
            'success' => false,
            'message' => "Erreur lors de la création de la réservation."
        ];
    }

    public function update($id, $data)
    {
        $sql = "UPDATE reservations SET 
                    notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['notes'] ?? '',
            $id
        ]);

        return [
            'success' => $ok,
            'message' => $ok ? "Réservation modifiée avec succès." : "Erreur lors de la modification."
        ];
    }

    public function find($id)
    {
        $sql = "SELECT r.*,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                       a.numero_abonne,
                       a.classe,
                       a.telephone_parent,
                       a.email_parent,
                       a.statut as abonne_statut,
                       l.titre as livre_titre,
                       l.code_livre,
                       l.auteur,
                       l.editeur,
                       l.quantite_disponible,
                       l.statut as livre_statut,
                       c.nom as categorie_nom,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       DATEDIFF(r.date_expiration, CURDATE()) as jours_restants
                FROM reservations r
                LEFT JOIN abonnes a ON r.abonne_id = a.id
                LEFT JOIN livres l ON r.livre_id = l.id
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                LEFT JOIN users u ON r.created_by = u.id
                WHERE r.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        // Récupérer les détails de la réservation
        $reservation = $this->find($id);
        if (!$reservation) {
            return ['success' => false, 'message' => "Réservation introuvable."];
        }

        // Supprimer la réservation
        $stmt = $this->db->prepare("DELETE FROM reservations WHERE id = ?");
        $ok = $stmt->execute([$id]);
        
        if ($ok) {
            // Réorganiser les priorités pour ce livre
            $this->reorganiserPriorites($reservation['livre_id']);
            
            return ['success' => true, 'message' => "Réservation supprimée avec succès."];
        }

        return ['success' => false, 'message' => "Erreur lors de la suppression."];
    }

    public function changerStatut($id, $nouveau_statut, $notes = '')
    {
        $statuts_valides = ['active', 'satisfaite', 'expiree', 'annulee'];
        if (!in_array($nouveau_statut, $statuts_valides)) {
            return ['success' => false, 'message' => "Statut invalide."];
        }

        $reservation = $this->find($id);
        if (!$reservation) {
            return ['success' => false, 'message' => "Réservation introuvable."];
        }

        $sql = "UPDATE reservations SET 
                    statut = ?, 
                    notes = ?, 
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([$nouveau_statut, $notes, $id]);

        if ($ok && $nouveau_statut !== 'active') {
            // Réorganiser les priorités si la réservation n'est plus active
            $this->reorganiserPriorites($reservation['livre_id']);
        }

        $messages = [
            'satisfaite' => 'Réservation marquée comme satisfaite.',
            'expiree' => 'Réservation marquée comme expirée.',
            'annulee' => 'Réservation annulée.',
            'active' => 'Réservation réactivée.'
        ];

        return [
            'success' => $ok,
            'message' => $ok ? $messages[$nouveau_statut] : "Erreur lors du changement de statut."
        ];
    }

    public function getReservationsByAbonne($abonne_id)
    {
        $sql = "SELECT r.*,
                       l.titre as livre_titre,
                       l.code_livre,
                       l.quantite_disponible,
                       c.nom as categorie_nom,
                       DATEDIFF(r.date_expiration, CURDATE()) as jours_restants
                FROM reservations r
                LEFT JOIN livres l ON r.livre_id = l.id
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                WHERE r.abonne_id = ?
                ORDER BY r.date_reservation DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$abonne_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReservationsByLivre($livre_id)
    {
        $sql = "SELECT r.*,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                       a.numero_abonne,
                       a.classe,
                       a.telephone_parent,
                       DATEDIFF(r.date_expiration, CURDATE()) as jours_restants
                FROM reservations r
                LEFT JOIN abonnes a ON r.abonne_id = a.id
                WHERE r.livre_id = ? AND r.statut = 'active'
                ORDER BY r.priorite ASC, r.date_reservation ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$livre_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats()
    {
        $stats = [];
        
        // Total réservations actives
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservations WHERE statut = 'active'");
        $stmt->execute();
        $stats['actives'] = $stmt->fetch()['total'];

        // Réservations qui expirent bientôt (3 jours)
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservations WHERE statut = 'active' AND date_expiration <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
        $stmt->execute();
        $stats['expirent_bientot'] = $stmt->fetch()['total'];

        // Réservations expirées
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservations WHERE statut = 'active' AND date_expiration < CURDATE()");
        $stmt->execute();
        $stats['expirees'] = $stmt->fetch()['total'];

        // Réservations satisfaites ce mois
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservations WHERE statut = 'satisfaite' AND DATE_FORMAT(updated_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute();
        $stats['satisfaites_mois'] = $stmt->fetch()['total'];

        return $stats;
    }

    public function marquerExpireesAutomatiquement()
    {
        $sql = "UPDATE reservations 
                SET statut = 'expiree', 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE statut = 'active' 
                AND date_expiration < CURDATE()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }

    public function creerEmpruntDepuisReservation($reservation_id)
    {
        $reservation = $this->find($reservation_id);
        if (!$reservation || $reservation['statut'] !== 'active') {
            return ['success' => false, 'message' => "Réservation invalide."];
        }

        // Vérifier que le livre est disponible
        if ($reservation['quantite_disponible'] <= 0) {
            return ['success' => false, 'message' => "Livre non disponible."];
        }

        // Créer l'emprunt
        $emprunt_data = [
            'livre_id' => $reservation['livre_id'],
            'abonne_id' => $reservation['abonne_id'],
            'date_emprunt' => date('Y-m-d'),
            'date_retour_prevue' => date('Y-m-d', strtotime('+14 days')),
            'observations_emprunt' => 'Créé à partir de la réservation #' . $reservation_id
        ];

        require_once(__DIR__ . '/../emprunts/controller.php');
        $emprunt_controller = new EmpruntController();
        $result = $emprunt_controller->create($emprunt_data);

        if ($result['success']) {
            // Marquer la réservation comme satisfaite
            $this->changerStatut($reservation_id, 'satisfaite', 'Emprunt créé le ' . date('d/m/Y'));
            
            return [
                'success' => true,
                'message' => "Emprunt créé avec succès à partir de la réservation.",
                'emprunt_id' => $result['emprunt_id']
            ];
        }

        return $result;
    }

    private function reorganiserPriorites($livre_id)
    {
        // Récupérer toutes les réservations actives pour ce livre, triées par priorité actuelle
        $stmt = $this->db->prepare("SELECT id FROM reservations WHERE livre_id = ? AND statut = 'active' ORDER BY priorite ASC, date_reservation ASC");
        $stmt->execute([$livre_id]);
        $reservations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Réassigner les priorités de 1 à N
        foreach ($reservations as $index => $reservation_id) {
            $nouvelle_priorite = $index + 1;
            $update_stmt = $this->db->prepare("UPDATE reservations SET priorite = ? WHERE id = ?");
            $update_stmt->execute([$nouvelle_priorite, $reservation_id]);
        }
    }

    public function exportData($filters = [])
    {
        $where = "WHERE 1=1";
        $params = [];

        // Appliquer les mêmes filtres que pour l'index
        if (!empty($filters['search'])) {
            $where .= " AND (a.nom LIKE ? OR a.prenom LIKE ? OR l.titre LIKE ? OR l.code_livre LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['statut'])) {
            $where .= " AND r.statut = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['date_debut'])) {
            $where .= " AND r.date_reservation >= ?";
            $params[] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $where .= " AND r.date_reservation <= ?";
            $params[] = $filters['date_fin'];
        }

        $sql = "SELECT r.*,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                       a.numero_abonne,
                       a.classe,
                       l.titre as livre_titre,
                       l.code_livre,
                       l.auteur,
                       c.nom as categorie_nom,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM reservations r
                LEFT JOIN abonnes a ON r.abonne_id = a.id
                LEFT JOIN livres l ON r.livre_id = l.id
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                LEFT JOIN users u ON r.created_by = u.id
                $where
                ORDER BY r.date_reservation DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new ReservationController();

    switch ($_POST['action']) {
        case 'create':
            $result = $controller->create($_POST);
            echo json_encode($result);
            break;

        case 'update':
            $result = $controller->update($_POST['id'], $_POST);
            echo json_encode($result);
            break;

        case 'delete':
            $result = $controller->delete($_POST['id']);
            echo json_encode($result);
            break;

        case 'change_status':
            $result = $controller->changerStatut($_POST['id'], $_POST['statut'], $_POST['notes'] ?? '');
            echo json_encode($result);
            break;

        case 'create_emprunt':
            $result = $controller->creerEmpruntDepuisReservation($_POST['id']);
            echo json_encode($result);
            break;

        case 'mark_expired':
            $count = $controller->marquerExpireesAutomatiquement();
            echo json_encode(['success' => true, 'count' => $count, 'message' => "$count réservations marquées comme expirées."]);
            break;
    }
    exit;
}
?>