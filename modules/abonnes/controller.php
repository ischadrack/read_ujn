<?php
require_once(__DIR__ . '/../../config/config.php');

requireLogin();

class AbonneController
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
            $where .= " AND (a.nom LIKE ? OR a.prenom LIKE ? OR a.numero_abonne LIKE ? OR a.classe LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

       if (!empty($filters['statut'])) {

            if ($filters['statut'] === 'expire') {
                // Abonnés actifs mais date dépassée
                $where .= " AND a.statut = 'actif' AND a.date_expiration < CURDATE()";

            } else {
                $where .= " AND a.statut = ?";
                $params[] = $filters['statut'];
            }
        }


        if (!empty($filters['niveau'])) {
            $where .= " AND a.niveau = ?";
            $params[] = $filters['niveau'];
        }

        if (!empty($filters['classe'])) {
            $where .= " AND a.classe = ?";
            $params[] = $filters['classe'];
        }

        // Validation du tri
        $allowed_sorts = ['nom', 'prenom', 'numero_abonne', 'classe', 'date_inscription', 'created_at'];
        if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) $order = 'DESC';

        // Compter le total
        $count_sql = "SELECT COUNT(*) as total FROM abonnes a $where";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];

        // Récupérer les données avec pagination
        $sql = "SELECT a.*,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.abonne_id = a.id AND e.statut IN ('en_cours', 'en_retard')) as emprunts_actifs,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.abonne_id = a.id AND e.statut = 'en_retard') as emprunts_retard
                FROM abonnes a
                LEFT JOIN users u ON a.created_by = u.id
                $where
                ORDER BY a.$sort $order
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $abonnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $abonnes,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function create($data)
    {
        // Vérifier si le numéro d'abonné existe déjà
        $check_stmt = $this->db->prepare("SELECT id FROM abonnes WHERE numero_abonne = ?");
        $check_stmt->execute([$data['numero_abonne']]);
        if ($check_stmt->fetch()) {
            return [
                'success' => false,
                'message' => "Ce numéro d'abonné existe déjà."
            ];
        }

        // Calculer la date d'expiration (1 an par défaut)
        $date_expiration = date('Y-m-d', strtotime($data['date_inscription'] . ' +1 year'));

        $sql = "INSERT INTO abonnes (
                    numero_abonne, nom, prenom, date_naissance, sexe, classe, niveau,
                    nom_parent, telephone_parent, email_parent, adresse, date_inscription,
                    date_expiration, nb_emprunts_max, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['numero_abonne'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'] ?? null,
            $data['sexe'],
            $data['classe'] ?? '',
            $data['niveau'] ?? 'primaire',
            $data['nom_parent'] ?? '',
            $data['telephone_parent'] ?? '',
            $data['email_parent'] ?? '',
            $data['adresse'] ?? '',
            $data['date_inscription'],
            $date_expiration,
            $data['nb_emprunts_max'] ?? 3,
            $data['notes'] ?? '',
            $_SESSION['user_id']
        ]);

        if ($ok) {
            return [
                'success' => true,
                'message' => "Abonné créé avec succès.",
                'abonne_id' => $this->db->lastInsertId()
            ];
        }

        return [
            'success' => false,
            'message' => "Erreur lors de la création de l'abonné."
        ];
    }

    public function update($id, $data)
    {
        // Vérifier si le numéro d'abonné existe déjà (autre que cet abonné)
        $check_stmt = $this->db->prepare("SELECT id FROM abonnes WHERE numero_abonne = ? AND id != ?");
        $check_stmt->execute([$data['numero_abonne'], $id]);
        if ($check_stmt->fetch()) {
            return [
                'success' => false,
                'message' => "Ce numéro d'abonné existe déjà."
            ];
        }

        $sql = "UPDATE abonnes SET
                    numero_abonne = ?, nom = ?, prenom = ?, date_naissance = ?, sexe = ?,
                    classe = ?, niveau = ?, nom_parent = ?, telephone_parent = ?, email_parent = ?,
                    adresse = ?, date_expiration = ?, nb_emprunts_max = ?, statut = ?, notes = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['numero_abonne'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'] ?? null,
            $data['sexe'],
            $data['classe'] ?? '',
            $data['niveau'] ?? 'primaire',
            $data['nom_parent'] ?? '',
            $data['telephone_parent'] ?? '',
            $data['email_parent'] ?? '',
            $data['adresse'] ?? '',
            $data['date_expiration'],
            $data['nb_emprunts_max'] ?? 3,
            $data['statut'] ?? 'actif',
            $data['notes'] ?? '',
            $id
        ]);

        return [
            'success' => $ok,
            'message' => $ok ? "Abonné modifié avec succès." : "Erreur lors de la modification."
        ];
    }

    public function find($id)
    {
        $sql = "SELECT a.*,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.abonne_id = a.id) as total_emprunts,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.abonne_id = a.id AND e.statut IN ('en_cours', 'en_retard')) as emprunts_actifs,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.abonne_id = a.id AND e.statut = 'en_retard') as emprunts_retard,
                       (SELECT SUM(ap.montant) FROM amendes_pertes ap WHERE ap.abonne_id = a.id AND ap.statut = 'impayee') as total_amendes_impayees
                FROM abonnes a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        // Vérifier s'il y a des emprunts en cours
        $check_stmt = $this->db->prepare("SELECT COUNT(*) FROM emprunts WHERE abonne_id = ? AND statut IN ('en_cours', 'en_retard')");
        $check_stmt->execute([$id]);
        if ($check_stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => "Impossible de supprimer : cet abonné a des emprunts en cours."
            ];
        }

        $stmt = $this->db->prepare("DELETE FROM abonnes WHERE id = ?");
        $ok = $stmt->execute([$id]);

        return [
            'success' => $ok,
            'message' => $ok ? "Abonné supprimé avec succès." : "Erreur lors de la suppression."
        ];
    }

    public function getStats()
    {
        $stats = [];

        // Total abonnés actifs
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM abonnes WHERE statut = 'actif'");
        $stmt->execute();
        $stats['total_abonnes'] = $stmt->fetch()['total'];

        // Nouveaux abonnements ce mois
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM abonnes WHERE DATE_FORMAT(date_inscription, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute();
        $stats['nouveaux_mois'] = $stmt->fetch()['total'];

        // Abonnements expirés
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM abonnes WHERE date_expiration < CURDATE() AND statut = 'actif'");
        $stmt->execute();
        $stats['expires'] = $stmt->fetch()['total'];

        // Abonnés suspendus
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM abonnes WHERE statut = 'suspendu'");
        $stmt->execute();
        $stats['suspendus'] = $stmt->fetch()['total'];

        return $stats;
    }

    public function generateNumeroAbonne()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT MAX(CAST(SUBSTRING(numero_abonne, 5) AS UNSIGNED)) as max_num FROM abonnes WHERE numero_abonne LIKE ?");
        $stmt->execute([$year . '%']);
        $result = $stmt->fetch();
        $next_num = ($result['max_num'] ?? 0) + 1;

        return $year . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    }

    public function getEmpruntsActifs($abonne_id)
    {
        $sql = "SELECT e.*,
                       l.titre, l.code_livre, l.auteur, l.editeur,
                       c.nom as categorie_nom
                FROM emprunts e
                LEFT JOIN livres l ON e.livre_id = l.id
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                WHERE e.abonne_id = ? AND e.statut IN ('en_cours', 'en_retard')
                ORDER BY e.date_emprunt DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$abonne_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistoriqueEmprunts($abonne_id, $limit = 10)
    {
        $sql = "SELECT e.*,
                       l.titre, l.code_livre, l.auteur,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM emprunts e
                LEFT JOIN livres l ON e.livre_id = l.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.abonne_id = ?
                ORDER BY e.date_emprunt DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$abonne_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new AbonneController();

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

        case 'generate_numero':
            $numero = $controller->generateNumeroAbonne();
            echo json_encode(['numero' => $numero]);
            break;

        case 'get_emprunts_actifs':
            $emprunts = $controller->getEmpruntsActifs($_POST['abonne_id']);
            echo json_encode(['success' => true, 'emprunts' => $emprunts]);
            break;

        case 'get_historique':
            $historique = $controller->getHistoriqueEmprunts($_POST['abonne_id'], $_POST['limit'] ?? 10);
            echo json_encode(['success' => true, 'historique' => $historique]);
            break;
    }
    exit;
}
?>