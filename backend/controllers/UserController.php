<?php
namespace BOSTARTER\Controllers;

/**
 * GESTORE UTENTI BOSTARTER
 * 
 * Questo controller gestisce tutto ciò che riguarda gli utenti registrati:
 * - Visualizzazione e modifica del profilo
 * - Gestione dei dati personali
 * - Cambio password
 * - Impostazioni privacy
 * - Statistiche utente
 * 
 * È come l'ufficio anagrafe: tiene traccia di tutti i dati degli utenti
 * e permette loro di aggiornarli quando necessario!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione completamente nuova
 */

use BOSTARTER\Models\GestoreUtenti;
use BOSTARTER\Utils\Validator;
use BOSTARTER\Utils\BaseController;

require_once __DIR__ . '/../utils/BaseController.php';

class GestoreUtentiController extends BaseController
{
    // Modello per gli utenti
    private $gestoreUtenti;

    /**
     * Costruttore - Prepara il nostro gestore degli utenti
     */
    public function __construct() {
        parent::__construct(); // Inizializza connessione DB e logger dalla classe base
        $this->gestoreUtenti = new GestoreUtenti($this->connessioneDatabase);
    }    /**
     * Recupera il profilo completo di un utente
     * 
     * È come aprire la carta d'identità di una persona
     * 
     * @param int $idUtente ID dell'utente di cui vogliamo il profilo
     * @return array Dati del profilo o errore
     */
    public function ottieniProfiloUtente($idUtente) {
        try {
            // Validazione parametri usando il metodo della classe base
            $validazione = $this->validaParametri(['idUtente'], ['idUtente' => $idUtente]);
            if ($validazione !== true) {
                return $this->rispostaStandardizzata(false, 'ID utente non valido', null, $validazione);
            }

            $risultato = $this->gestoreUtenti->ottieniUtentePerId($idUtente);
            
            if ($risultato['stato'] === 'successo') {                // Rimuoviamo dati sensibili dal profilo pubblico
                $profiloSicuro = $risultato['utente'];
                unset($profiloSicuro['password']);
                unset($profiloSicuro['email']); // Email visibile solo al proprietario
                
                return $this->rispostaStandardizzata(true, 'Profilo recuperato con successo', $profiloSicuro);
            }
            
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nel recupero profilo utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a recuperare il profilo. Riprova più tardi.'
            ];
        }
    }

    /**
     * Recupera il profilo PRIVATO dell'utente (con dati sensibili)
     * 
     * Solo per l'utente stesso - include email e altre info private
     * 
     * @param int $idUtente ID dell'utente
     * @param int $idRichiedente ID di chi sta facendo la richiesta
     * @return array Dati del profilo privato o errore
     */
    public function ottieniProfiloPrivato($idUtente, $idRichiedente) {
        // Controllo di sicurezza: solo l'utente stesso può vedere i suoi dati privati
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi accedere ai dati privati di altri utenti!'
            ];
        }
        
        try {
            $risultato = $this->gestoreUtenti->ottieniUtentePerId($idUtente);
            
            if ($risultato['stato'] === 'successo') {
                // Per il profilo privato, rimuoviamo solo la password
                $profiloPrivato = $risultato['utente'];
                unset($profiloPrivato['password']);
                
                return [
                    'stato' => 'successo',
                    'profilo' => $profiloPrivato,
                    'messaggio' => 'Profilo privato recuperato con successo'
                ];
            }
            
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nel recupero profilo privato utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a recuperare il tuo profilo. Riprova più tardi.'
            ];
        }
    }

    /**
     * Aggiorna il profilo dell'utente
     * 
     * È come quando vai in comune a cambiare i tuoi dati sulla carta d'identità
     * 
     * @param int $idUtente ID dell'utente
     * @param array $nuoviDati Nuovi dati da salvare
     * @param int $idRichiedente ID di chi sta facendo la richiesta
     * @return array Risultato dell'operazione
     */
    public function aggiornaProfilo($idUtente, $nuoviDati, $idRichiedente) {
        // Solo l'utente stesso può modificare il suo profilo
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi modificare il profilo di altri utenti!'
            ];
        }
        
        try {
            // Validazione dei dati in arrivo
            $datiValidati = $this->validaDatiProfilo($nuoviDati);
            if (!$datiValidati['valido']) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Dati non validi: ' . $datiValidati['errore']
                ];
            }
            
            $risultato = $this->gestoreUtenti->aggiornaProfilo($idUtente, $datiValidati['dati']);
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nell'aggiornamento profilo utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco ad aggiornare il profilo. Riprova più tardi.'
            ];
        }
    }

    /**
     * Cambia la password dell'utente
     * 
     * @param int $idUtente ID dell'utente
     * @param string $passwordVecchia Password attuale
     * @param string $passwordNuova Nuova password
     * @param int $idRichiedente ID di chi sta facendo la richiesta
     * @return array Risultato dell'operazione
     */
    public function cambiaPassword($idUtente, $passwordVecchia, $passwordNuova, $idRichiedente) {
        // Solo l'utente stesso può cambiare la sua password
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi cambiare la password di altri utenti!'
            ];
        }
        
        try {
            // Prima verifichiamo che la password attuale sia corretta
            $utenteCorrente = $this->gestoreUtenti->ottieniUtentePerId($idUtente);
            if ($utenteCorrente['stato'] !== 'successo') {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Utente non trovato'
                ];
            }
            
            // Verifichiamo la password vecchia
            if (!password_verify($passwordVecchia, $utenteCorrente['utente']['password'])) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'La password attuale non è corretta'
                ];
            }
            
            // Validazione della nuova password
            $validazionePassword = Validator::validatePassword($passwordNuova);
            if ($validazionePassword !== true) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Nuova password non valida: ' . $validazionePassword
                ];
            }
            
            // Hash della nuova password
            $hashNuovaPassword = password_hash($passwordNuova, PASSWORD_ARGON2ID);
            
            // Aggiorniamo la password nel database
            $statement = $this->connessioneDatabase->prepare("
                UPDATE utenti 
                SET password = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $statement->execute([$hashNuovaPassword, $idUtente]);
            
            return [
                'stato' => 'successo',
                'messaggio' => 'Password cambiata con successo!'
            ];
            
        } catch (\Exception $errore) {
            error_log("Errore nel cambio password utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Errore nel cambio password. Riprova più tardi.'
            ];
        }
    }

    /**
     * Recupera le statistiche dell'utente
     * 
     * @param int $idUtente ID dell'utente
     * @return array Statistiche dell'utente
     */
    public function ottieniStatisticheUtente($idUtente) {
        try {
            // Progetti creati
            $statement = $this->connessioneDatabase->prepare("
                SELECT COUNT(*) as progetti_creati FROM projects WHERE creator_id = ?
            ");
            $statement->execute([$idUtente]);
            $progettiCreati = $statement->fetch(\PDO::FETCH_ASSOC)['progetti_creati'];
            
            // Progetti finanziati
            $statement = $this->connessioneDatabase->prepare("
                SELECT COUNT(DISTINCT project_id) as progetti_finanziati 
                FROM funding WHERE user_id = ? AND status = 'confirmed'
            ");
            $statement->execute([$idUtente]);
            $progettiFinanziati = $statement->fetch(\PDO::FETCH_ASSOC)['progetti_finanziati'];
            
            // Totale finanziato
            $statement = $this->connessioneDatabase->prepare("
                SELECT COALESCE(SUM(amount), 0) as totale_finanziato 
                FROM funding WHERE user_id = ? AND status = 'confirmed'
            ");
            $statement->execute([$idUtente]);
            $totaleFinanziato = $statement->fetch(\PDO::FETCH_ASSOC)['totale_finanziato'];
            
            // Data registrazione
            $statement = $this->connessioneDatabase->prepare("
                SELECT created_at FROM utenti WHERE id = ?
            ");
            $statement->execute([$idUtente]);
            $dataRegistrazione = $statement->fetch(\PDO::FETCH_ASSOC)['created_at'];
            
            return [
                'stato' => 'successo',
                'statistiche' => [
                    'progetti_creati' => $progettiCreati,
                    'progetti_finanziati' => $progettiFinanziati,
                    'totale_finanziato' => number_format($totaleFinanziato, 2),
                    'membro_dal' => date('d/m/Y', strtotime($dataRegistrazione)),
                    'giorni_membro' => floor((time() - strtotime($dataRegistrazione)) / (60 * 60 * 24))
                ]
            ];
            
        } catch (\Exception $errore) {
            error_log("Errore nel recupero statistiche utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a recuperare le statistiche.'
            ];
        }
    }

    /**
     * Elimina l'account dell'utente (GDPR compliance)
     * 
     * @param int $idUtente ID dell'utente
     * @param string $password Password di conferma
     * @param int $idRichiedente ID di chi sta facendo la richiesta
     * @return array Risultato dell'operazione
     */
    public function eliminaAccount($idUtente, $password, $idRichiedente) {
        // Solo l'utente stesso può eliminare il suo account
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi eliminare l\'account di altri utenti!'
            ];
        }
        
        try {
            // Verifichiamo la password
            $utenteCorrente = $this->gestoreUtenti->ottieniUtentePerId($idUtente);
            if ($utenteCorrente['stato'] !== 'successo') {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Utente non trovato'
                ];
            }
            
            if (!password_verify($password, $utenteCorrente['utente']['password'])) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Password non corretta'
                ];
            }
            
            // Avviamo una transazione per eliminare tutto in sicurezza
            $this->connessioneDatabase->beginTransaction();
            
            try {
                // Anonimizziamo i dati invece di eliminarli (GDPR)
                $statement = $this->connessioneDatabase->prepare("
                    UPDATE utenti SET 
                        email = CONCAT('deleted_', id, '@deleted.local'),
                        nickname = CONCAT('utente_eliminato_', id),
                        nome = 'Account',
                        cognome = 'Eliminato',
                        password = '',
                        deleted_at = NOW()
                    WHERE id = ?
                ");
                $statement->execute([$idUtente]);
                
                // Eliminiamo le notifiche
                $statement = $this->connessioneDatabase->prepare("
                    DELETE FROM notifications WHERE user_id = ?
                ");
                $statement->execute([$idUtente]);
                
                $this->connessioneDatabase->commit();
                
                return [
                    'stato' => 'successo',
                    'messaggio' => 'Account eliminato con successo. Ci dispiace vederti andare!'
                ];
                
            } catch (\Exception $erroreTransazione) {
                $this->connessioneDatabase->rollBack();
                throw $erroreTransazione;
            }
            
        } catch (\Exception $errore) {
            error_log("Errore nell'eliminazione account utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Errore nell\'eliminazione dell\'account. Riprova più tardi.'
            ];
        }
    }

    /**
     * Valida i dati del profilo utente
     * 
     * @param array $dati Dati da validare
     * @return array Risultato della validazione
     */
    private function validaDatiProfilo($dati) {
        $datiValidati = [];
        $errori = [];
        
        // Validazione nome
        if (isset($dati['nome'])) {
            $nome = trim($dati['nome']);
            if (strlen($nome) < 2 || strlen($nome) > 50) {
                $errori[] = 'Il nome deve essere tra 2 e 50 caratteri';
            } else {
                $datiValidati['nome'] = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
            }
        }
        
        // Validazione cognome
        if (isset($dati['cognome'])) {
            $cognome = trim($dati['cognome']);
            if (strlen($cognome) < 2 || strlen($cognome) > 50) {
                $errori[] = 'Il cognome deve essere tra 2 e 50 caratteri';
            } else {
                $datiValidati['cognome'] = htmlspecialchars($cognome, ENT_QUOTES, 'UTF-8');
            }
        }
        
        // Validazione nickname
        if (isset($dati['nickname'])) {
            $nickname = trim($dati['nickname']);
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $nickname)) {
                $errori[] = 'Il nickname deve contenere solo lettere, numeri e underscore (3-20 caratteri)';
            } else {
                // Controlliamo che il nickname non sia già in uso
                $statement = $this->connessioneDatabase->prepare("
                    SELECT id FROM utenti WHERE nickname = ? AND id != ?
                ");
                $statement->execute([$nickname, $_SESSION['user_id'] ?? 0]);
                
                if ($statement->fetch()) {
                    $errori[] = 'Questo nickname è già in uso';
                } else {
                    $datiValidati['nickname'] = $nickname;
                }
            }
        }
        
        // Validazione email
        if (isset($dati['email'])) {
            $email = filter_var(trim($dati['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $errori[] = 'Email non valida';
            } else {
                // Controlliamo che l'email non sia già in uso
                $statement = $this->connessioneDatabase->prepare("
                    SELECT id FROM utenti WHERE email = ? AND id != ?
                ");
                $statement->execute([$email, $_SESSION['user_id'] ?? 0]);
                
                if ($statement->fetch()) {
                    $errori[] = 'Questa email è già in uso';
                } else {
                    $datiValidati['email'] = $email;
                }
            }
        }
        
        return [
            'valido' => empty($errori),
            'dati' => $datiValidati,
            'errore' => implode(', ', $errori)
        ];
    }
}

// Alias per compatibilità con il codice esistente
class_alias('BOSTARTER\Controllers\GestoreUtentiController', 'UserController');