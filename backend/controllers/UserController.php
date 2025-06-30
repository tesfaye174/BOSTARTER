<?php
namespace BOSTARTER\Controllers;
use BOSTARTER\Models\GestoreUtenti;
use BOSTARTER\Utils\Validator;
use BOSTARTER\Utils\BaseController;
require_once __DIR__ . '/../utils/BaseController.php';
class GestoreUtentiController extends BaseController
{
    private $gestoreUtenti;
    public function __construct() {
        parent::__construct(); 
        $this->gestoreUtenti = new GestoreUtenti($this->connessioneDatabase);
    }    
    public function ottieniProfiloUtente($idUtente) {
        try {
            $validazione = $this->validaParametri(['idUtente'], ['idUtente' => $idUtente]);
            if ($validazione !== true) {
                return $this->rispostaStandardizzata(false, 'ID utente non valido', null, $validazione);
            }
            $risultato = $this->gestoreUtenti->ottieniUtentePerId($idUtente);
            if ($risultato['stato'] === 'successo') {                
                $profiloSicuro = $risultato['utente'];
                unset($profiloSicuro['password']);
                unset($profiloSicuro['email']); 
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
    public function ottieniProfiloPrivato($idUtente, $idRichiedente) {
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi accedere ai dati privati di altri utenti!'
            ];
        }
        try {
            $risultato = $this->gestoreUtenti->ottieniUtentePerId($idUtente);
            if ($risultato['stato'] === 'successo') {
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
    public function aggiornaProfilo($idUtente, $nuoviDati, $idRichiedente) {
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi modificare il profilo di altri utenti!'
            ];
        }
        try {
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
    public function cambiaPassword($idUtente, $passwordVecchia, $passwordNuova, $idRichiedente) {
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi cambiare la password di altri utenti!'
            ];
        }
        try {
            $utenteCorrente = $this->gestoreUtenti->ottieniUtentePerId($idUtente);
            if ($utenteCorrente['stato'] !== 'successo') {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Utente non trovato'
                ];
            }
            if (!password_verify($passwordVecchia, $utenteCorrente['utente']['password'])) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'La password attuale non è corretta'
                ];
            }
            $validazionePassword = Validator::validatePassword($passwordNuova);
            if ($validazionePassword !== true) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Nuova password non valida: ' . $validazionePassword
                ];
            }
            $hashNuovaPassword = password_hash($passwordNuova, PASSWORD_ARGON2ID);
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
    public function ottieniStatisticheUtente($idUtente) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                SELECT COUNT(*) as progetti_creati FROM projects WHERE creator_id = ?
            ");
            $statement->execute([$idUtente]);
            $progettiCreati = $statement->fetch(\PDO::FETCH_ASSOC)['progetti_creati'];
            $statement = $this->connessioneDatabase->prepare("
                SELECT COUNT(DISTINCT project_id) as progetti_finanziati 
                FROM funding WHERE user_id = ? AND status = 'confirmed'
            ");
            $statement->execute([$idUtente]);
            $progettiFinanziati = $statement->fetch(\PDO::FETCH_ASSOC)['progetti_finanziati'];
            $statement = $this->connessioneDatabase->prepare("
                SELECT COALESCE(SUM(amount), 0) as totale_finanziato 
                FROM funding WHERE user_id = ? AND status = 'confirmed'
            ");
            $statement->execute([$idUtente]);
            $totaleFinanziato = $statement->fetch(\PDO::FETCH_ASSOC)['totale_finanziato'];
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
    public function eliminaAccount($idUtente, $password, $idRichiedente) {
        if ($idUtente !== $idRichiedente) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Non puoi eliminare l\'account di altri utenti!'
            ];
        }
        try {
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
            $this->connessioneDatabase->beginTransaction();
            try {
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
    private function validaDatiProfilo($dati) {
        $datiValidati = [];
        $errori = [];
        if (isset($dati['nome'])) {
            $nome = trim($dati['nome']);
            if (strlen($nome) < 2 || strlen($nome) > 50) {
                $errori[] = 'Il nome deve essere tra 2 e 50 caratteri';
            } else {
                $datiValidati['nome'] = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
            }
        }
        if (isset($dati['cognome'])) {
            $cognome = trim($dati['cognome']);
            if (strlen($cognome) < 2 || strlen($cognome) > 50) {
                $errori[] = 'Il cognome deve essere tra 2 e 50 caratteri';
            } else {
                $datiValidati['cognome'] = htmlspecialchars($cognome, ENT_QUOTES, 'UTF-8');
            }
        }
        if (isset($dati['nickname'])) {
            $nickname = trim($dati['nickname']);
            if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $nickname)) {
                $errori[] = 'Il nickname deve contenere solo lettere, numeri e underscore (3-20 caratteri)';
            } else {
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
        if (isset($dati['email'])) {
            $email = filter_var(trim($dati['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $errori[] = 'Email non valida';
            } else {
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
