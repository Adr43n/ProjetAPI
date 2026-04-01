<?php

namespace R301\Modele\Joueur;

use DateTime;

/**
 * Joueur - Classe métier
 *
 * Représente un joueur de l'équipe.
 * Contient uniquement la logique métier (pas d'accès DB).
 * Utilise le typage strict : chaque propriété a un type et une visibilité.
 *
 * Avantages du typage strict :
 *  - PHP valide les types à l'affectation
 *  - L'IDE auto-complète et détecte les erreurs de type
 *  - Le code est autodocumenté (on sait qu'un getJoueurId() retourne int)
 *  - Plus facile à déboguer (erreur = stack trace nette)
 *
 * Propriétés privées + getters/setters :
 *  - Encapsulation : personne ne peut modifier `$nom` en direct
 *  - On pourrait ajouter de la validation dans les setters
 *  - Facilite les refactoring futurs (changement interne = pas casse l'API)
 */
class Joueur {
    private int $joueurId;           // Auto-généré par la BDD (ID primaire)
    private string $nom;
    private string $prenom;
    private string $numeroDeLicence; // Identifiant fédération du joueur
    private DateTime $dateDeNaissance;
    private int $tailleEnCm;
    private int $poidsEnKg;
    private ?JoueurStatut $statut;   // Peut être null si statut indéfini

    /**
     * Constructeur fortement typé
     *
     * En PHP 8 on peut typer aussi les paramètres et retour.
     * Ici tous les paramètres sont typés = validation automatique.
     *
     * $joueurId = 0 signifie "nouveau joueur, pas encore en BDD".
     * La BDD génèrera un vrai ID lors de l'INSERT.
     */
    public function __construct(
        int $joueurId,
        string $nom,
        string $prenom,
        string $numeroDeLicence,
        DateTime $dateDeNaissance,
        int $tailleEnCm,
        int $poidsEnKg,
        ?JoueurStatut $statut  // le '?' = peut être null (nullable)
    ) {
        $this->joueurId = $joueurId;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->numeroDeLicence = $numeroDeLicence;
        $this->dateDeNaissance = $dateDeNaissance;
        $this->tailleEnCm = $tailleEnCm;
        $this->poidsEnKg = $poidsEnKg;
        $this->statut = $statut;
    }

    /**
     * Logique métier : recherche textuelle sur nom/prénom
     *
     * Utilisé par le contrôleur pour filtrer les résultats.
     * C'est une méthode métier (pas d'accès DB, juste de la logique).
     *
     * La recherche est insensible à la casse (strtolower).
     */
    public function nomOuPrenomContient(string $recherche) : bool {
        return str_contains(strtolower($this->nom), strtolower($recherche))
            || str_contains(strtolower($this->prenom), strtolower($recherche));
    }

    /**
     * Affichage "humain" pour les listes déroulantes
     * Format : "00123 : Dupont Jean" ou "00123 : Dupont Jean (BLESSE)" si pas actif
     */
    public function toString() : string {
        $selectableString = "";
        $selectableString .= $this->getNumeroDeLicence() . ' : ' . $this->nom . ' ' . $this->prenom;

        if ($this->statut !== JoueurStatut::ACTIF) {
            $selectableString .= ' (' . $this->statut->name . ')';
        }
        return $selectableString;
    }

    // === GETTERS ===
    // Tous typés pour que l'IDE sache ce qu'on retourne

    public function getJoueurId(): int
    {
        return $this->joueurId;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getNumeroDeLicence(): string
    {
        return $this->numeroDeLicence;
    }

    public function getDateDeNaissance() : DateTime {
        return $this->dateDeNaissance;
    }

    public function getTailleEnCm(): int
    {
        return $this->tailleEnCm;
    }

    public function getPoidsEnKg(): int
    {
        return $this->poidsEnKg;
    }

    public function getStatut(): ?JoueurStatut
    {
        return $this->statut;
    }

    // === SETTERS ===
    // Permettent de modifier l'objet après sa création
    // (utilisés notamment dans le DAO lors de UPDATE)

    public function setJoueurId(int $joueurId): void
    {
        $this->joueurId = $joueurId;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function setNumeroDeLicence(string $numeroDeLicence): void
    {
        $this->numeroDeLicence = $numeroDeLicence;
    }

    public function setDateDeNaissance(DateTime $dateDeNaissance): void
    {
        $this->dateDeNaissance = $dateDeNaissance;
    }

    public function setTailleEnCm(int $tailleEnCm): void
    {
        $this->tailleEnCm = $tailleEnCm;
    }

    public function setPoidsEnKg(int $poidsEnKg): void
    {
        $this->poidsEnKg = $poidsEnKg;
    }

    public function setStatut(?JoueurStatut $statut): void
    {
        $this->statut = $statut;
    }
}

