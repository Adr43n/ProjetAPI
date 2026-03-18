CREATE DATABASE IF NOT EXISTS auth_db;

USE auth_db;

DROP TABLE IF EXISTS utilisateurs;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer un utilisateur de test
-- Email: admin@admin.com, Mot de passe: admin (haché avec password_hash)
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
VALUES ('Admin', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

COMMIT;
