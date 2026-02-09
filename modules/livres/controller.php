<?php
require_once(__DIR__ . '/../../config/config.php');

requireLogin();

class LivreController
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
            $where .= " AND (l.titre LIKE ? OR l.auteur LIKE ? OR l.code_livre LIKE ? OR l.isbn LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['categorie_id'])) {
            $where .= " AND l.categorie_id = ?";
            $params[] = $filters['categorie_id'];
        }

        if (!empty($filters['statut'])) {
            $where .= " AND l.statut = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['niveau_classe'])) {
            $where .= " AND l.niveau_classe = ?";
            $params[] = $filters['niveau_classe'];
        }

        if (!empty($filters['langue'])) {
            $where .= " AND l.langue = ?";
            $params[] = $filters['langue'];
        }

        if (!empty($filters['disponible'])) {
            $where .= " AND l.quantite_disponible > 0";
        }

        // Validation du tri
        $allowed_sorts = ['titre', 'auteur', 'categorie_id', 'quantite_disponible', 'created_at'];
        if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) $order = 'DESC';

        // Compter le total
        $count_sql = "SELECT COUNT(*) as total FROM livres l $where";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];

        // Récupérer les données avec pagination
        $sql = "SELECT l.*, 
                       c.nom as categorie_nom, c.color as categorie_color,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id AND e.statut = 'en_cours') as emprunts_actifs,
                       (SELECT COUNT(*) FROM reservations r WHERE r.livre_id = l.id AND r.statut = 'active') as reservations_actives
                FROM livres l
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                LEFT JOIN users u ON l.created_by = u.id
                $where
                ORDER BY l.$sort $order
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $livres,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function create($data)
    {
        // Vérifier si le code livre existe déjà
        $check_stmt = $this->db->prepare("SELECT id FROM livres WHERE code_livre = ?");
        $check_stmt->execute([$data['code_livre']]);
        if ($check_stmt->fetch()) {
            return [
                'success' => false,
                'message' => "Ce code livre existe déjà."
            ];
        }

        $sql = "INSERT INTO livres (
                    titre, code_livre, auteur, editeur, isbn, categorie_id, niveau_classe,
                    description, nombre_pages, langue, annee_publication, quantite_stock,
                    quantite_disponible, prix_unitaire, etat, date_acquisition, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['titre'],
            $data['code_livre'],
            $data['auteur'] ?? '',
            $data['editeur'] ?? '',
            $data['isbn'] ?? '',
            $data['categorie_id'],
            $data['niveau_classe'] ?? '',
            $data['description'] ?? '',
            $data['nombre_pages'] ?? 0,
            $data['langue'] ?? 'Français',
            $data['annee_publication'] ?? null,
            $data['quantite_stock'] ?? 1,
            $data['quantite_stock'] ?? 1, // quantite_disponible = quantite_stock initialement
            $data['prix_unitaire'] ?? 0,
            $data['etat'] ?? 'bon',
            $data['date_acquisition'] ?? null,
            $_SESSION['user_id']
        ]);

        if ($ok) {
            return [
                'success' => true,
                'message' => "Livre créé avec succès.",
                'livre_id' => $this->db->lastInsertId()
            ];
        }

        return [
            'success' => false,
            'message' => "Erreur lors de la création du livre."
        ];
    }

    public function update($id, $data)
    {
        // Vérifier si le code livre existe déjà (autre que ce livre)
        $check_stmt = $this->db->prepare("SELECT id FROM livres WHERE code_livre = ? AND id != ?");
        $check_stmt->execute([$data['code_livre'], $id]);
        if ($check_stmt->fetch()) {
            return [
                'success' => false,
                'message' => "Ce code livre existe déjà."
            ];
        }

        $sql = "UPDATE livres SET 
            titre = ?, 
            code_livre = ?, 
            auteur = ?, 
            editeur = ?, 
            isbn = ?, 
            categorie_id = ?, 
            niveau_classe = ?, 
            description = ?, 
            nombre_pages = ?, 
            langue = ?, 
            annee_publication = ?, 
            quantite_stock = ?, 
            prix_unitaire = ?, 
            etat = ?, 
            statut = ?, 
            date_acquisition = ?, 
            quantite_disponible = ?
        WHERE id = ?";
        
        // Calculer la nouvelle quantité disponible
        $current_stmt = $this->db->prepare("SELECT quantite_stock, quantite_empruntee, quantite_perdue FROM livres WHERE id = ?");
        $current_stmt->execute([$id]);
        $current = $current_stmt->fetch();
        
        $new_disponible = $data['quantite_stock'] - $current['quantite_empruntee'] - $current['quantite_perdue'];
        
        $update_stmt = $this->db->prepare($sql);
            $ok = $update_stmt->execute([
                $data['titre'],
                $data['code_livre'],
                $data['auteur'] ?? '',
                $data['editeur'] ?? '',
                $data['isbn'] ?? '',
                $data['categorie_id'],
                $data['niveau_classe'] ?? '',
                $data['description'] ?? '',
                $data['nombre_pages'] ?? 0,
                $data['langue'] ?? 'Français',
                $data['annee_publication'] ?? null,
                $data['quantite_stock'] ?? 1,
                $data['prix_unitaire'] ?? 0,
                $data['etat'] ?? 'bon',
                $data['statut'] ?? 'actif',
                $data['date_acquisition'] ?? null,
                max(0, $new_disponible),
                $id
            ]);
        return [
            'success' => $ok,
            'message' => $ok ? "Livre modifié avec succès." : "Erreur lors de la modification."
        ];
    }

    public function find($id)
    {
        $sql = "SELECT l.*, 
                       c.nom as categorie_nom, c.color as categorie_color,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id) as total_emprunts,
                       (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id AND e.statut = 'en_cours') as emprunts_actifs,
                       (SELECT COUNT(*) FROM reservations r WHERE r.livre_id = l.id AND r.statut = 'active') as reservations_actives
                FROM livres l
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                LEFT JOIN users u ON l.created_by = u.id
                WHERE l.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        // Vérifier s'il y a des emprunts en cours
        $check_stmt = $this->db->prepare("SELECT COUNT(*) FROM emprunts WHERE livre_id = ? AND statut = 'en_cours'");
        $check_stmt->execute([$id]);
        if ($check_stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => "Impossible de supprimer : ce livre a des emprunts en cours."
            ];
        }

        $stmt = $this->db->prepare("DELETE FROM livres WHERE id = ?");
        $ok = $stmt->execute([$id]);
        
        return [
            'success' => $ok,
            'message' => $ok ? "Livre supprimé avec succès." : "Erreur lors de la suppression."
        ];
    }

    public function getStats()
    {
        $stats = [];
        
        // Total livres actifs
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM livres WHERE statut = 'actif'");
        $stmt->execute();
        $stats['total_livres'] = $stmt->fetch()['total'];

        // Livres disponibles
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM livres WHERE quantite_disponible > 0 AND statut = 'actif'");
        $stmt->execute();
        $stats['disponibles'] = $stmt->fetch()['total'];

        // Livres empruntés
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM livres WHERE quantite_empruntee > 0 AND statut = 'actif'");
        $stmt->execute();
        $stats['empruntes'] = $stmt->fetch()['total'];

        // Nouveaux livres ce mois
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM livres WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute();
        $stats['nouveaux_mois'] = $stmt->fetch()['total'];

        return $stats;
    }

    public function generateCodeLivre()
    {
        $year = date('Y');
        $stmt = $this->db->prepare("SELECT MAX(CAST(SUBSTRING(code_livre, 6) AS UNSIGNED)) as max_num FROM livres WHERE code_livre LIKE ?");
        $stmt->execute(['LIV' . $year . '%']);
        $result = $stmt->fetch();
        $next_num = ($result['max_num'] ?? 0) + 1;
        
        return 'LIV' . $year . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    }

    public function getCategories()
    {
        $stmt = $this->db->prepare("SELECT * FROM categories_livres ORDER BY nom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmpruntsHistory($id)
    {
        $sql = "SELECT e.*, 
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom,
                       a.numero_abonne,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM emprunts e
                LEFT JOIN abonnes a ON e.abonne_id = a.id
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.livre_id = ?
                ORDER BY e.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new LivreController();

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

        case 'generate_code':
            $code = $controller->generateCodeLivre();
            echo json_encode(['code' => $code]);
            break;
    }
    exit;
}
?>