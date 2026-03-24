
<?php


use R301\Vue\Component\SelectPerformance;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['action'])
        && isset($_POST['participationId'])
        && isset($_POST['rencontreId'])
        && isset($_POST['performance'])
) :
    switch($_POST['action']) {
        case "update":
            $result = callAPI('/api/participations/' . $_POST['participationId'] . '/performance', 'PUT', [
                'performance' => $_POST['performance']
            ]);
            if (!$result['success']) {
                error_log("Erreur lors de la mise à jour de la performance");
            }
            break;
        case "delete":
            $result = callAPI('/api/participations/' . $_POST['participationId'] . '/performance', 'PUT', [
                'performance' => null
            ]);
            if (!$result['success']) {
                error_log("Erreur lors de la suppression de la performance");
            }
            break;
    }

    header('Location: ' . BASE_PATH . '/feuilleDeMatch/evaluation?id=' . $_POST['rencontreId']);
    die();
else :
    if (!isset($_GET['id'])) :
        header("Location: " . BASE_PATH . "/rencontre"); die();
    else :
        $resultFeuille = callAPI('/api/participations/rencontre/' . $_GET['id']);
        $feuilleDeMatch = $resultFeuille['data'];
        
        // Helper function to find participant at a given position
        function getParticipantAuPosteEval($participations, $poste, $titulaireOuRemplacant) {
            foreach ($participations as $participant) {
                if ($participant['poste'] === $poste && $participant['titulaire_ou_remplacant'] === $titulaireOuRemplacant) {
                    return $participant;
                }
            }
            return null;
        }
?>
<div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; padding-right: 30px">
    <h1>Évaluations</h1>
    <?php if($feuilleDeMatch['est_evaluee']) : ?>
        <div class="etat-feuille-de-match feuille-de-match-complete">
            TERMINÉES
        </div>
    <?php else: ?>
        <div class="etat-feuille-de-match feuille-de-match-incomplete">
            INCOMPLÈTES
        </div>
    <?php endif; ?>
</div>

<div class="container" style="display: flex; flex-direction: row; justify-content: space-between">
    <?php 
    $statutsTypes = ['TITULAIRE', 'REMPLACANT'];
    foreach ($statutsTypes as $titulaireOuRemplacant) : 
    ?>
        <table style="width: 49.5%">
            <caption>
                <?php echo $titulaireOuRemplacant.'S' ?>
            </caption>
            <tr>
                <th style="width:15%">Poste</th>
                <th style="width:25%">Joueur</th>
                <th style="width:15%">Performance</th>
                <th style="width:20%">Mettre à jour la performance</th>
                <th style="width:25%; min-width: 150px;"></th>
            </tr>

            <?php
            $postes = ['TOPLANE', 'JUNGLE', 'MIDLANE', 'ADCARRY', 'SUPPORT'];
            foreach ($postes as $poste):
                $participant = getParticipantAuPosteEval($feuilleDeMatch['participations'], $poste, $titulaireOuRemplacant);
                $selectedValue = null;

                if ($participant !== null && isset($participant['performance']) && $participant['performance'] !== null) {
                    $selectedValue = $participant['performance'];
                }

                $select = new SelectPerformance(
                        null,
                        $selectedValue
                );
                ?>
                <form action="<?= BASE_PATH ?>/feuilleDeMatch/evaluation" method="post">
                    <tr>
                        <input type="hidden" name="rencontreId" value="<?php if($participant !== null) echo $feuilleDeMatch['rencontre_id']; ?>" />
                        <input type="hidden" name="participationId" value="<?php if($participant !== null) echo $participant['participation_id']; ?>" />
                        <td><?php echo $poste; ?></td>
                        <td><?php  if($participant !== null) echo $participant['joueur_nom'] . ' ' . $participant['joueur_prenom'] ?></td>
                        <td><?php  if($participant !== null && isset($participant['performance']) && $participant['performance'] !== null) echo $participant['performance'] ?></td>
                        <td><?php $select->toHTML(); ?></td>
                        <?php if($participant !== null) : ?>
                        <td class="actions">
                                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') : ?>
                                <button class="update" type="submit" name="action" value="update">Mettre à jour</button>
                                <button class="delete" type="submit" name="action" value="delete" style="margin-left: 8px">Supprimer</button>
                                <?php endif; ?>
                        </td>
                        <?php else: ?>
                        <td></td>
                        <?php endif; ?>
                    </tr>
                </form>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
</div>
<?php endif; endif; ?>