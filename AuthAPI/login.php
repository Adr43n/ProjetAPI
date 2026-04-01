<?php
/**
 * Endpoint de connexion - Authentification utilisateur
 * Valide les identifiants et délivre un JWT (JSON Web Token)
 *
 * POST /AuthAPI/login.php
 * {
 *   "email": "user@example.com",
 *   "password": "pass123"
 * }
 */

require_once __DIR__ . '/DatabaseHandlerAuth.php';
require_once __DIR__ . '/jwt_secret.php';
require_once __DIR__ . '/../jwt_utils.php';

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

// Récupère le JSON du corpo de la requête
$input = json_decode(file_get_contents('php://input'), true);

// Validation basique des champs requis
if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et password requis']);
    exit;
}

$email = trim($input['email']);
$password = trim($input['password']);

try {
    $pdo = DatabaseHandlerAuth::getInstance()->getPdo();

    // Cherche l'utilisateur par email (prepared statement = protection contre SQL injection)
    $query = "SELECT * FROM utilisateurs WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérification : utilisateur existe + mot de passe correct
    // password_verify() compare le mdp en clair avec le hash stocké (algo bcrypt)
    // On fait pas de distinction erreur (user not found vs bad password) pour des raisons de sécurité
    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiants invalides']);
        exit;
    }

    // === GÉNÉRATION DU JWT ===
    // Le JWT est composé de 3 parties : header.payload.signature
    // La signature est calculée avec JWT_SECRET (clé symétrique HS256)
    // Seul l'AuthAPI connaît la clé → personne peut forger un token valide

    $headers = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = [
        'id'        => $user['id'],
        'email'     => $user['email'],
        'nom'       => $user['nom'],
        'role'      => $user['role'],
        'joueur_id' => $user['joueur_id'], // null si pas un joueur (ex: coach, staff)
        'iat'       => time(),              // Issued At - quand le token a été créé
        'exp'       => time() + 7200        // Expiration - valable 2h après création
    ];
    $token = generate_jwt($headers, $payload, JWT_SECRET);

    // Retour au client avec le token et infos publiques
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id'    => $user['id'],
            'email' => $user['email'],
            'nom'   => $user['nom'],
            'role'  => $user['role']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}