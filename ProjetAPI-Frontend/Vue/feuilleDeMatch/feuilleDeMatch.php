<?php

use R301\Vue\Component\Select;

if (!isset($_GET['id'])) :
    header("Location: " . BASE_PATH . "/rencontre");
else :
    $resultFeuille = callAPI('/api/participations/rencontre/' . $_GET['id']);
    $feuilleDeMatch = $resultFeuille['data'];
    
    $resultJoueurs = callAPI('/api/joueurs');
    $joueursSelectionnables = $resultJoueurs['data'];
    
    // Helper function to find participant at a given position
    function getParticipantAuPoste($participations, $poste, $titulaireOuRemplacant) {
        foreach ($participations as $participant) {
            if ($participant['poste'] === $poste && $participant['titulaire_ou_remplacant'] === $titulaireOuRemplacant) {
                return $participant;
            }
        }
        return null;
    }
?>
<div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; padding-right: 30px">
    <h1>Feuille de Match</h1>
    <?php if($feuilleDeMatch['est_complete']) : ?>
    <div class="etat-feuille-de-match feuille-de-match-complete">
        COMPLÈTE
    </div>
    <?php else: ?>
    <div class="etat-feuille-de-match feuille-de-match-incomplete">
        INCOMPLÈTE
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
            <th style="width:30%">Joueur</th>
            <th style="width:35%">Sélectionner un joueur</th>
            <th style="width:20%; min-width: 150px;"></th>
        </tr>

        <?php
            $postes = ['TOPLANE', 'JUNGLE', 'MIDLANE', 'ADCARRY', 'SUPPORT'];
            foreach ($postes as $poste):
                $participant = getParticipantAuPoste($feuilleDeMatch['participations'], $poste, $titulaireOuRemplacant);
                $selectedValue = null;
                $selectableValues = [];

                foreach ($joueursSelectionnables as $joueursSelectionnable) {
                    $selectableValues[$joueursSelectionnable['joueur_id']] = $joueursSelectionnable['nom'] . ' ' . $joueursSelectionnable['prenom'];
                }

                if ($participant !== null) {
                    $selectableValues[$participant['joueur_id']] = $participant['joueur_nom'] . ' ' . $participant['joueur_prenom'];
                    $selectedValue = $participant['joueur_nom'] . ' ' . $participant['joueur_prenom'];
                }

                $select = new Select(
                        $selectableValues,
                        "joueurId",
                        null,
                        $selectedValue,
                );
        ?>
        <form action="<?= BASE_PATH ?>/feuilleDeMatch/modifier" method="post">
            <tr>
                <input type="hidden" name="participationId" value="<?php if($participant !== null) echo $participant['participation_id']; ?>" />
                <input type="hidden" name="poste" value="<?php echo $poste ?>" />
                <input type="hidden" name="rencontreId" value="<?php echo $_GET['id'] ?>" />
                <input type="hidden" name="titulaireOuRemplacant" value="<?php echo $titulaireOuRemplacant ?>" />
                <td><?php echo $poste; ?></td>
                <td><?php  if($participant !== null) echo $participant['joueur_nom'] . ' ' . $participant['joueur_prenom'] ?></td>
                <td><?php $select->toHTML(); ?></td>
                <td class="actions">
                    <?php if(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') : ?>
                    <?php if($participant !== null) : ?>
                    <button class="update" type="submit" name="action" value="update">Modifier</button>
                    <button class="delete" type="submit" name="action" value="delete" style="margin-left: 8px">Supprimer</button>
                    <?php else: ?>
                    <button class="create" type="submit" name="action" value="create">Assigner</button>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </form>
        <?php endforeach; ?>
    </table>
    <?php endforeach; ?>
</div>
<?php endif; ?>