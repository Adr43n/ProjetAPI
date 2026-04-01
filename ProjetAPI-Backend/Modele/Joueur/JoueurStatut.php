<?php
namespace R301\Modele\Joueur;

/**
 * JoueurStatut - Énumération PHP 8
 *
 * Défini les seuls statuts possibles pour un joueur.
 * C'est BEAUCOUP mieux que des strings brutes dans le code.
 *
 * Avantages de l'enum vs string :
 *  ✅ Typage fort : JoueurStatut au lieu de string (IDE auto-complète)
 *  ✅ Pas de mauvaise valeur : pas d'erreur "CTIF" au lieu de "ACTIF"
 *  ✅ Code self-documenting : on voit les choix possibles
 *  ✅ Facile à itérer : JoueurStatut::cases() retourne tous les statuts
 *
 * Avant Enum (mauvaise pratique) :
 *   $statut = "ACTIF";  // risque d'erreur typo
 *
 * Après Enum (bonne pratique) :
 *   $statut = JoueurStatut::ACTIF;  // validation à la compilation
 *
 * Stockage en BDD :
 *   On stocke le nom (string) "ACTIF", "BLESSE", etc.
 *   Le DAO reconvertit : string → Enum via fromName()
 */
enum JoueurStatut
{
    // Les seules valeurs autorisées
    case ACTIF;     // Peut jouer
    case BLESSE;    // Blessé temporaire
    case ABSENT;    // Indisponible pour d'autres raisons
    case SUSPENDU;  // Sanction disciplinaire

    /**
     * Convertit une string en Enum
     * Utile lors du chargement de la BDD où on a des strings
     *
     * Exemple :
     *   $statut = JoueurStatut::fromName('ACTIF');  // retourne JoueurStatut::ACTIF
     *   $statut = JoueurStatut::fromName('CTIF');   // retourne null (erreur)
     */
    public static function fromName(string $name): ?JoueurStatut
    {
        // Itère sur tous les cas de l'enum et cherche le nom qui correspond
        foreach (self::cases() as $status) {
            if ($name === $status->name) {
                return $status;
            }
        }

        // Pas trouvé → retourne null (ou on pourrait throw une exception)
        return null;
    }
}
