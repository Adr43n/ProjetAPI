<?php
/**
 * Frontend - ProjetAPI
 *
 * Point d'entrée du frontend.
 * Gère la session utilisateur et le routage des vues.
 * Communique avec l'API Backend via HTTP + JWT.
 */

require_once __DIR__ . '/Psr4AutoloaderClass.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('R301', __DIR__);

// === CONFIGURATION ===
// Base path du projet (chemin racine pour les liens)
define('BASE_PATH', '/ProjetAPI/ProjetAPI-Frontend');
// URLs d'accès aux APIs
define('API_BASE_URL', 'http://localhost/ProjetAPI/ProjetAPI-Backend');
define('AUTH_API_URL', 'http://localhost/ProjetAPI/AuthAPI');

/**
 * Fonction générique pour appeler l'API Backend
 *
 * Enveloppe curl pour faire les requêtes HTTP.
 * Gère automatiquement :
 *  - Headers Content-Type: application/json
 *  - Authentification : ajoute le JWT dans Authorization header (Bearer token)
 *  - Encode/décode automatiquement le JSON
 *  - Normalise la réponse en {success, data, message}
 *
 * @param string $endpoint - URL relative, ex: "/api/joueurs" ou "/api/joueurs/5"
 * @param string $method - GET, POST, PUT, DELETE
 * @param array $data - Données à envoyer (pour POST/PUT)
 * @return array {success: bool, data: array, message: string}
 */
function callAPI($endpoint, $method = 'GET', $data = null) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // === PRÉPARATION DES HEADERS ===
    $headers = ['Content-Type: application/json'];

    // Si un token est stocké en session, on l'envoie
    // (reçu lors du login, stocké dans $_SESSION['token'])
    if (isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }

    // === ENCODING DES DONNÉES ===
    // Si on envoie des données (POST, PUT), on les met dans le body JSON
    if ($data !== null) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // === EXÉCUTION + DÉCODE ===
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    // === NORMALISATION DE LA RÉPONSE ===
    // Les vues attendent toujours le même format, peu importe la réponse
    return [
        'success' => $httpCode === 200 && isset($result['success']) && $result['success'] == true,
        'data' => isset($result['data']) ? $result['data'] : [],
        'message' => isset($result['message']) ? $result['message'] : ''
    ];
}

// Fonction pour appeler l'API d'authentification (login)
function callAuthAPI($endpoint, $data) {
    $url = AUTH_API_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $httpCode === 200 && isset($result['success']) && $result['success'] == true,
        'data' => $result,
        'token' => isset($result['token']) ? $result['token'] : null,
        'user' => isset($result['user']) ? $result['user'] : null
    ];
}

// Extraire la route relative (sans le préfixe /ProjetAPI/ProjetAPI-Frontend)
$requestUri = $_SERVER["REQUEST_URI"];
$route = $requestUri;
if (str_starts_with($route, BASE_PATH)) {
    $route = substr($route, strlen(BASE_PATH));
}
// Gérer /index.php comme la racine
if ($route === '/index.php' || $route === '' || $route === false || $route === '/') {
    session_start();
    if (isset($_SESSION['username'])) {
        header('Location: ' . BASE_PATH . '/tableauDeBord');
    } else {
        header('Location: ' . BASE_PATH . '/login');
    }
    exit;
}

if (preg_match('/\.(?:png|jpg|jpeg|gif|ico|css|js)\??.*$/', $requestUri)) {
    return false; // serve the requested resource as-is.
} else {

session_start();
// /rencontre est accessible sans connexion (liste publique des matchs)
$routeSansQuery = strtok($route, '?');
if ($routeSansQuery !== "/login" && $routeSansQuery !== "/rencontre" && !isset($_SESSION['username'])) {
    header('Location: ' . BASE_PATH . '/login');
    exit;
}
// Si connecté et sur la page login, rediriger vers tableau de bord
if ($routeSansQuery === "/login" && isset($_SESSION['username'])) {
    header('Location: ' . BASE_PATH . '/tableauDeBord');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <title>R3.01</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" charset="UTF-8"/>
        <link rel="stylesheet" href="<?= BASE_PATH ?>/stylesheet.css"/>
        <link rel="icon" type="image/jpg" href="<?= BASE_PATH ?>/favicon.jpg">
    </head>
    <body>
    <?php if ($routeSansQuery !== '/login') : ?>
        <nav class="navbar">
            <?php if (isset($_SESSION['username'])): ?>
            <a href="<?= BASE_PATH ?>/tableauDeBord" class="dropbtn">Tableau de bord</a>
            <div class="dropdown">
                <button class="dropbtn">Joueurs</button>
                <div class="dropdown-content">
                    <a href="<?= BASE_PATH ?>/joueur/ajouter">Ajouter un joueur</a>
                    <a href="<?= BASE_PATH ?>/joueur">Liste de joueurs</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropbtn">Rencontres</button>
                <div class="dropdown-content">
                    <a href="<?= BASE_PATH ?>/rencontre/ajouter">Ajouter une rencontre</a>
                    <a href="<?= BASE_PATH ?>/rencontre">Liste des rencontres</a>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= BASE_PATH ?>/rencontre" class="dropbtn">Rencontres</a>
            <a href="<?= BASE_PATH ?>/login" class="dropbtn">Se connecter</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    <?php
        require_once __DIR__ . '/Vue' . strtok($route, '?') . '.php';
    } ?>
    </body>
</html>