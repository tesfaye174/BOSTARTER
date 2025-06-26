<?php
namespace BOSTARTER\Controllers;

/**
 * Controller Progetti BOSTARTER
 * 
 * Questo controller implementa tutte le operazioni CRUD relative ai progetti:
 * - Creazione e validazione di nuovi progetti
 * - Aggiunta di ricompense ai progetti
 * - Pubblicazione e gestione visibilità progetti
 * - Operazioni di finanziamento e transazioni monetarie
 * - Recupero progetti per creatore o categoria
 * 
 * Interagisce con le stored procedure del database per garantire
 * l'integrità transazionale e la sicurezza delle operazioni.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 * @since 1.5.0 - Aggiunta sicurezza transazionale
 */

// Includiamo le dipendenze necessarie
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/BaseController.php';

use BOSTARTER\Utils\BaseController;

class ProjectController extends BaseController {
    /**
     * Costruttore - Inizializza la connessione al database e logging 
     * ereditati dalla classe BaseController
     * 
     * @throws PDOException Se la connessione al database fallisce
     */
    public function __construct() {
        parent::__construct(); // Inizializza connessione DB e logger dalla classe base
    }

    /**
     * Crea un nuovo progetto sulla piattaforma
     * 
     * Implementa la validazione dei dati e utilizza stored procedure
     * per garantire l'atomicità dell'operazione e la consistenza dei dati.
     * Gestisce anche errori di database con messaggi appropriati.
     * 
     * @param array $projectData Dati del progetto con chiavi:
     *                          - name: Nome del progetto (string)
     *                          - creator_id: ID dell'utente creatore (int)
     *                          - description: Descrizione dettagliata (string)
     *                          - budget: Budget richiesto in euro (float)
     *                          - project_type: Tipologia del progetto (string)
     *                          - end_date: Data termine raccolta fondi (YYYY-MM-DD)
     * @return array Risultato dell'operazione con status, messaggio e ID progetto se successo
     * @throws PDOException In caso di errori di database
     */
    public function createProject($projectData) {
        try {
            // Prima di tutto, controlliamo che i dati siano validi
            $validation = Validator::validateProjectData($projectData);
            if ($validation !== true) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Dati del progetto non validi: ' . implode(', ', $validation)
                ];
            }

            // Prepariamo la chiamata alla stored procedure per creare il progetto
            $statement = $this->connessioneDatabase->prepare("CALL create_project(?, ?, ?, ?, ?, ?, @p_project_id, @p_success, @p_message)");
            
            // Associamo i parametri in modo sicuro
            $statement->bindParam(1, $projectData['name']);
            $statement->bindParam(2, $projectData['creator_id'], \PDO::PARAM_INT);
            $statement->bindParam(3, $projectData['description']);
            $statement->bindParam(4, $projectData['budget']);
            $statement->bindParam(5, $projectData['project_type']);
            $statement->bindParam(6, $projectData['end_date']);
            $statement->execute();

            // Otteniamo il risultato della stored procedure
            $risultato = $this->connessioneDatabase->query("SELECT @p_project_id as project_id, @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            if ($risultato['success']) {
                return [
                    'stato' => 'successo',
                    'messaggio' => 'Progetto creato con successo! Ora è visibile sulla piattaforma.',
                    'id_progetto' => $risultato['project_id']
                ];
            } else {
                return [
                    'stato' => 'errore',
                    'messaggio' => $risultato['message'],
                    'id_progetto' => null
                ];
            }
            
        } catch (\PDOException $errore) {
            // Log dell'errore per il debug
            error_log("Errore nella creazione progetto: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un problema durante la creazione del progetto. Riprova più tardi.'
            ];
        } catch (\Exception $errore) {
            // Errore generico
            error_log("Errore generico nella creazione progetto: " . $errore->getMessage());
              return [
                'stato' => 'errore',
                'messaggio' => 'Errore imprevisto. Il nostro team è stato notificato.'
            ];
        }
    }

    /**
     * Aggiunge una ricompensa a un progetto esistente
     * 
     * Le ricompense sono elementi fondamentali nei progetti di crowdfunding
     * e vengono gestite come entità separate ma collegate al progetto principale.
     * 
     * @param array $rewardData Dati della ricompensa con chiavi:
     *                         - project_id: ID del progetto associato (int)
     *                         - title: Titolo della ricompensa (string)
     *                         - description: Descrizione della ricompensa (string)
     *                         - amount: Importo minimo di donazione per ottenerla (float)
     * @return array Risultato dell'operazione
     * @throws PDOException In caso di errori di database
     */
    public function addProjectReward($rewardData) {
        // Validiamo i dati della ricompensa
        $validation = Validator::validateRewardData($rewardData);
        if ($validation !== true) {
            return [
                'stato' => 'errore',
                'messaggio' => implode(', ', $validation)
            ];
        }

        try {            
            // Prepariamo la chiamata per aggiungere la ricompensa
            $statement = $this->connessioneDatabase->prepare("CALL add_project_reward(?, ?, ?, ?, @p_success, @p_message)");
            
            $statement->bindParam(1, $rewardData['project_id'], \PDO::PARAM_INT);
            $statement->bindParam(2, $rewardData['title']);
            $statement->bindParam(3, $rewardData['description']);
            $statement->bindParam(4, $rewardData['amount']);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];        
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco ad aggiungere la ricompensa: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Pubblica un progetto rendendolo visibile a tutti gli utenti
     * 
     * La pubblicazione è un'operazione critica che implica controlli di validità
     * e completezza del progetto. Solo progetti completi possono essere pubblicati.
     * La stored procedure verifica la presenza di tutti i campi obbligatori e
     * almeno una ricompensa prima della pubblicazione.
     * 
     * @param int $projectId L'ID del progetto da pubblicare
     * @return array Risultato dell'operazione
     * @throws PDOException In caso di errori di database
     */
    public function publishProject($projectId) {        
        try {
            $statement = $this->connessioneDatabase->prepare("CALL publish_project(?, @p_success, @p_message)");            
            $statement->bindParam(1, $projectId, \PDO::PARAM_INT);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];        
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a pubblicare il progetto: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Recupera tutti i progetti di un creatore specifico
     * 
     * Questo metodo utilizza una stored procedure ottimizzata per recuperare
     * tutti i progetti associati a un creatore, inclusi i dati sullo stato
     * attuale di finanziamento.
     * 
     * @param int $creatorId L'ID dell'utente creatore
     * @return array Risultato contenente lo stato dell'operazione e la lista dei progetti
     * @throws PDOException In caso di errori di database
     */
    public function getCreatorProjects($creatorId) {        
        try {
            $statement = $this->connessioneDatabase->prepare("CALL get_creator_projects(?)");
            
            $statement->bindParam(1, $creatorId, \PDO::PARAM_INT);
            $statement->execute();

            return [
                'stato' => 'successo',
                'progetti' => $statement->fetchAll(\PDO::FETCH_ASSOC)
            ];
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a recuperare i progetti: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Gestisce il finanziamento di un progetto da parte di un utente
     * 
     * Implementa una transazione per garantire che il finanziamento sia registrato
     * correttamente e che vengano creati tutti i record associati (ricompense, notifiche).
     * La stored procedure gestisce l'atomicità dell'operazione e i controlli di validità.
     * 
     * @param array $fundingData Array con i dati del finanziamento:
     *                          - project_id: ID del progetto da finanziare (int)
     *                          - user_id: ID dell'utente finanziatore (int)
     *                          - amount: Importo del finanziamento in euro (float)
     * @return array Risultato dell'operazione di finanziamento
     * @throws PDOException In caso di errori nelle transazioni
     */
    public function fundProject($fundingData) {
        // Validiamo i dati del finanziamento
        $validation = Validator::validateFundData($fundingData);
        if ($validation !== true) {
            return [
                'stato' => 'errore',
                'messaggio' => implode(', ', $validation)
            ];
        }

        try {            
            // Chiamiamo la stored procedure per il finanziamento
            $statement = $this->connessioneDatabase->prepare("CALL fund_project(?, ?, ?, @p_success, @p_message)");
            
            $statement->bindParam(1, $fundingData['project_id'], \PDO::PARAM_INT);
            $statement->bindParam(2, $fundingData['user_id'], \PDO::PARAM_INT);
            $statement->bindParam(3, $fundingData['amount']);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un problema durante il finanziamento: ' . $errore->getMessage()
            ];        
        }
    }
}