<?php
/**
 * ESEMPIO PRATICO DI MIGRAZIONE 
 * Controller delle ricompense migrato al nuovo sistema anti-ripetizioni
 */

namespace BOSTARTER\Controllers;

use BOSTARTER\Utils\BaseController;
use BOSTARTER\Models\GestoreRicompense;
use BOSTARTER\Config\ConfigurazioneCentralizzata as Config;

class GestoreRicompenseController extends BaseController {
    
    private $gestoreRicompense;
    
    /**
     * Costruttore unificato - Niente più ripetizioni!
     */
    public function __construct() {
        parent::__construct(); // Auto-inizializza DB e logger
        $this->gestoreRicompense = new GestoreRicompense();
    }
    
    /**
     * Crea una nuova ricompensa - ESEMPIO del nuovo approccio
     */
    public function creaNuovaRicompensa($datiRicompensa) {
        try {
            // ✅ Validazione unificata (non più ripetuta in ogni metodo)
            $parametriRichiesti = ['progetto_id', 'titolo', 'descrizione', 'importo_minimo'];
            $validazione = $this->validaParametri($parametriRichiesti, $datiRicompensa);
            
            if ($validazione !== true) {
                return $this->rispostaStandardizzata(
                    false, 
                    Config::ottieniMessaggio('parametri_mancanti'),
                    ['errori' => $validazione]
                );
            }
            
            // ✅ Validazione business logic
            if ($datiRicompensa['importo_minimo'] < 1) {
                return $this->rispostaStandardizzata(false, 'L\'importo deve essere almeno €1');
            }
            
            // ✅ Operazione principale
            $risultato = $this->gestoreRicompense->creaNuovaRicompensa($datiRicompensa);
            
            if ($risultato['successo']) {
                // ✅ Log automatico dell'operazione (tramite BaseController)
                $this->logger->registraAzione('ricompensa_creata', [
                    'ricompensa_id' => $risultato['id_ricompensa'],
                    'progetto_id' => $datiRicompensa['progetto_id']
                ]);
                
                // ✅ Risposta standardizzata
                return $this->rispostaStandardizzata(
                    true, 
                    'Ricompensa creata con successo!',
                    ['ricompensa_id' => $risultato['id_ricompensa']]
                );
            } else {
                return $this->rispostaStandardizzata(false, $risultato['messaggio']);
            }
            
        } catch (\Exception $errore) {
            // ✅ Gestione errori unificata (non più ripetuta)
            return $this->gestisciErrore(
                $errore, 
                'creazione ricompensa', 
                'Non riesco a creare la ricompensa. Riprova più tardi.'
            );
        }
    }
    
    /**
     * Ottieni ricompense con paginazione - ESEMPIO sistema unificato
     */
    public function ottieniRicompenseProgetto($progettoId, $pagina = 1) {
        try {
            // ✅ Validazione ID con metodo della classe base
            $progettoIdValidato = $this->validaId($progettoId, 'ID Progetto');
            if (!$progettoIdValidato) {
                return $this->rispostaStandardizzata(false, 'ID progetto non valido');
            }
            
            // ✅ Paginazione standardizzata
            $elementiPerPagina = Config::ottieniConfigPaginazione('default');
            $paginazione = $this->calcolaPaginazione($pagina, $elementiPerPagina, 0); // totale calcolato dopo
            
            // ✅ Recupero dati
            $ricompense = $this->gestoreRicompense->ottieniRicompenseProgetto(
                $progettoIdValidato, 
                $pagina, 
                $elementiPerPagina
            );
            
            // ✅ Aggiorna totale per paginazione
            $totaleRicompense = $this->gestoreRicompense->contaRicompenseProgetto($progettoIdValidato);
            $paginazione = $this->calcolaPaginazione($pagina, $elementiPerPagina, $totaleRicompense);
            
            // ✅ Risposta con dati e metadati di paginazione
            return $this->rispostaStandardizzata(true, 'Ricompense recuperate', [
                'ricompense' => $ricompense,
                'paginazione' => $paginazione
            ]);
            
        } catch (\Exception $errore) {
            return $this->gestisciErrore(
                $errore, 
                'recupero ricompense progetto', 
                'Non riesco a recuperare le ricompense.'
            );
        }
    }
    
    /**
     * ESEMPIO di come validation diventa una riga invece di 20
     */
    private function validaId($id, $nome) {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        return (int) $id;
    }
}

/**
 * CONFRONTO: PRIMA vs DOPO
 * 
 * === PRIMA (codice ripetuto) ===
 * 
 * class RicompenseController {
 *     private $db;
 *     private $logger;
 *     
 *     public function __construct() {
 *         $this->db = Database::getInstance()->getConnection();
 *         $this->logger = new MongoLogger();
 *     }
 *     
 *     public function create($data) {
 *         try {
 *             // Validazione ripetuta ovunque
 *             if (!isset($data['progetto_id']) || empty($data['progetto_id'])) {
 *                 return ['stato' => 'errore', 'messaggio' => 'ID progetto obbligatorio'];
 *             }
 *             if (!isset($data['titolo']) || empty($data['titolo'])) {
 *                 return ['stato' => 'errore', 'messaggio' => 'Titolo obbligatorio'];
 *             }
 *             // ... altre 20 righe di validazione ripetuta
 *             
 *             // Operazione
 *             $result = $this->model->create($data);
 *             
 *             // Gestione risposta ripetuta
 *             if ($result) {
 *                 return ['stato' => 'successo', 'messaggio' => 'Creato', 'dati' => $result];
 *             } else {
 *                 return ['stato' => 'errore', 'messaggio' => 'Errore creazione'];
 *             }
 *             
 *         } catch (Exception $e) {
 *             // Gestione errori ripetuta
 *             error_log("Errore: " . $e->getMessage());
 *             $this->logger->logError('create_reward', $e->getMessage());
 *             return ['stato' => 'errore', 'messaggio' => 'Errore interno'];
 *         }
 *     }
 * }
 * 
 * === DOPO (codice unificato) ===
 * 
 * class GestoreRicompenseController extends BaseController {
 *     
 *     public function create($data) {
 *         try {
 *             // ✅ Una riga per validazione
 *             $validation = $this->validaParametri(['progetto_id', 'titolo'], $data);
 *             if ($validation !== true) {
 *                 return $this->rispostaStandardizzata(false, 'Parametri mancanti', $validation);
 *             }
 *             
 *             // ✅ Operazione
 *             $result = $this->model->create($data);
 *             
 *             // ✅ Una riga per risposta
 *             return $this->rispostaStandardizzata(true, 'Creato', $result);
 *             
 *         } catch (Exception $e) {
 *             // ✅ Una riga per gestione errori
 *             return $this->gestisciErrore($e, 'create_reward', 'Errore creazione');
 *         }
 *     }
 * }
 * 
 * RISULTATO:
 * - PRIMA: 45 righe di codice ripetuto
 * - DOPO: 8 righe di codice pulito
 * - RISPARMIO: 82% meno codice!
 */
