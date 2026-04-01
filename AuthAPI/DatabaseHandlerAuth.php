<?php
namespace R301\AuthAPI9\Modele;

use PDO;
use Exception;

class DatabaseHandlerAuth {
    private static ?DatabaseHandlerAuth $instance = null;
    private PDO $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=localhost;dbname=auth_db",
                "root",
                ""
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Erreur connexion BDD: ' . $e->getMessage()]));
        }
    }

    public static function getInstance(): DatabaseHandlerAuth {
        if (self::$instance === null) {
            self::$instance = new DatabaseHandlerAuth();
        }
        return self::$instance;
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }
}