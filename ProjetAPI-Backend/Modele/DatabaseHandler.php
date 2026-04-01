<?php

namespace R301\Modele;

use Exception;
use PDO;

/**
 * DatabaseHandler - Pattern Singleton
 *
 * Gère la connexion à la base de données.
 * Le pattern Singleton garantit qu'il n'existe qu'une SEULE instance PDO
 * dans toute l'application (pas de reconnexions inutiles).
 *
 * Utilisation : DatabaseHandler::getInstance()->pdo()
 */
class DatabaseHandler {
    private static ?DatabaseHandler $instance = null;
    private PDO $linkpdo;
    private string $server;
    private string $db;
    private string $login;
    private string $mdp;

    /**
     * Constructeur privé
     * (empêche l'instanciation directe : new DatabaseHandler() échoue)
     * Oblige donc à passer par getInstance()
     */
    private function __construct(){
        try{
            $this->server = "localhost";
            $this->db = "r301";
            $this->login = "r301";
            $this->mdp = "7z3AgWdX54Zkq5!";

            // Connexion PDO avec configuration minimale
            $this->linkpdo = new PDO(
                "mysql:host=" . $this->server . ";dbname=" . $this->db,
                $this->login,
                $this->mdp
            );

            // Petite optim : retourner les résultats en tableau associatif par défaut
            // (sinon il faut le passer en param à chaque fetch...)
            $this->linkpdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        catch(Exception $e){
            die("Erreur connexion BDD : " . $e->getMessage());
        }
    }

    /**
     * Récupère l'instance unique du DatabaseHandler
     * Crée la connexion au premier appel, puis la réutilise
     */
    public static function getInstance(): DatabaseHandler
    {
        if (self::$instance == null) {
            self::$instance = new DatabaseHandler();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO pour les requêtes
     * (utilisé par les DAO pour prepare/execute)
     */
    public function pdo(): PDO {
        return $this->linkpdo;
    }
}