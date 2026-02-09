<?php
require_once(__DIR__ . '/../../config/config.php');

requireLogin();

class CategoriesLivresController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function index($filters = [], $page = 1, $limit = 20, $sort = 'nom', $order = 'ASC')
    {
        $where = "WHERE 1=1";
        $params = [];
        $offset = ($page - 1) * $limit;

        // Filtres
        if (!empty($filters['search'])) {
            $where .= " AND (c.nom LIKE ? OR c.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['age_min'])) {
            $where .= " AND c.age_minimum >= ?";
            $params[] = $filters['age_min'];
        }

        if (!empty($filters['age_max'])) {
            $where .= " AND c.age_maximum <= ?";
            $params[] = $filters['age_max'];
        }

        // Validation du tri
        $allowed_sorts = ['nom', 'description', 'livres_count', 'age_minimum', 'age_maximum', 'created_at'];
        if (!in_array($sort, $allowed_sorts)) $sort = 'nom';
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) $order = 'ASC';

        // Compter le total
        $count_sql = "SELECT COUNT(*) as total FROM categories_livres c $where";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];

        // Récupérer les données avec pagination
        $sql = "SELECT c.*, 
                       COUNT(l.id) as livres_count
                FROM categories_livres c
                LEFT JOIN livres l ON c.id = l.categorie_id AND l.statut = 'actif'
                $where
                GROUP BY c.id
                ORDER BY $sort $order
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $categories,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function create($data)
    {
        // Vérifier si le nom existe déjà
        $check_stmt = $this->db->prepare("SELECT id FROM categories_livres WHERE nom = ?");
        $check_stmt->execute([$data['nom']]);
        if ($check_stmt->fetch()) {
            return [
                'success' => false,
                'message' => "Cette catégorie existe déjà."
            ];
        }

        $sql = "INSERT INTO categories_livres (nom, description, age_minimum, age_maximum, color) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['nom'],
            $data['description'] ?? '',
            $data['age_minimum'] ?? 3,
            $data['age_maximum'] ?? 18,
            $data['color'] ?? '#3b82f6'
        ]);

        if ($ok) {
            return [
                'success' => true,
                'message' => "Catégorie créée avec succès.",
                'categorie_id' => $this->db->lastInsertId()
            ];
        }

        return [
            'success' => false,
            'message' => "Erreur lors de la création de la catégorie."
        ];
    }

    public function update($id, $data)
    {
        // Vérifier si le nom existe déjà (autre que cette catégorie)
        $check_stmt = $this->db->prepare("SELECT id FROM categories_livres WHERE nom = ? AND id != ?");
        $check_stmt->execute([$data['nom'], $id]);
        if ($check_stmt->fetch()) {
            return [
                'success' => false,
                'message' => "Cette catégorie existe déjà."
            ];
        }

        $sql = "UPDATE categories_livres SET nom = ?, description = ?, age_minimum = ?, age_maximum = ?, color = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            $data['nom'],
            $data['description'] ?? '',
            $data['age_minimum'] ?? 3,
            $data['age_maximum'] ?? 18,
            $data['color'] ?? '#3b82f6',
            $id
        ]);

        return [
            'success' => $ok,
            'message' => $ok ? "Catégorie modifiée avec succès." : "Erreur lors de la modification."
        ];
    }

    public function delete($id)
    {
        // Vérifier s'il y a des livres dans cette catégorie
        $check_stmt = $this->db->prepare("SELECT COUNT(*) FROM livres WHERE categorie_id = ?");
        $check_stmt->execute([$id]);
        if ($check_stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => "Impossible de supprimer : des livres sont associés à cette catégorie."
            ];
        }

        $stmt = $this->db->prepare("DELETE FROM categories_livres WHERE id = ?");
        $ok = $stmt->execute([$id]);
        
        return [
            'success' => $ok,
            'message' => $ok ? "Catégorie supprimée avec succès." : "Erreur lors de la suppression."
        ];
    }

    public function find($id)
    {
        $sql = "SELECT c.*, COUNT(l.id) as livres_count
                FROM categories_livres c
                LEFT JOIN livres l ON c.id = l.categorie_id AND l.statut = 'actif'
                WHERE c.id = ?
                GROUP BY c.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStats()
    {
        $stats = [];
        
        // Total catégories
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM categories_livres");
        $stmt->execute();
        $stats['total_categories'] = $stmt->fetch()['total'];

        // Catégorie avec le plus de livres
        $stmt = $this->db->prepare("
            SELECT c.nom, COUNT(l.id) as count 
            FROM categories_livres c 
            LEFT JOIN livres l ON c.id = l.categorie_id AND l.statut = 'actif'
            GROUP BY c.id 
            ORDER BY count DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $top_category = $stmt->fetch();
        $stats['top_category'] = $top_category ? $top_category['nom'] : 'Aucune';
        $stats['top_category_count'] = $top_category ? $top_category['count'] : 0;

        // Catégories vides
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM categories_livres c 
            LEFT JOIN livres l ON c.id = l.categorie_id AND l.statut = 'actif'
            WHERE l.id IS NULL
        ");
        $stmt->execute();
        $stats['empty_categories'] = $stmt->fetch()['total'];

        // Tranche d'âge la plus populaire
        $stmt = $this->db->prepare("
            SELECT 
                CONCAT(age_minimum, '-', age_maximum, ' ans') as tranche,
                COUNT(l.id) as count
            FROM categories_livres c
            LEFT JOIN livres l ON c.id = l.categorie_id AND l.statut = 'actif'
            GROUP BY c.age_minimum, c.age_maximum
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->execute();
        $top_age = $stmt->fetch();
        $stats['top_age_range'] = $top_age ? $top_age['tranche'] : 'Aucune';

        return $stats;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new CategoriesLivresController();

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
    }
    exit;
}
?>