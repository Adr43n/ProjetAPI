<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && isset($_POST['poste'])
    && isset($_POST['titulaireOuRemplacant'])
    && isset($_POST['joueurId']) && $_POST['joueurId'] !== ""
    && isset($_POST['rencontreId'])
) {
    switch($_POST['action']) {
        case "create":
            $result = callAPI('/api/participations', 'POST', [
                'joueur_id' => (int)$_POST['joueurId'],
                'rencontre_id' => (int)$_POST['rencontreId'],
                'poste' => $_POST['poste'],
                'titulaire_ou_remplacant' => $_POST['titulaireOuRemplacant']
            ]);
            if (!$result['success']) {
                error_log("Erreur lors de l'ajout d'une participation");
            }
            break;
        case "update":
            if (isset($_POST['participationId'])) {
                $result = callAPI('/api/participations/' . $_POST['participationId'], 'PUT', [
                    'poste' => $_POST['poste'],
                    'titulaire_ou_remplacant' => $_POST['titulaireOuRemplacant'],
                    'joueur_id' => (int)$_POST['joueurId']
                ]);
                if (!$result['success']) {
                    error_log("Erreur lors de la modification de la participation");
                }
            }
            break;
        case "delete":
            if (isset($_POST['participationId'])) {
                $result = callAPI('/api/participations/' . $_POST['participationId'], 'DELETE');
                if (!$result['success']) {
                    error_log("Erreur lors de la suppression de la participation");
                }
            }
            break;
        default:
    }
    header('Location: ' . BASE_PATH . '/feuilleDeMatch/feuilleDeMatch?id='.$_POST['rencontreId']);
} else {
    if (isset($_POST['rencontreId'])) {
        header('Location: ' . BASE_PATH . '/feuilleDeMatch/feuilleDeMatch?id='.$_POST['rencontreId']);
    } else {
        header('Location: ' . BASE_PATH . '/rencontre');
    }
}