<?php
namespace BOSTARTER\Controllers;

/*****************************************
 * Controller Notifiche BOSTARTER
 * 
 * Questo controller implementa il sistema di gestione delle notifiche:
 * - Recupero notifiche per utente (lette/non lette)
 * - Marcatura notifiche come lette (singola o massiva)
 * - Eliminazione notifiche
 * - Conteggio notifiche non lette (per badge UI)
 * 
 * Implementa controlli di sicurezza per garantire che un utente
 * possa accedere solo alle proprie notifiche.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 * @since 1.8.5 - Implementazione paginazione e ottimizzazione query
 */

use BOSTARTER\Models\GestoreNotifiche;
use BOSTARTER\Utils\BaseController;

class NotificationController extends BaseController
{
    // Istanza del modello per le notifiche
    private $gestoreNotifiche;

    /**
     * Costruttore - Inizializza la connessione database e il modello notifiche
     * 
     * @throws PDOException Se la connessione al database fallisce
     */
    public function __construct() {
        parent::__construct(); // Inizializza la connessione database e logger dalla classe base
        $this->gestoreNotifiche = new GestoreNotifiche($this->connessioneDatabase);
    }

    /**
     * Recupera tutte le notifiche non lette di un utente specifico
     * 
     * Questo metodo ottimizza le query mostrando solo le notifiche ancora
     * non visualizzate dall'utente, ordinate per data di creazione
     * (più recenti prima).
     * 
     * @param int $userId ID dell'utente di cui recuperare le notifiche
     * @return array Risultato dell'operazione con array di notifiche e conteggio
     * @throws Exception In caso di errori di accesso al database
     */
    public function getUnreadNotifications($userId) {
        try {
            // Validazione parametri usando il metodo della classe base
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
            // Usa il gestore errori della classe base
            return $this->gestisciErrore($errore, 'recupero notifiche non lette', 'Ops! Non riesco a recuperare le tue notifiche. Riprova tra poco.');
        }
    }

    /**
     * Recupera tutte le notifiche dell'utente con paginazione
     * 
     * Implementa un sistema di paginazione efficiente per evitare
     * problemi di performance quando un utente ha molte notifiche.
     * I risultati includono metadati di paginazione per facilitare
     * l'implementazione dell'UI.
     * 
     * @param int $userId ID dell'utente
     * @param int $page Numero di pagina richiesta (default: 1)
     * @param int $perPage Numero di elementi per pagina (default: 20)
     * @return array Risultato dell'operazione con notifiche e metadati paginazione
     * @throws Exception In caso di errori di database
     */
    public function getAllNotifications($userId, $page = 1, $perPage = 20) {
        try {
            // Calcoliamo l'offset per la paginazione
            $offset = ($page - 1) * $perPage;
            
            $statement = $this->connessioneDatabase->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            
            $statement->execute([$userId, $perPage, $offset]);
            $notifiche = $statement->fetchAll(\PDO::FETCH_ASSOC);
            
            // Contiamo anche il totale per la paginazione
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

    /**
     * Marca una notifica specifica come letta
     * 
     * Include controlli di sicurezza per verificare la proprietà
     * della notifica e impedire manipolazioni non autorizzate.
     * L'operazione registra anche il timestamp di lettura.
     * 
     * @param int $notificationId ID della notifica da marcare
     * @param int $userId ID dell'utente (per verifica proprietà)
     * @return array Risultato dell'operazione
     * @throws Exception In caso di errori di accesso al database
     */
    public function markAsRead($notificationId, $userId = null) {
        try {
            // Se abbiamo l'ID utente, facciamo un controllo di sicurezza extra
            if ($userId !== null) {
                $statement = $this->connessioneDatabase->prepare("
                    SELECT user_id FROM notifications WHERE id = ?
                ");
                $statement->execute([$notificationId]);
                $notifica = $statement->fetch(\PDO::FETCH_ASSOC);
                
                // Controlliamo che la notifica appartenga davvero all'utente
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

    /**
     * Marca tutte le notifiche di un utente come lette (operazione massiva)
     * 
     * Esegue un'operazione di aggiornamento in batch sul database
     * per ottimizzare le performance quando ci sono molte notifiche da
     * aggiornare contemporaneamente.
     * 
     * @param int $userId ID dell'utente
     * @return array Risultato dell'operazione
     * @throws Exception In caso di errori di database
     */
    public function markAllAsRead($userId) {
        try {
            $risultato = $this->gestoreNotifiche->marcaTutteComeLette($userId);
            
            // Aggiungiamo informazioni più dettagliate
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

    /**
     * Elimina una notifica specifica
     * 
     * Include controlli di sicurezza per verificare la proprietà
     * della notifica prima di permettere l'eliminazione.
     * 
     * @param int $notificationId ID della notifica da eliminare
     * @param int $userId ID dell'utente (per verifica proprietà)
     * @return array Risultato dell'operazione
     * @throws Exception In caso di errori di accesso al database
     */
    public function deleteNotification($notificationId, $userId = null) {
        try {
            // Controllo di sicurezza: solo il proprietario può eliminare la notifica
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

    /**
     * Conta quante notifiche non lette ha l'utente
     * 
     * È come contare le lettere ancora sigillate nella cassetta
     * 
     * @param int $idUtente ID dell'utente
     * @return array Risultato con il conteggio
     */
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

    /**
     * Crea una nuova notifica
     * 
     * È come quando il postino ti porta una nuova lettera
     * 
     * @param array $datiNotifica Dati della notifica da creare
     * @return array Risultato dell'operazione
     */
    public function creaNuovaNotifica($datiNotifica) {
        try {
            // Validazione di base dei dati
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

    /**
     * Elimina le notifiche più vecchie di X giorni
     * 
     * Pulizia automatica, come svuotare periodicamente la cassetta della posta
     * 
     * @param int $idUtente ID dell'utente
     * @param int $giorni Elimina notifiche più vecchie di questi giorni
     * @return array Risultato dell'operazione
     */
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

    /**
     * Gestisce azioni multiple su più notifiche (bulk operations)
     * 
     * @param array $idsNotifiche Array degli ID delle notifiche
     * @param string $azione Azione da eseguire ('mark_read', 'delete')
     * @param int $idUtente ID dell'utente per controlli di sicurezza
     * @return array Risultato dell'operazione
     */
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
                        continue 2; // Skip to next iteration
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

// Alias per compatibilità con il codice esistente
class_alias('BOSTARTER\Controllers\GestoreNotificheController', 'BOSTARTER\Controllers\NotificationController');