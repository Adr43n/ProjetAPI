<?php
require_once __DIR__ . '/Psr4AutoloaderClass.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$loader->addNamespace('R301', __DIR__);

// Base path du projet (sous-dossier)
define('BASE_PATH', '/ProjetAPI/ProjetAPI-Frontend');
define('API_BASE_URL', 'http://localhost/ProjetAPI/ProjetAPI-Backend');
define('AUTH_API_URL', 'http://localhost/ProjetAPI/AuthAPI');

// Fonction pour appeler l'API Backend
// On envoie le token dans le header pour s'authentifier
function callAPI($endpoint, $method = 'GET', $data = null) {
    $url = API_BASE_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // On prépare les headers avec le token
    $headers = ['Content-Type: application/json'];
    if (isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }
    
    // Si on envoie des données (POST, PUT), on les met dans le body
    if ($data !== null) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    // On retourne un tableau avec le résultat
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
if (strtok($route, '?') !== "/login" && !isset($_SESSION ['username'])) {
    header('Location: ' . BASE_PATH . '/login');
    exit;
}
// Si connecté et sur la page login, rediriger vers tableau de bord
if (strtok($route, '?') === "/login" && isset($_SESSION ['username'])) {
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
    <?php if (strtok($route, '?') !== '/login') : ?>
        <nav class="navbar">
            <a href="<?= BASE_PATH ?>/tableauDeBord" class="dropbtn">Tableau de bord</a>
            <div class="dropdown">
                <button class="dropbtn">Joueurs</button>
                <div class="dropdown-content">
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') : ?>
                    <a href="<?= BASE_PATH ?>/joueur/ajouter">Ajouter un joueur</a>
                    <?php endif; ?>
                    <a href="<?= BASE_PATH ?>/joueur">Liste de joueurs</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropbtn">Rencontres</button>
                <div class="dropdown-content">
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') : ?>
                    <a href="<?= BASE_PATH ?>/rencontre/ajouter">Ajouter une rencontre</a>
                    <?php endif; ?>
                    <a href="<?= BASE_PATH ?>/rencontre">Liste des rencontres</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    <?php
        require_once __DIR__ . '/Vue' . strtok($route, '?') . '.php';
    } ?>
    </body>
</html>