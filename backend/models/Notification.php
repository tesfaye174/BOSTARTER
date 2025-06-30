<?php
namespace BOSTARTER\Models;
class GestoreNotifiche {
    private $database;
    private $nomeTabella = 'notifications';
    public function __construct($database) {
        $this->database = $database;
    }
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
