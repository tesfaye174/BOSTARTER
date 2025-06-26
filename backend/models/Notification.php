<?php
namespace BOSTARTER\Models;

/**
 * Modello per la gestione delle notifiche nella piattaforma BOSTARTER
 * 
 * Implementa tutte le operazioni CRUD relative alle notifiche degli utenti:
 * - Creazione di nuove notifiche specifiche per utente
 * - Recupero delle notifiche filtrate per utente e stato
 * - Modifica dello stato delle notifiche (lettura/non lettura)
 * - Eliminazione delle notifiche obsolete o non necessarie
 * - Conteggio notifiche per stato (per badge UI)
 * 
 * Tutte le operazioni sono ottimizzate per performance ed includono
 * gestione delle eccezioni e logging degli errori.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 * @since 1.5.0 - Aggiunta funzionalità di eliminazione massiva
 */
class GestoreNotifiche {
    /**
     * @var PDO $database Connessione al database
     */
    private $database;
    
    /**
     * @var string $nomeTabella Nome della tabella notifiche nel database
     */
    private $nomeTabella = 'notifications';

    /**
     * Costruttore - Inizializza la connessione al database
     * 
     * @param PDO $database Istanza attiva di connessione PDO al database
     */
    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Crea una nuova notifica per un utente specifico
     * 
     * Inserisce un nuovo record nella tabella notifiche con i dati forniti.
     * Lo stato di lettura è impostato a 0 (non letto) per default.
     * Il timestamp di creazione viene generato automaticamente.
     * 
     * @param array $datiNotifica Dati della notifica da creare:
     *                           - user_id: ID dell'utente destinatario (int, obbligatorio)
     *                           - type: Tipo di notifica (string, es: 'project_funded', 'comment_received')
     *                           - title: Titolo della notifica (string)
     *                           - message: Contenuto dettagliato della notifica (string)
     *                           - link: URL opzionale per azione da compiere (string, null se assente)
     * @return array Risultato dell'operazione con ID della notifica se successo
     * @throws PDOException In caso di errori di database
     */
    public function creaNuovaNotifica($datiNotifica) {
        try {
            $statement = $this->database->prepare("
                INSERT INTO notifications (
                    user_id, 
                    type, 
                    title, 
                    message, 
                    link, 
                    is_read, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");

            $statement->execute([
                $datiNotifica['user_id'],
                $datiNotifica['type'],
                $datiNotifica['title'],
                $datiNotifica['message'],
                $datiNotifica['link'] ?? null
            ]);

            return [
                'stato' => 'successo',
                'messaggio' => 'Notifica creata con successo',
                'id_notifica' => $this->database->lastInsertId()
            ];
            
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un errore durante la creazione della notifica: ' . $errore->getMessage()
            ];
        }    
    }

    /**
     * Recupera le notifiche non lette di un utente specifico
     * 
     * Restituisce un array contenente tutte le notifiche non lette (is_read = 0)
     * dell'utente specificato, ordinate dalla più recente alla più vecchia.
     * 
     * @param int $idUtente ID dell'utente di cui recuperare le notifiche
     * @return array Lista delle notifiche non lette o array vuoto se non trovate
     * @throws PDOException In caso di errori di database (vengono catturati internamente)
     */
    public function ottieniNotificheNonLette($idUtente) {
        try {
            $statement = $this->database->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC
            ");
            
            $statement->execute([$idUtente]);
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $errore) {
            error_log("Errore nel recupero notifiche non lette: " . $errore->getMessage());
            return [];
        }
    }

    /**
     * Marca una notifica come letta
     * 
     * Aggiorna lo stato della notifica impostandola come letta (is_read = 1)
     * e registrando il timestamp di lettura (read_at = NOW()).
     * 
     * @param int $idNotifica ID della notifica da marcare come letta
     * @return array Risultato dell'operazione
     * @throws PDOException In caso di errori di database
     */
    public function marcaComeLetta($idNotifica) {
        try {            
            $statement = $this->database->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW()
                WHERE id = ?
            ");
            
            $statement->execute([$idNotifica]);
            
            return [
                'stato' => 'successo',
                'messaggio' => 'Notifica marcata come letta'
            ];
            
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un errore durante l\'aggiornamento della notifica: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Marca tutte le notifiche di un utente come lette (operazione massiva)
     * 
     * Aggiorna tutte le notifiche non lette di un utente in un'unica query
     * per ottimizzare le performance con grandi volumi di dati.
     * 
     * @param int $idUtente ID dell'utente di cui aggiornare le notifiche
     * @return array Risultato dell'operazione
     * @throws PDOException In caso di errori di database
     */
    public function marcaTutteComeLette($idUtente) {
        try {
            $statement = $this->database->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $statement->execute([$idUtente]);
            
            return [
                'stato' => 'successo',
                'messaggio' => 'Tutte le notifiche sono state marcate come lette'
            ];
            
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un errore durante l\'aggiornamento delle notifiche: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Elimina una notifica specifica dal database
     * 
     * Rimuove permanentemente una notifica dal sistema.
     * 
     * @param int $idNotifica ID della notifica da eliminare
     * @return array Risultato dell'operazione
     * @throws PDOException In caso di errori di database
     */
    public function eliminaNotifica($idNotifica) {
        try {
            $statement = $this->database->prepare("DELETE FROM notifications WHERE id = ?");
            $statement->execute([$idNotifica]);
            
            return [
                'stato' => 'successo',
                'messaggio' => 'Notifica eliminata con successo'
            ];
            
        } catch (\PDOException $errore) {
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un errore durante l\'eliminazione della notifica: ' . $errore->getMessage()
            ];
        }
    }

    /**
     * Recupera il conteggio delle notifiche non lette per un utente
     * 
     * Metodo ottimizzato che esegue una query COUNT() invece di recuperare
     * tutti i record, utile per l'UI (badge di notifica).
     * 
     * @param int $idUtente ID dell'utente
     * @return int Numero di notifiche non lette (0 se non ce ne sono)
     * @throws PDOException In caso di errori di database (vengono catturati internamente)
     */
    public function contaNotificheNonLette($idUtente) {
        try {
            $statement = $this->database->prepare("
                SELECT COUNT(*) as conteggio 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $statement->execute([$idUtente]);
            $risultato = $statement->fetch(\PDO::FETCH_ASSOC);
            
            return $risultato['conteggio'] ?? 0;
            
        } catch (\PDOException $errore) {
            error_log("Errore nel conteggio notifiche non lette: " . $errore->getMessage());
            return 0;
        }
    }
}

// Alias per compatibilità con il codice esistente
class_alias('BOSTARTER\Models\GestoreNotifiche', 'BOSTARTER\Models\Notification');