<?php
namespace BOSTARTER\Controllers;
use BOSTARTER\Models\Notification;
use BOSTARTER\Utils\BaseController;
class NotificationController extends BaseController
{
    private $gestoreNotifiche;
    public function __construct() {
        parent::__construct(); 
        $this->gestoreNotifiche = new Notification($this->connessioneDatabase);
    }
    public function getUnreadNotifications($userId) {
        try {
            $validazione = $this->validaParametri(['idUtente'], ['idUtente' => $userId]);            
            if ($validazione !== true) {
                return $this->rispostaStandardizzata(false, 'Parametri mancanti: ' . implode(', ', $validazione));
            }
            $notificheTrovate = $this->gestoreNotifiche->ottieniNotificheNonLette($userId);
            return $this->rispostaStandardizzata(true, 'Notifiche recuperate con successo', [
                'notifiche' => $notificheTrovate,
                'conteggio' => count($notificheTrovate)
            ]);
        } catch (\Exception $errore) {
            return $this->gestisciErrore($errore, 'recupero notifiche non lette', 'Ops! Non riesco a recuperare le tue notifiche. Riprova tra poco.');
        }
    }
    public function getAllNotifications($userId, $page = 1, $perPage = 20) {
        try {
            $offset = ($page - 1) * $perPage;
            $statement = $this->connessioneDatabase->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $statement->execute([$userId, $perPage, $offset]);
            $notifiche = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statementConteggio = $this->connessioneDatabase->prepare("
                SELECT COUNT(*) as totale FROM notifications WHERE user_id = ?
            ");
            $statementConteggio->execute([$userId]);
            $totale = $statementConteggio->fetch(\PDO::FETCH_ASSOC)['totale'];
            return [
                'stato' => 'successo',
                'dati' => $notifiche,
                'paginazione' => [
                    'pagina_corrente' => $page,
                    'per_pagina' => $perPage,
                    'totale' => $totale,
                    'pagine_totali' => ceil($totale / $perPage)
                ]
            ];
        } catch (\Exception $errore) {
            error_log("Errore nel recupero di tutte le notifiche per utente {$userId}: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a recuperare le notifiche. Riprova più tardi.'
            ];
        }
    }
    public function markAsRead($notificationId, $userId = null) {
        try {
            if ($userId !== null) {
                $statement = $this->connessioneDatabase->prepare("
                    SELECT user_id FROM notifications WHERE id = ?
                ");
                $statement->execute([$notificationId]);
                $notifica = $statement->fetch(\PDO::FETCH_ASSOC);
                if (!$notifica || $notifica['user_id'] != $userId) {
                    return [
                        'stato' => 'errore',
                        'messaggio' => 'Non puoi modificare questa notifica!'
                    ];
                }
            }
            $risultato = $this->gestoreNotifiche->marcaComeLetta($notificationId);
            return $risultato;
        } catch (\Exception $errore) {
            error_log("Errore nel marcare notifica {$notificationId} come letta: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a marcare la notifica come letta. Riprova.'
            ];
        }
    }
    public function markAllAsRead($userId) {
        try {
            $risultato = $this->gestoreNotifiche->marcaTutteComeLette($userId);
            if ($risultato['stato'] === 'successo') {
                $risultato['messaggio'] = 'Tutte le notifiche sono state marcate come lette con successo';
            }
            return $risultato;
        } catch (\Exception $errore) {
            error_log("Errore nel marcare tutte le notifiche come lette per utente {$userId}: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Si è verificato un errore durante l\'aggiornamento delle notifiche'
            ];
        }
    }
    public function deleteNotification($notificationId, $userId = null) {
        try {
            if ($userId !== null) {
                $statement = $this->connessioneDatabase->prepare("
                    SELECT user_id FROM notifications WHERE id = ?
                ");
                $statement->execute([$notificationId]);
                $notifica = $statement->fetch(\PDO::FETCH_ASSOC);
                if (!$notifica || $notifica['user_id'] != $userId) {
                    return [
                        'stato' => 'errore',
                        'messaggio' => 'Non puoi eliminare questa notifica!'
                    ];
                }
            }
            $risultato = $this->gestoreNotifiche->eliminaNotifica($notificationId);
            return $risultato;
        } catch (\Exception $errore) {
            error_log("Errore nell'eliminazione notifica {$notificationId}: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a eliminare la notifica. Riprova più tardi.'
            ];
        }
    }
    public function contaNotificheNonLette($idUtente) {
        try {
            $conteggio = $this->gestoreNotifiche->contaNotificheNonLette($idUtente);
            return [
                'stato' => 'successo',
                'conteggio' => $conteggio,
                'messaggio' => $conteggio === 0 ? 
                    'Non hai notifiche da leggere!' : 
                    "Hai {$conteggio} notifiche da leggere"
            ];
        } catch (\Exception $errore) {
            error_log("Errore nel conteggio notifiche non lette per utente {$idUtente}: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a contare le notifiche.',
                'conteggio' => 0
            ];
        }
    }
    public function creaNuovaNotifica($datiNotifica) {
        try {
            if (empty($datiNotifica['user_id']) || empty($datiNotifica['message'])) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Dati della notifica incompleti: serve almeno utente e messaggio'
                ];
            }
            $risultato = $this->gestoreNotifiche->creaNuovaNotifica(
                $datiNotifica['user_id'],
                $datiNotifica['message'],
                $datiNotifica['type'] ?? 'info',
                $datiNotifica['related_id'] ?? null
            );
            return $risultato;
        } catch (\Exception $errore) {
            error_log("Errore nella creazione notifica: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a creare la notifica. Riprova.'
            ];
        }
    }
    public function pulisciNotificheVecchie($idUtente, $giorni = 30) {
        try {
            $statement = $this->connessioneDatabase->prepare("
                DELETE FROM notifications 
                WHERE user_id = ? 
                AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND is_read = 1
            ");
            $statement->execute([$idUtente, $giorni]);
            $notificheEliminate = $statement->rowCount();
            return [
                'stato' => 'successo',
                'messaggio' => "Eliminate {$notificheEliminate} notifiche vecchie",
                'eliminate' => $notificheEliminate
            ];
        } catch (\Exception $errore) {
            error_log("Errore nella pulizia notifiche vecchie per utente {$idUtente}: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a pulire le notifiche vecchie.'
            ];
        }
    }
    public function azioniMultiple($idsNotifiche, $azione, $idUtente) {
        try {
            if (empty($idsNotifiche) || !is_array($idsNotifiche)) {
                return [
                    'stato' => 'errore',
                    'messaggio' => 'Nessuna notifica selezionata'
                ];
            }
            $successi = 0;
            $errori = 0;
            foreach ($idsNotifiche as $idNotifica) {
                switch ($azione) {
                    case 'mark_read':
                        $risultato = $this->marcaComeLetta($idNotifica, $idUtente);
                        break;
                    case 'delete':
                        $risultato = $this->eliminaNotifica($idNotifica, $idUtente);
                        break;
                    default:
                        continue 2; 
                }
                if ($risultato['stato'] === 'successo') {
                    $successi++;
                } else {
                    $errori++;
                }
            }
            return [
                'stato' => 'successo',
                'messaggio' => "Operazione completata: {$successi} successi, {$errori} errori",
                'dettagli' => [
                    'successi' => $successi,
                    'errori' => $errori,
                    'totale' => count($idsNotifiche)
                ]
            ];
        } catch (\Exception $errore) {
            error_log("Errore nelle azioni multiple sulle notifiche: " . $errore->getMessage());
            return [
                'stato' => 'errore',
                'messaggio' => 'Errore durante l\'operazione multipla'
            ];
        }
    }
}
