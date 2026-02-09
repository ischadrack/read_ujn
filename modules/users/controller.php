<?php
require_once '../../../config/config.php';

class UserController {
    private $db;
    
    public function __construct() {
        $database = new BibliothequeDatabase();
        $this->db = $database->getConnection();
    }
    
    public function index($filters = [], $page = 1, $limit = 20, $sort = 'created_at', $order = 'DESC') {
        $where_conditions = [];
        $params = [];
        
        // Filtres
        if (!empty($filters['search'])) {
            $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['role'])) {
            $where_conditions[] = "role = ?";
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Compter le total
        $count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Récupérer les données avec gestion sécurisée des requêtes
        $offset = ($page - 1) * $limit;
        
        // Validation des paramètres de tri
        $allowed_sorts = ['created_at', 'last_name', 'email', 'role', 'status'];
        $sort = in_array($sort, $allowed_sorts) ? $sort : 'created_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * 
                FROM users 
                $where_clause 
                ORDER BY $sort $order 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        return [
            'data' => $users,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    public function create($data) {
        try {
            // Validation des données
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                throw new Exception("Les champs nom d'utilisateur, email et mot de passe sont obligatoires");
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("L'adresse email n'est pas valide");
            }
            
            if (strlen($data['password']) < 6) {
                throw new Exception("Le mot de passe doit contenir au moins 6 caractères");
            }
            
            // Vérifier si l'email ou le nom d'utilisateur existe déjà
            $check_stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check_stmt->execute([$data['email'], $data['username']]);
            if ($check_stmt->fetch()) {
                throw new Exception("L'email ou le nom d'utilisateur existe déjà");
            }
            
            $sql = "INSERT INTO users (username, email, password, first_name, last_name, role, status, telephone, specialite, photo, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['first_name'],
                $data['last_name'],
                $data['role'] ?? 'bibliothecaire',
                $data['status'] ?? 'active',
                $data['telephone'] ?? '',
                $data['specialite'] ?? '',
                $data['photo'] ?? ''
            ]);
            
            if ($result) {
                $new_user_id = $this->db->lastInsertId();
                return ['success' => true, 'message' => 'Utilisateur créé avec succès', 'id' => $new_user_id];
            }
            
            throw new Exception("Erreur lors de la création");
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            // Validation des données
            if (empty($data['username']) || empty($data['email'])) {
                throw new Exception("Les champs nom d'utilisateur et email sont obligatoires");
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("L'adresse email n'est pas valide");
            }
            
            // Vérifier si l'email ou le nom d'utilisateur existe déjà (sauf pour cet utilisateur)
            $check_stmt = $this->db->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $check_stmt->execute([$data['email'], $data['username'], $id]);
            if ($check_stmt->fetch()) {
                throw new Exception("L'email ou le nom d'utilisateur existe déjà");
            }
            
            $sql = "UPDATE users SET 
                    username = ?, email = ?, first_name = ?, last_name = ?, 
                    role = ?, status = ?, telephone = ?, specialite = ?";
            
            $params = [
                $data['username'],
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $data['role'],
                $data['status'],
                $data['telephone'] ?? '',
                $data['specialite'] ?? ''
            ];
            
            // Ajouter le mot de passe s'il est fourni
            if (!empty($data['password'])) {
                if (strlen($data['password']) < 6) {
                    throw new Exception("Le mot de passe doit contenir au moins 6 caractères");
                }
                $sql .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Ajouter la photo si fournie
            if (isset($data['photo'])) {
                $sql .= ", photo = ?";
                $params[] = $data['photo'];
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return ['success' => true, 'message' => 'Utilisateur modifié avec succès'];
            }
            
            throw new Exception("Erreur lors de la modification");
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function delete($id) {
        try {
            // Empêcher la suppression de son propre compte
            if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
                throw new Exception("Vous ne pouvez pas supprimer votre propre compte");
            }
            
            // Vérifier s'il y a des relations avec d'autres tables
            $check_stmt = $this->db->prepare("SELECT COUNT(*) as count FROM emprunts WHERE created_by = ?");
            $check_stmt->execute([$id]);
            $emprunts_count = $check_stmt->fetch()['count'];
            
            if ($emprunts_count > 0) {
                throw new Exception("Impossible de supprimer cet utilisateur car il a des emprunts associés");
            }
            
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Utilisateur supprimé avec succès'];
            }
            
            throw new Exception("Erreur lors de la suppression");
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getStats() {
        $stats = [];
        
        // Total utilisateurs
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch()['total'];
        
        // Utilisateurs actifs
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
        $stmt->execute();
        $stats['users_actifs'] = $stmt->fetch()['total'];
        
        // Nouveaux utilisateurs ce mois
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
        $stmt->execute();
        $stats['nouveaux_mois'] = $stmt->fetch()['total'];
        
        // Administrateurs
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
        $stmt->execute();
        $stats['admins'] = $stmt->fetch()['total'];
        
        return $stats;
    }

    public function getUserActivities($user_id, $limit = 10) {
        // Récupérer les activités depuis les tables existantes
        $activities = [];
        
        // Activités des emprunts créés
        $stmt = $this->db->prepare("
            SELECT 'create_emprunt' as action, 
                   CONCAT('Création d\\'emprunt pour l\\'abonné ID: ', abonne_id) as description,
                   created_at
            FROM emprunts 
            WHERE created_by = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $emprunts_activities = $stmt->fetchAll();
        
        $activities = array_merge($activities, $emprunts_activities);
        
        // Trier par date
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    public function getUserStatistics($user_id) {
        $stats = [
            'total_connexions' => 0,
            'derniere_connexion' => null,
            'emprunts_crees' => 0,
            'retours_traites' => 0
        ];
        
        // Compter les emprunts créés par cet utilisateur
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM emprunts WHERE created_by = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        $stats['emprunts_crees'] = $result['count'];
        
        // Compter les retours traités par cet utilisateur
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM emprunts WHERE processed_by = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        $stats['retours_traites'] = $result['count'];
        
        // Récupérer la dernière connexion
        $stmt = $this->db->prepare("SELECT last_login FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        $stats['derniere_connexion'] = $result['last_login'];
        
        return $stats;
    }

    public function uploadPhoto($file) {
        try {
            $upload_dir = '../../../assets/uploads/users/';
            
            // Créer le dossier s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Vérifier le type de fichier
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("Type de fichier non autorisé. Seuls les formats JPG, PNG et GIF sont acceptés.");
            }
            
            // Vérifier la taille (max 2MB)
            if ($file['size'] > 2097152) {
                throw new Exception("La taille du fichier ne doit pas dépasser 2MB.");
            }
            
            // Générer un nom unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            // Déplacer le fichier
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => true, 'filename' => $filename];
            } else {
                throw new Exception("Erreur lors de l'upload du fichier.");
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Traitement des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }
    
    $controller = new UserController();
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    
    switch ($_POST['action']) {
        case 'delete':
            if (isset($_POST['id'])) {
                $response = $controller->delete($_POST['id']);
            }
            break;
            
        case 'toggle_status':
            if (isset($_POST['id']) && isset($_POST['status'])) {
                $user = $controller->getById($_POST['id']);
                if ($user) {
                    $new_status = $_POST['status'] === 'active' ? 'inactive' : 'active';
                    $user_data = $user;
                    $user_data['status'] = $new_status;
                    $response = $controller->update($_POST['id'], $user_data);
                }
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>