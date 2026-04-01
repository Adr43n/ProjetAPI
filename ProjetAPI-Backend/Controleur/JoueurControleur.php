<?php

namespace R301\Controleur;

use DateTime;
use R301\Modele\Joueur\Joueur;
use R301\Modele\Joueur\JoueurDAO;
use R301\Modele\Joueur\JoueurStatut;

/**
 * JoueurControleur - Orchestre la logique métier des joueurs
 */
class JoueurControleur {
    private static ?JoueurControleur $instance = null;
    private JoueurDAO $joueurs;

    private function __construct() {
        $this->joueurs = JoueurDAO::getInstance();
    }

    public static function getInstance(): JoueurControleur {
        if (self::$instance == null) {
            self::$instance = new JoueurControleur();
        }
        return self::$instance;
    }

    /**
     * Crée un nouveau joueur
     * Valide les données d'entrée (DateTime, statut) puis délègue au DAO pour l'INSERT
     */
    public function ajouterJoueur(
        string $nom,
        string $prenom,
        string $numeroDeLicence,
        DateTime $dateDeNaissance,
        int $tailleEnCm,
        int $poidsEnKg,
        string $statut
    ) : bool {
        // On crée un objet Joueur validé (avec enum, DateTime, etc.)
        // Bien mieux que de passer des strings brutes au DAO
        $joueurACreer = new Joueur(
            0,  // joueurId = 0 = "pas encore créé", la BDD génèrera l'ID auto-increment
            $nom,
            $prenom,
            $numeroDeLicence,
            $dateDeNaissance,
            $tailleEnCm,
            $poidsEnKg,
            JoueurStatut::fromName($statut)  // Conversion string → enum (valide le statut)
        );

        return $this->joueurs->insertJoueur($joueurACreer);
    }

    /**
     * Récupère un joueur par ID
     * Simple délégation au DAO
     */
    public function getJoueurById(int $joueurId) : Joueur {
        return $this->joueurs->selectJoueurById($joueurId);
    }

    /**
     * Logique métier complexe : lister les joueurs sélectionnables pour une feuille de match
     *
     * Règles métier :
     *  1. Doit être ACTIF (pas blessé, pas retraité)
     *  2. Ne doit PAS déjà être sur la feuille de match de cette rencontre
     *
     * C'est du filtrage métier : plutôt que de faire une grosse requête SQL complexe,
     * on récupère les ACTIFS du DAO, puis on filtre en mémoire avec une règle métier
     * (appel à ParticipationControleur pour vérifier l'absence).
     * Ça rend le code plus lisible et maintenable.
     */
    public function listerLesJoueursSelectionnablesPourUnMatch(int $rencontreId) : array {
        // Étape 1 : récupère tous les joueurs actifs (via DAO)
        $joueursActifs = $this->joueurs->selectJoueursByStatut(JoueurStatut::ACTIF);
        $joueursSelectionnables = [];

        // Étape 2 : filtre en appliquant la règle métier (pas déjà sur la feuille)
        foreach ($joueursActifs as $joueur) {
            // Appelle un autre contrôleur pour vérifier un état du métier
            // (orchestration inter-controleurs)
            if (!ParticipationControleur::getInstance()->lejoueurEstDejaSurLaFeuilleDeMatch($rencontreId, $joueur->getJoueurId())) {
                $joueursSelectionnables[] = $joueur;
            }
        }

        return $joueursSelectionnables;
    }

    /**
     * Liste tous les joueurs
     * Simple délégation au DAO
     */
    public function listerTousLesJoueurs() : array {
        return $this->joueurs->selectAllJoueurs();
    }

    /**
     * Modifie un joueur existant
     * Logique : on récupère l'objet existant, on change ses propriétés, on sauvegarde
     */
    public function modifierJoueur(
        int $joueurId,
        string $nom,
        string $prenom,
        string $numeroDeLicence,
        DateTime $dateDeNaissance,
        int $tailleEnCm,
        int $poidsEnKg,
        string $statut
    ) : bool {
        // Récupère le joueur en BDD
        $joueurAModifier = $this->joueurs->selectJoueurById($joueurId);

        // Applique les modifications via les setters
        // (les setters peuvent avoir de la validation si besoin)
        $joueurAModifier->setNom($nom);
        $joueurAModifier->setPrenom($prenom);
        $joueurAModifier->setNumeroDeLicence($numeroDeLicence);
        $joueurAModifier->setDateDeNaissance($dateDeNaissance);
        $joueurAModifier->setTailleEnCm($tailleEnCm);
        $joueurAModifier->setPoidsEnKg($poidsEnKg);
        $joueurAModifier->setStatut(JoueurStatut::fromName($statut));

        // Persiste les modifications
        return $this->joueurs->updateJoueur($joueurAModifier);
    }

    /**
     * Recherche les joueurs avec filtrage multi-critères
     *
     * Exemple de logique métier côté contrôleur :
     *  - Filtre par nom/prénom (recherche textuelle)
     *  - Filtre par statut (optionnel)
     *
     * Plutôt que d'écrire une grosse requête SQL avec plein de conditions,
     * on charge les données et on filtre en mémoire.
     * Bon pour la lisibilité du code métier, pas grave pour la perf ici.
     */
    public function rechercherLesJoueurs(string $recherche, string $statut) : array {
        $tousLesjoueurs = $this->joueurs->selectAllJoueurs();
        $joueursTrouves = [];

        foreach ($tousLesjoueurs as $joueur) {
            $conserverDansLaListe = true;

            // Filtre 1 : recherche textuelle sur nom/prénom
            if ($recherche !== "") {
                $conserverDansLaListe = $joueur->nomOuPrenomContient($recherche);
            }

            // Filtre 2 : statut (et conserve les filtres précédents)
            if ($conserverDansLaListe && $statut !== "") {
                $conserverDansLaListe = $joueur->getStatut() == JoueurStatut::fromName($statut);
            }

            if ($conserverDansLaListe) {
                $joueursTrouves[] = $joueur;
            }
        }

        return $joueursTrouves;
    }

    /**
     * Supprime un joueur
     * Simple délégation au DAO
     */
    public function supprimerJoueur(int $joueurId) : bool {
        return $this->joueurs->supprimerJoueur($joueurId);
    }
}