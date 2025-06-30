<?php
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/services/MongoLogger.php';
class PasswordMigration {
    private $db;
    private $mongoLogger;
    private $migratedCount = 0;
    private $skippedCount = 0;
    private $errorCount = 0;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mongoLogger = new MongoLogger();
    }
    public function migrate() {
        echo "🔐 AVVIO MIGRAZIONE PASSWORD SICURE\n";
        echo "====================================\n";
        try {
            $stmt = $this->db->prepare("SELECT id, email, password_hash FROM utenti");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Trovati " . count($users) . " utenti da analizzare\n\n";
            foreach ($users as $user) {
                $this->migrateUserPassword($user);
            }
            echo "\n" . str_repeat("=", 50) . "\n";
            echo "MIGRAZIONE COMPLETATA\n";
            echo "Migrate: " . $this->migratedCount . "\n";
            echo "Saltate: " . $this->skippedCount . "\n";
            echo "Errori: " . $this->errorCount . "\n";
            $this->mongoLogger->logEvent('password_migration_completed', [
                'migrated_count' => $this->migratedCount,
                'skipped_count' => $this->skippedCount,
                'error_count' => $this->errorCount,
                'total_users' => count($users),
                'migration_date' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo "ERRORE CRITICO: " . $e->getMessage() . "\n";
            $this->mongoLogger->logEvent('password_migration_failed', [
                'error' => $e->getMessage(),
                'migration_date' => date('Y-m-d H:i:s')
            ]);
        }
    }
    private function migrateUserPassword($user) {
        $userId = $user['id'];
        $email = $user['email'];
        $currentHash = $user['password_hash'];
        if (empty($currentHash)) {
            echo "⚠️  Utente {$email}: password vuota - SALTATO\n";
            $this->skippedCount++;
            return;
        }
        if ($this->isSecureHash($currentHash)) {
            echo "✅ Utente {$email}: già sicuro - SALTATO\n";
            $this->skippedCount++;
            return;
        }
        $hashType = $this->detectHashType($currentHash);
        if ($hashType === 'unknown') {
            echo "❓ Utente {$email}: formato hash sconosciuto - SALTATO\n";
            $this->skippedCount++;
            return;
        }
        $this->resetToTemporaryPassword($userId, $email, $hashType);
    }
    private function isSecureHash($hash) {
        return (
            strpos($hash, '$argon2') === 0 ||
            strpos($hash, '$2y$') === 0 ||
            strpos($hash, '$2a$') === 0 ||
            strpos($hash, '$2x$') === 0
        );
    }
    private function detectHashType($hash) {
        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            return 'md5';
        }
        if (strlen($hash) < 32 && !strpos($hash, '$')) {
            return 'plaintext';
        }
        return 'unknown';
    }
    private function resetToTemporaryPassword($userId, $email, $oldType) {
        try {
            $tempPassword = $this->generateTempPassword();
            $secureHash = password_hash($tempPassword, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
            $stmt = $this->db->prepare("UPDATE utenti SET password_hash = ?, password_reset_required = 1 WHERE id = ?");
            $stmt->execute([$secureHash, $userId]);
            $this->saveTemporaryPassword($userId, $email, $tempPassword, $oldType);
            echo "🔄 Utente {$email}: migrato da {$oldType} a Argon2ID\n";
            $this->migratedCount++;
            $this->mongoLogger->logEvent('password_migrated', [
                'user_id' => $userId,
                'email' => $email,
                'old_type' => $oldType,
                'new_type' => 'argon2id',
                'migration_date' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            echo "❌ Errore utente {$email}: " . $e->getMessage() . "\n";
            $this->errorCount++;
        }
    }
    private function generateTempPassword() {
        $words = ['Secure', 'Access', 'Login', 'Portal', 'System', 'Safe'];
        $password = $words[array_rand($words)] . 
                   $words[array_rand($words)] . 
                   rand(100, 999) . 
                   '!';
        return $password;
    }
    private function saveTemporaryPassword($userId, $email, $tempPassword, $oldType) {
        $logData = [
            'user_id' => $userId,
            'email' => $email,
            'temp_password' => $tempPassword,
            'old_type' => $oldType,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $logFile = __DIR__ . '/temp_passwords_' . date('Y-m-d') . '.json';
        if (file_exists($logFile)) {
            $existingData = json_decode(file_get_contents($logFile), true);
        } else {
            $existingData = [];
        }
        $existingData[] = $logData;
        file_put_contents($logFile, json_encode($existingData, JSON_PRETTY_PRINT));
        echo "   📧 Password temporanea salvata in: {$logFile}\n";
    }
}
if (!defined('DEVELOPMENT_MODE') || DEVELOPMENT_MODE !== true) {
    echo "❌ ERRORE: Questo script può essere eseguito solo in modalità sviluppo\n";
    echo "   Definire DEVELOPMENT_MODE = true nel config.php\n";
    exit(1);
}
echo "⚠️  ATTENZIONE: Questo script modificherà le password nel database.\n";
echo "   Gli utenti con password MD5/plain text riceveranno password temporanee.\n";
echo "   Continuare? (y/N): ";
$handle = fopen("php:
$line = fgets($handle);
fclose($handle);
if (trim(strtolower($line)) !== 'y') {
    echo "Migrazione annullata.\n";
    exit(0);
}
$migration = new PasswordMigration();
$migration->migrate();
