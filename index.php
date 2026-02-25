<?php
require_once __DIR__ . '/Psr4AutoloaderClass.php';
use R301\Psr4AutoloaderClass;

$loader = new Psr4AutoloaderClass;
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$loader->addNamespace('R301', __DIR__);

// Base path du projet (sous-dossier)
define('BASE_PATH', '/ProjetAPI');

// Extraire la route relative (sans le préfixe /ProjetAPI)
$requestUri = $_SERVER["REQUEST_URI"];
$route = $requestUri;
if (str_starts_with($route, BASE_PATH)) {
    $route = substr($route, strlen(BASE_PATH));
}
if ($route === '' || $route === false) {
    $route = '/login';
}

if (preg_match('/\.(?:png|jpg|jpeg|gif|ico|css|js)\??.*$/', $requestUri)) {
    return false; // serve the requested resource as-is.
} else {

session_start();
if (strtok($route, '?') !== "/login" && !isset($_SESSION ['username'])) {
    header('Location: ' . BASE_PATH . '/login');
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
        </nav>
    <?php endif; ?>
    <?php
        require_once __DIR__ . '/Vue' . strtok($route, '?') . '.php';
    } ?>
    </body>
</html>