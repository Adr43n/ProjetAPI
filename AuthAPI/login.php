<?php
require_once __DIR__ . '/DatabaseHandlerAuth.php';

use R301\AuthAPI9\Modele\DatabaseHandlerAuth;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et password requis']);
    exit;
}

$email = trim($input['email']);
$password = trim($input['password']);

try {
    $pdo = DatabaseHandlerAuth::getInstance()->getPdo();
    
    $query = "SELECT * FROM utilisateurs WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiants invalides']);
        exit;
    }
    
    // Authentification réussie
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'nom' => $user['nom'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}