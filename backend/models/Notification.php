<?php
namespace BOSTARTER\Models;

/**
 * Modello Notifiche - Gestione del sistema di notifiche BOSTARTER
 * 
 * Questa classe gestisce tutte le operazioni relative alle notifiche:
 * - Creazione di nuove notifiche per gli utenti
 * - Recupero delle notifiche per utente
 * - Marcatura delle notifiche come lette
 * - Eliminazione delle notifiche vecchie o non necessarie
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 */
class GestoreNotifiche {
    // Connessione al database
    private $database;
    private $nomeTabella = 'notifications';

    /**
     * Costruttore - Inizializza la connessione al database
     * 
     * @param PDO $database Connessione al database
     */
    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Crea una nuova notifica per un utente
     * 
     * @param array $datiNotifica Dati della notifica da creare
     * @return array Risultato dell'operazione con ID della notifica se successo
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
        }    }

    /**
     * Recupera le notifiche non lette di un utente
     * 
     * @param int $idUtente ID dell'utente
     * @return array Lista delle notifiche non lette
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
     * @param int $idNotifica ID della notifica da marcare come letta
     * @return array Risultato dell'operazione
     */
    public function marcaComeLetta($idNotifica) {
        try {            $statement = $this->database->prepare("
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
     * Marca tutte le notifiche di un utente come lette
     * 
     * @param int $idUtente ID dell'utente
     * @return array Risultato dell'operazione
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
     * Elimina una notifica specifica
     * 
     * @param int $idNotifica ID della notifica da eliminare     * @return array Risultato dell'operazione
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
     * @param int $idUtente ID dell'utente
     * @return int Numero di notifiche non lette
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