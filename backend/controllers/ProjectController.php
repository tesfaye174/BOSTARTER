<?php
namespace BOSTARTER\Controllers;

/**
 * GESTORE PROGETTI BOSTARTER
 * 
 * Questo controller gestisce tutti i progetti della piattaforma:
 * - Creazione di nuovi progetti
 * - Modifica e aggiornamento progetti esistenti
 * - Pubblicazione e cancellazione progetti
 * - Ricerca e filtri sui progetti
 * 
 * È come il direttore di una galleria d'arte: decide quali opere esporre,
 * come presentarle e si assicura che tutto sia in ordine per i visitatori!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione completamente riscritta per essere più umana
 */

// Includiamo le dipendenze necessarie
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/BaseController.php';

use BOSTARTER\Utils\BaseController;

class GestoreProgetti extends BaseController {
    /**
     * Costruttore - Prepara il nostro gestore progetti
     */
    public function __construct() {
        parent::__construct(); // Inizializza connessione DB e logger dalla classe base
    }/**
     * Crea un nuovo progetto sulla piattaforma
     * 
     * È come quando un artista presenta la sua nuova opera alla galleria
     * 
     * @param array $datiProgetto Array contenente tutti i dati del progetto
     * @return array Risultato dell'operazione con status e messaggio
     */
    public function creaNuovoProgetto($datiProgetto) {
        try {
            // Prima di tutto, controlliamo che i dati siano validi
            $validazione = Validator::validateProjectData($datiProgetto);
            if ($validazione !== true) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Dati del progetto non validi: ' . implode(', ', $validazione)
                ];
            }

            // Prepariamo la chiamata alla stored procedure per creare il progetto
            $statement = $this->connessioneDatabase->prepare("CALL create_project(?, ?, ?, ?, ?, ?, @p_project_id, @p_success, @p_message)");
            
            // Associamo i parametri in modo sicuro
            $statement->bindParam(1, $datiProgetto['name']);
            $statement->bindParam(2, $datiProgetto['creator_id'], \PDO::PARAM_INT);
            $statement->bindParam(3, $datiProgetto['description']);
            $statement->bindParam(4, $datiProgetto['budget']);
            $statement->bindParam(5, $datiProgetto['project_type']);
            $statement->bindParam(6, $datiProgetto['end_date']);
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
     * @param array $datiRicompensa Array contenente i dati della ricompensa
     * @return array Risultato dell'operazione
     */
    public function aggiungiRicompensaProgetto($datiRicompensa) {
        // Validiamo i dati della ricompensa
        $validazione = Validator::validateRewardData($datiRicompensa);
        if ($validazione !== true) {
            return [
                'stato' => 'errore',
                'messaggio' => implode(', ', $validazione)
            ];
        }

        try {            // Prepariamo la chiamata per aggiungere la ricompensa
            $statement = $this->connessioneDatabase->prepare("CALL add_project_reward(?, ?, ?, ?, @p_success, @p_message)");
            
            $statement->bindParam(1, $datiRicompensa['project_id'], \PDO::PARAM_INT);
            $statement->bindParam(2, $datiRicompensa['title']);
            $statement->bindParam(3, $datiRicompensa['description']);
            $statement->bindParam(4, $datiRicompensa['amount']);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco ad aggiungere la ricompensa: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Pubblica un progetto rendendolo visibile a tutti gli utenti
     * 
     * @param int $idProgetto L'ID del progetto da pubblicare
     * @return array Risultato dell'operazione
     */
    public function pubblicaProgetto($idProgetto) {        try {
            $statement = $this->connessioneDatabase->prepare("CALL publish_project(?, @p_success, @p_message)");            
            $statement->bindParam(1, $idProgetto, \PDO::PARAM_INT);
            $statement->execute();

            $risultato = $this->connessioneDatabase->query("SELECT @p_success as success, @p_message as message")->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'stato' => $risultato['success'] ? 'successo' : 'errore',
                'messaggio' => $risultato['message']
            ];        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a pubblicare il progetto: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Recupera tutti i progetti di un creatore specifico
     * 
     * @param int $idCreatore L'ID dell'utente creatore
     * @return array Lista dei progetti del creatore
     */
    public function ottieniProgettiCreatore($idCreatore) {        try {
            $statement = $this->connessioneDatabase->prepare("CALL get_creator_projects(?)");
            
            $statement->bindParam(1, $idCreatore, \PDO::PARAM_INT);
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
     * @param array $datiFinanziamento Array con project_id, user_id e amount
     * @return array Risultato dell'operazione di finanziamento
     */
    public function finanziaProgetto($datiFinanziamento) {
        // Validiamo i dati del finanziamento
        $validazione = Validator::validateFundData($datiFinanziamento);
        if ($validazione !== true) {
            return [
                'stato' => 'errore',
                'messaggio' => implode(', ', $validazione)
            ];
        }

        try {            // Chiamiamo la stored procedure per il finanziamento
            $statement = $this->connessioneDatabase->prepare("CALL fund_project(?, ?, ?, @p_success, @p_message)");
            
            $statement->bindParam(1, $datiFinanziamento['project_id'], \PDO::PARAM_INT);
            $statement->bindParam(2, $datiFinanziamento['user_id'], \PDO::PARAM_INT);
            $statement->bindParam(3, $datiFinanziamento['amount']);
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
    
    // ======================================
    // METODI DI COMPATIBILITÀ CON IL VECCHIO CODICE
    // ======================================
    
    /**
     * Alias per creaNuovoProgetto() - compatibilità
     */
    public function creaProgetto($datiProgetto) {
        return $this->creaNuovoProgetto($datiProgetto);
    }
    
    /**
     * Alias per aggiungiRicompensaProgetto() - compatibilità
     */
    public function aggiungiRicompensa($datiRicompensa) {
        return $this->aggiungiRicompensaProgetto($datiRicompensa);
    }
    
    /**
     * Alias per pubblicaProgetto() - compatibilità
     */
    public function pubblica($idProgetto) {
        return $this->pubblicaProgetto($idProgetto);
    }
    
    /**
     * Alias per finanziaProgetto() - compatibilità
     */
    public function finanzia($datiFinanziamento) {
        return $this->finanziaProgetto($datiFinanziamento);
    }
}

// Alias per compatibilità con il codice esistente
class_alias('BOSTARTER\Controllers\GestoreProgetti', 'ProjectController');