<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $result = callAPI('/api/joueurs/' . $_POST['id'], 'DELETE');

        if (!$result['success']) {
            error_log("Erreur lors de la suppression du joueur");
        }
    }
}

header('Location: ' . BASE_PATH . '/joueur');