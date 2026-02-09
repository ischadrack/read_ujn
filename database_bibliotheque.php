<?php
class BibliothequeDatabase
{
    private $host = 'localhost';
    private $db_name = 'afriory_librairy';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            exit;
        }

        return $this->conn;
    }

    public function createTables()
    {
        try {
            // Table USERS (gestionnaires et administrateurs)
            $users_table = "CREATE TABLE IF NOT EXISTS users (
                id INT NOT NULL AUTO_INCREMENT,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                photo VARCHAR(255) DEFAULT '',
                role ENUM('admin','bibliothecaire','assistant') DEFAULT 'bibliothecaire',
                status ENUM('active','inactive') DEFAULT 'active',
                telephone VARCHAR(20) DEFAULT '',
                specialite VARCHAR(200) DEFAULT '',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX idx_email (email),
                INDEX idx_username (username),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($users_table);

            // Table CATEGORIES_LIVRES
            $categories_table = "CREATE TABLE IF NOT EXISTS categories_livres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                description TEXT,
                age_minimum INT DEFAULT 3,
                age_maximum INT DEFAULT 18,
                color VARCHAR(7) DEFAULT '#3b82f6',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_nom (nom)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($categories_table);

            // Table LIVRES
            $livres_table = "CREATE TABLE IF NOT EXISTS livres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titre VARCHAR(255) NOT NULL,
                code_livre VARCHAR(100) UNIQUE NOT NULL,
                auteur VARCHAR(255) DEFAULT '',
                editeur VARCHAR(255) DEFAULT '',
                isbn VARCHAR(50) DEFAULT '',
                categorie_id INT NOT NULL,
                niveau_classe VARCHAR(100) DEFAULT '',
                description TEXT,
                nombre_pages INT DEFAULT 0,
                langue VARCHAR(50) DEFAULT 'Français',
                annee_publication YEAR DEFAULT NULL,
                quantite_stock INT DEFAULT 1,
                quantite_disponible INT DEFAULT 1,
                quantite_empruntee INT DEFAULT 0,
                quantite_perdue INT DEFAULT 0,
                prix_unitaire DECIMAL(10,2) DEFAULT 0,
                etat ENUM('neuf','bon','use','deteriore') DEFAULT 'bon',
                statut ENUM('actif','inactif','archive') DEFAULT 'actif',
                date_acquisition DATE,
                photo VARCHAR(255) DEFAULT '',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (categorie_id) REFERENCES categories_livres(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_titre (titre),
                INDEX idx_code (code_livre),
                INDEX idx_auteur (auteur),
                INDEX idx_statut (statut),
                INDEX idx_disponible (quantite_disponible)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($livres_table);

            // Table ABONNES
            $abonnes_table = "CREATE TABLE IF NOT EXISTS abonnes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                numero_abonne VARCHAR(50) UNIQUE NOT NULL,
                nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100) NOT NULL,
                date_naissance DATE,
                sexe ENUM('M','F') NOT NULL,
                classe VARCHAR(50) DEFAULT '',
                niveau ENUM('maternelle','primaire','secondaire') DEFAULT 'primaire',
                nom_parent VARCHAR(200) DEFAULT '',
                telephone_parent VARCHAR(20) DEFAULT '',
                email_parent VARCHAR(150) DEFAULT '',
                adresse TEXT DEFAULT '',
                photo VARCHAR(255) DEFAULT '',
                date_inscription DATE NOT NULL,
                date_expiration DATE NOT NULL,
                statut ENUM('actif','suspendu','expire','archive') DEFAULT 'actif',
                nb_emprunts_max INT DEFAULT 3,
                nb_emprunts_actuel INT DEFAULT 0,
                total_amendes DECIMAL(10,2) DEFAULT 0,
                notes TEXT DEFAULT '',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_numero (numero_abonne),
                INDEX idx_nom (nom, prenom),
                INDEX idx_classe (classe),
                INDEX idx_statut (statut),
                INDEX idx_parent (nom_parent)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($abonnes_table);

            // Table EMPRUNTS
            $emprunts_table = "CREATE TABLE IF NOT EXISTS emprunts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                livre_id INT NOT NULL,
                abonne_id INT NOT NULL,
                date_emprunt DATE NOT NULL,
                date_retour_prevue DATE NOT NULL,
                date_retour_effective DATE DEFAULT NULL,
                duree_jours INT DEFAULT 14,
                quantite INT DEFAULT 1,
                etat_livre_emprunt ENUM('neuf','bon','use','deteriore') DEFAULT 'bon',
                etat_livre_retour ENUM('neuf','bon','use','deteriore') DEFAULT NULL,
                statut ENUM('en_cours','rendu','en_retard','perdu','deteriore') DEFAULT 'en_cours',
                nb_renouvellements INT DEFAULT 0,
                max_renouvellements INT DEFAULT 2,
                observations_emprunt TEXT DEFAULT '',
                observations_retour TEXT DEFAULT '',
                amende DECIMAL(10,2) DEFAULT 0,
                created_by INT NOT NULL,
                processed_by INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
                FOREIGN KEY (abonne_id) REFERENCES abonnes(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_livre (livre_id),
                INDEX idx_abonne (abonne_id),
                INDEX idx_dates (date_emprunt, date_retour_prevue),
                INDEX idx_statut (statut),
                INDEX idx_retard (date_retour_prevue, statut)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($emprunts_table);

            // Table AMENDES_PERTES
            $amendes_table = "CREATE TABLE IF NOT EXISTS amendes_pertes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                abonne_id INT NOT NULL,
                emprunt_id INT DEFAULT NULL,
                livre_id INT DEFAULT NULL,
                type ENUM('retard','perte','deterioration','autre') NOT NULL,
                montant DECIMAL(10,2) NOT NULL DEFAULT 0,
                description TEXT DEFAULT '',
                statut ENUM('impayee','payee','annulee','remise') DEFAULT 'impayee',
                date_amende DATE NOT NULL,
                date_paiement DATE DEFAULT NULL,
                mode_paiement VARCHAR(50) DEFAULT '',
                recu_numero VARCHAR(100) DEFAULT '',
                created_by INT NOT NULL,
                processed_by INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (abonne_id) REFERENCES abonnes(id) ON DELETE CASCADE,
                FOREIGN KEY (emprunt_id) REFERENCES emprunts(id) ON DELETE CASCADE,
                FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_abonne (abonne_id),
                INDEX idx_type (type),
                INDEX idx_statut (statut),
                INDEX idx_date (date_amende)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($amendes_table);

            // Table RESERVATIONS
            $reservations_table = "CREATE TABLE IF NOT EXISTS reservations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                livre_id INT NOT NULL,
                abonne_id INT NOT NULL,
                date_reservation DATE NOT NULL,
                date_expiration DATE NOT NULL,
                statut ENUM('active','satisfaite','expiree','annulee') DEFAULT 'active',
                priorite INT DEFAULT 1,
                notes TEXT DEFAULT '',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
                FOREIGN KEY (abonne_id) REFERENCES abonnes(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_livre (livre_id),
                INDEX idx_abonne (abonne_id),
                INDEX idx_statut (statut),
                INDEX idx_priorite (priorite, date_reservation)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($reservations_table);

            // Table NOTIFICATIONS
            $notifications_table = "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                destinataire_type ENUM('user','abonne','parent') NOT NULL,
                destinataire_id INT NOT NULL,
                type ENUM('retard','reservation','rappel','amende','info') DEFAULT 'info',
                titre VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                date_envoi DATE DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_destinataire (destinataire_type, destinataire_id),
                INDEX idx_type (type),
                INDEX idx_read (is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->conn->exec($notifications_table);

            // Triggers pour mettre à jour les quantités
            $trigger_emprunt = "CREATE TRIGGER IF NOT EXISTS update_livre_quantities_emprunt 
                               AFTER INSERT ON emprunts 
                               FOR EACH ROW 
                               UPDATE livres 
                               SET quantite_disponible = quantite_disponible - NEW.quantite,
                                   quantite_empruntee = quantite_empruntee + NEW.quantite 
                               WHERE id = NEW.livre_id";
            $this->conn->exec($trigger_emprunt);

            $trigger_retour = "CREATE TRIGGER IF NOT EXISTS update_livre_quantities_retour 
                              AFTER UPDATE ON emprunts 
                              FOR EACH ROW 
                              BEGIN
                                IF NEW.statut = 'rendu' AND OLD.statut != 'rendu' THEN
                                  UPDATE livres 
                                  SET quantite_disponible = quantite_disponible + NEW.quantite,
                                      quantite_empruntee = quantite_empruntee - NEW.quantite 
                                  WHERE id = NEW.livre_id;
                                ELSEIF NEW.statut = 'perdu' AND OLD.statut != 'perdu' THEN
                                  UPDATE livres 
                                  SET quantite_empruntee = quantite_empruntee - NEW.quantite,
                                      quantite_perdue = quantite_perdue + NEW.quantite 
                                  WHERE id = NEW.livre_id;
                                END IF;
                              END";
            $this->conn->exec($trigger_retour);

            // Insert default admin user
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admin_count = $stmt->fetchColumn();

            if ($admin_count == 0) {
                $admin_insert = "INSERT INTO users (username, email, password, first_name, last_name, role) 
                                VALUES ('admin', 'admin@unjournouveau.cd', ?, 'Admin', 'Bibliothèque', 'admin')";
                $stmt = $this->conn->prepare($admin_insert);
                $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
            }

            // Insert default categories
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM categories_livres");
            $stmt->execute();
            $cat_count = $stmt->fetchColumn();

            if ($cat_count == 0) {
                $categories = [
                    ['Contes et Histoires', 'Contes, fables et histoires pour enfants', 3, 12, '#f59e0b'],
                    ['Livres Éducatifs', 'Manuels scolaires et livres pédagogiques', 5, 18, '#3b82f6'],
                    ['Bandes Dessinées', 'BD et romans graphiques', 6, 16, '#8b5cf6'],
                    ['Romans Jeunesse', 'Romans adaptés aux jeunes lecteurs', 8, 18, '#10b981'],
                    ['Documentaires', 'Livres documentaires et encyclopédies', 7, 18, '#ef4444'],
                    ['Poésie', 'Recueils de poésie et comptines', 4, 15, '#f97316']
                ];

                $stmt = $this->conn->prepare("INSERT INTO categories_livres (nom, description, age_minimum, age_maximum, color) VALUES (?, ?, ?, ?, ?)");
                foreach ($categories as $cat) {
                    $stmt->execute($cat);
                }
            }

        } catch (PDOException $exception) {
            echo "Error creating tables: " . $exception->getMessage();
        }
    }
}
?>