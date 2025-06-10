<?php
namespace BOSTARTER\Controllers;

/**
 * GESTORE NOTIFICHE BOSTARTER
 * 
 * Questo controller gestisce tutte le notifiche del sistema:
 * - Mostra le notifiche agli utenti
 * - Marca le notifiche come lette
 * - Elimina notifiche vecchie
 * - Conta quelle non lette
 * 
 * È come la cassetta delle lettere di casa: ricevi messaggi importanti
 * e poi decidi quando leggerli e quando buttarli via!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione completamente riscritta per essere più umana
 */

use BOSTARTER\Models\GestoreNotifiche;
use BOSTARTER\Utils\BaseController;

class GestoreNotificheController extends BaseController
{
    // Modello per le notifiche
    private $gestoreNotifiche;

    /**
     * Costruttore - Prepara il nostro gestore delle notifiche
     */
    public function __construct() {
        parent::__construct(); // Inizializza la connessione database e logger dalla classe base
        $this->gestoreNotifiche = new GestoreNotifiche($this->connessioneDatabase);
    }

    /**
     * Recupera tutte le notifiche che l'utente non ha ancora letto
     * 
     * È come aprire la cassetta delle lettere e vedere solo le buste ancora sigillate
     * 
     * @param int $idUtente ID dell'utente di cui vogliamo le notifiche
     * @return array Risultato dell'operazione con le notifiche trovate
     */
    public function ottieniNotificheNonLette($idUtente) {
        try {
            // Validazione parametri usando il metodo della classe base
            $validazione = $this->validaParametri(['idUtente'], ['idUtente' => $idUtente]);            if ($validazione !== true) {
                return $this->rispostaStandardizzata(false, 'Parametri mancanti: ' . implode(', ', $validazione));
            }
            
            $notificheTrovate = $this->gestoreNotifiche->ottieniNotificheNonLette($idUtente);
            
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
     * Recupera tutte le notifiche dell'utente (lette e non lette)
     * 
     * @param int $idUtente ID dell'utente
     * @param int $pagina Pagina per la paginazione (default: 1)
     * @param int $quantita Quante notifiche per pagina (default: 20)
     * @return array Risultato dell'operazione
     */
    public function ottieniTutteLeNotifiche($idUtente, $pagina = 1, $quantita = 20) {
        try {
            // Calcoliamo l'offset per la paginazione
            $spostamento = ($pagina - 1) * $quantita;
            
            $statement = $this->connessioneDatabase->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            
            $statement->execute([$idUtente, $quantita, $spostamento]);
            $notifiche = $statement->fetchAll(\PDO::FETCH_ASSOC);
            
            // Contiamo anche il totale per la paginazione
            $statementConteggio = $this->connessioneDatabase->prepare("
                SELECT COUNT(*) as totale FROM notifications WHERE user_id = ?
            ");
            $statementConteggio->execute([$idUtente]);
            $totale = $statementConteggio->fetch(\PDO::FETCH_ASSOC)['totale'];
            
            return [
                'stato' => 'successo',
                'dati' => $notifiche,
                'paginazione' => [
                    'pagina_corrente' => $pagina,
                    'per_pagina' => $quantita,
                    'totale' => $totale,
                    'pagine_totali' => ceil($totale / $quantita)
                ]
            ];
            
        } catch (\Exception $errore) {
            error_log("Errore nel recupero di tutte le notifiche per utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a recuperare le notifiche. Riprova più tardi.'
            ];
        }
    }

    /**
     * Marca una notifica come letta
     * 
     * È come quando apri una lettera: da quel momento non è più "da leggere"
     * 
     * @param int $idNotifica ID della notifica da marcare
     * @param int $idUtente ID dell'utente (per sicurezza)
     * @return array Risultato dell'operazione
     */
    public function marcaComeLetta($idNotifica, $idUtente = null) {
        try {
            // Se abbiamo l'ID utente, facciamo un controllo di sicurezza extra
            if ($idUtente !== null) {
                $statement = $this->connessioneDatabase->prepare("
                    SELECT user_id FROM notifications WHERE id = ?
                ");
                $statement->execute([$idNotifica]);
                $notifica = $statement->fetch(\PDO::FETCH_ASSOC);
                
                // Controlliamo che la notifica appartenga davvero all'utente
                if (!$notifica || $notifica['user_id'] != $idUtente) {
                    return [
                        'stato' => 'errore',
                        'messaggio' => 'Non puoi modificare questa notifica!'
                    ];
                }
            }
            
            $risultato = $this->gestoreNotifiche->marcaComeLetta($idNotifica);
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nel marcare notifica {$idNotifica} come letta: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Non riesco a marcare la notifica come letta. Riprova.'
            ];
        }
    }

    /**
     * Marca TUTTE le notifiche dell'utente come lette
     * 
     * È come svuotare la cassetta delle lettere tutta in una volta
     * 
     * @param int $idUtente ID dell'utente
     * @return array Risultato dell'operazione
     */
    public function marcaTutteComeLette($idUtente) {
        try {
            $risultato = $this->gestoreNotifiche->marcaTutteComeLette($idUtente);
            
            // Aggiungiamo informazioni più dettagliate
            if ($risultato['stato'] === 'successo') {
                $risultato['messaggio'] = 'Perfetto! Tutte le tue notifiche sono ora marcate come lette.';
            }
            
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nel marcare tutte le notifiche come lette per utente {$idUtente}: " . $errore->getMessage());
            
            return [
                'stato' => 'errore',
                'messaggio' => 'Ops! Non riesco a marcare tutte le notifiche. Riprova tra poco.'
            ];
        }
    }

    /**
     * Elimina una notifica specifica
     * 
     * Come buttare via una lettera che non ti serve più
     * 
     * @param int $idNotifica ID della notifica da eliminare
     * @param int $idUtente ID dell'utente (per sicurezza)
     * @return array Risultato dell'operazione
     */
    public function eliminaNotifica($idNotifica, $idUtente = null) {
        try {
            // Controllo di sicurezza: solo il proprietario può eliminare la notifica
            if ($idUtente !== null) {
                $statement = $this->connessioneDatabase->prepare("
                    SELECT user_id FROM notifications WHERE id = ?
                ");
                $statement->execute([$idNotifica]);
                $notifica = $statement->fetch(\PDO::FETCH_ASSOC);
                
                if (!$notifica || $notifica['user_id'] != $idUtente) {
                    return [
                        'stato' => 'errore',
                        'messaggio' => 'Non puoi eliminare questa notifica!'
                    ];
                }
            }
            
            $risultato = $this->gestoreNotifiche->eliminaNotifica($idNotifica);
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nell'eliminazione notifica {$idNotifica}: " . $errore->getMessage());
            
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