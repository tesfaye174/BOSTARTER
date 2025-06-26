<?php
/**
 * Servizio per l'invio di email di notifica BOSTARTER
 * 
 * Gestisce l'invio di tutte le comunicazioni email verso gli utenti:
 * - Email di benvenuto dopo registrazione
 * - Conferme di operazioni importanti
 * - Notifiche su progetti seguiti
 * - Alert di sicurezza
 * 
 * Utilizza PHPMailer per SMTP sicuro e supporta sia invio immediato
 * che accodamento per invio differito tramite cron job.
 */

namespace BOSTARTER\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotificationService {
    private $db;
    private $mailer;
    private $config;
    private $templateEngine;

    /**
     * Inizializza il servizio email con connessione al database e configurazione SMTP
     * 
     * @param object $db Connessione al database per accesso ai modelli email e log
     */
    public function __construct($db) {
        $this->db = $db;
        $this->config = $this->loadEmailConfig();
        $this->initializeMailer();
        $this->templateEngine = new EmailTemplateEngine($db);
    }

    /**
     * Carica la configurazione SMTP e sender dalle variabili d'ambiente
     * 
     * @return array Configurazione completa per connessione SMTP ed invio
     */
    private function loadEmailConfig() {
        return [
            'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',       // Server SMTP da utilizzare
            'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,               // Porta SMTP (25, 465, 587)
            'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',        // Username autenticazione SMTP
            'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',        // Password autenticazione SMTP
            'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls',         // Tipo connessione sicura (tls/ssl)
            'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@bostarter.com',  // Mittente delle email
            'from_name' => $_ENV['FROM_NAME'] ?? 'BOSTARTER',       // Nome visualizzato del mittente
            'reply_to' => $_ENV['REPLY_TO'] ?? 'support@bostarter.com' // Indirizzo risposte
        ];
    }

    /**
     * Configura l'istanza PHPMailer con le impostazioni SMTP
     * Gestisce il livello di debug e tutte le opzioni di connessione
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true); // true abilita le eccezioni per una migliore gestione errori
        
        try {
            // Configurazione server SMTP con tutti i parametri necessari per la connessione
            $this->mailer->isSMTP();                                    // Utilizza protocollo SMTP
            $this->mailer->Host = $this->config['smtp_host'];           // Server SMTP (es. smtp.gmail.com)
            $this->mailer->SMTPAuth = true;                             // Abilita autenticazione SMTP
            $this->mailer->Username = $this->config['smtp_username'];   // Username SMTP
            $this->mailer->Password = $this->config['smtp_password'];   // Password SMTP
            $this->mailer->SMTPSecure = $this->config['smtp_secure'];   // Encryption (tls/ssl)
            $this->mailer->Port = $this->config['smtp_port'];           // Porta TCP (587 per TLS)
            
            // Configurazione debug - utile per troubleshooting problemi di connessione
            $this->mailer->SMTPDebug = 0;             // 0=off, 1=errors, 2=messages, 3=verbose
            $this->mailer->Debugoutput = 'error_log'; // Output nel log PHP invece che stdout
            
            // Timeout connessione per evitare blocchi in caso di problemi server
            $this->mailer->Timeout = 30;              // Timeout in secondi per connessione SMTP
            
            // Configurazione mittente predefinito per tutte le email
            $this->mailer->setFrom(
                $this->config['from_email'], 
                $this->config['from_name'], 
                false  // false = non verificare il mittente (necessario per alcuni server)
            );
            $this->mailer->addReplyTo($this->config['reply_to'], $this->config['from_name']);
            
            // Configurazione caratteri per supporto UTF-8 completo
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            
        } catch (Exception $e) {
            // Log dettagliato dell'errore per facilitare troubleshooting
            error_log("Errore configurazione email SMTP: " . $e->getMessage());
        }
    }

    /**
     * Accoda una notifica email per invio differito o immediato
     * 
     * Il sistema di code email permette:
     * - Gestione separata dell'invio rispetto alla generazione
     * - Retry automatico in caso di fallimenti temporanei
     * - Prioritizzazione delle email urgenti
     * - Tracciamento completo dello stato di invio
     * 
     * @param int $userId ID dell'utente destinatario
     * @param int $notificationId ID della notifica collegata
     * @param string $templateName Nome del template predefinito (welcome, password_reset, ecc)
     * @param array $variables Dati da inserire nel template (sostituzioni)
     * @param string $priority Priorità di invio: 'high', 'normal', 'low'
     * @return bool Successo dell'inserimento nella coda
     */
    public function queueEmailNotification($userId, $notificationId, $templateName, $variables = [], $priority = 'normal') {
        try {
            // Ottieni email e impostazioni utente
            $user = $this->getUserEmailSettings($userId);
            if (!$user || !$user['email_enabled']) {
                return false;
            }

            // Controlla le ore di silenzio
            if ($this->isQuietHours($user)) {
                $scheduledAt = $this->getNextAllowedTime($user);
            } else {
                $scheduledAt = date('Y-m-d H:i:s');
            }

            // Ottieni il template
            $template = $this->templateEngine->getTemplate($templateName);
            if (!$template) {
                throw new Exception("Template not found: $templateName");
            }

            // Esegui il rendering del contenuto email
            $subject = $this->templateEngine->render($template['subject'], $variables);
            $body = $this->templateEngine->render($template['body_html'], $variables);

            // Accoda l'email nel database
            $stmt = $this->db->prepare("
                INSERT INTO email_queue (user_id, notification_id, to_email, subject, body, template, priority, scheduled_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $userId,
                $notificationId,
                $user['email'],
                $subject,
                $body,
                $templateName,
                $priority,
                $scheduledAt
            ]);

        } catch (Exception $e) {
            error_log("Error queueing email notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elenca e invia le email in coda
     * Questo metodo deve essere eseguito tramite un job cron periodico
     * 
     * @param int $batchSize Numero massimo di email da elaborare per volta
     * @return array Statistiche sul processamento (email elaborate, inviate con successo, fallite)
     */
    public function processEmailQueue($batchSize = 50) {
        try {
            // Ottieni le email in attesa di invio
            $stmt = $this->db->prepare("
                SELECT * FROM email_queue 
                WHERE status = 'pending' 
                AND scheduled_at <= NOW() 
                AND attempts < max_attempts
                ORDER BY priority DESC, scheduled_at ASC 
                LIMIT ?
            ");
            $stmt->execute([$batchSize]);
            $emails = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $processed = 0;
            $successful = 0;

            foreach ($emails as $email) {
                $processed++;
                
                // Segna come in elaborazione
                $this->updateEmailStatus($email['id'], 'processing');

                try {
                    // Invia l'email
                    $this->mailer->clearAddresses();
                    $this->mailer->addAddress($email['to_email']);
                    $this->mailer->Subject = $email['subject'];
                    $this->mailer->Body = $email['body'];
                    $this->mailer->isHTML(true);

                    if ($this->mailer->send()) {
                        // Segna come inviata
                        $this->updateEmailStatus($email['id'], 'sent', null, date('Y-m-d H:i:s'));
                        $this->logEmailDelivery($email, 'sent');
                        $successful++;
                    } else {
                        throw new Exception("Failed to send email");
                    }

                } catch (Exception $e) {
                    // Segna come fallita e incrementa i tentativi
                    $this->updateEmailStatus($email['id'], 'failed', $e->getMessage());
                    $this->incrementEmailAttempts($email['id']);
                    $this->logEmailDelivery($email, 'failed', $e->getMessage());
                    
                    error_log("Failed to send email to {$email['to_email']}: " . $e->getMessage());
                }
            }

            return [
                'processed' => $processed,
                'successful' => $successful,
                'failed' => $processed - $successful
            ];

        } catch (Exception $e) {
            error_log("Error processing email queue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invia immediatamente una notifica email
     * Utile per comunicazioni urgenti che non devono essere accodate
     * 
     * @param int $userId ID dell'utente destinatario
     * @param string $templateName Nome del template email da utilizzare
     * @param array $variables Variabili da sostituire nel template
     * @return bool Successo dell'invio email
     */
    public function sendImmediateEmail($userId, $templateName, $variables = []) {
        try {
            // Ottieni email utente
            $user = $this->getUserEmailSettings($userId);
            if (!$user || !$user['email_enabled']) {
                return false;
            }

            // Ottieni il template
            $template = $this->templateEngine->getTemplate($templateName);
            if (!$template) {
                throw new Exception("Template not found: $templateName");
            }

            // Esegui il rendering del contenuto email
            $subject = $this->templateEngine->render($template['subject'], $variables);
            $body = $this->templateEngine->render($template['body_html'], $variables);

            // Invia l'email
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['nickname']);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->isHTML(true);

            $result = $this->mailer->send();
            
            if ($result) {
                $this->logEmailDelivery([
                    'user_id' => $userId,
                    'to_email' => $user['email'],
                    'subject' => $subject,
                    'template' => $templateName
                ], 'sent');
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error sending immediate email: " . $e->getMessage());
            $this->logEmailDelivery([
                'user_id' => $userId,
                'to_email' => $user['email'] ?? 'unknown',
                'template' => $templateName
            ], 'failed', $e->getMessage());
            return false;
        }
    }

    /**
     * Ottieni le impostazioni email dell'utente
     * Include preferenze su abilitazione email, frequenza e ore di silenzio
     * 
     * @param int $userId ID dell'utente
     * @return array|false Array con le impostazioni email o false se non trovato
     */
    private function getUserEmailSettings($userId) {
        $stmt = $this->db->prepare("
            SELECT u.email, u.nickname, 
                   ns.email_enabled, ns.email_frequency, ns.quiet_hours_start, 
                   ns.quiet_hours_end, ns.timezone            FROM utenti u
            LEFT JOIN notification_settings ns ON u.id = ns.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Controlla se l'ora corrente è all'interno delle ore di silenzio dell'utente
     * 
     * @param array $user Array con le informazioni dell'utente
     * @return bool True se è nelle ore di silenzio, false altrimenti
     */
    private function isQuietHours($user) {
        if (!$user['quiet_hours_start'] || !$user['quiet_hours_end']) {
            return false;
        }

        $timezone = new \DateTimeZone($user['timezone'] ?? 'UTC');
        $now = new \DateTime('now', $timezone);
        $currentTime = $now->format('H:i:s');
        
        $startTime = $user['quiet_hours_start'];
        $endTime = $user['quiet_hours_end'];

        // Gestisci le ore di silenzio notturne (es. 22:00 - 08:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        } else {
            return $currentTime >= $startTime && $currentTime <= $endTime;
        }
    }

    /**
     * Ottieni il prossimo orario consentito per inviare email dopo le ore di silenzio
     * 
     * @param array $user Array con le informazioni dell'utente
     * @return string Data e ora nel formato Y-m-d H:i:s
     */
    private function getNextAllowedTime($user) {
        $timezone = new \DateTimeZone($user['timezone'] ?? 'UTC');
        $now = new \DateTime('now', $timezone);
        
        $endTime = $user['quiet_hours_end'];
        $nextAllowed = clone $now;
        $nextAllowed->setTime(
            (int)substr($endTime, 0, 2),
            (int)substr($endTime, 3, 2),
            0
        );

        // Se l'ora di fine è domani (ore di silenzio notturne)
        if ($user['quiet_hours_start'] > $user['quiet_hours_end'] && $now->format('H:i:s') >= $user['quiet_hours_start']) {
            $nextAllowed->add(new \DateInterval('P1D'));
        }

        return $nextAllowed->format('Y-m-d H:i:s');
    }

    /**
     * Aggiorna lo stato di un'email nella coda
     * Può aggiornare anche il messaggio di errore e la data di invio
     * 
     * @param int $emailId ID dell'email da aggiornare
     * @param string $status Nuovo stato (pending, processing, sent, failed)
     * @param string|null $errorMessage Messaggio di errore (se presente)
     * @param string|null $sentAt Data e ora di invio (se presente)
     */
    private function updateEmailStatus($emailId, $status, $errorMessage = null, $sentAt = null) {
        $stmt = $this->db->prepare("
            UPDATE email_queue 
            SET status = ?, error_message = ?, sent_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $errorMessage, $sentAt, $emailId]);
    }

    /**
     * Incrementa il contatore dei tentativi di invio per un'email
     * Utile per gestire i retry in caso di invio fallito
     * 
     * @param int $emailId ID dell'email da aggiornare
     */
    private function incrementEmailAttempts($emailId) {
        $stmt = $this->db->prepare("
            UPDATE email_queue 
            SET attempts = attempts + 1 
            WHERE id = ?
        ");
        $stmt->execute([$emailId]);
    }

    /**
     * Registra la consegna dell'email per scopi analitici
     * Salva nel log le informazioni rilevanti sull'invio
     * 
     * @param array $email Array con i dati dell'email
     * @param string $status Stato della consegna (sent, failed)
     * @param string|null $errorMessage Messaggio di errore (se presente)
     */
    private function logEmailDelivery($email, $status, $errorMessage = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (user_id, notification_id, type, message, delivery_method, status, error_message)
                VALUES (?, ?, 'email', ?, 'email', ?, ?)
            ");
            $stmt->execute([
                $email['user_id'],
                $email['notification_id'] ?? null,
                $email['subject'],
                $status,
                $errorMessage
            ]);
        } catch (Exception $e) {
            error_log("Error logging email delivery: " . $e->getMessage());
        }
    }

    /**
     * Ottieni statistiche sulla coda delle email
     * Restituisce il conteggio delle email per stato e priorità
     * 
     * @return array Statistiche sulla coda
     */
    public function getQueueStatistics() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    status,
                    priority,
                    COUNT(*) as count,
                    MIN(created_at) as oldest,
                    MAX(created_at) as newest
                FROM email_queue 
                GROUP BY status, priority
                ORDER BY status, priority
            ");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting queue statistics: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Email Template Engine for rendering dynamic content
 */
class EmailTemplateEngine {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get template by name
     */
    public function getTemplate($name) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notification_templates 
                WHERE name = ? AND is_active = TRUE
            ");
            $stmt->execute([$name]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting template: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Render template with variables
     */
    public function render($template, $variables = []) {
        $content = $template;
        
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }
        
        // Remove any unreplaced variables
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
        
        return $content;
    }

    /**
     * Create or update template
     */
    public function saveTemplate($name, $type, $subject, $bodyText, $bodyHtml, $variables = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_templates (name, type, subject, body_text, body_html, variables)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                type = VALUES(type),
                subject = VALUES(subject),
                body_text = VALUES(body_text),
                body_html = VALUES(body_html),
                variables = VALUES(variables),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            return $stmt->execute([
                $name,
                $type,
                $subject,
                $bodyText,
                $bodyHtml,
                json_encode($variables)
            ]);
        } catch (Exception $e) {
            error_log("Error saving template: " . $e->getMessage());
            return false;
        }
    }
}
