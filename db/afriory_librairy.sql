-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 16 déc. 2025 à 11:08
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `afriory_librairy`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnes`
--

DROP TABLE IF EXISTS `abonnes`;
CREATE TABLE IF NOT EXISTS `abonnes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_abonne` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `sexe` enum('M','F') COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `niveau` enum('maternelle','primaire','secondaire') COLLATE utf8mb4_unicode_ci DEFAULT 'primaire',
  `nom_parent` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `telephone_parent` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `email_parent` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `date_inscription` date NOT NULL,
  `date_expiration` date NOT NULL,
  `statut` enum('actif','suspendu','expire','archive') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `nb_emprunts_max` int DEFAULT '3',
  `nb_emprunts_actuel` int DEFAULT '0',
  `total_amendes` decimal(10,2) DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_abonne` (`numero_abonne`),
  KEY `created_by` (`created_by`),
  KEY `idx_numero` (`numero_abonne`),
  KEY `idx_nom` (`nom`,`prenom`),
  KEY `idx_classe` (`classe`),
  KEY `idx_statut` (`statut`),
  KEY `idx_parent` (`nom_parent`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `abonnes`
--

INSERT INTO `abonnes` (`id`, `numero_abonne`, `nom`, `prenom`, `date_naissance`, `sexe`, `classe`, `niveau`, `nom_parent`, `telephone_parent`, `email_parent`, `adresse`, `photo`, `date_inscription`, `date_expiration`, `statut`, `nb_emprunts_max`, `nb_emprunts_actuel`, `total_amendes`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '20250001', 'NDANZE', 'RACHEL', '2025-09-11', 'M', '6ème', 'primaire', 'RACHEL NDANZE', '+24366464646', 'isj@gmail.com', 'GOMA', '', '2025-09-24', '2026-06-20', 'actif', 2, 2, 5.00, 'fff', 1, '2025-09-24 08:50:11', '2025-09-26 13:47:24');

-- --------------------------------------------------------

--
-- Structure de la table `amendes_pertes`
--

DROP TABLE IF EXISTS `amendes_pertes`;
CREATE TABLE IF NOT EXISTS `amendes_pertes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `abonne_id` int NOT NULL,
  `emprunt_id` int DEFAULT NULL,
  `livre_id` int DEFAULT NULL,
  `type` enum('retard','perte','deterioration','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('impayee','payee','annulee','remise') COLLATE utf8mb4_unicode_ci DEFAULT 'impayee',
  `date_amende` date NOT NULL,
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `recu_numero` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_by` int NOT NULL,
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `emprunt_id` (`emprunt_id`),
  KEY `livre_id` (`livre_id`),
  KEY `created_by` (`created_by`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_abonne` (`abonne_id`),
  KEY `idx_type` (`type`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date` (`date_amende`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `amendes_pertes`
--

INSERT INTO `amendes_pertes` (`id`, `abonne_id`, `emprunt_id`, `livre_id`, `type`, `montant`, `description`, `statut`, `date_amende`, `date_paiement`, `mode_paiement`, `recu_numero`, `created_by`, `processed_by`, `created_at`, `updated_at`) VALUES
(8, 1, 3, 1, 'retard', 5.00, 'gfyjdfdcgfd', 'impayee', '2025-09-24', '0000-00-00', '', '', 1, 1, '2025-09-24 13:47:48', '2025-09-25 09:57:51');

-- --------------------------------------------------------

--
-- Structure de la table `categories_livres`
--

DROP TABLE IF EXISTS `categories_livres`;
CREATE TABLE IF NOT EXISTS `categories_livres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `age_minimum` int DEFAULT '3',
  `age_maximum` int DEFAULT '18',
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#3b82f6',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories_livres`
--

INSERT INTO `categories_livres` (`id`, `nom`, `description`, `age_minimum`, `age_maximum`, `color`, `created_at`) VALUES
(1, 'Contes et Histoires', 'Contes, fables et histoires pour enfants', 3, 12, '#f59e0b', '2025-09-24 08:46:22'),
(2, 'Livres Éducatifs', 'Manuels scolaires et livres pédagogiques', 5, 18, '#3b82f6', '2025-09-24 08:46:22'),
(3, 'Bandes Dessinées', 'BD et romans graphiques', 6, 16, '#ef4444', '2025-09-24 08:46:22'),
(4, 'Romans Jeunesse', 'Romans adaptés aux jeunes lecteurs', 8, 18, '#10b981', '2025-09-24 08:46:22'),
(5, 'Documentaires', 'Livres documentaires et encyclopédies', 7, 18, '#ef4444', '2025-09-24 08:46:22'),
(6, 'Poésie', 'Recueils de poésie et comptines', 4, 15, '#14b8a6', '2025-09-24 08:46:22');

-- --------------------------------------------------------

--
-- Structure de la table `emprunts`
--

DROP TABLE IF EXISTS `emprunts`;
CREATE TABLE IF NOT EXISTS `emprunts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `livre_id` int NOT NULL,
  `abonne_id` int NOT NULL,
  `date_emprunt` date NOT NULL,
  `date_retour_prevue` date NOT NULL,
  `date_retour_effective` date DEFAULT NULL,
  `duree_jours` int DEFAULT '14',
  `quantite` int DEFAULT '1',
  `etat_livre_emprunt` enum('neuf','bon','use','deteriore') COLLATE utf8mb4_unicode_ci DEFAULT 'bon',
  `etat_livre_retour` enum('neuf','bon','use','deteriore') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('en_cours','rendu','en_retard','perdu','deteriore') COLLATE utf8mb4_unicode_ci DEFAULT 'en_cours',
  `nb_renouvellements` int DEFAULT '0',
  `max_renouvellements` int DEFAULT '2',
  `observations_emprunt` text COLLATE utf8mb4_unicode_ci,
  `observations_retour` text COLLATE utf8mb4_unicode_ci,
  `amende` decimal(10,2) DEFAULT '0.00',
  `created_by` int NOT NULL,
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_livre` (`livre_id`),
  KEY `idx_abonne` (`abonne_id`),
  KEY `idx_dates` (`date_emprunt`,`date_retour_prevue`),
  KEY `idx_statut` (`statut`),
  KEY `idx_retard` (`date_retour_prevue`,`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `emprunts`
--

INSERT INTO `emprunts` (`id`, `livre_id`, `abonne_id`, `date_emprunt`, `date_retour_prevue`, `date_retour_effective`, `duree_jours`, `quantite`, `etat_livre_emprunt`, `etat_livre_retour`, `statut`, `nb_renouvellements`, `max_renouvellements`, `observations_emprunt`, `observations_retour`, `amende`, `created_by`, `processed_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-09-24', '2025-10-22', '2025-09-24', 14, 1, 'bon', 'bon', 'rendu', 1, 2, 'BVHFFF', 'FD', 0.00, 1, 1, '2025-09-24 09:26:27', '2025-09-24 09:27:20'),
(2, 1, 1, '2025-09-24', '2025-10-08', '2025-09-24', 14, 1, 'bon', 'bon', 'rendu', 0, 2, 'DDDFSDF', 'cfgggfd', 0.00, 1, 1, '2025-09-24 09:28:04', '2025-09-24 10:54:42'),
(3, 1, 1, '2025-09-24', '2025-10-22', NULL, 14, 1, 'bon', NULL, 'en_cours', 1, 2, 'fer', '', 0.00, 1, NULL, '2025-09-24 12:20:37', '2025-09-25 12:40:40'),
(4, 1, 1, '2025-09-24', '2025-10-08', NULL, 14, 1, 'bon', NULL, 'en_cours', 0, 2, 'gfghdghd', '', 0.00, 1, NULL, '2025-09-24 14:22:44', '2025-09-24 14:22:44');

--
-- Déclencheurs `emprunts`
--
DROP TRIGGER IF EXISTS `update_livre_quantities_emprunt`;
DELIMITER $$
CREATE TRIGGER `update_livre_quantities_emprunt` AFTER INSERT ON `emprunts` FOR EACH ROW UPDATE livres 
                               SET quantite_disponible = quantite_disponible - NEW.quantite,
                                   quantite_empruntee = quantite_empruntee + NEW.quantite 
                               WHERE id = NEW.livre_id
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_livre_quantities_retour`;
DELIMITER $$
CREATE TRIGGER `update_livre_quantities_retour` AFTER UPDATE ON `emprunts` FOR EACH ROW BEGIN
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
                              END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

DROP TABLE IF EXISTS `livres`;
CREATE TABLE IF NOT EXISTS `livres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_livre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auteur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `editeur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `isbn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `categorie_id` int NOT NULL,
  `niveau_classe` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci,
  `nombre_pages` int DEFAULT '0',
  `langue` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Français',
  `annee_publication` year DEFAULT NULL,
  `quantite_stock` int DEFAULT '1',
  `quantite_disponible` int DEFAULT '1',
  `quantite_empruntee` int DEFAULT '0',
  `quantite_perdue` int DEFAULT '0',
  `prix_unitaire` decimal(10,2) DEFAULT '0.00',
  `etat` enum('neuf','bon','use','deteriore') COLLATE utf8mb4_unicode_ci DEFAULT 'bon',
  `statut` enum('actif','inactif','archive') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `date_acquisition` date DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_livre` (`code_livre`),
  KEY `categorie_id` (`categorie_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_titre` (`titre`),
  KEY `idx_code` (`code_livre`),
  KEY `idx_auteur` (`auteur`),
  KEY `idx_statut` (`statut`),
  KEY `idx_disponible` (`quantite_disponible`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`id`, `titre`, `code_livre`, `auteur`, `editeur`, `isbn`, `categorie_id`, `niveau_classe`, `description`, `nombre_pages`, `langue`, `annee_publication`, `quantite_stock`, `quantite_disponible`, `quantite_empruntee`, `quantite_perdue`, `prix_unitaire`, `etat`, `statut`, `date_acquisition`, `photo`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'à nous l\'école', 'LIV20250001', 'JKS', 'YVES', 'RIEN', 3, '6ème', 'BVHGFYHG', 93, 'Français', '1907', 4, 2, 2, 0, 0.02, 'bon', 'actif', '2025-09-24', '', 1, '2025-09-24 09:25:32', '2025-09-26 13:53:54');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `destinataire_type` enum('user','abonne','parent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinataire_id` int NOT NULL,
  `type` enum('retard','reservation','rappel','amende','info') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `date_envoi` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_destinataire` (`destinataire_type`,`destinataire_id`),
  KEY `idx_type` (`type`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `livre_id` int NOT NULL,
  `abonne_id` int NOT NULL,
  `date_reservation` date NOT NULL,
  `date_expiration` date NOT NULL,
  `statut` enum('active','satisfaite','expiree','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `priorite` int DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_livre` (`livre_id`),
  KEY `idx_abonne` (`abonne_id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_priorite` (`priorite`,`date_reservation`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `livre_id`, `abonne_id`, `date_reservation`, `date_expiration`, `statut`, `priorite`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-09-24', '2025-10-08', 'annulee', 1, 'dsres', 1, '2025-09-24 13:49:58', '2025-09-24 13:51:37');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `role` enum('admin','bibliothecaire','assistant') COLLATE utf8mb4_unicode_ci DEFAULT 'bibliothecaire',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `specialite` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `photo`, `role`, `status`, `telephone`, `specialite`, `created_at`, `last_login`) VALUES
(1, 'Admin', 'Bibliothèque', 'admin', 'admin@unjournouveau.cd', '$2y$10$1c8yWWgrgtRkn2IO9U73DufsCSGLuCwFNHu4D.odOOtq/AG72c01e', 'user_1_1759235791.jpg', 'admin', 'active', '06655565', '', '2025-09-24 08:46:22', '2025-10-24 07:46:16'),
(2, 'JEAN', 'NDANZE', 'Chadrack', 'ischadrack2@gmail.com', '$2y$10$yPgxqzC.H1aPMXGb7Ixl7uuKtrkICUJRtcUTliLDHoZPdb90GmI1a', '68d4f0d0bfe3d.png', 'bibliothecaire', 'inactive', '+2436655565', 'INFO', '2025-09-25 07:35:44', NULL),
(3, 'Yves', 'EMMA', 'Yves', 'yves@gmail.com', '$2y$10$s4i7RtT5QiL8UZt.XFygHerdFjPIp2tug5QONYBbNrY/oKeWOCAT6', '68d699882bb1a.jpeg', 'bibliothecaire', 'active', '', '', '2025-09-25 14:10:48', '2025-09-25 17:09:15');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `abonnes`
--
ALTER TABLE `abonnes`
  ADD CONSTRAINT `abonnes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `amendes_pertes`
--
ALTER TABLE `amendes_pertes`
  ADD CONSTRAINT `amendes_pertes_ibfk_1` FOREIGN KEY (`abonne_id`) REFERENCES `abonnes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amendes_pertes_ibfk_2` FOREIGN KEY (`emprunt_id`) REFERENCES `emprunts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amendes_pertes_ibfk_3` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amendes_pertes_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `amendes_pertes_ibfk_5` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `emprunts`
--
ALTER TABLE `emprunts`
  ADD CONSTRAINT `emprunts_ibfk_1` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprunts_ibfk_2` FOREIGN KEY (`abonne_id`) REFERENCES `abonnes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprunts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprunts_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `livres`
--
ALTER TABLE `livres`
  ADD CONSTRAINT `livres_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories_livres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `livres_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`abonne_id`) REFERENCES `abonnes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
