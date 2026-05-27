-- =============================================
-- VoyageVista - Base de donnees
-- Compatible MAMP / MySQL 5.6+
-- =============================================

CREATE DATABASE IF NOT EXISTS voyagevista
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE voyagevista;

-- =============================================
-- TABLE : utilisateurs
-- =============================================
CREATE TABLE utilisateurs (
    id                 INT          AUTO_INCREMENT PRIMARY KEY,
    username           VARCHAR(50)  NOT NULL UNIQUE,
    email              VARCHAR(100) NOT NULL UNIQUE,
    password           VARCHAR(255) NOT NULL,
    role               ENUM('admin','prestataire','utilisateur') NOT NULL DEFAULT 'utilisateur',
    avatar_url         VARCHAR(255) DEFAULT NULL,
    bio                TEXT         DEFAULT NULL,
    telephone          VARCHAR(20)  DEFAULT NULL,
    est_actif          TINYINT(1)   NOT NULL DEFAULT 1,
    date_inscription   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME     DEFAULT NULL
);

-- =============================================
-- TABLE : destinations
-- =============================================
CREATE TABLE destinations (
    id               INT           AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(100)  NOT NULL,
    description      TEXT          DEFAULT NULL,
    pays             VARCHAR(100)  DEFAULT NULL,
    region           ENUM('Europe','Asie','Afrique','Amerique','Oceanie') DEFAULT NULL,
    categorie        ENUM('Aventure','Nightlife','Plage','Gastronomie','Culture','Nature','Sport','Detente','Road Trip') DEFAULT NULL,
    budget           ENUM('€','€€','€€€') DEFAULT NULL,
    prix_base        DECIMAL(10,2) DEFAULT 0.00,
    image_url        VARCHAR(255)  DEFAULT NULL,
    note_moyenne     DECIMAL(3,2)  DEFAULT 0.00,
    nb_voyageurs_min INT           DEFAULT 1,
    nb_voyageurs_max INT           DEFAULT 50,
    est_active       TINYINT(1)    NOT NULL DEFAULT 1,
    date_creation    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLE : hebergements
-- =============================================
CREATE TABLE hebergements (
    id             INT           AUTO_INCREMENT PRIMARY KEY,
    destination_id INT           NOT NULL,
    prestataire_id INT           NOT NULL,
    nom            VARCHAR(150)  NOT NULL,
    description    TEXT          DEFAULT NULL,
    type           ENUM('hotel','appartement','villa','auberge','camping','autre') DEFAULT 'hotel',
    adresse        VARCHAR(255)  DEFAULT NULL,
    prix_nuit      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    capacite       INT           NOT NULL DEFAULT 1,
    image_url      VARCHAR(255)  DEFAULT NULL,
    note_moyenne   DECIMAL(3,2)  DEFAULT 0.00,
    est_actif      TINYINT(1)    NOT NULL DEFAULT 1,
    date_creation  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (prestataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE : activites
-- =============================================
CREATE TABLE activites (
    id             INT           AUTO_INCREMENT PRIMARY KEY,
    destination_id INT           NOT NULL,
    prestataire_id INT           NOT NULL,
    nom            VARCHAR(150)  NOT NULL,
    description    TEXT          DEFAULT NULL,
    categorie      VARCHAR(100)  DEFAULT NULL,
    prix           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duree_heures   DECIMAL(4,1)  DEFAULT NULL,
    image_url      VARCHAR(255)  DEFAULT NULL,
    note_moyenne   DECIMAL(3,2)  DEFAULT 0.00,
    est_actif      TINYINT(1)    NOT NULL DEFAULT 1,
    date_creation  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    FOREIGN KEY (prestataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE : services
-- =============================================
CREATE TABLE services (
    id             INT           AUTO_INCREMENT PRIMARY KEY,
    prestataire_id INT           NOT NULL,
    type           ENUM('hebergement','activite','transport') NOT NULL,
    ref_id         INT           NOT NULL,
    nom            VARCHAR(150)  NOT NULL,
    prix           DECIMAL(10,2) DEFAULT 0.00,
    statut         ENUM('actif','inactif','en_attente') DEFAULT 'en_attente',
    date_creation  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE : disponibilites
-- =============================================
CREATE TABLE disponibilites (
    id           INT        AUTO_INCREMENT PRIMARY KEY,
    service_id   INT        NOT NULL,
    date_debut   DATE       NOT NULL,
    date_fin     DATE       NOT NULL,
    places_dispo INT        NOT NULL DEFAULT 1,
    est_bloque   TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE : reservations
-- =============================================
CREATE TABLE reservations (
    id               INT           AUTO_INCREMENT PRIMARY KEY,
    user_id          INT           NOT NULL,
    destination_id   INT           NOT NULL,
    service_id       INT           DEFAULT NULL,
    date_debut       DATE          NOT NULL,
    date_fin         DATE          NOT NULL,
    nb_voyageurs     INT           NOT NULL DEFAULT 1,
    prix_total       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    statut           ENUM('en_attente','confirmee','annulee','terminee') DEFAULT 'en_attente',
    date_reservation TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)        REFERENCES utilisateurs(id)  ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id)  ON DELETE CASCADE,
    FOREIGN KEY (service_id)     REFERENCES services(id)      ON DELETE SET NULL
);

-- =============================================
-- TABLE : avis
-- =============================================
CREATE TABLE avis (
    id             INT        AUTO_INCREMENT PRIMARY KEY,
    user_id        INT        NOT NULL,
    destination_id INT        DEFAULT NULL,
    service_id     INT        DEFAULT NULL,
    note           TINYINT    NOT NULL,
    commentaire    TEXT       DEFAULT NULL,
    est_valide     TINYINT(1) NOT NULL DEFAULT 0,
    date_creation  TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)        REFERENCES utilisateurs(id)  ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id)  ON DELETE SET NULL,
    FOREIGN KEY (service_id)     REFERENCES services(id)      ON DELETE SET NULL
);

-- =============================================
-- TABLE : favoris
-- =============================================
CREATE TABLE favoris (
    id             INT       AUTO_INCREMENT PRIMARY KEY,
    user_id        INT       NOT NULL,
    destination_id INT       NOT NULL,
    date_ajout     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favori (user_id, destination_id),
    FOREIGN KEY (user_id)        REFERENCES utilisateurs(id)  ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(id)  ON DELETE CASCADE
);

-- =============================================
-- TABLE : signalements
-- =============================================
CREATE TABLE signalements (
    id               INT          AUTO_INCREMENT PRIMARY KEY,
    user_id          INT          NOT NULL,
    cible_type       ENUM('destination','service','utilisateur','avis') DEFAULT NULL,
    cible_id         INT          DEFAULT NULL,
    raison           VARCHAR(255) NOT NULL,
    description      TEXT         DEFAULT NULL,
    statut           ENUM('ouvert','en_cours','resolu','rejete') NOT NULL DEFAULT 'ouvert',
    traite_par       INT          DEFAULT NULL,
    date_signalement TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement  DATETIME     DEFAULT NULL,
    FOREIGN KEY (user_id)    REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (traite_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- =============================================
-- TABLE : notifications
-- =============================================
CREATE TABLE notifications (
    id         INT        AUTO_INCREMENT PRIMARY KEY,
    user_id    INT        NOT NULL,
    message    TEXT       NOT NULL,
    lu         TINYINT(1) NOT NULL DEFAULT 0,
    type       ENUM('info','alerte','reservation','promotion') DEFAULT 'info',
    lien       VARCHAR(255) DEFAULT NULL,
    date_envoi TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- =============================================
-- INDEX
-- =============================================
CREATE INDEX idx_reservations_user     ON reservations(user_id);
CREATE INDEX idx_reservations_dest     ON reservations(destination_id);
CREATE INDEX idx_reservations_statut   ON reservations(statut);
CREATE INDEX idx_reservations_date     ON reservations(date_reservation);
CREATE INDEX idx_signalements_statut   ON signalements(statut);
CREATE INDEX idx_notifs_user_lu        ON notifications(user_id, lu);
CREATE INDEX idx_hebergements_dest     ON hebergements(destination_id);
CREATE INDEX idx_activites_dest        ON activites(destination_id);
CREATE INDEX idx_services_prestataire  ON services(prestataire_id);
CREATE INDEX idx_avis_destination      ON avis(destination_id);