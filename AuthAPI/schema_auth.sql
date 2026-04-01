CREATE DATABASE IF NOT EXISTS auth_db;

USE auth_db;

DROP TABLE IF EXISTS utilisateurs;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    joueur_id INT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMIT;
