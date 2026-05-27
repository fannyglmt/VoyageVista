-- Création de la base de données
CREATE DATABASE IF NOT EXISTS voyagevista DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE voyagevista;

-- Table des utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'prestataire', 'utilisateur') DEFAULT 'utilisateur',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des destinations
CREATE TABLE destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix_base DECIMAL(10,2),
    image_url VARCHAR(255)
);

-- Table des réservations (Sujet : Hébergement/Transports)
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    destination_id INT,
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (destination_id) REFERENCES destinations(id)
);

-- Table des signalements (Partie Houda)
CREATE TABLE signalements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    raison VARCHAR(255),
    description TEXT,
    statut ENUM('ouvert', 'en cours', 'resolu') DEFAULT 'ouvert',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
);

-- Table des notifications (Partie Houda)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
);