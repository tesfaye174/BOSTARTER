<?php
namespace BOSTARTER\Models;

/**
 * MODELLO GESTORE NOTIFICHE BOSTARTER
 * 
 * Questo modello gestisce tutte le operazioni relative alle notifiche:
 * - Creazione di nuove notifiche
 * - Recupero notifiche per utente
 * - Marcatura come lette
 * - Eliminazione notifiche
 * 
 * È il ponte tra il controller e il database per le notifiche
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 */

class GestoreNotifiche
{
    private $connessioneDatabase;

    /**
     * Costruttore - Inizializza la connessione al database
     * 
     * @param PDO $connessioneDatabase Connessione PDO al database
     */
    public function __construct($connessioneDatabase) {
        $this->connessioneDatabase = $connessioneDatabase;
    }

    /**
     * Recupera tutte le notifiche non lette di un utente
     * 
     * @param int $idUtente ID dell'utente
     * @return array Array delle notifiche non lette
     */
    public function ottieniNotificheNonLette($idUtente) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                SELECT id, message, type, related_id, created_at, is_read
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
                ORDER BY created_at DESC
            ");
            
            $statement->execute([$idUtente]);
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $errore) {
            error_log("Errore nel recupero notifiche non lette per utente {$idUtente}: " . $errore->getMessage());
            return [];
        }
    }

    /**
     * Marca una notifica come letta
     * 
     * @param int $idNotifica ID della notifica da marcare
     * @return array Risultato dell'operazione
     */
    public function marcaComeLetta($idNotifica) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ?
            ");
            
            $successo = $statement->execute([$idNotifica]);
            
            if ($successo && $statement->rowCount() > 0) {
                return [
                    'stato' => 'successo',
                    'messaggio' => 'Notifica marcata come letta'
                ];
            } else {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Notifica non trovata o già letta'
                ];
            }
            
        } catch (\Exception $errore) {
            error_log("Errore nel marcare notifica {$idNotifica} come letta: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Errore nel database durante l\'aggiornamento'
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
            $statement = $this->connessioneDatabase->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $successo = $statement->execute([$idUtente]);
            $notificheAggiornate = $statement->rowCount();
            
            return [
                'stato' => 'successo',
                'messaggio' => "Marcate {$notificheAggiornate} notifiche come lette",
                'aggiornate' => $notificheAggiornate
            ];
            
        } catch (\Exception $errore) {
            error_log("Errore nel marcare tutte le notifiche come lette per utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Errore nel database durante l\'aggiornamento'
            ];
        }
    }

    /**
     * Elimina una notifica specifica
     * 
     * @param int $idNotifica ID della notifica da eliminare
     * @return array Risultato dell'operazione
     */
    public function eliminaNotifica($idNotifica) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                DELETE FROM notifications WHERE id = ?
            ");
            
            $successo = $statement->execute([$idNotifica]);
            
            if ($successo && $statement->rowCount() > 0) {
                return [
                    'stato' => 'successo',
                    'messaggio' => 'Notifica eliminata con successo'
                ];
            } else {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Notifica non trovata'
                ];
            }
            
        } catch (\Exception $errore) {
            error_log("Errore nell'eliminazione notifica {$idNotifica}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Errore nel database durante l\'eliminazione'
            ];
        }
    }

    /**
     * Conta le notifiche non lette di un utente
     * 
     * @param int $idUtente ID dell'utente
     * @return int Numero di notifiche non lette
     */
    public function contaNotificheNonLette($idUtente) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                SELECT COUNT(*) as conteggio
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $statement->execute([$idUtente]);
            $risultato = $statement->fetch(\PDO::FETCH_ASSOC);
            
            return (int)$risultato['conteggio'];
            
        } catch (\Exception $errore) {
            error_log("Errore nel conteggio notifiche non lette per utente {$idUtente}: " . $errore->getMessage());
            return 0;
        }
    }

    /**
     * Crea una nuova notifica
     * 
     * @param int $idUtente ID dell'utente destinatario
     * @param string $messaggio Testo della notifica
     * @param string $tipo Tipo di notifica (default: 'info')
     * @param int|null $idRelativo ID dell'elemento correlato (progetto, commento, etc.)
     * @return array Risultato dell'operazione
     */
    public function creaNuovaNotifica($idUtente, $messaggio, $tipo = 'info', $idRelativo = null) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                INSERT INTO notifications (user_id, message, type, related_id, created_at, is_read)
                VALUES (?, ?, ?, ?, NOW(), 0)
            ");
            
            $successo = $statement->execute([$idUtente, $messaggio, $tipo, $idRelativo]);
            
            if ($successo) {
                $idNotifica = $this->connessioneDatabase->lastInsertId();
                
                return [
                    'stato' => 'successo',
                    'messaggio' => 'Notifica creata con successo',
                    'id_notifica' => $idNotifica
                ];
            } else {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Errore durante la creazione della notifica'
                ];
            }
            
        } catch (\Exception $errore) {
            error_log("Errore nella creazione notifica per utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Errore nel database durante la creazione'
            ];
        }
    }

    /**
     * Recupera le notifiche recenti di un utente con paginazione
     * 
     * @param int $idUtente ID dell'utente
     * @param int $limite Numero massimo di notifiche da recuperare
     * @param int $offset Offset per la paginazione
     * @return array Array delle notifiche
     */
    public function ottieniNotificheRecenti($idUtente, $limite = 20, $offset = 0) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                SELECT id, message, type, related_id, created_at, is_read, read_at
                FROM notifications 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $statement->execute([$idUtente, $limite, $offset]);
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $errore) {
            error_log("Errore nel recupero notifiche recenti per utente {$idUtente}: " . $errore->getMessage());
            return [];
        }
    }
}
