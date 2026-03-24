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

// On récupère les données envoyées en JSON
$input = json_decode(file_get_contents('php://input'), true);

// On vérifie que l'email et le mot de passe sont bien envoyés
if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et password requis']);
    exit;
}

$email = trim($input['email']);
$password = trim($input['password']);

try {
    $pdo = DatabaseHandlerAuth::getInstance()->getPdo();
    
    // On cherche l'utilisateur avec cet email
    $query = "SELECT * FROM utilisateurs WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si l'utilisateur n'existe pas ou que le mot de passe est faux
    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiants invalides']);
        exit;
    }
    
    // On génère un token aléatoire
    $token = bin2hex(random_bytes(32));
    // Le token expire dans 2 heures
    $expiration = date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    // On supprime les anciens tokens de cet utilisateur
    $deleteStmt = $pdo->prepare("DELETE FROM tokens WHERE utilisateur_id = :id");
    $deleteStmt->execute(['id' => $user['id']]);
    
    // On enregistre le nouveau token dans la base de données
    $insertStmt = $pdo->prepare("INSERT INTO tokens (utilisateur_id, token, date_expiration) VALUES (:id, :token, :expiration)");
    $insertStmt->execute([
        'id' => $user['id'],
        'token' => $token,
        'expiration' => $expiration
    ]);
    
    // On retourne le token et les infos de l'utilisateur
    echo json_encode([
        'success' => true,
        'token' => $token,
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