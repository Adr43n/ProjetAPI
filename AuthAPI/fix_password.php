<?php
require "DatabaseHandlerAuth.php";

use R301\AuthAPI9\Modele\DatabaseHandlerAuth;

$pdo = DatabaseHandlerAuth::getInstance()->getPdo();

// joueur_id = ID du joueur dans la base r301 (NULL si l'utilisateur n'est pas un joueur)
$utilisateurs = [
    ["nom"=>"Dupont", "email"=>"dupont@mail.com", "mp"=>"mdp123",   "role"=>"user",  "joueur_id"=>1],
    ["nom"=>"Martin", "email"=>"martin@mail.com", "mp"=>"admin123", "role"=>"admin", "joueur_id"=>null],
    ["nom"=>"Durand", "email"=>"durand@mail.com", "mp"=>"pass456",  "role"=>"user",  "joueur_id"=>2],
];

foreach($utilisateurs as $u){
    $hash = password_hash($u['mp'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom,email,mot_de_passe,role,joueur_id) VALUES(?,?,?,?,?)");
    $stmt->execute([$u['nom'],$u['email'],$hash,$u['role'],$u['joueur_id']]);
    echo "Utilisateur {$u['email']} ajouté <br>";
}
?>