<?php
namespace Server;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private static $instance = null;
    private $mailer;
    private $fromEmail = 'noreply@bostarter.com';
    private $fromName = 'BOSTARTER';

    private function __construct() {
        $this->initializeMailer();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeMailer(): void {
        $this->mailer = new PHPMailer(true);
        
        // Configurazione del server SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $this->mailer->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $_ENV['SMTP_PORT'] ?? 587;
        $this->mailer->CharSet = 'UTF-8';

        // Impostazioni mittente
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
    }

    /**
     * Invia l'email di attivazione account
     * @param string $to Email del destinatario
     * @param string $name Nome del destinatario
     * @param string $activationUrl URL di attivazione
     * @return bool True se l'invio è riuscito
     */
    public function sendActivationEmail(string $to, string $name, string $activationUrl): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to, $name);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = 'Attiva il tuo account BOSTARTER';
            
            // Template HTML dell'email
            $body = <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .button { 
                        display: inline-block; 
                        padding: 12px 24px; 
                        background-color: #007bff; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 4px; 
                        margin: 20px 0; 
                    }
                    .footer { font-size: 12px; color: #666; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Benvenuto su BOSTARTER!</h2>
                    <p>Ciao {$name},</p>
                    <p>Grazie per esserti registrato su BOSTARTER. Per completare la registrazione e attivare il tuo account, clicca sul pulsante qui sotto:</p>
                    
                    <a href="{$activationUrl}" class="button">Attiva il tuo account</a>
                    
                    <p>Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
                    <p>{$activationUrl}</p>
                    
                    <p>Il link scadrà tra 24 ore per motivi di sicurezza.</p>
                    
                    <div class="footer">
                        <p>Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.</p>
                        <p>&copy; 2024 BOSTARTER. Tutti i diritti riservati.</p>
                    </div>
                </div>
            </body>
            </html>
            HTML;

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Errore nell'invio dell'email di attivazione: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invia un'email di recupero password
     * @param string $to Email del destinatario
     * @param string $name Nome del destinatario
     * @param string $resetUrl URL per il reset della password
     * @return bool True se l'invio è riuscito
     */
    public function sendPasswordResetEmail(string $to, string $name, string $resetUrl): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to, $name);
            $this->mailer->isHTML(true);
            
            $this->mailer->Subject = 'Reset Password BOSTARTER';
            
            // Template HTML dell'email
            $body = <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .button { 
                        display: inline-block; 
                        padding: 12px 24px; 
                        background-color: #007bff; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 4px; 
                        margin: 20px 0; 
                    }
                    .warning { color: #dc3545; }
                    .footer { font-size: 12px; color: #666; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Reset Password BOSTARTER</h2>
                    <p>Ciao {$name},</p>
                    <p>Abbiamo ricevuto una richiesta di reset della password per il tuo account. Per procedere, clicca sul pulsante qui sotto:</p>
                    
                    <a href="{$resetUrl}" class="button">Reset Password</a>
                    
                    <p>Se il pulsante non funziona, copia e incolla questo link nel tuo browser:</p>
                    <p>{$resetUrl}</p>
                    
                    <p class="warning">Il link scadrà tra 1 ora per motivi di sicurezza.</p>
                    <p>Se non hai richiesto il reset della password, ignora questa email.</p>
                    
                    <div class="footer">
                        <p>Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.</p>
                        <p>&copy; 2024 BOSTARTER. Tutti i diritti riservati.</p>
                    </div>
                </div>
            </body>
            </html>
            HTML;

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Errore nell'invio dell'email di reset password: " . $e->getMessage());
            return false;
        }
    }
} 