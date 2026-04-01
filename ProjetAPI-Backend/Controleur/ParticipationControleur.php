<?php
namespace R301\Controleur;

use R301\Modele\Joueur\JoueurStatut;
use R301\Modele\Participation\FeuilleDeMatch;
use R301\Modele\Participation\Participation;
use R301\Modele\Participation\ParticipationDAO;
use R301\Modele\Participation\Performance;
use R301\Modele\Participation\Poste;
use R301\Modele\Participation\TitulaireOuRemplacant;

class ParticipationControleur {
    private static ?ParticipationControleur $instance = null;
    private ParticipationDAO $participations;
    private JoueurControleur $joueurs;
    private RencontreControleur $rencontres;

    private function __construct() {
        $this->participations = ParticipationDAO::getInstance();
        $this->joueurs = JoueurControleur::getInstance();
        $this->rencontres = RencontreControleur::getInstance();
    }

    public static function getInstance(): ParticipationControleur {
        if (self::$instance == null) {
            self::$instance = new ParticipationControleur();
        }
        return self::$instance;
    }

    public function lejoueurEstDejaSurLaFeuilleDeMatch(int $rencontreId, int $joueurId) : bool {
        return $this->participations->lejoueurEstDejaSurLaFeuilleDeMatch($rencontreId, $joueurId);
    }

    public function joueurADesParticipations(int $joueurId): bool {
        return $this->participations->joueurADesParticipations($joueurId);
    }

    // Retourne les participations avec évaluation du joueur pour les matchs joués
    public function getEvaluationsJoueur(int $joueurId): array {
        $participations = $this->participations->selectParticipationsByJoueurId($joueurId);
        return array_filter($participations, function($p) {
            return $p->getRencontre()->estPassee();
        });
    }

    public function listerToutesLesParticipations() : array {
        return $this->participations->selectAllParticipations();
    }

    public function getFeuilleDeMatch(int $rencontreId) : FeuilleDeMatch {
        return new FeuilleDeMatch($this->participations->selectParticipationsByRencontreId($rencontreId));
    }

    public function assignerUnParticipant(
        int $joueurId,
        int $rencontreId,
        Poste $poste,
        TitulaireOuRemplacant $titulaireOuRemplacant
    ) : bool {
        $joueur = $this->joueurs->getJoueurById($joueurId);

        // Seuls les joueurs ACTIFS peuvent être sélectionnés
        if ($joueur->getStatut() !== JoueurStatut::ACTIF) {
            return false;
        }

        if ($this->participations->lePosteEstDejaOccupe($rencontreId, $poste, $titulaireOuRemplacant)
            || $this->lejoueurEstDejaSurLaFeuilleDeMatch($rencontreId, $joueurId)
        ) {
            return false;
        } else {
            $rencontre = $this->rencontres->getRenconterById($rencontreId);

            $participationACreer = new Participation(
                0,
                $joueur,
                $rencontre,
                $titulaireOuRemplacant,
                null,
                $poste
            );

            return $this->participations->insertParticipation($participationACreer);
        }
    }

    public function modifierParticipation(
        int $participationId,
        Poste $poste,
        TitulaireOuRemplacant $titulaireOuRemplacant,
        int $joueurId
    ) : bool {
        $participationAModifier = $this->participations->selectParticipationById($participationId);

        // Impossible de modifier la feuille de match une fois le match joué
        if ($participationAModifier->getRencontre()->estPassee()) {
            return false;
        }

        if ($participationAModifier->getParticipant()->getJoueurId() != $joueurId) {
            $participationAModifier->setParticipant($this->joueurs->getJoueurById($joueurId));
        }

        $participationAModifier->setPoste($poste);
        $participationAModifier->setTitulaireOuRemplacant($titulaireOuRemplacant);

        return $this->participations->updateParticipation($participationAModifier);
    }

    public function supprimerLaParticipation(int $participationId) : bool {
        $participation = $this->participations->selectParticipationById($participationId);

        // Impossible de retirer un joueur de la feuille une fois le match joué
        if ($participation->getRencontre()->estPassee()) {
            return false;
        }

        return $this->participations->deleteParticipation($participationId);
    }

    public function mettreAJourLaPerformance(
        int $participationId,
        string $performance
    ) : bool {
        $participationAEvaluer = $this->participations->selectParticipationById($participationId);

        if (!$participationAEvaluer->getRencontre()->estPassee()) {
            return false;
        }

        $participationAEvaluer->setPerformance(Performance::fromName($performance));
        return $this->participations->updatePerformance($participationAEvaluer);
    }

    public function supprimerLaPerformance(int $participationId) : bool {
        $participationAEvaluer = $this->participations->selectParticipationById($participationId);

        if (!$participationAEvaluer->getRencontre()->estPassee()) {
            return false;
        }

        $participationAEvaluer->setPerformance(null);
        return $this->participations->updatePerformance($participationAEvaluer);
    }
}