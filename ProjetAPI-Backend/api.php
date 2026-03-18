<?php
require_once __DIR__ . '/Psr4AutoloaderClass.php';

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

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Fonction utilitaire pour convertir un joueur en tableau
function joueurToArray($joueur): array {
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

// Fonction utilitaire pour convertir une rencontre en tableau
function rencontreToArray($rencontre): array {
    return [
        'rencontre_id' => $rencontre->getRencontreId(),
        'date_heure' => $rencontre->getDateEtHeure()->format('Y-m-d H:i:s'),
        'equipe_adverse' => $rencontre->getEquipeAdverse(),
        'adresse' => $rencontre->getAdresse(),
        'lieu' => $rencontre->getLieu() ? $rencontre->getLieu()->name : null,
        'resultat' => $rencontre->getResultat() ? $rencontre->getResultat()->name : null
    ];
}

// Fonction utilitaire pour convertir une participation en tableau
function participationToArray($participation): array {
    return [
        'participation_id' => $participation->getParticipationId(),
        'joueur_id' => $participation->getParticipant()->getJoueurId(),
        'joueur_nom' => $participation->getParticipant()->getNom(),
        'joueur_prenom' => $participation->getParticipant()->getPrenom(),
        'rencontre_id' => $participation->getRencontre()->getRencontreId(),
        'poste' => $participation->getPoste()->name,
        'titulaire_ou_remplacant' => $participation->getTitulaireOuRemplacant()->name,
        'performance' => $participation->getPerformance() ? $participation->getPerformance()->name : null
    ];
}

// Fonction utilitaire pour convertir un commentaire en tableau
function commentaireToArray($commentaire): array {
    return [
        'commentaire_id' => $commentaire->getCommentaireId(),
        'contenu' => $commentaire->getContenu(),
        'date' => $commentaire->getDate()->format('Y-m-d H:i:s')
    ];
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
        $result = JoueurControleur::getInstance()->supprimerJoueur($joueurId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Joueur supprimé avec succès']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
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
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
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
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
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
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
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
                'poste_le_plus_performant' => $postePerformant?->name,
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
    
    // 404 si endpoint non trouvé
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint non trouvé']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 