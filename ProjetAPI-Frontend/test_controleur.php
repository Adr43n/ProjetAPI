<?php
require_once __DIR__ . '/Psr4AutoloaderClass.php';

$loader = new R301\Psr4AutoloaderClass();
$loader->register();
$loader->addNamespace('R301', __DIR__);

use R301\Controleur\JoueurControleur;

echo "Test du JoueurControleur Frontend:\n\n";

$controleur = JoueurControleur::getInstance();
$joueurs = $controleur->listerTousLesJoueurs();

echo "Nombre de joueurs: " . count($joueurs) . "\n";

if (count($joueurs) > 0) {
    echo "\nPremier joueur:\n";
    print_r($joueurs[0]);
} else {
    echo "\n❌ Aucun joueur retourné\n";
}
