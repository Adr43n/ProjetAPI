<?php

use R301\Vue\Component\Formulaire;

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_PATH . '/joueur');
    die();
}

$resultJoueur = callAPI('/api/joueurs/' . $_GET['id']);
$joueur = $resultJoueur['data'];
?>

<h1>Commentaires de <?php echo $joueur['prenom'] . ' ' . $joueur['nom']; ?></h1>

<?php
$form = new Formulaire(BASE_PATH . "/joueur/commentaire/ajouter");
$form->addTextArea("contenu");
$form->addHiddenInput("joueurId", $_GET['id']);
$form->addButton("submit", "create", "Publier le commentaire", "Publier le commentaire");
echo $form;

$resultCommentaires = callAPI('/api/joueurs/' . $_GET['id'] . '/commentaires');
$commentaires = $resultCommentaires['data'];

usort($commentaires, function ($a, $b) { return strtotime($b['date']) <=> strtotime($a['date']); });

?>
<div class="container">
    <table>
        <tr>
            <th style="min-width: 100px; width: 1%">Date</th>
            <th style="width: 80%">Commentaire</th>
            <th style="width: 1%"></th>
        </tr>
        <?php foreach ($commentaires as $commentaire): ?>
        <form action="<?= BASE_PATH ?>/joueur/commentaire/supprimer" method="post">
            <input type="hidden" name="commentaireId" value="<?php echo $commentaire['commentaire_id']; ?>" />
            <input type="hidden" name="joueurId" value="<?php echo $_GET['id']; ?>" />
            <tr>
                <td><?php echo date('d/m/Y H:i', strtotime($commentaire['date'])); ?></td>
                <td><?php echo $commentaire['contenu']; ?></td>
                <td class="actions">
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') : ?>
                    <button class="delete" type="submit">Supprimer</button>
                    <?php endif; ?>
                </td>
            </tr>
        </form>
        <?php endforeach; ?>
    </table>
</div>
