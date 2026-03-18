<?php

$resultEquipe = callAPI('/api/statistiques/equipe');
$resultJoueurs = callAPI('/api/statistiques/joueurs');

$statistiquesEquipe = $resultEquipe['data'];
$statistiquesJoueurs = $resultJoueurs['data'];

?>

<div class="TripleGrid">
    <div>
        <h1><?php echo $statistiquesEquipe['nb_victoires']; ?></h1>
        <p> matchs gagnés</p>
    </div>
    <div>
        <h1><?php echo $statistiquesEquipe['nb_nuls']; ?></h1>
        <p> matchs nuls</p>
    </div>
    <div>
        <h1><?php echo $statistiquesEquipe['nb_defaites']; ?></h1>
        <p> matchs perdus</p>
    </div>
    <div>
        <h1><?php echo $statistiquesEquipe['pourcentage_victoires']; ?>%</h1>
        <p> de matchs gagnés</p>
    </div>
    <div>
        <h1><?php echo $statistiquesEquipe['pourcentage_nuls']; ?>%</h1>
        <p> de matchs nuls</p>
    </div>
    <div>
        <h1><?php echo $statistiquesEquipe['pourcentage_defaites']; ?>%</h1>
        <p> de matchs perdus</p>
    </div>
</div>
<div class="overflow">
    <table >
        <tr>
            <th style="width:15%;">Joueur</th>
            <th style="width:7%;">Statut</th>
            <th style="width:7%;">Poste le plus performant</th>
            <th style="width:7%;">Nombre de matchs consécutifs</th>
            <th style="width:7%;">Nombre titularisations</th>
            <th style="width:7%;">Nombre remplaçants</th>
            <th style="width:7%;">Moyenne évaluations</th>
            <th style="width:7%;">Pourcentage gagnés</th>
        </tr>
        <?php foreach ($statistiquesJoueurs as $stats): ?>
        <tr>
            <td><?php echo $stats['nom'] . ' ' . $stats['prenom']; ?></td>
            <td><?php echo $stats['statut']; ?></td>
            <td><?php echo $stats['poste_le_plus_performant'] ?? ''; ?></td>
            <td><?php echo $stats['nb_rencontres_consecutives']; ?></td>
            <td><?php echo $stats['nb_titularisations']; ?></td>
            <td><?php echo $stats['nb_remplacant']; ?></td>
            <td><?php echo $stats['moyenne_evaluations'] ?? ''; ?></td>
            <td><?php echo $stats['pourcentage_matchs_gagnes'] ?? ''; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
