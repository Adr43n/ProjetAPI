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

// On récupère le token envoyé en JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token requis']);
    exit;
}

$token = trim($input['token']);

try {
    $pdo = DatabaseHandlerAuth::getInstance()->getPdo();

    // On vérifie que le token existe et qu'il n'est pas expiré
    $query = "SELECT t.*, u.nom, u.email, u.role 
              FROM tokens t 
              JOIN utilisateurs u ON t.utilisateur_id = u.id 
              WHERE t.token = :token AND t.date_expiration > NOW()";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['token' => $token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si le token n'existe pas ou est expiré
    if (!$result) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré']);
        exit;
    }

    // Le token est valide, on retourne les infos de l'utilisateur
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $result['utilisateur_id'],
            'email' => $result['email'],
            'nom' => $result['nom'],
            'role' => $result['role']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
