<?php

namespace R301\Modele\Joueur;

use DateTime;
use PDO;
use R301\Modele\DatabaseHandler;

/**
 * JoueurDAO - Data Access Object pour la table 'joueur'
 *
 * Pattern DAO : centralise TOUTES les opérations DB pour l'entité Joueur.
 * Avantages :
 *  - Séparation données/logique métier
 *  - Réutilisabilité (un DAO = une classe)
 *  - Facile à tester/mocker
 *  - Accès DB = au ONE place si changement de DB
 *
 * Utilisé par : JoueurControleur
 * Dépend de : DatabaseHandler (connexion), Joueur (objet métier)
 */
class JoueurDAO {
    private static ?JoueurDAO $instance = null;
    private DatabaseHandler $database;

    private function __construct() {
        $this->database = DatabaseHandler::getInstance();
    }

    public static function getInstance(): JoueurDAO {
        if (self::$instance == null) {
            self::$instance = new JoueurDAO();
        }
        return self::$instance;
    }

    /**
     * Mapping BDD → Objet Métier
     *
     * Transforme une ligne de BDD (tableau associatif) en objet Joueur.
     * Gère aussi les conversions de type (ex: string → DateTime).
     * C'est ici qu'on fait le "Object-Relational Mapping" (ORM).
     *
     * @param array $dbLine - Ligne de la BDD
     * @return Joueur - Objet métier riche (avec getters, validation, etc.)
     */
    private function mapToJoueur(array $dbLine): Joueur {
        return new Joueur(
            $dbLine['joueur_id'],
            $dbLine['nom'],
            $dbLine['prenom'],
            $dbLine['numero_licence'],
            new DateTime($dbLine['date_naissance']),  // Conversion string → DateTime
            $dbLine['taille'],
            $dbLine['poids'],
            JoueurStatut::fromName($dbLine['statut']), // Conversion string → Enum
        );
    }

    /**
     * Récupère tous les joueurs
     * Retourne un tableau d'objets Joueur (pas de tableaux bruts)
     */
    public function selectAllJoueurs(): array {
        $query = 'SELECT * FROM joueur';
        $statement = $this->database->pdo()->prepare($query);
        if ($statement->execute()){
            // array_map applique mapToJoueur() à chaque ligne
            // Résultat : tableau d'objets Joueur au lieu d'arrays
            return array_map(
                function($joueur) { return $this->mapToJoueur($joueur); },
                $statement->fetchAll(PDO::FETCH_ASSOC)
            );
        } else {
            exit();
        }
    }

    /**
     * Cherche les joueurs par statut (ACTIF, RESERVE, BLESSE, etc.)
     * Utilise prepared statement = sécurité SQL injection
     */
    public function selectJoueursByStatut(JoueurStatut $statut): array {
        $query = 'SELECT * FROM joueur WHERE statut = :statut';
        $statement = $this->database->pdo()->prepare($query);
        // bindValue remplace :statut par la valeur échappée proprement
        $statement->bindValue(':statut', $statut->name);
        if ($statement->execute()){
            return array_map(
                function($joueur) { return $this->mapToJoueur($joueur); },
                $statement->fetchAll(PDO::FETCH_ASSOC)
            );
        } else {
            exit();
        }
    }

    /**
     * Récupère un joueur par son ID
     */
    public function selectJoueurById(int $joueurId): Joueur {
        $query = 'SELECT * FROM joueur WHERE joueur_id = :joueur_id';
        $statement = $this->database->pdo()->prepare($query);
        $statement->bindValue(':joueur_id', $joueurId);
        if ($statement->execute()){
             return $this->mapToJoueur($statement->fetch(PDO::FETCH_ASSOC));
        } else {
            exit();
        }
    }

    /**
     * Crée un nouveau joueur dans la BD
     * En entrée : un objet Joueur complet (avec ses getters)
     */
    public function insertJoueur(Joueur $joueurACreer): bool {
        $query = '
            INSERT INTO joueur(numero_licence,nom,prenom,date_naissance,taille,poids,statut)
            VALUES (:numero_licence,:nom,:prenom,:date_naissance,:taille,:poids,:statut)
        ';
        $statement = $this->database->pdo()->prepare($query);

        // Extraction des données depuis l'objet + conversion au format SQL si besoin
        $statement->bindValue(':numero_licence', $joueurACreer->getNumeroDeLicence());
        $statement->bindValue(':nom', $joueurACreer->getNom());
        $statement->bindValue(':prenom', $joueurACreer->getPrenom());
        // DateTime → string au format SQL (YYYY-MM-DD)
        $statement->bindValue(':date_naissance', $joueurACreer->getDateDeNaissance()->format('Y-m-d'));
        $statement->bindValue(':taille', $joueurACreer->getTailleEnCm());
        $statement->bindValue(':poids', $joueurACreer->getPoidsEnKg());
        // Enum → string (on récupère le nom de la valeur)
        $statement->bindValue(':statut', $joueurACreer->getStatut()->name);

        return $statement->execute();
    }

    /**
     * Modifie un joueur existant
     */
    public function updateJoueur(Joueur $joueurAModifier): bool {
        $query = 'UPDATE joueur
                  SET
                    nom = :nom ,
                    prenom = :prenom,
                    numero_licence = :numero_licence,
                    date_naissance = :date_naissance,
                    taille = :taille,
                    poids = :poids,
                    statut = :statut
                  WHERE joueur_id = :joueur_id';
        $statement = $this->database->pdo()->prepare($query);

        $statement->bindValue(':joueur_id', $joueurAModifier->getJoueurId());
        $statement->bindValue(':numero_licence', $joueurAModifier->getNumeroDeLicence());
        $statement->bindValue(':nom', $joueurAModifier->getNom());
        $statement->bindValue(':prenom', $joueurAModifier->getPrenom());
        $statement->bindValue(':date_naissance', $joueurAModifier->getDateDeNaissance()->format('Y-m-d'));
        $statement->bindValue(':taille', $joueurAModifier->getTailleEnCm());
        $statement->bindValue(':poids', $joueurAModifier->getPoidsEnKg());
        $statement->bindValue(':statut', $joueurAModifier->getStatut()->name);

        return $statement->execute();
    }

    /**
     * Supprime un joueur (DELETE)
     * Retourne true si au moins une ligne a été supprimée
     */
    public function supprimerJoueur(string $joueurId) : bool {
        $query = 'DELETE FROM joueur WHERE joueur_id = :joueur_id';
        $statement = $this->database->pdo()->prepare($query);
        $statement->bindValue(':joueur_id', $joueurId);
        $statement->execute();
        // rowCount() = nombre de lignes affectées par la dernière requête SQL
        return $statement->rowCount() > 0;
    }
}