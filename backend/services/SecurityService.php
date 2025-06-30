<?php
namespace BOSTARTER\Services;
class ServizioSicurezza {
    private $connessioneDatabase;
    private $sistemaCache;
    private $configurazioniSicurezza;
    const PREFISSO_LIMITE_RICHIESTE = 'limite_richieste:';
    const PREFISSO_IP_BLOCCATI = 'ip_bloccato:';
    const PREFISSO_LOGIN_FALLITI = 'login_falliti:';
    public function __construct($database, $cache = null) {
        $this->connessioneDatabase = $database;
        $this->sistemaCache = $cache;
        $this->configurazioniSicurezza = $this->caricaConfigurazioniSicurezza();
        $this->creaTabellePerSicurezza();
    }
    private function caricaConfigurazioniSicurezza() {
        return [
            'controllo_limiti_attivo' => $_ENV['RATE_LIMIT_ENABLED'] ?? true,
            'limite_richieste_api' => $_ENV['API_RATE_LIMIT'] ?? 100, 
            'limite_tentativi_login' => $_ENV['LOGIN_RATE_LIMIT'] ?? 5, 
            'limite_notifiche' => $_ENV['NOTIFICATION_RATE_LIMIT'] ?? 50, 
            'blocco_automatico_attivo' => $_ENV['AUTO_BLOCK_ENABLED'] ?? true,
            'massimi_login_falliti' => $_ENV['MAX_FAILED_LOGINS'] ?? 10, 
            'durata_blocco_secondi' => $_ENV['BLOCK_DURATION'] ?? 3600, 
            'rilevamento_spam_attivo' => $_ENV['SPAM_DETECTION_ENABLED'] ?? true,
            'lunghezza_massima_commento' => $_ENV['MAX_COMMENT_LENGTH'] ?? 5000,
            'lunghezza_massima_descrizione_progetto' => $_ENV['MAX_PROJECT_DESC_LENGTH'] ?? 50000,
            'registra_eventi_sicurezza' => $_ENV['LOG_SECURITY_EVENTS'] ?? true,
            'avvisa_admin_per_attacchi' => $_ENV['ALERT_ADMIN_ON_ATTACK'] ?? true,
            'soglia_attivita_sospetta' => $_ENV['SUSPICIOUS_ACTIVITY_THRESHOLD'] ?? 20, 
        ];
    }
    private function creaTabellePerSicurezza() {
        try {
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
            $this->connessioneDatabase->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chiave_identificativa VARCHAR(255) NOT NULL COMMENT 'Chiave univoca per il limite',
                    contatore_richieste INT DEFAULT 1 COMMENT 'Numero di richieste effettuate',
                    finestra_temporale TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Inizio della finestra temporale',
                    scadenza TIMESTAMP NULL DEFAULT NULL COMMENT 'Quando scade questo limite',
                    UNIQUE KEY uk_chiave (chiave_identificativa)
                ) ENGINE=InnoDB COMMENT='Gestione dei limiti di richieste'
            ");
        } catch (\PDOException $errore) {
            error_log("Errore nella creazione tabelle sicurezza: " . $errore->getMessage());
        }
    }
    public function controllaSeIpBloccato($indirizzoIp = null): bool {
        $indirizzoIp = $indirizzoIp ?? $this->ottieniIndirizzoIpCorrente();
        try {
            if ($this->sistemaCache) {
                $chiaveCache = self::PREFISSO_IP_BLOCCATI . $indirizzoIp;
                $risultatoCache = $this->sistemaCache->get($chiaveCache);
                if ($risultatoCache !== false) {
                    return (bool) $risultatoCache;
                }
            }
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
            if ($this->sistemaCache) {
                $this->sistemaCache->set($chiaveCache, $ipBloccato, 300);
            }
            return $ipBloccato;
        } catch (\Exception $errore) {
            error_log("Errore nel controllo IP bloccato: " . $errore->getMessage());
            return false; 
        }
    }
    public function controllaLimiteRichieste($chiaveIdentificativa, $limiteMassimo, $finestraTemporale): bool {
        if (!$this->configurazioniSicurezza['controllo_limiti_attivo']) {
            return true; 
        }
        try {
            $adesso = time();
            $scadenza = $adesso + $finestraTemporale;
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
            $statement = $this->connessioneDatabase->prepare("
                SELECT contatore_richieste 
                FROM rate_limits 
                WHERE chiave_identificativa = ? AND scadenza > NOW()
            ");
            $statement->execute([$chiaveIdentificativa]);
            $risultato = $statement->fetch(\PDO::FETCH_ASSOC);
            if ($risultato && $risultato['contatore_richieste'] > $limiteMassimo) {
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
            return true; 
        }
    }
    public function bloccaIndirizzoIp($indirizzoIp, $motivo = 'Attività sospetta', $dettagliAggiuntivi = []) {
        try {
            $this->registraEventoSicurezza('ip_blocked', array_merge([
                'motivo' => $motivo,
                'durata_blocco' => $this->configurazioniSicurezza['durata_blocco_secondi']
            ], $dettagliAggiuntivi), $indirizzoIp);
            if ($this->sistemaCache) {
                $chiaveCache = self::PREFISSO_IP_BLOCCATI . $indirizzoIp;
                $this->sistemaCache->delete($chiaveCache);
            }
            error_log("IP {$indirizzoIp} bloccato per: {$motivo}");
        } catch (\Exception $errore) {
            error_log("Errore nel blocco IP: " . $errore->getMessage());
        }
    }
    public function registraEventoSicurezza($tipoEvento, $dettagli = [], $indirizzoIp = null, $userId = null) {
        if (!$this->configurazioniSicurezza['registra_eventi_sicurezza']) {
            return; 
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
    private function ottieniIndirizzoIpCorrente(): string {
        $intestazioniDaControllare = [
            'HTTP_CF_CONNECTING_IP',     
            'HTTP_CLIENT_IP',            
            'HTTP_X_FORWARDED_FOR',      
            'HTTP_X_FORWARDED',          
            'HTTP_X_CLUSTER_CLIENT_IP',  
            'HTTP_FORWARDED_FOR',        
            'HTTP_FORWARDED',            
            'REMOTE_ADDR'                
        ];
        foreach ($intestazioniDaControllare as $intestazione) {
            if (!empty($_SERVER[$intestazione])) {
                $ips = explode(',', $_SERVER[$intestazione]);
                $ip = trim($ips[0]); 
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    public function puliziaAutomaticaDatiVecchi() {
        try {
            $this->connessioneDatabase->exec("
                DELETE FROM security_events 
                WHERE timestamp_evento < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $this->connessioneDatabase->exec("
                DELETE FROM rate_limits 
                WHERE scadenza < NOW()
            ");
            error_log("Pulizia automatica dati sicurezza completata");
        } catch (\Exception $errore) {
            error_log("Errore nella pulizia dati sicurezza: " . $errore->getMessage());
        }
    }
    public function ottieniStatisticheSicurezza($giorni = 7): array {
        try {
            $risultato = [];
            $statement = $this->connessioneDatabase->prepare("
                SELECT tipo_evento, COUNT(*) as conteggio
                FROM security_events 
                WHERE timestamp_evento > DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY tipo_evento
                ORDER BY conteggio DESC
            ");
            $statement->execute([$giorni]);
            $risultato['eventi_per_tipo'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
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
    public function isIPBlocked(string $indirizzoIP): bool {
        try {
            if ($this->sistemaCache) {
                $chiaveCache = self::PREFISSO_IP_BLOCCATI . $indirizzoIP;
                $bloccato = $this->sistemaCache->get($chiaveCache);
                if ($bloccato !== false) {
                    return (bool)$bloccato;
                }
            }
            $query = "SELECT COUNT(*) FROM blocchi_ip 
                     WHERE indirizzo_ip = ? 
                     AND (scadenza_blocco IS NULL OR scadenza_blocco > NOW())";
            $stmt = $this->connessioneDatabase->prepare($query);
            $stmt->execute([$indirizzoIP]);
            $isBlocked = $stmt->fetchColumn() > 0;
            if ($this->sistemaCache) {
                $this->sistemaCache->set($chiaveCache, $isBlocked, 300);
            }
            return $isBlocked;
        } catch (\Exception $e) {
            error_log("Errore controllo IP bloccato: " . $e->getMessage());
            return false; 
        }
    }
}
