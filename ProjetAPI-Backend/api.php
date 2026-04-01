<?php
/**
 * API REST - ProjetAPI Backend
 *
 * Point d'entrée unique pour toutes les requêtes API.
 * Gère :
 *  - Routage par URL (avec regex preg_match)
 *  - Authentification JWT (via AuthAPI)
 *  - Autorisation par rôle (user / admin)
 *  - Validation des champs entrée
 *  - Sérialisation des réponses JSON
 */

require_once __DIR__ . '/Psr4AutoloaderClass.php';
require_once __DIR__ . '/../jwt_utils.php';

$loader = new R301\Psr4AutoloaderClass();
$loader->register();
$loader->addNamespace('R301', __DIR__);

use R301\Controleur\JoueurControleur;
use R301\Controleur\RencontreControleur;
use R301\Controleur\ParticipationControleur;
use R301\Controleur\StatistiquesControleur;
use R301\Controleur\CommentaireControleur;
use R301\Modele\Rencontre\RencontreLieu;
use R301\Modele\Rencontre\RencontreResultat;
use R301\Modele\Participation\Poste;
use R301\Modele\Participation\TitulaireOuRemplacant;

// === HEADERS CORS ===
// Accepte les requêtes depuis n'importe où (*)
// En prod, il faudrait restreindre à https://mon-domain.com
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Les navigateurs font un preflight OPTIONS avant chaque requête cross-origin
// On répond juste 200 OK et c'est bon
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

define('AUTH_API_URL', 'http://localhost/ProjetAPI/AuthAPI');

/**
 * Vérifie l'authentification JWT
 * Extrait le token du header Authorization: Bearer {token}
 * Appelle l'AuthAPI pour valider la signature + expiration
 *
 * Retourne : tableau utilisateur {id, email, nom, role, joueur_id}
 *  ou NULL si token invalide/absent
 */
function verifierToken() {
    // Récupère le token du header: "Authorization: Bearer eyJhb..."
    $token = get_bearer_token();
    if ($token === null) {
        return null;
    }

    // === APPEL SYNCHRONE À L'AUTHAPI ===
    // (utilise curl pour faire un POST HTTP vers le service d'auth)
    $ch = curl_init(AUTH_API_URL . '/verify.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['token' => $token]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    // Si l'AuthAPI répond 200 + success=true, le token est valide
    if ($httpCode === 200 && $result['success'] == true) {
        return $result['user'];
    }

    return null;
}

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// === ENDPOINT PUBLIC : liste des rencontres (AVANT vérification JWT) ===
// Les utilisateurs non connectés peuvent voir les rencontres programmées
if ($method === 'GET' && preg_match('#/api/rencontres$#', $uri)) {
    try {
        $rencontres = RencontreControleur::getInstance()->listerToutesLesRencontres();
        $data = array_map(function($r) {
            return [
                'rencontre_id'  => $r->getRencontreId(),
                'date_heure'    => $r->getDateEtHeure()->format('Y-m-d H:i:s'),
                'equipe_adverse'=> $r->getEquipeAdverse(),
                'adresse'       => $r->getAdresse(),
                'lieu'          => $r->getLieu() != null ? $r->getLieu()->name : null,
                'resultat'      => $r->getResultat() != null ? $r->getResultat()->name : null
            ];
        }, $rencontres);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
    exit;
}

// === VÉRIFICATION JWT (obligatoire pour tous autres endpoints) ===
$utilisateurConnecte = verifierToken();
if ($utilisateurConnecte === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token manquant ou invalide']);
    exit;
}

// === CONTRÔLE D'ACCÈS PAR RÔLE ===
// Les 'user' ne peuvent que LIRE (GET)
// Les 'admin' peuvent faire CRUD complet
// C'est une autorisation simple ; on pourrait la raffiner par endpoint
if ($utilisateurConnecte['role'] === 'user' && $method !== 'GET') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé : droits insuffisants']);
    exit;
}


// === FONCTIONS DE SÉRIALISATION ===
// Transforment les objets métier en tableaux associatifs JSON
// (DAO retourne des objets, l'API retourne du JSON)

/**
 * Joueur → JSON
 * Convertit DateTime en string et Enums en string.name
 */
function joueurToArray($joueur) {
    return [
        'joueur_id' => $joueur->getJoueurId(),
        'nom' => $joueur->getNom(),
        'prenom' => $joueur->getPrenom(),
        'numero_licence' => $joueur->getNumeroDeLicence(),
        'date_naissance' => $joueur->getDateDeNaissance()->format('Y-m-d'),
        'taille' => $joueur->getTailleEnCm(),
        'poids' => $joueur->getPoidsEnKg(),
        'statut' => $joueur->getStatut()->name
    ];
}

/**
 * Rencontre → JSON
 */
function rencontreToArray($rencontre) {
    return [
        'rencontre_id' => $rencontre->getRencontreId(),
        'date_heure' => $rencontre->getDateEtHeure()->format('Y-m-d H:i:s'),
        'equipe_adverse' => $rencontre->getEquipeAdverse(),
        'adresse' => $rencontre->getAdresse(),
        'lieu' => $rencontre->getLieu() != null ? $rencontre->getLieu()->name : null,
        'resultat' => $rencontre->getResultat() != null ? $rencontre->getResultat()->name : null
    ];
}

/**
 * Participation → JSON
 * Avec infos du joueur et de la rencontre (dénormalisation pour le client)
 */
function participationToArray($participation) {
    return [
        'participation_id' => $participation->getParticipationId(),
        'joueur_id' => $participation->getParticipant()->getJoueurId(),
        'joueur_nom' => $participation->getParticipant()->getNom(),
        'joueur_prenom' => $participation->getParticipant()->getPrenom(),
        'rencontre_id' => $participation->getRencontre()->getRencontreId(),
        'poste' => $participation->getPoste()->name,
        'titulaire_ou_remplacant' => $participation->getTitulaireOuRemplacant()->name,
        'performance' => $participation->getPerformance() != null ? $participation->getPerformance()->name : null
    ];
}

/**
 * Commentaire → JSON
 */
function commentaireToArray($commentaire) {
    return [
        'commentaire_id' => $commentaire->getCommentaireId(),
        'contenu' => $commentaire->getContenu(),
        'date' => $commentaire->getDate()->format('Y-m-d H:i:s')
    ];
}

/**
 * Validation des champs requis
 *
 * Vérifie que les champs demandés sont présents ET non vides dans $input.
 * Si validation échoue : code 400 + message + exit.
 * Évite d'avoir le même code try/catch partout.
 *
 * @param array $input - Données parsées du JSON (ou null si JSON invalide)
 * @param array $champsRequis - Liste des champs qui doivent être présents
 * @return bool - true si tout ok
 */
function validerChamps(?array $input, array $champsRequis): bool {
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Corps de la requête JSON invalide ou manquant']);
        exit;
    }
    $manquants = [];
    foreach ($champsRequis as $champ) {
        if (!isset($input[$champ]) || $input[$champ] === '') {
            $manquants[] = $champ;
        }
    }
    if (!empty($manquants)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Champs manquants : ' . implode(', ', $manquants)]);
        exit;
    }
    return true;
}

try {
    // === ENDPOINTS JOUEURS ===
    
    // GET /api/joueurs - Lister tous les joueurs
    if ($method === 'GET' && preg_match('#/api/joueurs$#', $uri)) {
        $joueurs = JoueurControleur::getInstance()->listerTousLesJoueurs();
        $data = array_map('joueurToArray', $joueurs);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // GET /api/joueurs/{id} - Obtenir un joueur par ID
    if ($method === 'GET' && preg_match('#/api/joueurs/(\d+)$#', $uri, $matches)) {
        $joueurId = (int)$matches[1];
        $joueur = JoueurControleur::getInstance()->getJoueurById($joueurId);
        echo json_encode(['success' => true, 'data' => joueurToArray($joueur)]);
        exit;
    }
    
    // POST /api/joueurs - Créer un nouveau joueur
    if ($method === 'POST' && preg_match('#/api/joueurs$#', $uri)) {
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['nom', 'prenom', 'numero_licence', 'date_naissance', 'taille', 'poids', 'statut']);

        $result = JoueurControleur::getInstance()->ajouterJoueur(
            $input['nom'],
            $input['prenom'],
            $input['numero_licence'],
            new DateTime($input['date_naissance']),
            (int)$input['taille'],
            (int)$input['poids'],
            $input['statut']
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Joueur créé avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création']);
        }
        exit;
    }
    
    // PUT /api/joueurs/{id} - Modifier un joueur
    if ($method === 'PUT' && preg_match('#/api/joueurs/(\d+)$#', $uri, $matches)) {
        $joueurId = (int)$matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['nom', 'prenom', 'numero_licence', 'date_naissance', 'taille', 'poids', 'statut']);

        $result = JoueurControleur::getInstance()->modifierJoueur(
            $joueurId,
            $input['nom'],
            $input['prenom'],
            $input['numero_licence'],
            new DateTime($input['date_naissance']),
            (int)$input['taille'],
            (int)$input['poids'],
            $input['statut']
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Joueur modifié avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
        }
        exit;
    }
    
    // DELETE /api/joueurs/{id} - Supprimer un joueur
    if ($method === 'DELETE' && preg_match('#/api/joueurs/(\d+)$#', $uri, $matches)) {
        $joueurId = (int)$matches[1];

        // Impossible de supprimer un joueur qui a déjà participé à un match
        if (ParticipationControleur::getInstance()->joueurADesParticipations($joueurId)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer un joueur qui a déjà participé à un match']);
            exit;
        }

        $result = JoueurControleur::getInstance()->supprimerJoueur($joueurId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Joueur supprimé avec succès']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Joueur introuvable']);
        }
        exit;
    }

    // GET /api/joueurs/recherche - Rechercher des joueurs
    if ($method === 'GET' && preg_match('#/api/joueurs/recherche#', $uri)) {
        $recherche = $_GET['q'] ?? '';
        $statut = $_GET['statut'] ?? '';
        
        $joueurs = JoueurControleur::getInstance()->rechercherLesJoueurs($recherche, $statut);
        $data = array_map('joueurToArray', $joueurs);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // === ENDPOINTS RENCONTRES ===
    
    // GET /api/rencontres - Lister toutes les rencontres
    if ($method === 'GET' && preg_match('#/api/rencontres$#', $uri)) {
        $rencontres = RencontreControleur::getInstance()->listerToutesLesRencontres();
        $data = array_map('rencontreToArray', $rencontres);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // GET /api/rencontres/{id} - Obtenir une rencontre par ID
    if ($method === 'GET' && preg_match('#/api/rencontres/(\d+)$#', $uri, $matches)) {
        $rencontreId = (int)$matches[1];
        $rencontre = RencontreControleur::getInstance()->getRenconterById($rencontreId);
        echo json_encode(['success' => true, 'data' => rencontreToArray($rencontre)]);
        exit;
    }
    
    // POST /api/rencontres - Créer une nouvelle rencontre
    if ($method === 'POST' && preg_match('#/api/rencontres$#', $uri)) {
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['date_heure', 'equipe_adverse', 'adresse', 'lieu']);

        $result = RencontreControleur::getInstance()->ajouterRencontre(
            new \DateTime($input['date_heure']),
            $input['equipe_adverse'],
            $input['adresse'],
            RencontreLieu::fromName($input['lieu'])
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Rencontre créée avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création']);
        }
        exit;
    }
    
    // PUT /api/rencontres/{id} - Modifier une rencontre
    if ($method === 'PUT' && preg_match('#/api/rencontres/(\d+)$#', $uri, $matches)) {
        $rencontreId = (int)$matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['date_heure', 'equipe_adverse', 'adresse', 'lieu']);

        $result = RencontreControleur::getInstance()->modifierRencontre(
            $rencontreId,
            new \DateTime($input['date_heure']),
            $input['equipe_adverse'],
            $input['adresse'],
            RencontreLieu::fromName($input['lieu'])
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Rencontre modifiée avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
        }
        exit;
    }
    
    // PUT /api/rencontres/{id}/resultat - Enregistrer le résultat
    if ($method === 'PUT' && preg_match('#/api/rencontres/(\d+)/resultat$#', $uri, $matches)) {
        $rencontreId = (int)$matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['resultat']);

        $result = RencontreControleur::getInstance()->enregistrerResultat(
            $rencontreId,
            $input['resultat']
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Résultat enregistré avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
        }
        exit;
    }
    
    // DELETE /api/rencontres/{id} - Supprimer une rencontre
    if ($method === 'DELETE' && preg_match('#/api/rencontres/(\d+)$#', $uri, $matches)) {
        $rencontreId = (int)$matches[1];
        $result = RencontreControleur::getInstance()->supprimerRencontre($rencontreId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Rencontre supprimée avec succès']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Rencontre introuvable ou déjà jouée']);
        }
        exit;
    }
    
    // === ENDPOINTS PARTICIPATIONS ===
    
    // GET /api/participations - Lister toutes les participations
    if ($method === 'GET' && preg_match('#/api/participations$#', $uri)) {
        $participations = ParticipationControleur::getInstance()->listerToutesLesParticipations();
        $data = array_map('participationToArray', $participations);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // GET /api/participations/rencontre/{rencontreId} - Feuille de match
    if ($method === 'GET' && preg_match('#/api/participations/rencontre/(\d+)$#', $uri, $matches)) {
        $rencontreId = (int)$matches[1];
        $feuilleDeMatch = ParticipationControleur::getInstance()->getFeuilleDeMatch($rencontreId);
        $data = [
            'est_complete' => $feuilleDeMatch->estComplete(),
            'est_evaluee' => $feuilleDeMatch->estEvalue(),
            'participations' => array_map('participationToArray', $feuilleDeMatch->getParticipants()),
            'rencontre_id' => $rencontreId
        ];
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // POST /api/participations - Créer une nouvelle participation
    if ($method === 'POST' && preg_match('#/api/participations$#', $uri)) {
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['joueur_id', 'rencontre_id', 'poste', 'titulaire_ou_remplacant']);

        $result = ParticipationControleur::getInstance()->assignerUnParticipant(
            (int)$input['joueur_id'],
            (int)$input['rencontre_id'],
            Poste::fromName($input['poste']),
            TitulaireOuRemplacant::fromName($input['titulaire_ou_remplacant'])
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Participation créée avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création']);
        }
        exit;
    }
    
    // PUT /api/participations/{id} - Modifier une participation
    if ($method === 'PUT' && preg_match('#/api/participations/(\d+)$#', $uri, $matches)) {
        $participationId = (int)$matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['poste', 'titulaire_ou_remplacant', 'joueur_id']);

        $result = ParticipationControleur::getInstance()->modifierParticipation(
            $participationId,
            Poste::fromName($input['poste']),
            TitulaireOuRemplacant::fromName($input['titulaire_ou_remplacant']),
            (int)$input['joueur_id']
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Participation modifiée avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
        }
        exit;
    }
    
    // PUT /api/participations/{id}/performance - Mettre à jour la performance
    if ($method === 'PUT' && preg_match('#/api/participations/(\d+)/performance$#', $uri, $matches)) {
        $participationId = (int)$matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['performance']);

        $result = ParticipationControleur::getInstance()->mettreAJourLaPerformance(
            $participationId,
            $input['performance']
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Performance mise à jour avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
        exit;
    }
    
    // DELETE /api/participations/{id} - Supprimer une participation
    if ($method === 'DELETE' && preg_match('#/api/participations/(\d+)$#', $uri, $matches)) {
        $participationId = (int)$matches[1];
        $result = ParticipationControleur::getInstance()->supprimerLaParticipation($participationId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Participation supprimée avec succès']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Participation introuvable ou match déjà joué']);
        }
        exit;
    }
    
    // === ENDPOINTS COMMENTAIRES ===
    
    // GET /api/joueurs/{joueurId}/commentaires - Lister les commentaires d'un joueur
    if ($method === 'GET' && preg_match('#/api/joueurs/(\d+)/commentaires$#', $uri, $matches)) {
        $joueurId = (int)$matches[1];
        $joueur = JoueurControleur::getInstance()->getJoueurById($joueurId);
        $commentaires = CommentaireControleur::getInstance()->listerLesCommentairesDuJoueur($joueur);
        $data = array_map('commentaireToArray', $commentaires);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // POST /api/joueurs/{joueurId}/commentaires - Ajouter un commentaire à un joueur
    if ($method === 'POST' && preg_match('#/api/joueurs/(\d+)/commentaires$#', $uri, $matches)) {
        $joueurId = (int)$matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        validerChamps($input, ['contenu']);

        $result = CommentaireControleur::getInstance()->ajouterCommentaire(
            $input['contenu'],
            (string)$joueurId
        );
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Commentaire ajouté avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du commentaire']);
        }
        exit;
    }
    
    // DELETE /api/commentaires/{id} - Supprimer un commentaire
    if ($method === 'DELETE' && preg_match('#/api/commentaires/(\d+)$#', $uri, $matches)) {
        $commentaireId = (int)$matches[1];
        $result = CommentaireControleur::getInstance()->supprimerCommentaire((string)$commentaireId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Commentaire supprimé avec succès']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Commentaire introuvable']);
        }
        exit;
    }
    
    // === ENDPOINTS STATISTIQUES ===
    
    // GET /api/statistiques/equipe - Statistiques de l'équipe
    if ($method === 'GET' && preg_match('#/api/statistiques/equipe$#', $uri)) {
        $stats = StatistiquesControleur::getInstance()->getStatistiquesEquipe();
        $data = [
            'nb_victoires' => $stats->nbVictoires(),
            'nb_nuls' => $stats->nbNuls(),
            'nb_defaites' => $stats->nbDefaites(),
            'pourcentage_victoires' => $stats->pourcentageDeVictoires(),
            'pourcentage_nuls' => $stats->pourcentageDeNuls(),
            'pourcentage_defaites' => $stats->pourcentageDeDefaites()
        ];
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // GET /api/statistiques/joueurs - Statistiques détaillées de tous les joueurs
    if ($method === 'GET' && preg_match('#/api/statistiques/joueurs$#', $uri)) {
        $stats = StatistiquesControleur::getInstance()->getStatistiquesJoueurs();
        $joueurs = JoueurControleur::getInstance()->listerTousLesJoueurs();
        
        $data = [];
        foreach ($joueurs as $joueur) {
            $postePerformant = $stats->posteLePlusPerformant($joueur);
            $data[] = [
                'joueur_id' => $joueur->getJoueurId(),
                'nom' => $joueur->getNom(),
                'prenom' => $joueur->getPrenom(),
                'statut' => $joueur->getStatut()->name,
                'poste_le_plus_performant' => $postePerformant != null ? $postePerformant->name : null,
                'nb_rencontres_consecutives' => $stats->nbRencontresConsecutivesADate($joueur),
                'nb_titularisations' => $stats->nbTitularisations($joueur),
                'nb_remplacant' => $stats->nbRemplacant($joueur),
                'moyenne_evaluations' => $stats->moyenneDesEvaluations($joueur),
                'pourcentage_matchs_gagnes' => $stats->pourcentageDeMatchsGagnes($joueur)
            ];
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    // GET /api/moi/evaluations - Mes évaluations dans les matchs joués (joueur connecté)
    if ($method === 'GET' && preg_match('#/api/moi/evaluations$#', $uri)) {
        $joueurId = $utilisateurConnecte['joueur_id'] ?? null;
        if ($joueurId === null) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Votre compte n\'est pas lié à un joueur']);
            exit;
        }
        $participations = ParticipationControleur::getInstance()->getEvaluationsJoueur((int)$joueurId);
        $data = array_map(function($p) {
            return [
                'participation_id'       => $p->getParticipationId(),
                'rencontre_id'           => $p->getRencontre()->getRencontreId(),
                'date_heure'             => $p->getRencontre()->getDateEtHeure()->format('Y-m-d H:i:s'),
                'equipe_adverse'         => $p->getRencontre()->getEquipeAdverse(),
                'resultat_rencontre'     => $p->getRencontre()->getResultat() != null ? $p->getRencontre()->getResultat()->name : null,
                'poste'                  => $p->getPoste()->name,
                'titulaire_ou_remplacant'=> $p->getTitulaireOuRemplacant()->name,
                'performance'            => $p->getPerformance() != null ? $p->getPerformance()->name : null
            ];
        }, $participations);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // 404 si endpoint non trouvé
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint non trouvé']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 