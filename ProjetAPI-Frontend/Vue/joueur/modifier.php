<h1>Modifier un joueur</h1>
<?php

use R301\Vue\Component\Formulaire;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['id'])
    && isset($_POST['nom'])
    && isset($_POST['prenom'])
    && isset($_POST['dateDeNaissance'])
    && isset($_POST['tailleEnCm'])
    && isset($_POST['poidsEnKg'])
    && isset($_POST['statut'])
) {

    $result = callAPI('/api/joueurs/' . $_GET['id'], 'PUT', [
        'nom' => $_POST['nom'],
        'prenom' => $_POST['prenom'],
        'numero_licence' => $_POST['numeroDeLicence'],
        'date_naissance' => $_POST['dateDeNaissance'],
        'taille' => (int)$_POST['tailleEnCm'],
        'poids' => (int)$_POST['poidsEnKg'],
        'statut' => $_POST['statut']
    ]);

    if ($result['success']) {
        header('Location: ' . BASE_PATH . '/joueur');
    }else{
        error_log("Erreur lors de la modification du joueur");
    }
} else {
    if (!isset($_GET['id'])) {
        header("Location: " . BASE_PATH . "/joueur");
    } else {
        $result = callAPI('/api/joueurs/' . $_GET['id']);
        $joueur = $result['data'];

        $formulaire = new Formulaire(BASE_PATH . "/joueur/modifier?id=".$joueur['joueur_id']);
        $formulaire->setText("Nom", "nom", "", $joueur['nom']);
        $formulaire->setText("Prenom", "prenom", "", $joueur['prenom']);
        $formulaire->setText("Numéro de license", "numeroDeLicence", "00042", $joueur['numero_licence']);
        $formulaire->setDate("Date de naissance", "dateDeNaissance", $joueur['date_naissance']);
        $formulaire->setText("Taille (en cm)", "tailleEnCm", "", $joueur['taille']);
        $formulaire->setText("Poids (en Kg)", "poidsEnKg", "", $joueur['poids']);
        $formulaire->setSelect("Statut", ['ACTIF', 'BLESSE', 'ABSENT', 'SUSPENDU'], "statut");
        $formulaire->addButton("Submit", "update", "modifier","Modifier");
        echo $formulaire;
    }
}