<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\FundingModel;
use BOSTARTER\Backend\Models\ProjectModel; // Per verificare stato progetto
use BOSTARTER\Backend\Router;

class FundingController {

    private $fundingModel;
    private $projectModel;

    public function __construct() {
        $this->fundingModel = new FundingModel();
        $this->projectModel = new ProjectModel();
    }

    /**
     * Aggiunge un nuovo finanziamento a un progetto.
     * Richiede autenticazione.
     */
    public function addFunding(): void {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            Router::jsonResponse(['error' => 'Autenticazione richiesta per finanziare.'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Validazione base
        if (!isset($input['progetto_nome'], $input['importo'], $input['reward_codice'])) {
            Router::jsonResponse(['error' => 'Dati mancanti per il finanziamento (progetto_nome, importo, reward_codice).'], 400);
            return;
        }
        if (!is_numeric($input['importo']) || $input['importo'] <= 0) {
             Router::jsonResponse(['error' => 'L\'importo deve essere un numero positivo.'], 400);
             return;
        }

        // Verifica che il progetto esista e sia aperto
        $project = $this->projectModel->findByName($input['progetto_nome']);
        if (!$project) {
             Router::jsonResponse(['error' => 'Progetto non trovato.'], 404);
             return;
        }
        if ($project['stato'] !== 'aperto') {
             Router::jsonResponse(['error' => 'Questo progetto non è più aperto ai finanziamenti.'], 403); // Forbidden
             return;
        }
        // TODO: Verificare che la reward esista per quel progetto

        $fundingData = [
            'utente_email' => $_SESSION['user_email'],
            'progetto_nome' => $input['progetto_nome'],
            'importo' => $input['importo'],
            'reward_codice' => $input['reward_codice']
        ];

        try {
            if ($this->fundingModel->addFunding($fundingData)) {
                // Il trigger nel DB dovrebbe aver gestito l'eventuale chiusura del progetto
                // Potresti voler restituire lo stato aggiornato del progetto o il totale raccolto
                $newTotal = $this->fundingModel->getTotalFundingForProject($input['progetto_nome']);
                Router::jsonResponse([
                    'message' => 'Finanziamento aggiunto con successo.',
                    'progetto_nome' => $input['progetto_nome'],
                    'nuovo_totale_raccolto' => $newTotal
                ], 201);
            } else {
                // L'errore potrebbe essere dovuto a reward non valida, progetto chiuso nel frattempo, etc.
                Router::jsonResponse(['error' => 'Errore durante l\'aggiunta del finanziamento. Verifica che la reward sia valida e il progetto sia aperto.'], 500);
            }
        } catch (\PDOException $e) {
            error_log("Errore DB in addFunding (Controller): " . $e->getMessage());
            // Gestisci violazioni FK o altri errori DB
            if ($e->getCode() == 23000) { // Violazione FK
                 Router::jsonResponse(['error' => 'Errore: Utente, progetto o reward non validi.'], 400);
            } else {
                 Router::jsonResponse(['error' => 'Errore database durante l\'aggiunta del finanziamento.'], 500);
            }
        } catch (\Exception $e) {
            error_log("Errore generico in addFunding (Controller): " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore interno del server.'], 500);
        }
    }

    /**
     * Ottiene i finanziamenti per un progetto specifico (accessibile pubblicamente?).
     */
    public function getProjectFundings(): void {
        if (!isset($_GET['projectName'])) {
            Router::jsonResponse(['error' => 'Nome del progetto mancante (?projectName=...).'], 400);
            return;
        }
        $projectName = $_GET['projectName'];

        try {
            $fundings = $this->fundingModel->getFundingsByProject($projectName);
            Router::jsonResponse($fundings);
        } catch (\Exception $e) {
            error_log("Errore in getProjectFundings: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore nel recupero dei finanziamenti del progetto.'], 500);
        }
    }

    /**
     * Ottiene i finanziamenti effettuati dall'utente loggato.
     * Richiede autenticazione.
     */
    public function getUserFundings(): void {
        session_start();

        if (!isset($_SESSION['user_email'])) {
            Router::jsonResponse(['error' => 'Autenticazione richiesta.'], 401);
            return;
        }
        $userEmail = $_SESSION['user_email'];

        try {
            $fundings = $this->fundingModel->getFundingsByUser($userEmail);
            Router::jsonResponse($fundings);
        } catch (\Exception $e) {
            error_log("Errore in getUserFundings: " . $e->getMessage());
            Router::jsonResponse(['error' => 'Errore nel recupero dei tuoi finanziamenti.'], 500);
        }
    }

}
?>