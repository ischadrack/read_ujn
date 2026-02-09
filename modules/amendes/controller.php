<?php
require_once(__DIR__ . '/../../config/config.php');

requireLogin();

class AmendePerteController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function index($filters = [], $page = 1, $limit = 20, $sort = 'created_at', $order = 'DESC')
    {
        $where = "WHERE 1=1";
        $params = [];
        $offset = ($page - 1) * $limit;

        // Filtres
        if (!empty($filters['search'])) {
            $where .= " AND (a.nom LIKE ? OR a.prenom LIKE ? OR ap.description LIKE ? OR ap.recu_numero LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['type'])) {
            $where .= " AND ap.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['statut'])) {
            $where .= " AND ap.statut = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['date_debut'])) {
            $where .= " AND ap.date_amende >= ?";
            $params[] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $where .= " AND ap.date_amende <= ?";
            $params[] = $filters['date_fin'];
        }

        // Validation du tri
        $allowed_sorts = ['date_amende', 'montant', 'type', 'statut', 'created_at'];
        if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) $order = 'DESC';

        // Compter le total
        $count_sql = "SELECT COUNT(*) as total 
                      FROM amendes_pertes ap
                      LEFT JOIN abonnes a ON ap.abonne_id = a.id
                      $where";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];

        // Récupérer les données avec pagination
        $sql = "SELECT ap.*,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                       a.numero_abonne,
                       a.classe,
                       l.titre as livre_titre,
                       l.code_livre,
                       e.date_emprunt,
                       e.date_retour_prevue,
                       CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
                       CONCAT(u2.first_name, ' ', u2.last_name) as processed_by_name
                FROM amendes_pertes ap
                LEFT JOIN abonnes a ON ap.abonne_id = a.id
                LEFT JOIN livres l ON ap.livre_id = l.id
                LEFT JOIN emprunts e ON ap.emprunt_id = e.id
                LEFT JOIN users u1 ON ap.created_by = u1.id
                LEFT JOIN users u2 ON ap.processed_by = u2.id
                $where
                ORDER BY ap.$sort $order
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $amendes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $amendes,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function create($data)
    {
        // Vérifier que l'abonné existe
        $check_stmt = $this->db->prepare("SELECT id FROM abonnes WHERE id = ?");
        $check_stmt->execute([$data['abonne_id']]);
        if (!$check_stmt->fetch()) {
            return [
                'success' => false,
                'message' => "Abonné introuvable."
            ];
        }

        $sql = "INSERT INTO amendes_pertes (
                    abonne_id, emprunt_id, livre_id, type, montant, description,
                    date_amende, statut, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['abonne_id'],
            $data['emprunt_id'] ?? null,
            $data['livre_id'] ?? null,
            $data['type'],
            $data['montant'],
            $data['description'] ?? '',
            $data['date_amende'],
            $data['statut'] ?? 'impayee',
            $_SESSION['user_id']
        ]);

        if ($ok) {
            // Mettre à jour le total des amendes de l'abonné
            $this->updateAbonneTotalAmendes($data['abonne_id']);
            
            return [
                'success' => true,
                'message' => "Amende créée avec succès.",
                'amende_id' => $this->db->lastInsertId()
            ];
        }

        return [
            'success' => false,
            'message' => "Erreur lors de la création de l'amende."
        ];
    }

    public function update($id, $data)
    {
        $sql = "UPDATE amendes_pertes SET 
                    type = ?, montant = ?, description = ?, statut = ?,
                    date_paiement = ?, mode_paiement = ?, recu_numero = ?,
                    processed_by = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['type'],
            $data['montant'],
            $data['description'] ?? '',
            $data['statut'],
            $data['date_paiement'] ?? null,
            $data['mode_paiement'] ?? '',
            $data['recu_numero'] ?? '',
            $_SESSION['user_id'],
            $id
        ]);

        if ($ok) {
            // Récupérer l'abonne_id pour mettre à jour le total
            $amende = $this->find($id);
            if ($amende) {
                $this->updateAbonneTotalAmendes($amende['abonne_id']);
            }
        }

        return [
            'success' => $ok,
            'message' => $ok ? "Amende modifiée avec succès." : "Erreur lors de la modification."
        ];
    }

    public function find($id)
    {
        $sql = "SELECT ap.*,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                       a.numero_abonne,
                       a.classe,
                       a.telephone_parent,
                       l.titre as livre_titre,
                       l.code_livre,
                       l.prix_unitaire,
                       e.date_emprunt,
                       e.date_retour_prevue,
                       e.date_retour_effective,
                       CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
                       CONCAT(u2.first_name, ' ', u2.last_name) as processed_by_name
                FROM amendes_pertes ap
                LEFT JOIN abonnes a ON ap.abonne_id = a.id
                LEFT JOIN livres l ON ap.livre_id = l.id
                LEFT JOIN emprunts e ON ap.emprunt_id = e.id
                LEFT JOIN users u1 ON ap.created_by = u1.id
                LEFT JOIN users u2 ON ap.processed_by = u2.id
                WHERE ap.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        // Récupérer l'amende avant suppression
        $amende = $this->find($id);
        if (!$amende) {
            return [
                'success' => false,
                'message' => "Amende introuvable."
            ];
        }

        $stmt = $this->db->prepare("DELETE FROM amendes_pertes WHERE id = ?");
        $ok = $stmt->execute([$id]);
        
        if ($ok) {
            // Mettre à jour le total des amendes de l'abonné
            $this->updateAbonneTotalAmendes($amende['abonne_id']);
        }

        return [
            'success' => $ok,
            'message' => $ok ? "Amende supprimée avec succès." : "Erreur lors de la suppression."
        ];
    }

    public function getStats()
    {
        $stats = [];
        
        // Total amendes impayées
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM amendes_pertes WHERE statut = 'impayee'");
        $stmt->execute();
        $stats['total_impayees'] = $stmt->fetch()['total'];

        // Total payées ce mois
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(montant), 0) as total FROM amendes_pertes WHERE statut = 'payee' AND DATE_FORMAT(date_paiement, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute();
        $stats['payees_mois'] = $stmt->fetch()['total'];

        // Nombre d'amendes de retard
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM amendes_pertes WHERE type = 'retard' AND statut = 'impayee'");
        $stmt->execute();
        $stats['retards'] = $stmt->fetch()['total'];

        // Nombre de pertes
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM amendes_pertes WHERE type = 'perte'");
        $stmt->execute();
        $stats['pertes'] = $stmt->fetch()['total'];

        return $stats;
    }

    public function getAmendesByAbonne($abonne_id)
    {
        $sql = "SELECT ap.*,
                       l.titre as livre_titre,
                       l.code_livre,
                       e.date_emprunt,
                       e.date_retour_prevue
                FROM amendes_pertes ap
                LEFT JOIN livres l ON ap.livre_id = l.id
                LEFT JOIN emprunts e ON ap.emprunt_id = e.id
                WHERE ap.abonne_id = ?
                ORDER BY ap.date_amende DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$abonne_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function payerAmende($id, $data)
    {
        $sql = "UPDATE amendes_pertes SET 
                    statut = 'payee',
                    date_paiement = ?,
                    mode_paiement = ?,
                    recu_numero = ?,
                    processed_by = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['date_paiement'],
            $data['mode_paiement'],
            $data['recu_numero'],
            $_SESSION['user_id'],
            $id
        ]);

        if ($ok) {
            // Mettre à jour le total des amendes de l'abonné
            $amende = $this->find($id);
            if ($amende) {
                $this->updateAbonneTotalAmendes($amende['abonne_id']);
            }
        }

        return [
            'success' => $ok,
            'message' => $ok ? "Paiement enregistré avec succès." : "Erreur lors de l'enregistrement du paiement."
        ];
    }

    private function updateAbonneTotalAmendes($abonne_id)
    {
        $stmt = $this->db->prepare("UPDATE abonnes SET total_amendes = (SELECT COALESCE(SUM(montant), 0) FROM amendes_pertes WHERE abonne_id = ? AND statut = 'impayee') WHERE id = ?");
        $stmt->execute([$abonne_id, $abonne_id]);
    }

    public function createFromEmprunt($emprunt_id)
    {
        // Récupérer les détails de l'emprunt
        $stmt = $this->db->prepare("
            SELECT e.*, l.prix_unitaire, l.titre, a.id as abonne_id,
                   DATEDIFF(CURDATE(), e.date_retour_prevue) as jours_retard
            FROM emprunts e
            JOIN livres l ON e.livre_id = l.id
            JOIN abonnes a ON e.abonne_id = a.id
            WHERE e.id = ?
        ");
        $stmt->execute([$emprunt_id]);
        $emprunt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emprunt) {
            return ['success' => false, 'message' => 'Emprunt introuvable'];
        }

        $montant = 0;
        $type = '';
        $description = '';

        // Calculer l'amende selon le type
        if ($emprunt['statut'] == 'en_retard') {
            $type = 'retard';
            $montant = $emprunt['jours_retard'] * 100; // 100 FC par jour de retard
            $description = "Retard de {$emprunt['jours_retard']} jour(s) pour le livre \"{$emprunt['titre']}\"";
        } elseif ($emprunt['statut'] == 'perdu') {
            $type = 'perte';
            $montant = $emprunt['prix_unitaire'] * 0.8; // 80% du prix du livre
            $description = "Perte du livre \"{$emprunt['titre']}\"";
        } elseif ($emprunt['statut'] == 'deteriore') {
            $type = 'deterioration';
            $montant = $emprunt['prix_unitaire'] * 0.5; // 50% du prix du livre
            $description = "Détérioration du livre \"{$emprunt['titre']}\"";
        }

        // Créer l'amende
        $data = [
            'abonne_id' => $emprunt['abonne_id'],
            'emprunt_id' => $emprunt_id,
            'livre_id' => $emprunt['livre_id'],
            'type' => $type,
            'montant' => $montant,
            'description' => $description,
            'date_amende' => date('Y-m-d'),
            'statut' => 'impayee'
        ];

        return $this->create($data);
    }
}


// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new AmendePerteController();

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

        case 'payer':
            $result = $controller->payerAmende($_POST['id'], $_POST);
            echo json_encode($result);
            break;

        case 'create_from_emprunt':
            $result = $controller->createFromEmprunt($_POST['emprunt_id']);
            echo json_encode($result);
            break;
    }
    exit;
}
?>