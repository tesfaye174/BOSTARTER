<?php
namespace BOSTARTER\Services;

/**
 * SERVIZIO SICUREZZA BOSTARTER
 * 
 * Questo servizio è come il sistema di sicurezza di una banca:
 * - Controlla chi può entrare e quante volte
 * - Blocca gli IP sospetti che provano troppi attacchi
 * - Monitora tutto quello che succede per individuare minacce
 * - Protegge da spam e contenuti dannosi
 * 
 * È il nostro guardiano digitale che lavora 24/7 per mantenere BOSTARTER sicuro!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione riscritta con commenti umani
 */

class ServizioSicurezza {
    // Connessioni e configurazioni principali
    private $connessioneDatabase;
    private $sistemaCache;
    private $configurazioniSicurezza;
    
    // Prefissi per le chiavi di cache (organizzazione)
    const PREFISSO_LIMITE_RICHIESTE = 'limite_richieste:';
    const PREFISSO_IP_BLOCCATI = 'ip_bloccato:';
    const PREFISSO_LOGIN_FALLITI = 'login_falliti:';
    
    /**
     * Costruttore - Inizializza il nostro sistema di sicurezza
     * 
     * @param PDO $database Connessione al database
     * @param mixed $cache Sistema di cache (opzionale)
     */
    public function __construct($database, $cache = null) {
        $this->connessioneDatabase = $database;
        $this->sistemaCache = $cache;
        $this->configurazioniSicurezza = $this->caricaConfigurazioniSicurezza();
        $this->creaTabellePerSicurezza();
    }
    
    /**
     * Carica tutte le configurazioni per la sicurezza del sito
     * 
     * È come impostare i parametri del sistema d'allarme di casa:
     * quanto deve essere sensibile, cosa deve controllare, ecc.
     * 
     * @return array Configurazioni di sicurezza
     */
    private function caricaConfigurazioniSicurezza() {
        return [
            // ===== CONTROLLO LIMITI RICHIESTE =====
            'controllo_limiti_attivo' => $_ENV['RATE_LIMIT_ENABLED'] ?? true,
            'limite_richieste_api' => $_ENV['API_RATE_LIMIT'] ?? 100, // richieste all'ora per le API
            'limite_tentativi_login' => $_ENV['LOGIN_RATE_LIMIT'] ?? 5, // tentativi ogni 15 minuti
            'limite_notifiche' => $_ENV['NOTIFICATION_RATE_LIMIT'] ?? 50, // notifiche all'ora
            
            // ===== BLOCCO AUTOMATICO IP SOSPETTI =====
            'blocco_automatico_attivo' => $_ENV['AUTO_BLOCK_ENABLED'] ?? true,
            'massimi_login_falliti' => $_ENV['MAX_FAILED_LOGINS'] ?? 10, // dopo 10 tentativi blocchiamo
            'durata_blocco_secondi' => $_ENV['BLOCK_DURATION'] ?? 3600, // bloccato per 1 ora
            
            // ===== PROTEZIONE DA SPAM E CONTENUTI DANNOSI =====
            'rilevamento_spam_attivo' => $_ENV['SPAM_DETECTION_ENABLED'] ?? true,
            'lunghezza_massima_commento' => $_ENV['MAX_COMMENT_LENGTH'] ?? 5000,
            'lunghezza_massima_descrizione_progetto' => $_ENV['MAX_PROJECT_DESC_LENGTH'] ?? 50000,
            
            // ===== MONITORAGGIO E ALERTING =====
            'registra_eventi_sicurezza' => $_ENV['LOG_SECURITY_EVENTS'] ?? true,
            'avvisa_admin_per_attacchi' => $_ENV['ALERT_ADMIN_ON_ATTACK'] ?? true,
            'soglia_attivita_sospetta' => $_ENV['SUSPICIOUS_ACTIVITY_THRESHOLD'] ?? 20, // eventi sospetti per triggerare allarme
        ];
    }
    
    /**
     * Crea le tabelle necessarie per il sistema di sicurezza
     * 
     * È come installare le telecamere di sicurezza: prepariamo tutto
     * quello che serve per monitorare e registrare gli eventi
     */
    private function creaTabellePerSicurezza() {
        try {
            // Tabella per registrare tutti gli eventi di sicurezza
            $this->connessioneDatabase->exec("
                CREATE TABLE IF NOT EXISTS security_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tipo_evento VARCHAR(50) NOT NULL COMMENT 'Tipo di evento (login_failed, ip_blocked, etc.)',
                    indirizzo_ip VARCHAR(45) NOT NULL COMMENT 'IP da cui proviene l\\'evento',
                    user_id INT NULL COMMENT 'ID utente se applicabile',
                    dettagli_evento JSON NULL COMMENT 'Dettagli aggiuntivi in formato JSON',
                    timestamp_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ip (indirizzo_ip),
                    INDEX idx_tipo_evento (tipo_evento),
                    INDEX idx_timestamp (timestamp_evento)
                ) ENGINE=InnoDB COMMENT='Registro degli eventi di sicurezza'
            ");
            
            // Tabella per gestire i rate limits
            $this->connessioneDatabase->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chiave_identificativa VARCHAR(255) NOT NULL COMMENT 'Chiave univoca per il limite',
                    contatore_richieste INT DEFAULT 1 COMMENT 'Numero di richieste effettuate',
                    finestra_temporale TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Inizio della finestra temporale',
                    scadenza TIMESTAMP NOT NULL COMMENT 'Quando scade questo limite',
                    UNIQUE KEY uk_chiave (chiave_identificativa)
                ) ENGINE=InnoDB COMMENT='Gestione dei limiti di richieste'
            ");
            
        } catch (\PDOException $errore) {
            error_log("Errore nella creazione tabelle sicurezza: " . $errore->getMessage());
        }
    }
    
    /**
     * Controlla se un IP è attualmente bloccato
     * 
     * È come controllare se qualcuno è nella lista nera all'ingresso di un locale
     * 
     * @param string $indirizzoIp IP da controllare (default: IP corrente)
     * @return bool True se l'IP è bloccato
     */
    public function controllaSeIpBloccato($indirizzoIp = null): bool {
        $indirizzoIp = $indirizzoIp ?? $this->ottieniIndirizzoIpCorrente();
        
        try {
            // Prima controlliamo nella cache per prestazioni migliori
            if ($this->sistemaCache) {
                $chiaveCache = self::PREFISSO_IP_BLOCCATI . $indirizzoIp;
                $risultatoCache = $this->sistemaCache->get($chiaveCache);
                if ($risultatoCache !== false) {
                    return (bool) $risultatoCache;
                }
            }
            
            // Se non è in cache, controlliamo nel database
            $statement = $this->connessioneDatabase->prepare("
                SELECT COUNT(*) as bloccato 
                FROM security_events 
                WHERE tipo_evento = 'ip_blocked' 
                AND indirizzo_ip = ? 
                AND timestamp_evento > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $statement->execute([
                $indirizzoIp, 
                $this->configurazioniSicurezza['durata_blocco_secondi']
            ]);
            
            $risultato = $statement->fetch(\PDO::FETCH_ASSOC);
            $ipBloccato = $risultato['bloccato'] > 0;
            
            // Salviamo il risultato in cache per 5 minuti
            if ($this->sistemaCache) {
                $this->sistemaCache->set($chiaveCache, $ipBloccato, 300);
            }
            
            return $ipBloccato;
            
        } catch (\Exception $errore) {
            error_log("Errore nel controllo IP bloccato: " . $errore->getMessage());
            return false; // In caso di errore, permettiamo l'accesso
        }
    }
    
    /**
     * Controlla e applica i limiti di richieste (rate limiting)
     * 
     * È come un buttafuori che conta quante persone entrano e impedisce
     * a qualcuno di entrare troppe volte in poco tempo
     * 
     * @param string $chiaveIdentificativa Identificativo univoco (es: 'api:192.168.1.1')
     * @param int $limiteMassimo Numero massimo di richieste permesse
     * @param int $finestraTemporale Finestra temporale in secondi
     * @return bool True se la richiesta è permessa, False se è stato superato il limite
     */
    public function controllaLimiteRichieste($chiaveIdentificativa, $limiteMassimo, $finestraTemporale): bool {
        if (!$this->configurazioniSicurezza['controllo_limiti_attivo']) {
            return true; // Se i controlli sono disattivati, permettiamo tutto
        }
        
        try {
            $adesso = time();
            $scadenza = $adesso + $finestraTemporale;
            
            // Prima proviamo a incrementare il contatore
            $statement = $this->connessioneDatabase->prepare("
                INSERT INTO rate_limits (chiave_identificativa, contatore_richieste, scadenza) 
                VALUES (?, 1, FROM_UNIXTIME(?))
                ON DUPLICATE KEY UPDATE 
                    contatore_richieste = CASE 
                        WHEN scadenza < NOW() THEN 1  -- Reset se scaduto
                        ELSE contatore_richieste + 1  -- Altrimenti incrementa
                    END,
                    scadenza = CASE 
                        WHEN scadenza < NOW() THEN FROM_UNIXTIME(?)  -- Nuova scadenza se reset
                        ELSE scadenza  -- Mantieni la scadenza esistente
                    END
            ");
            
            $statement->execute([$chiaveIdentificativa, $scadenza, $scadenza]);
            
            // Ora controlliamo se abbiamo superato il limite
            $statement = $this->connessioneDatabase->prepare("
                SELECT contatore_richieste 
                FROM rate_limits 
                WHERE chiave_identificativa = ? AND scadenza > NOW()
            ");
            
            $statement->execute([$chiaveIdentificativa]);
            $risultato = $statement->fetch(\PDO::FETCH_ASSOC);
            
            if ($risultato && $risultato['contatore_richieste'] > $limiteMassimo) {
                // Registriamo l'evento di superamento limite
                $this->registraEventoSicurezza('rate_limit_exceeded', [
                    'chiave' => $chiaveIdentificativa,
                    'contatore' => $risultato['contatore_richieste'],
                    'limite' => $limiteMassimo
                ]);
                
                return false;
            }
            
            return true;
            
        } catch (\Exception $errore) {
            error_log("Errore nel controllo rate limit: " . $errore->getMessage());
            return true; // In caso di errore, permettiamo la richiesta
        }
    }
    
    /**
     * Blocca automaticamente un IP per attività sospette
     * 
     * @param string $indirizzoIp IP da bloccare
     * @param string $motivo Motivo del blocco
     * @param array $dettagliAggiuntivi Dettagli extra del blocco
     */
    public function bloccaIndirizzoIp($indirizzoIp, $motivo = 'Attività sospetta', $dettagliAggiuntivi = []) {
        try {
            $this->registraEventoSicurezza('ip_blocked', array_merge([
                'motivo' => $motivo,
                'durata_blocco' => $this->configurazioniSicurezza['durata_blocco_secondi']
            ], $dettagliAggiuntivi), $indirizzoIp);
            
            // Invalidiamo la cache per questo IP
            if ($this->sistemaCache) {
                $chiaveCache = self::PREFISSO_IP_BLOCCATI . $indirizzoIp;
                $this->sistemaCache->delete($chiaveCache);
            }
            
            // Log dell'evento
            error_log("IP {$indirizzoIp} bloccato per: {$motivo}");
            
        } catch (\Exception $errore) {
            error_log("Errore nel blocco IP: " . $errore->getMessage());
        }
    }
    
    /**
     * Registra un evento di sicurezza nel database
     * 
     * @param string $tipoEvento Tipo di evento
     * @param array $dettagli Dettagli dell'evento
     * @param string $indirizzoIp IP (opzionale, default: IP corrente)
     * @param int $userId ID utente (opzionale)
     */
    public function registraEventoSicurezza($tipoEvento, $dettagli = [], $indirizzoIp = null, $userId = null) {
        if (!$this->configurazioniSicurezza['registra_eventi_sicurezza']) {
            return; // Se il logging è disattivato, non registriamo nulla
        }
        
        try {
            $indirizzoIp = $indirizzoIp ?? $this->ottieniIndirizzoIpCorrente();
            $userId = $userId ?? ($_SESSION['user_id'] ?? null);
            
            $statement = $this->connessioneDatabase->prepare("
                INSERT INTO security_events (tipo_evento, indirizzo_ip, user_id, dettagli_evento)
                VALUES (?, ?, ?, ?)
            ");
            
            $statement->execute([
                $tipoEvento,
                $indirizzoIp,
                $userId,
                json_encode($dettagli, JSON_UNESCAPED_UNICODE)
            ]);
            
        } catch (\Exception $errore) {
            error_log("Errore nella registrazione evento sicurezza: " . $errore->getMessage());
        }
    }
    
    /**
     * Ottiene l'indirizzo IP del client corrente
     * 
     * Gestisce proxy, CDN e load balancer per ottenere il vero IP
     * 
     * @return string Indirizzo IP del client
     */
    private function ottieniIndirizzoIpCorrente(): string {
        // Lista delle intestazioni da controllare in ordine di priorità
        $intestazioniDaControllare = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy condivisi
            'HTTP_X_FORWARDED_FOR',      // Proxy standard
            'HTTP_X_FORWARDED',          // Variante proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster load balancer
            'HTTP_FORWARDED_FOR',        // RFC 7239
            'HTTP_FORWARDED',            // RFC 7239
            'REMOTE_ADDR'                // IP diretto (fallback)
        ];
        
        foreach ($intestazioniDaControllare as $intestazione) {
            if (!empty($_SERVER[$intestazione])) {
                $ips = explode(',', $_SERVER[$intestazione]);
                $ip = trim($ips[0]); // Prendiamo il primo IP della lista
                
                // Validazione base dell'IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback: ritorniamo l'IP locale o un placeholder
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Pulisce i dati vecchi per mantenere le tabelle efficienti
     * 
     * Dovrebbe essere chiamato periodicamente da un cron job
     */
    public function puliziaAutomaticaDatiVecchi() {
        try {
            // Rimuove eventi di sicurezza più vecchi di 30 giorni
            $this->connessioneDatabase->exec("
                DELETE FROM security_events 
                WHERE timestamp_evento < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            // Rimuove rate limits scaduti
            $this->connessioneDatabase->exec("
                DELETE FROM rate_limits 
                WHERE scadenza < NOW()
            ");
            
            error_log("Pulizia automatica dati sicurezza completata");
            
        } catch (\Exception $errore) {
            error_log("Errore nella pulizia dati sicurezza: " . $errore->getMessage());
        }
    }
    
    /**
     * Ottiene statistiche di sicurezza per gli amministratori
     * 
     * @param int $giorni Numero di giorni da analizzare
     * @return array Statistiche di sicurezza
     */
    public function ottieniStatisticheSicurezza($giorni = 7): array {
        try {
            $risultato = [];
            
            // Eventi di sicurezza per tipo negli ultimi X giorni
            $statement = $this->connessioneDatabase->prepare("
                SELECT tipo_evento, COUNT(*) as conteggio
                FROM security_events 
                WHERE timestamp_evento > DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY tipo_evento
                ORDER BY conteggio DESC
            ");
            $statement->execute([$giorni]);
            $risultato['eventi_per_tipo'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
            
            // IP più attivi (potenziali attaccanti)
            $statement = $this->connessioneDatabase->prepare("
                SELECT indirizzo_ip, COUNT(*) as conteggio
                FROM security_events 
                WHERE timestamp_evento > DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY indirizzo_ip
                ORDER BY conteggio DESC
                LIMIT 10
            ");
            $statement->execute([$giorni]);
            $risultato['ip_piu_attivi'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
            
            // Trend giornaliero
            $statement = $this->connessioneDatabase->prepare("
                SELECT DATE(timestamp_evento) as giorno, COUNT(*) as eventi
                FROM security_events 
                WHERE timestamp_evento > DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(timestamp_evento)
                ORDER BY giorno
            ");
            $statement->execute([$giorni]);
            $risultato['trend_giornaliero'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
            
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nel recupero statistiche sicurezza: " . $errore->getMessage());
            return [];
        }
    }
}

// Alias per compatibilità con il codice esistente
class_alias('BOSTARTER\Services\ServizioSicurezza', 'BOSTARTER\Services\SecurityService');
