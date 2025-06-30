<?php
namespace BOSTARTER\Services;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
class EmailNotificationService {
    private $db;
    private $mailer;
    private $config;
    private $templateEngine;
    public function __construct($db) {
        $this->db = $db;
        $this->config = $this->loadEmailConfig();
        $this->initializeMailer();
        $this->templateEngine = new EmailTemplateEngine($db);
    }
    private function loadEmailConfig() {
        return [
            'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',       
            'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,               
            'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',        
            'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',        
            'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls',         
            'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@bostarter.com',  
            'from_name' => $_ENV['FROM_NAME'] ?? 'BOSTARTER',       
            'reply_to' => $_ENV['REPLY_TO'] ?? 'support@bostarter.com' 
        ];
    }
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true); 
        try {
            $this->mailer->isSMTP();                                    
            $this->mailer->Host = $this->config['smtp_host'];           
            $this->mailer->SMTPAuth = true;                             
            $this->mailer->Username = $this->config['smtp_username'];   
            $this->mailer->Password = $this->config['smtp_password'];   
            $this->mailer->SMTPSecure = $this->config['smtp_secure'];   
            $this->mailer->Port = $this->config['smtp_port'];           
            $this->mailer->SMTPDebug = 0;             
            $this->mailer->Debugoutput = 'error_log'; 
            $this->mailer->Timeout = 30;              
            $this->mailer->setFrom(
                $this->config['from_email'], 
                $this->config['from_name'], 
                false  
            );
            $this->mailer->addReplyTo($this->config['reply_to'], $this->config['from_name']);
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
        } catch (Exception $e) {
            error_log("Errore configurazione email SMTP: " . $e->getMessage());
        }
    }
    public function queueEmailNotification($userId, $notificationId, $templateName, $variables = [], $priority = 'normal') {
        try {
            $user = $this->getUserEmailSettings($userId);
            if (!$user || !$user['email_enabled']) {
                return false;
            }
            if ($this->isQuietHours($user)) {
                $scheduledAt = $this->getNextAllowedTime($user);
            } else {
                $scheduledAt = date('Y-m-d H:i:s');
            }
            $template = $this->templateEngine->getTemplate($templateName);
            if (!$template) {
                throw new Exception("Template not found: $templateName");
            }
            $subject = $this->templateEngine->render($template['subject'], $variables);
            $body = $this->templateEngine->render($template['body_html'], $variables);
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
    public function processEmailQueue($batchSize = 50) {
        try {
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
                $this->updateEmailStatus($email['id'], 'processing');
                try {
                    $this->mailer->clearAddresses();
                    $this->mailer->addAddress($email['to_email']);
                    $this->mailer->Subject = $email['subject'];
                    $this->mailer->Body = $email['body'];
                    $this->mailer->isHTML(true);
                    if ($this->mailer->send()) {
                        $this->updateEmailStatus($email['id'], 'sent', null, date('Y-m-d H:i:s'));
                        $this->logEmailDelivery($email, 'sent');
                        $successful++;
                    } else {
                        throw new Exception("Failed to send email");
                    }
                } catch (Exception $e) {
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
    public function sendImmediateEmail($userId, $templateName, $variables = []) {
        try {
            $user = $this->getUserEmailSettings($userId);
            if (!$user || !$user['email_enabled']) {
                return false;
            }
            $template = $this->templateEngine->getTemplate($templateName);
            if (!$template) {
                throw new Exception("Template not found: $templateName");
            }
            $subject = $this->templateEngine->render($template['subject'], $variables);
            $body = $this->templateEngine->render($template['body_html'], $variables);
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
    private function isQuietHours($user) {
        if (!$user['quiet_hours_start'] || !$user['quiet_hours_end']) {
            return false;
        }
        $timezone = new \DateTimeZone($user['timezone'] ?? 'UTC');
        $now = new \DateTime('now', $timezone);
        $currentTime = $now->format('H:i:s');
        $startTime = $user['quiet_hours_start'];
        $endTime = $user['quiet_hours_end'];
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        } else {
            return $currentTime >= $startTime && $currentTime <= $endTime;
        }
    }
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
        if ($user['quiet_hours_start'] > $user['quiet_hours_end'] && $now->format('H:i:s') >= $user['quiet_hours_start']) {
            $nextAllowed->add(new \DateInterval('P1D'));
        }
        return $nextAllowed->format('Y-m-d H:i:s');
    }
    private function updateEmailStatus($emailId, $status, $errorMessage = null, $sentAt = null) {
        $stmt = $this->db->prepare("
            UPDATE email_queue 
            SET status = ?, error_message = ?, sent_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $errorMessage, $sentAt, $emailId]);
    }
    private function incrementEmailAttempts($emailId) {
        $stmt = $this->db->prepare("
            UPDATE email_queue 
            SET attempts = attempts + 1 
            WHERE id = ?
        ");
        $stmt->execute([$emailId]);
    }
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
class EmailTemplateEngine {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
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
    public function render($template, $variables = []) {
        $content = $template;
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
        return $content;
    }
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
