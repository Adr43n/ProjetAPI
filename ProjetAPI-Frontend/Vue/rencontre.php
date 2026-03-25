
<?php

use R301\Vue\Component\SelectResultat;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['action'])
        && isset($_POST['rencontreId'])
) {
    switch($_POST['action']) {
        case "ouvrirFeuilleDeMatch":
            header('Location: ' . BASE_PATH . '/feuilleDeMatch/feuilleDeMatch?id='.$_POST['rencontreId']);
            die();
        case "ouvrirEvaluations":
            header('Location: ' . BASE_PATH . '/feuilleDeMatch/evaluation?id='.$_POST['rencontreId']);
            die();
        case "modifier":
            header('Location: ' . BASE_PATH . '/rencontre/modifier?id='.$_POST['rencontreId']);
            die();
        case "enregistrerResultat":
            if (isset($_POST['resultat'])) {
                $result = callAPI('/api/rencontres/' . $_POST['rencontreId'] . '/resultat', 'PUT', ['resultat' => $_POST['resultat']]);
                if (!$result['success']) {
                    error_log("Erreur lors de la mise à jour du resultat");
                }
                header('Location: ' . BASE_PATH . '/rencontre');
                die();
            }
        case "supprimer":
            $result = callAPI('/api/rencontres/' . $_POST['rencontreId'], 'DELETE');
            if (!$result['success']) {
                error_log("Erreur lors de la suppression de la rencontre");
            }
            header('Location: ' . BASE_PATH . '/rencontre');
            die();
    }
} else {

$result = callAPI('/api/rencontres');
$rencontres = $result['data'];


?>
<h1>Rencontres</h1>
<div class="overflow container">
    <table>
        <tr>
            <th style="width:10%">Date</th>
            <th style="width:10%">Equipe Adverse</th>
            <th style="width:20%">Adresse</th>
            <th style="width:8%">Lieu</th>
            <th style="width:8%">Résultat</th>
            <th style="width:20%; min-width: 200px;">Actions</th>
        </tr>
        <?php foreach ($rencontres as $rencontre):
            $estPassee = strtotime($rencontre['date_heure']) < time();
            $aResultat = !empty($rencontre['resultat']);
            
            $selectResultat = new SelectResultat(
                    null,
                    $rencontre['resultat']
            );
        ?>
        <form action="<?= BASE_PATH ?>/rencontre" method="post">
            <tr>
                <input type="hidden" name="rencontreId" value="<?php echo $rencontre['rencontre_id']; ?>" />
                <td><?php echo date('d/m/Y H:i', strtotime($rencontre['date_heure'])) ?></td>
                <td><?php echo $rencontre['equipe_adverse'] ?></td>
                <td><?php echo $rencontre['adresse'] ?></td>
                <td><?php echo $rencontre['lieu'] ?></td>
                <?php if ($estPassee && !$aResultat): ?>
                    <td><?php $selectResultat->toHTML(); ?></td>
                <?php else: ?>
                    <td><?php echo $rencontre['resultat'] ?? '' ?></td>
                <?php endif; ?>
                <td class="actions">
                    <?php if (!$estPassee): ?>
                    <button name="action" value="ouvrirFeuilleDeMatch" class="info">Feuilles de match</button>
                    <button name="action" value="modifier" class="update">Modifier</button>
                    <button name="action" value="supprimer" class="delete">Supprimer</button>
                    <?php else: ?>
                    <button name="action" value="ouvrirEvaluations" class="info">Évaluations</button>
                    <?php if ($estPassee && !$aResultat): ?>
                    <button class="create" name="action" value="enregistrerResultat">Enregistrer résultat</button>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </form>
        <?php endforeach; ?>
    </table>
</div>
<?php } ?>