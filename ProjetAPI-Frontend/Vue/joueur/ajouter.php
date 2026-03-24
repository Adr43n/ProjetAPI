<h1>Ajouter un joueur</h1>
<?php

use R301\Vue\Component\Formulaire;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['nom'])
    && isset($_POST['prenom'])
    && isset($_POST['numeroDeLicence'])
    && isset($_POST['dateDeNaissance'])
    && isset($_POST['tailleEnCm'])
    && isset($_POST['poidsEnKg'])
    && isset($_POST['statut'])
) {
    $result = callAPI('/api/joueurs', 'POST', [
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
    } else {
        echo '<p style="color:red;">Erreur : ' . ($result['message'] ?: 'Accès refusé') . '</p>';
    }
} else {
    $formulaire = new Formulaire(BASE_PATH . "/joueur/ajouter");
    $formulaire->setText("Nom", "nom");
    $formulaire->setText("Prenom", "prenom");
    $formulaire->setText("Numéro de license", "numeroDeLicence", "00042");
    $formulaire->setDate("Date de naissance", "dateDeNaissance");
    $formulaire->setText("Taille (en cm)", "tailleEnCm");
    $formulaire->setText("Poids (en kg)", "poidsEnKg");
    $formulaire->setSelect("Statut", ['ACTIF', 'BLESSE', 'ABSENT', 'SUSPENDU'], "statut");
    $formulaire->addButton("Submit", "create", "valider", "Valider");
    echo $formulaire;
}
