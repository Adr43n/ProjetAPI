<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['joueurId'])
    && isset($_POST['contenu'])
) {
    $result = callAPI('/api/joueurs/' . $_POST['joueurId'] . '/commentaires', 'POST', [
        'contenu' => $_POST['contenu']
    ]);
    
    if (!$result['success']) {
        error_log("Erreur lors de la création du commentaire");
    }
}

if (isset($_POST['joueurId'])) {
    header('Location: ' . BASE_PATH . '/joueur/commentaire?id='.$_POST['joueurId']);
} else {
    header('Location: ' . BASE_PATH . '/joueur');
}