<?php
require_once(__DIR__ . '/../../config/config.php');

requireLogin();

class EmpruntController
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
            $where .= " AND (l.titre LIKE ? OR a.nom LIKE ? OR a.prenom LIKE ? OR a.numero_abonne LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['statut'])) {
            $where .= " AND e.statut = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['abonne_id'])) {
            $where .= " AND e.abonne_id = ?";
            $params[] = $filters['abonne_id'];
        }

        if (!empty($filters['livre_id'])) {
            $where .= " AND e.livre_id = ?";
            $params[] = $filters['livre_id'];
        }

        if (!empty($filters['date_debut'])) {
            $where .= " AND e.date_emprunt >= ?";
            $params[] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $where .= " AND e.date_emprunt <= ?";
            $params[] = $filters['date_fin'];
        }

        if (!empty($filters['en_retard'])) {
            $where .= " AND e.statut = 'en_cours' AND e.date_retour_prevue < CURDATE()";
        }

        // Validation du tri
        $allowed_sorts = ['date_emprunt', 'date_retour_prevue', 'statut', 'created_at'];
        if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) $order = 'DESC';

        // Compter le total
        $count_sql = "SELECT COUNT(*) as total FROM emprunts e 
                      LEFT JOIN livres l ON e.livre_id = l.id 
                      LEFT JOIN abonnes a ON e.abonne_id = a.id $where";
        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];

        // Récupérer les données avec pagination
        $sql = "SELECT e.*, 
                       l.titre as livre_titre, l.code_livre, l.auteur,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom, a.numero_abonne, a.classe,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       CONCAT(p.first_name, ' ', p.last_name) as processed_by_name,
                       DATEDIFF(CURDATE(), e.date_retour_prevue) as jours_retard
                FROM emprunts e
                LEFT JOIN livres l ON e.livre_id = l.id
                LEFT JOIN abonnes a ON e.abonne_id = a.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN users p ON e.processed_by = p.id
                $where
                ORDER BY e.$sort $order
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $emprunts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $emprunts,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    public function create($data)
    {
        // Vérifier la disponibilité du livre
        $livre_stmt = $this->db->prepare("SELECT quantite_disponible FROM livres WHERE id = ? AND statut = 'actif'");
        $livre_stmt->execute([$data['livre_id']]);
        $livre = $livre_stmt->fetch();
        
        if (!$livre || $livre['quantite_disponible'] < 1) {
            return [
                'success' => false,
                'message' => "Ce livre n'est pas disponible pour l'emprunt."
            ];
        }

        // Vérifier les limites de l'abonné
        $abonne_stmt = $this->db->prepare("SELECT nb_emprunts_max, nb_emprunts_actuel, statut FROM abonnes WHERE id = ?");
        $abonne_stmt->execute([$data['abonne_id']]);
        $abonne = $abonne_stmt->fetch();
        
        if (!$abonne || $abonne['statut'] !== 'actif') {
            return [
                'success' => false,
                'message' => "Cet abonné n'est pas autorisé à emprunter."
            ];
        }

        if ($abonne['nb_emprunts_actuel'] >= $abonne['nb_emprunts_max']) {
            return [
                'success' => false,
                'message' => "L'abonné a atteint sa limite d'emprunts simultanés."
            ];
        }

        // Calculer la date de retour prévue
        $duree_jours = $data['duree_jours'] ?? 14;
        $date_retour_prevue = date('Y-m-d', strtotime($data['date_emprunt'] . ' +' . $duree_jours . ' days'));

        $this->db->beginTransaction();

        try {
            // Insérer l'emprunt
            $sql = "INSERT INTO emprunts (
                        livre_id, abonne_id, date_emprunt, date_retour_prevue, duree_jours,
                        quantite, etat_livre_emprunt, observations_emprunt, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['livre_id'],
                $data['abonne_id'],
                $data['date_emprunt'],
                $date_retour_prevue,
                $duree_jours,
                1, // quantite par défaut
                $data['etat_livre_emprunt'] ?? 'bon',
                $data['observations_emprunt'] ?? '',
                $_SESSION['user_id']
            ]);

            // Mettre à jour le nombre d'emprunts de l'abonné
            $update_abonne = $this->db->prepare("UPDATE abonnes SET nb_emprunts_actuel = nb_emprunts_actuel + 1 WHERE id = ?");
            $update_abonne->execute([$data['abonne_id']]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Emprunt créé avec succès.",
                'emprunt_id' => $this->db->lastInsertId()
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => "Erreur lors de la création de l'emprunt: " . $e->getMessage()
            ];
        }
    }

    public function returnBook($id, $data)
    {
        // Vérifier que l'emprunt existe et est en cours
        $emprunt_stmt = $this->db->prepare("SELECT * FROM emprunts WHERE id = ? AND statut = 'en_cours'");
        $emprunt_stmt->execute([$id]);
        $emprunt = $emprunt_stmt->fetch();

        if (!$emprunt) {
            return [
                'success' => false,
                'message' => "Emprunt introuvable ou déjà retourné."
            ];
        }

        $this->db->beginTransaction();

        try {
            // Calculer les amendes en cas de retard
            $amende = 0;
            $jours_retard = 0;
            $date_retour = $data['date_retour_effective'] ?? date('Y-m-d');
            
            if ($date_retour > $emprunt['date_retour_prevue']) {
                $jours_retard = (strtotime($date_retour) - strtotime($emprunt['date_retour_prevue'])) / (24 * 60 * 60);
                $amende = $jours_retard * 100; // 100 FC par jour de retard
            }

            // Mettre à jour l'emprunt
            $sql = "UPDATE emprunts SET 
                        date_retour_effective = ?, 
                        etat_livre_retour = ?, 
                        observations_retour = ?, 
                        statut = 'rendu', 
                        amende = ?, 
                        processed_by = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $date_retour,
                $data['etat_livre_retour'] ?? $emprunt['etat_livre_emprunt'],
                $data['observations_retour'] ?? '',
                $amende,
                $_SESSION['user_id'],
                $id
            ]);

            // Mettre à jour le nombre d'emprunts de l'abonné
            $update_abonne = $this->db->prepare("UPDATE abonnes SET nb_emprunts_actuel = nb_emprunts_actuel - 1 WHERE id = ?");
            $update_abonne->execute([$emprunt['abonne_id']]);

            // Créer une amende si nécessaire
            if ($amende > 0) {
                $amende_sql = "INSERT INTO amendes_pertes (abonne_id, emprunt_id, type, montant, description, date_amende, created_by) 
                              VALUES (?, ?, 'retard', ?, ?, ?, ?)";
                $amende_stmt = $this->db->prepare($amende_sql);
                $amende_stmt->execute([
                    $emprunt['abonne_id'],
                    $id,
                    $amende,
                    "Retard de $jours_retard jour(s)",
                    $date_retour,
                    $_SESSION['user_id']
                ]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Livre retourné avec succès." . ($amende > 0 ? " Amende: $amende FC" : "")
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => "Erreur lors du retour: " . $e->getMessage()
            ];
        }
    }

    public function renew($id)
    {
        // Vérifier l'emprunt
        $emprunt_stmt = $this->db->prepare("SELECT * FROM emprunts WHERE id = ? AND statut = 'en_cours'");
        $emprunt_stmt->execute([$id]);
        $emprunt = $emprunt_stmt->fetch();

        if (!$emprunt) {
            return [
                'success' => false,
                'message' => "Emprunt introuvable ou déjà retourné."
            ];
        }

        if ($emprunt['nb_renouvellements'] >= $emprunt['max_renouvellements']) {
            return [
                'success' => false,
                'message' => "Nombre maximum de renouvellements atteint."
            ];
        }

        // Nouvelle date de retour (+ 14 jours)
        $nouvelle_date = date('Y-m-d', strtotime($emprunt['date_retour_prevue'] . ' +14 days'));

        $sql = "UPDATE emprunts SET 
                    date_retour_prevue = ?, 
                    nb_renouvellements = nb_renouvellements + 1
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([$nouvelle_date, $id]);

        return [
            'success' => $ok,
            'message' => $ok ? "Emprunt renouvelé jusqu'au " . date('d/m/Y', strtotime($nouvelle_date)) : "Erreur lors du renouvellement."
        ];
    }

    public function find($id)
    {
        $sql = "SELECT e.*, 
                       l.titre as livre_titre, l.code_livre, l.auteur, l.photo as livre_photo,
                       CONCAT(a.nom, ' ', a.prenom) as abonne_nom, a.numero_abonne, a.classe, a.telephone_parent,
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       CONCAT(p.first_name, ' ', p.last_name) as processed_by_name,
                       DATEDIFF(CURDATE(), e.date_retour_prevue) as jours_retard
                FROM emprunts e
                LEFT JOIN livres l ON e.livre_id = l.id
                LEFT JOIN abonnes a ON e.abonne_id = a.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN users p ON e.processed_by = p.id
                WHERE e.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStats()
    {
        $stats = [];
        
        // Total emprunts en cours
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM emprunts WHERE statut = 'en_cours'");
        $stmt->execute();
        $stats['en_cours'] = $stmt->fetch()['total'];

        // Emprunts en retard
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM emprunts WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()");
        $stmt->execute();
        $stats['en_retard'] = $stmt->fetch()['total'];

        // Nouveaux emprunts ce mois
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM emprunts WHERE DATE_FORMAT(date_emprunt, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute();
        $stats['nouveaux_mois'] = $stmt->fetch()['total'];

        // Retours ce mois
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM emprunts WHERE statut = 'rendu' AND DATE_FORMAT(date_retour_effective, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute();
        $stats['retours_mois'] = $stmt->fetch()['total'];

        return $stats;
    }

    public function getAvailableBooks()
    {
        $sql = "SELECT l.id, l.titre, l.code_livre, l.auteur, l.quantite_disponible, c.nom as categorie
                FROM livres l
                LEFT JOIN categories_livres c ON l.categorie_id = c.id
                WHERE l.statut = 'actif' AND l.quantite_disponible > 0
                ORDER BY l.titre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveMembers()
    {
        $sql = "SELECT id, CONCAT(nom, ' ', prenom) as nom_complet, numero_abonne, classe, nb_emprunts_actuel, nb_emprunts_max
                FROM abonnes 
                WHERE statut = 'actif' AND date_expiration >= CURDATE()
                ORDER BY nom, prenom";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new EmpruntController();

    switch ($_POST['action']) {
        case 'create':
            $result = $controller->create($_POST);
            echo json_encode($result);
            break;

        case 'return':
            $result = $controller->returnBook($_POST['id'], $_POST);
            echo json_encode($result);
            break;

        case 'renew':
            $result = $controller->renew($_POST['id']);
            echo json_encode($result);
            break;
    }
    exit;
}
?>