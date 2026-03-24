<h1>Ajouter une rencontre</h1>

<?php

use R301\Vue\Component\Formulaire;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['dateHeure'])
        && isset($_POST['equipeAdverse'])
        && isset($_POST['adresse'])
        && isset($_POST['lieu'])
) {
    $result = callAPI('/api/rencontres', 'POST', [
        'date_heure' => $_POST['dateHeure'],
        'equipe_adverse' => $_POST['equipeAdverse'],
        'adresse' => $_POST['adresse'],
        'lieu' => $_POST['lieu']
    ]);

    if ($result['success']) {
        header('Location: ' . BASE_PATH . '/rencontre');
    }else{
        echo '<p style="color:red;">Erreur : ' . ($result['message'] ?: 'Accès refusé') . '</p>';
    }
} else {
    $formulaire = new Formulaire(BASE_PATH . "/rencontre/ajouter");
    $formulaire->setDateTime("Date", "dateHeure", date("Y-m-d H:i"));
    $formulaire->setText("Equipe adverse", "equipeAdverse");
    $formulaire->setText("Adresse", "adresse");
    $formulaire->setSelect("Lieu", ['DOMICILE', 'EXTERIEUR'], "lieu");
    $formulaire->addButton("Submit", "create", "Valider", "Modifier");
    echo $formulaire;
}