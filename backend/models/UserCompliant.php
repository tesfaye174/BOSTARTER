<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/BaseModel.php';

/**
 * Modello Utente - Gestione completa degli utenti della piattaforma BOSTARTER
 * 
 * Questa classe è il cuore della gestione utenti della piattaforma:
 * - Registra nuovi utenti in modo sicuro tramite stored procedure
 * - Si occupa del login e della verifica delle credenziali
 * - Gestisce l'aggiornamento dei profili utente
 * - Tiene traccia delle competenze e della reputazione
 * 
 * Implementa tutte le best practice di sicurezza necessarie per la gestione
 * di dati sensibili degli utenti.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 */

// Utilizziamo il namespace della classe base per chiarezza
use BOSTARTER\Utils\BaseModel;

class GestoreUtenti extends BaseModel {
    /**
     * Costruttore - Inizializza la connessione al database usando la classe base
     * 
     * Crea un'istanza pronta per lavorare con il database BOSTARTER
     */
    public function __construct() {
        parent::__construct(); // Ereditiamo la connessione dalla classe BaseModel
    }

    /**
     * Registra un nuovo utente nella piattaforma
     * 
     * Questo metodo è la porta d'ingresso per i nuovi utenti.
     * Utilizziamo una stored procedure per garantire che tutte le verifiche 
     * di integrità vengano eseguite direttamente a livello database.
     * 
     * La password verrà automaticamente crittografata nella stored procedure.
     * 
     * @param array $datiUtente Array con tutti i dati dell'utente da registrare
     * @return array Risultato dell'operazione con successo e messaggio
     */
    public function registraNuovoUtente($datiUtente) {
        try {
            // Utilizziamo il metodo eseguiQuery della BaseModel per gestire errori e logging
            $query = "CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, ?, @p_user_id, @p_success, @p_message)";
            
            // Prepariamo i parametri per la stored procedure
            $parametri = [
                $datiUtente['email'],
                $datiUtente['nickname'],
                $datiUtente['password'],    // La password verrà crittografata nella stored procedure
                $datiUtente['nome'],
                $datiUtente['cognome'],
                date('Y', strtotime($datiUtente['data_nascita'])),  // Estraiamo solo l'anno di nascita
                $datiUtente['luogo_nascita'],
                $datiUtente['tipo_utente']  // Tipo utente: creator, backer, etc.
            ];
            
            // Eseguiamo la stored procedure con i dati dell'utente
        $risultato = $this->eseguiQuery($query, $parametri, 'one');
        
        // Verifichiamo se l'esecuzione della stored procedure è riuscita
        if ($risultato === null) {
            return ['successo' => false, 'messaggio' => 'Errore durante la registrazione'];
        }
        
        // Recuperiamo i parametri di output dalla stored procedure
        // che contengono l'esito dell'operazione e un eventuale messaggio
        $risultatoOperazione = $this->eseguiQuery("SELECT @p_success as successo, @p_message as messaggio", [], 'one');
        
        // Verifichiamo se è stato possibile recuperare il risultato
        if ($risultatoOperazione === null) {
            return ['successo' => false, 'messaggio' => 'Errore nel recupero del risultato'];
        }
          
        // Restituiamo il risultato al controller
        return [
            'successo' => (bool) $risultatoOperazione['successo'],
            'messaggio' => $risultatoOperazione['messaggio']
        ];
        
    } catch (PDOException $errore) {
        // Registriamo l'errore nel log per il debugging
        // ma nascondiamo i dettagli tecnici all'utente finale per sicurezza
        error_log("Errore durante la registrazione utente: " . $errore->getMessage());
        
        return [
            'successo' => false,
            'messaggio' => 'Si è verificato un errore durante la registrazione. Riprova più tardi.'
        ];
    }
}

/**
 * Autentica un utente esistente nel sistema
 * 
 * Questo metodo verifica le credenziali fornite contro il database
 * e, se valide, restituisce i dati dell'utente. È il cuore del
 * processo di login.
 * 
 * Utilizza una stored procedure per garantire sicurezza e
 * coerenza delle operazioni a livello database.
 * 
 * @param string $email Email dell'utente
 * @param string $password Password in chiaro (sarà verificata contro l'hash)
 * @return array|null Dati dell'utente o null se autenticazione fallita
 */
    public function autenticaUtente($nickname, $password) {
        try {
            // Prepariamo la chiamata alla stored procedure per il login
            $statement = $this->connessione->prepare("CALL login_utente(?, ?, @user_id, @risultato)");
            $statement->execute([$nickname, $password]);
            
            // Recuperiamo il risultato dell'autenticazione
            $risultatoLogin = $this->connessione->query("SELECT @user_id as id_utente, @risultato as esito")->fetch();
            
            if ($risultatoLogin['esito'] === 'SUCCESS') {
                // Se il login è riuscito, recuperiamo i dettagli completi dell'utente
                $dettagliUtente = $this->ottieniUtentePerId($risultatoLogin['id_utente']);
                
                return [
                    'successo' => true,
                    'utente' => $dettagliUtente,
                    'messaggio' => 'Accesso effettuato con successo'
                ];
            } else {
                return [
                    'successo' => false,
                    'messaggio' => $risultatoLogin['esito']
                ];
            }
            
        } catch (PDOException $errore) {
            // Registriamo l'errore nel log
            error_log("Errore durante l'autenticazione: " . $errore->getMessage());
            
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante l\'accesso. Riprova più tardi.'
            ];
        }
    }    /**
     * Recupera i dettagli completi di un utente tramite il suo ID
     * 
     * Include informazioni personali, competenze e statistiche dell'utente
     * 
     * @param int $idUtente ID dell'utente da recuperare
     * @return array|null Dati dell'utente o null se non trovato
     */
    public function ottieniUtentePerId($idUtente) {
        try {            // Query per recuperare i dati principali dell'utente
            $statement = $this->connessione->prepare("
                SELECT id, nickname, email, nome, cognome, anno_nascita, luogo_nascita,
                       tipo_utente, created_at, affidabilita, nr_progetti
                FROM utenti 
                WHERE id = ?
            ");
            $statement->execute([$idUtente]);
            $datiUtente = $statement->fetch();

            if ($datiUtente) {                // Recuperiamo anche le competenze dell'utente
                $statement = $this->connessione->prepare("
                    SELECT c.id, c.nome, c.descrizione, su.livello
                    FROM skill_utente su
                    JOIN competenze c ON su.competenza_id = c.id
                    WHERE su.utente_id = ?
                    ORDER BY c.nome
                ");
                $statement->execute([$idUtente]);
                $datiUtente['competenze'] = $statement->fetchAll();

                // Rimuoviamo dati sensibili prima di restituire l'utente
                unset($datiUtente['password']);
                
                return $datiUtente;
            }
            
            return null;
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero utente: " . $errore->getMessage());
            return null;        }
    }

    /**
     * Recupera un utente tramite il suo nickname
     * 
     * @param string $nickname Nome utente da cercare
     * @return array|null Dati dell'utente o null se non trovato
     */
    public function ottieniUtenteDaNickname($nickname) {
        try {            $statement = $this->connessione->prepare("
                SELECT id, nickname, email, nome, cognome, anno_nascita, luogo_nascita,
                       tipo_utente, created_at, affidabilita, nr_progetti
                FROM utenti 
                WHERE nickname = ?
            ");
            $statement->execute([$nickname]);
            return $statement->fetch();
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero utente per nickname: " . $errore->getMessage());
            return null;
        }
    }

    /**
     * Aggiunge una competenza a un utente tramite stored procedure
     * 
     * @param int $idUtente ID dell'utente
     * @param int $idCompetenza ID della competenza da aggiungere  
     * @param string $livelloCompetenza Livello di competenza (Principiante, Intermedio, Avanzato)
     * @return array Risultato dell'operazione
     */
    public function aggiungiCompetenzaUtente($idUtente, $idCompetenza, $livelloCompetenza) {
        try {
            $statement = $this->connessione->prepare("CALL inserisci_skill_utente(?, ?, ?, @risultato)");
            $statement->execute([$idUtente, $idCompetenza, $livelloCompetenza]);
            
            $risultatoOperazione = $this->connessione->query("SELECT @risultato as esito")->fetch();
            
            if ($risultatoOperazione['esito'] === 'SUCCESS') {
                return [
                    'successo' => true,                'messaggio' => 'Competenza aggiunta con successo'
                ];
            } else {
                return [
                    'successo' => false,
                    'messaggio' => $risultatoOperazione['esito']
                ];
            }
            
        } catch (PDOException $errore) {
            error_log("Errore nell'aggiunta competenza: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante l\'aggiunta della competenza'
            ];
        }
    }

    /**
     * Aggiorna il profilo di un utente
     * 
     * Permette di modificare solo i campi autorizzati del profilo utente
     * 
     * @param int $idUtente ID dell'utente da aggiornare
     * @param array $datiAggiornamento Dati da aggiornare
     * @return array Risultato dell'operazione
     */
    public function aggiornaProfilo($idUtente, $datiAggiornamento) {
        try {
            // Campi che possono essere aggiornati dall'utente
            $campiConsentiti = ['email', 'nome', 'cognome', 'luogo_nascita', 'tipo_utente'];
            $aggiornamenti = [];
            $parametri = [];
            
            // Prepariamo solo i campi validi per l'aggiornamento
            foreach ($datiAggiornamento as $campo => $valore) {
                if (in_array($campo, $campiConsentiti)) {
                    $aggiornamenti[] = "$campo = ?";
                    $parametri[] = $valore;
                }
            }
            
            if (empty($aggiornamenti)) {
                return [
                    'successo' => false,
                    'messaggio' => 'Nessun campo valido da aggiornare'
                ];
            }
            
            // Aggiungiamo l'ID utente come ultimo parametro
            $parametri[] = $idUtente;
            $query = "UPDATE utenti SET " . implode(', ', $aggiornamenti) . " WHERE id = ?";
            
            $statement = $this->connessione->prepare($query);
            $statement->execute($parametri);
              return [
                'successo' => true,
                'messaggio' => 'Profilo aggiornato con successo'
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nell'aggiornamento profilo: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante l\'aggiornamento del profilo'
            ];
        }
    }

    /**
     * Cambia la password di un utente - VERSIONE SICURA
     * 
     * Verifica la password corrente e aggiorna con un hash sicuro
     * 
     * @param int $idUtente ID dell'utente
     * @param string $passwordCorrente Password attuale dell'utente
     * @param string $nuovaPassword Nuova password da impostare
     * @return array Risultato dell'operazione
     */
    public function cambiaPassword($idUtente, $passwordCorrente, $nuovaPassword) {
        try {
            // Recuperiamo l'hash della password corrente dal database
            $statement = $this->connessione->prepare("
                SELECT password_hash FROM utenti 
                WHERE id = ?
            ");
            $statement->execute([$idUtente]);
            $utenteCorrente = $statement->fetch(PDO::FETCH_ASSOC);
            
            if (!$utenteCorrente) {
                return [
                    'successo' => false,
                    'messaggio' => 'Utente non trovato'
                ];
            }
            
            // Verifichiamo che la password corrente sia corretta usando password_verify (SICURO)
            if (!password_verify($passwordCorrente, $utenteCorrente['password_hash'])) {
                return [
                    'successo' => false,
                    'messaggio' => 'La password corrente non è corretta'
                ];
            }
            
            // Creiamo un hash sicuro della nuova password usando Argon2ID
            $hashNuovaPassword = password_hash($nuovaPassword, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,  // 64 MB di memoria
                'time_cost' => 4,        // 4 iterazioni                'threads' => 3           // 3 thread paralleli
            ]);
            
            // Aggiorniamo la password nel database con l'hash sicuro
            $statement = $this->connessione->prepare("
                UPDATE utenti 
                SET password_hash = ? 
                WHERE id = ?
            ");
            $statement->execute([$hashNuovaPassword, $idUtente]);
            
            return [
                'successo' => true,
                'messaggio' => 'Password cambiata con successo'
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nel cambio password: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante il cambio password'
            ];
        }
    }

    /**
     * Recupera i progetti di un utente con paginazione
     * 
     * @param int $idUtente ID dell'utente
     * @param int $pagina Numero di pagina (default: 1)
     * @param int $elementiPerPagina Numero di progetti per pagina (default: 10)
     * @return array Lista dei progetti dell'utente
     */
    public function ottieniProgettiUtente($idUtente, $pagina = 1, $elementiPerPagina = 10) {
        try {
            $offset = ($pagina - 1) * $elementiPerPagina;
            
            // Query per recuperare i progetti dell'utente con statistiche di finanziamento
            $statement = $this->connessione->prepare("
                SELECT p.*, 
                       (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti f WHERE f.progetto_id = p.id) as totale_finanziamenti
                FROM progetti p
                WHERE p.creatore_id = ?
                ORDER BY p.data_creazione DESC
                LIMIT ? OFFSET ?
            ");            $statement->execute([$idUtente, $elementiPerPagina, $offset]);
            $progetti = $statement->fetchAll();
            
            // Calcoliamo campi aggiuntivi per ogni progetto
            foreach ($progetti as &$progetto) {
                $progetto['percentuale_completamento'] = $progetto['budget_richiesto'] > 0 
                    ? ($progetto['totale_finanziamenti'] / $progetto['budget_richiesto']) * 100 
                    : 0;
                $progetto['giorni_rimanenti'] = max(0, floor((strtotime($progetto['data_scadenza']) - time()) / (60 * 60 * 24)));
            }

            // Contiamo il totale dei progetti per la paginazione
            $statement = $this->connessione->prepare("SELECT COUNT(*) FROM progetti WHERE creatore_id = ?");
            $statement->execute([$idUtente]);
            $totaleProjetti = $statement->fetchColumn();

            return [
                'progetti' => $progetti,
                'totale' => $totaleProjetti,
                'pagina' => $pagina,
                'per_pagina' => $elementiPerPagina,
                'totale_pagine' => ceil($totaleProjetti / $elementiPerPagina)
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero progetti utente: " . $errore->getMessage());
            return null;
        }
    }

    /**
     * Recupera la cronologia dei finanziamenti di un utente
     * 
     * @param int $idUtente ID dell'utente
     * @param int $pagina Numero di pagina (default: 1)
     * @param int $elementiPerPagina Numero di finanziamenti per pagina (default: 10)
     * @return array Lista dei finanziamenti dell'utente
     */
    public function ottieniFinanziamentiUtente($idUtente, $pagina = 1, $elementiPerPagina = 10) {
        try {
            $offset = ($pagina - 1) * $elementiPerPagina;
            
            // Query per recuperare i finanziamenti con dettagli dei progetti
            $statement = $this->connessione->prepare("
                SELECT f.*, p.nome as nome_progetto, p.tipo as tipo_progetto, 
                       r.titolo as titolo_ricompensa, u.nickname as nickname_creatore
                FROM finanziamenti f
                JOIN progetti p ON f.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                LEFT JOIN reward r ON f.reward_id = r.id
                WHERE f.utente_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT ? OFFSET ?
            ");
            $statement->execute([$idUtente, $elementiPerPagina, $offset]);            $finanziamenti = $statement->fetchAll();

            // Contiamo il totale dei finanziamenti per la paginazione
            $statement = $this->connessione->prepare("SELECT COUNT(*) FROM finanziamenti WHERE utente_id = ?");
            $statement->execute([$idUtente]);
            $totaleFinanziamenti = $statement->fetchColumn();

            return [
                'finanziamenti' => $finanziamenti,
                'totale' => $totaleFinanziamenti,
                'pagina' => $pagina,
                'per_pagina' => $elementiPerPagina,
                'totale_pagine' => ceil($totaleFinanziamenti / $elementiPerPagina)
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero finanziamenti utente: " . $errore->getMessage());
            return null;
        }
    }

    /**
     * Recupera le candidature di un utente per progetti software
     * 
     * @param int $idUtente ID dell'utente
     * @param int $pagina Numero di pagina (default: 1)
     * @param int $elementiPerPagina Numero di candidature per pagina (default: 10)
     * @return array Lista delle candidature dell'utente
     */
    public function ottieniCandidatureUtente($idUtente, $pagina = 1, $elementiPerPagina = 10) {
        try {
            $offset = ($pagina - 1) * $elementiPerPagina;
            
            // Query per recuperare le candidature con dettagli dei progetti
            $statement = $this->connessione->prepare("
                SELECT c.*, p.nome as nome_progetto, ps.nome as nome_profilo,
                       u.nickname as nickname_creatore
                FROM candidature c
                JOIN profili_software ps ON c.profilo_id = ps.id
                JOIN progetti p ON ps.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE c.utente_id = ?
                ORDER BY c.data_candidatura DESC
                LIMIT ? OFFSET ?
            ");
            $statement->execute([$idUtente, $elementiPerPagina, $offset]);
            $candidature = $statement->fetchAll();

            // Contiamo il totale delle candidature per la paginazione
            $statement = $this->connessione->prepare("SELECT COUNT(*) FROM candidature WHERE utente_id = ?");
            $statement->execute([$idUtente]);
            $totaleCandidature = $statement->fetchColumn();

            return [
                'candidature' => $candidature,
                'totale' => $totaleCandidature,
                'pagina' => $pagina,
                'per_pagina' => $elementiPerPagina,
                'totale_pagine' => ceil($totaleCandidature / $elementiPerPagina)
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero candidature utente: " . $errore->getMessage());
            return null;
        }
    }

    /**
     * Verifica se un utente esiste già tramite email o nickname
     * 
     * @param string|null $email Email da verificare
     * @param string|null $nickname Nickname da verificare
     * @return bool True se l'utente esiste, false altrimenti
     */
    public function utenteEsiste($email = null, $nickname = null) {
        try {
            // Verifichiamo l'email se fornita
            if ($email) {
                $statement = $this->connessione->prepare("SELECT id FROM utenti WHERE email = ?");
                $statement->execute([$email]);
                if ($statement->fetch()) return true;
            }

            // Verifichiamo il nickname se fornito
            if ($nickname) {
                $statement = $this->connessione->prepare("SELECT id FROM utenti WHERE nickname = ?");
                $statement->execute([$nickname]);
                if ($statement->fetch()) return true;
            }

            return false;
            
        } catch (PDOException $errore) {
            error_log("Errore nella verifica esistenza utente: " . $errore->getMessage());
            return false;
        }
    }

    /**
     * Recupera le statistiche complete di un utente
     * 
     * @param int $idUtente ID dell'utente
     * @return array Statistiche dell'utente
     */
    public function ottieniStatisticheUtente($idUtente) {
        try {
            $statistiche = [];

            // Numero totale di progetti creati
            $statement = $this->connessione->prepare("SELECT COUNT(*) FROM progetti WHERE creatore_id = ?");
            $statement->execute([$idUtente]);
            $statistiche['progetti_creati'] = $statement->fetchColumn();

            // Importo totale finanziato dall'utente
            $statement = $this->connessione->prepare("SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE utente_id = ?");
            $statement->execute([$idUtente]);
            $statistiche['totale_finanziato'] = $statement->fetchColumn();

            // Importo totale ricevuto dai progetti dell'utente
            $statement = $this->connessione->prepare("
                SELECT COALESCE(SUM(f.importo), 0)
                FROM finanziamenti f
                JOIN progetti p ON f.progetto_id = p.id
                WHERE p.creatore_id = ?
            ");
            $statement->execute([$idUtente]);
            $statistiche['totale_ricevuto'] = $statement->fetchColumn();

            // Numero totale di candidature inviate
            $statement = $this->connessione->prepare("SELECT COUNT(*) FROM candidature WHERE utente_id = ?");
            $statement->execute([$idUtente]);
            $statistiche['candidature_inviate'] = $statement->fetchColumn();            return $statistiche;
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero statistiche utente: " . $errore->getMessage());
            return [];
        }
    }
}
