<h1>Modifier une rencontre</h1>

<?php

use R301\Vue\Component\Formulaire;


if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_GET['id'])
        && isset($_POST['dateHeure'])
        && isset($_POST['equipeAdverse'])
        && isset($_POST['adresse'])
        && isset($_POST['lieu'])
) {
    $result = callAPI('/api/rencontres/' . $_GET['id'], 'PUT', [
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
    if (!isset($_GET['id'])) {
        header("Location: " . BASE_PATH . "/rencontre");
    } else {
        $result = callAPI('/api/rencontres/' . $_GET['id']);
        $rencontre = $result['data'];

        $formulaire = new Formulaire(BASE_PATH . "/rencontre/modifier?id=" . $rencontre['rencontre_id']);
        $formulaire->setDateTime("Date", "dateHeure", date("Y-m-d H:i"), date("Y-m-d H:i", strtotime($rencontre['date_heure'])));
        $formulaire->setText("Equipe adverse", "equipeAdverse", "", $rencontre['equipe_adverse']);
        $formulaire->setText("Adresse", "adresse", "", $rencontre['adresse']);
        $formulaire->setSelect("Lieu", ['DOMICILE', 'EXTERIEUR'], "lieu", $rencontre['lieu']);
        $formulaire->addButton("Submit", "update", "Valider", "Modifier");
        echo $formulaire;
    }
}