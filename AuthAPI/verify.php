<?php
/**
 * Endpoint de vérification JWT
 * Valide un token sans accès à la DB (stateless)
 *
 * POST /AuthAPI/verify.php
 * { "token": "eyJhb..." }
 */

require_once __DIR__ . '/jwt_secret.php';
require_once __DIR__ . '/../jwt_utils.php';

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

// Récupère le token depuis le corps JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token requis']);
    exit;
}

$token = trim($input['token']);

// === VALIDATION DU JWT ===
// La vérification JWT ne demande PAS d'accès à la BDD parce que :
// - La signature est stockée DANS le token lui-même
// - On vérifie la signature avec JWT_SECRET (clé symétrique HS256)
// - Si la signature est valide = le token n'a pas été modifié
// - On vérifie l'expiration (champ 'exp' du payload)
// C'est ce qui rend les JWT "stateless" (no DB query needed)

if (!is_jwt_valid($token, JWT_SECRET)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré']);
    exit;
}

// Token OK : on extrait les infos utilisateur du payload
// Attention : le JWT se divise en 3 parties séparées par '.'
// [0] = header (base64url)
// [1] = payload (base64url) ← on la décode
// [2] = signature (base64url)
$tokenParts = explode('.', $token);
$payload = json_decode(base64_decode($tokenParts[1]), true);

// Retour au client avec les données du payload
echo json_encode([
    'success' => true,
    'user' => [
        'id'        => $payload['id'],
        'email'     => $payload['email'],
        'nom'       => $payload['nom'],
        'role'      => $payload['role'],
        'joueur_id' => $payload['joueur_id'] ?? null
    ]
]);
