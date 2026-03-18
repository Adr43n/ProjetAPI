<?php
require "DatabaseHandlerAuth.php";

use R301\AuthAPI9\Modele\DatabaseHandlerAuth;

$pdo = DatabaseHandlerAuth::getInstance()->getPdo();

$utilisateurs = [
    ["nom"=>"Dupont", "email"=>"dupont@mail.com", "mp"=>"mdp123", "role"=>"user"],
    ["nom"=>"Martin", "email"=>"martin@mail.com", "mp"=>"admin123", "role"=>"admin"],
    ["nom"=>"Durand", "email"=>"durand@mail.com", "mp"=>"pass456", "role"=>"user"],
    
];

foreach($utilisateurs as $u){
    $hash = password_hash($u['mp'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom,email,mot_de_passe,role) VALUES(?,?,?,?)");
    $stmt->execute([$u['nom'],$u['email'],$hash,$u['role']]);
    echo "Utilisateur {$u['email']} ajouté <br>";
}
?>