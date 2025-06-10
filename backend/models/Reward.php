<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Modello Ricompense - Gestione delle ricompense per i progetti BOSTARTER
 * 
 * Questa classe gestisce tutte le operazioni relative alle ricompense:
 * - Creazione di nuove ricompense per i progetti
 * - Recupero delle ricompense per progetto
 * - Aggiornamento e cancellazione delle ricompense
 * - Gestione delle statistiche delle ricompense
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 */
class GestoreRicompense {
    // Connessione al database principale
    private $database;
    private $connessione;
    
    /**
     * Costruttore - Inizializza la connessione al database
     */
    public function __construct() {
        $this->database = Database::getInstance();
        $this->connessione = $this->database->getConnection();
    }    /**
     * Recupera la lista completa delle ricompense di un progetto
     * 
     * @param int $idProgetto ID del progetto
     * @return array Lista delle ricompense ordinate per data di creazione
     */
    public function ottieniListaRicompense($idProgetto) {
        try {
            $statement = $this->connessione->prepare("
                SELECT *
                FROM reward
                WHERE progetto_id = ?
                ORDER BY created_at ASC
            ");
            $statement->execute([$idProgetto]);
            return $statement->fetchAll();
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero ricompense: " . $errore->getMessage());
            return [];
        }
    }
    
    /**
     * Recupera i dettagli completi di una ricompensa specifica
     * 
     * Include informazioni sul progetto e sul creatore
     * 
     * @param int $idRicompensa ID della ricompensa
     * @return array|null Dettagli della ricompensa o null se non trovata
     */
    public function ottieniDettagliRicompensa($idRicompensa) {
        try {
            // Query per recuperare ricompensa con dettagli progetto e creatore
            $statement = $this->connessione->prepare("
                SELECT r.*, p.nome as titolo_progetto, u.nickname as nickname_creatore
                FROM reward r
                JOIN progetti p ON r.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE r.id = ?
            ");
            $statement->execute([$idRicompensa]);
            $ricompensa = $statement->fetch();
            
            if (!$ricompensa) {
                return null;
            }

            // Recuperiamo il numero di donazioni per questa ricompensa
            $statement = $this->connessione->prepare("
                SELECT COUNT(*) as numero_donazioni
                FROM finanziamenti
                WHERE reward_id = ?            ");
            $statement->execute([$idRicompensa]);
            $ricompensa['numero_donazioni'] = $statement->fetchColumn();
            
            return $ricompensa;
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero dettagli ricompensa: " . $errore->getMessage());
            return null;
        }
    }
    
    /**
     * Crea una nuova ricompensa per un progetto
     * 
     * @param array $datiRicompensa Dati della ricompensa da creare
     * @return array Risultato dell'operazione con ID della ricompensa se successo
     */
    public function creaNuovaRicompensa($datiRicompensa) {
        try {
            $statement = $this->connessione->prepare("
                INSERT INTO reward (
                    progetto_id, codice, descrizione
                ) VALUES (?, ?, ?)
            ");
              $statement->execute([
                $datiRicompensa['progetto_id'],
                $datiRicompensa['codice'],
                $datiRicompensa['descrizione']
            ]);
            
            return [
                'successo' => true,
                'id_ricompensa' => $this->connessione->lastInsertId(),
                'messaggio' => 'Ricompensa creata con successo'
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nella creazione ricompensa: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante la creazione della ricompensa'
            ];
        }
    }

    /**
     * Aggiorna una ricompensa esistente
     * 
     * @param int $idRicompensa ID della ricompensa da aggiornare
     * @param array $datiAggiornamento Dati da aggiornare
     * @return array Risultato dell'operazione
     */
    public function aggiornaRicompensa($idRicompensa, $datiAggiornamento) {
        try {
            // Campi che possono essere aggiornati
            $campiConsentiti = [
                'titolo', 'descrizione', 'importo_minimo',
                'quantita_disponibile', 'data_consegna'
            ];
            
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
            
            // Aggiungiamo l'ID della ricompensa come ultimo parametro            $parametri[] = $idRicompensa;
            $query = "UPDATE reward SET " . implode(', ', $aggiornamenti) . " WHERE id = ?";
            
            $statement = $this->connessione->prepare($query);
            $statement->execute($parametri);
            
            return [
                'successo' => true,
                'messaggio' => 'Ricompensa aggiornata con successo'
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nell'aggiornamento ricompensa: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante l\'aggiornamento della ricompensa'
            ];
        }
    }
    
    /**
     * Elimina una ricompensa se possibile
     * 
     * Verifica prima che non ci siano donazioni associate
     * 
     * @param int $idRicompensa ID della ricompensa da eliminare
     * @return array Risultato dell'operazione
     */
    public function eliminaRicompensa($idRicompensa) {
        try {
            // Verifichiamo se ci sono donazioni associate a questa ricompensa
            $statement = $this->connessione->prepare("
                SELECT COUNT(*)
                FROM finanziamenti
                WHERE reward_id = ?
            ");
            $statement->execute([$idRicompensa]);
            
            if ($statement->fetchColumn() > 0) {
                return [
                    'successo' => false,
                    'messaggio' => 'Impossibile eliminare la ricompensa: ci sono donazioni associate'
                ];
            }
            
            // Eliminiamo la ricompensa se non ha donazioni
            $statement = $this->connessione->prepare("DELETE FROM reward WHERE id = ?");
            $statement->execute([$idRicompensa]);
              return [
                'successo' => true,
                'messaggio' => 'Ricompensa eliminata con successo'
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nell'eliminazione ricompensa: " . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante l\'eliminazione della ricompensa'
            ];
        }
    }
    
    /**
     * Verifica la disponibilità di una ricompensa
     * 
     * @param int $idRicompensa ID della ricompensa da verificare
     * @return array Informazioni sulla disponibilità
     */
    public function verificaDisponibilita($idRicompensa) {
        try {
            $statement = $this->connessione->prepare("
                SELECT COUNT(*) as disponibile
                FROM reward
                WHERE id = ?
            ");
            $statement->execute([$idRicompensa]);
            $risultato = $statement->fetchColumn();
            
            return [
                'disponibile' => $risultato > 0,
                'quantita' => 1 // La tabella reward non traccia ancora le quantità
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nella verifica disponibilità: " . $errore->getMessage());
            return [
                'disponibile' => false,
                'quantita' => 0
            ];
        }
    }
      /**
     * Recupera le donazioni per una ricompensa con paginazione
     * 
     * @param int $idRicompensa ID della ricompensa     * @param int $pagina Numero di pagina (default: 1)
     * @param int $elementiPerPagina Elementi per pagina (default: 10)
     * @return array Lista delle donazioni
     */
    public function ottieniDonazioniRicompensa($idRicompensa, $pagina = 1, $elementiPerPagina = 10) {
        try {
            $offset = ($pagina - 1) * $elementiPerPagina;
            
            // Query per recuperare le donazioni con dettagli dei donatori
            $statement = $this->connessione->prepare("
                SELECT f.*, u.nickname as nickname_donatore, u.avatar as avatar_donatore
                FROM finanziamenti f
                JOIN utenti u ON f.utente_id = u.id
                WHERE f.reward_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT ? OFFSET ?
            ");
            $statement->execute([$idRicompensa, $elementiPerPagina, $offset]);
            $donazioni = $statement->fetchAll();

            // Contiamo il totale delle donazioni per la paginazione
            $statement = $this->connessione->prepare("
                SELECT COUNT(*)
                FROM finanziamenti
                WHERE reward_id = ?
            ");
            $statement->execute([$idRicompensa]);
            $totaleDonazioni = $statement->fetchColumn();
            
            return [
                'donazioni' => $donazioni,
                'totale' => $totaleDonazioni,
                'pagina' => $pagina,
                'per_pagina' => $elementiPerPagina,
                'totale_pagine' => ceil($totaleDonazioni / $elementiPerPagina)
            ];
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero donazioni: " . $errore->getMessage());
            return null;
        }
    }
    
    /**
     * Recupera le statistiche delle ricompense di un progetto
     * 
     * @param int $idProgetto ID del progetto
     * @return array Statistiche complete delle ricompense
     */
    public function ottieniStatisticheProgetto($idProgetto) {
        try {
            // Recuperiamo il numero totale di ricompense
            $statement = $this->connessione->prepare("
                SELECT COUNT(*) as numero_ricompense,
                       COUNT(*) as totale_disponibili
                FROM reward
                WHERE progetto_id = ?
            ");
            $statement->execute([$idProgetto]);
            $statistiche = $statement->fetch();
            
            // Recuperiamo il numero di donazioni per ogni ricompensa
            $statement = $this->connessione->prepare("
                SELECT r.id, r.descrizione,
                       COUNT(f.id) as numero_donazioni,
                       SUM(f.importo) as totale_donazioni
                FROM reward r
                LEFT JOIN finanziamenti f ON r.id = f.reward_id
                WHERE r.progetto_id = ?
                GROUP BY r.id
                ORDER BY r.created_at ASC
            ");
            $statement->execute([$idProgetto]);
            $statistiche['ricompense'] = $statement->fetchAll();
            
            return $statistiche;
            
        } catch (PDOException $errore) {
            error_log("Errore nel recupero statistiche progetto: " . $errore->getMessage());
            return null;
        }
    }
}

// Alias per compatibilità con il codice esistente
class_alias('GestoreRicompense', 'Reward');