<?php
/**
 * BOSTARTER Database Migration System
 * Esegue le migrazioni del database
 */

require_once __DIR__ . '/config/database.php';

class MigrationManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runMigrations() {
        echo "🚀 Avvio migrazioni database BOSTARTER...\n";
        
        try {
            // Verifica se il database esiste
            $this->createDatabaseIfNotExists();
            
            // Esegue lo schema completo
            $this->executeSchemaFile();
            
            // Verifica integrità
            $this->verifySchema();
            
            echo "✅ Migrazioni completate con successo!\n";
            
        } catch (Exception $e) {
            echo "❌ Errore durante le migrazioni: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function createDatabaseIfNotExists() {
        $dbName = $_ENV['DB_NAME'] ?? 'bostarter';
        
        // Connessione temporanea senza database specificato
        $tempDb = new PDO(
            "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . ";charset=utf8mb4",
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASS'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $tempDb->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "📝 Database '$dbName' verificato/creato\n";
    }
    
    private function executeSchemaFile() {
        $schemaFile = __DIR__ . '/../database/schema_completo.sql';
        
        if (!file_exists($schemaFile)) {
            throw new Exception("File schema non trovato: $schemaFile");
        }
        
        $sql = file_get_contents($schemaFile);
        
        // Rimuove commenti e dividi in statement
        $statements = $this->parseSQLStatements($sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                $this->db->exec($statement);
                echo "✓ Eseguito: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "❌ Errore SQL: " . $e->getMessage() . "\n";
                    echo "Statement problematico:\n" . $statement . "\n";
                    throw new Exception("Errore SQL: " . $e->getMessage() . "\nStatement: " . $statement);
                }
            }
        }
    }
    
    private function parseSQLStatements($sql) {
        // Rimuove commenti
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Gestisce DELIMITER - rimuove completamente le righe DELIMITER
        $sql = preg_replace('/DELIMITER\s+\/\/\s*\n?/i', '', $sql);
        $sql = preg_replace('/DELIMITER\s+;\s*\n?/i', '', $sql);
        
        // Divide gli statement
        $statements = [];
        $currentStatement = '';
        $inProcedure = false;
        
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) continue;
            
            // Controlla se siamo in una procedura/trigger/funzione
            if (preg_match('/^(CREATE\s+(TRIGGER|PROCEDURE|FUNCTION|EVENT))/i', $line)) {
                $inProcedure = true;
                $currentStatement = $line . "\n";
                continue;
            }
            
            if ($inProcedure) {
                $currentStatement .= $line . "\n";
                // Cerca la fine della procedura
                if (preg_match('/^END\s*\/\/\s*$/i', $line)) {
                    // Rimuove il // dalla fine
                    $currentStatement = preg_replace('/\/\/\s*$/m', '', $currentStatement);
                    $statements[] = trim($currentStatement);
                    $currentStatement = '';
                    $inProcedure = false;
                }
            } else {
                $currentStatement .= $line . "\n";
                
                if (substr($line, -1) === ';') {
                    $statements[] = trim($currentStatement);
                    $currentStatement = '';
                }
            }
        }
        
        if (!empty($currentStatement)) {
            $statements[] = trim($currentStatement);
        }
        
        return $statements;
    }
    
    private function verifySchema() {
        $requiredTables = [
            'utenti', 'competenze', 'skill_utente', 'progetti', 
            'foto_progetti', 'rewards', 'componenti_hardware', 
            'profili_richiesti', 'skill_profili', 'candidature',
            'finanziamenti', 'commenti', 'risposte_commenti'
        ];
        
        foreach ($requiredTables as $table) {
            $result = $this->db->query("SHOW TABLES LIKE '$table'");
            
            if ($result->rowCount() === 0) {
                throw new Exception("Tabella mancante: $table");
            }
        }
        
        echo "🔍 Verifica schema completata\n";
    }
    
    public function seedDatabase() {
        echo "🌱 Popolamento database con dati di test...\n";
        
        try {
            // Utenti di test
            $this->seedUsers();
            
            // Progetti di test
            $this->seedProjects();
            
            echo "✅ Database popolato con successo!\n";
            
        } catch (Exception $e) {
            echo "❌ Errore durante il popolamento: " . $e->getMessage() . "\n";
        }
    }
    
    private function seedUsers() {
        $users = [
            [
                'email' => 'creator@test.com',
                'nickname' => 'TestCreator',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'nome' => 'Mario',
                'cognome' => 'Rossi',
                'anno_nascita' => 1990,
                'luogo_nascita' => 'Milano',
                'tipo_utente' => 'creatore',
                'affidabilita' => 4.5
            ],
            [
                'email' => 'user@test.com',
                'nickname' => 'TestUser',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'nome' => 'Luigi',
                'cognome' => 'Verdi',
                'anno_nascita' => 1985,
                'luogo_nascita' => 'Roma',
                'tipo_utente' => 'normale'
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO utenti (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, affidabilita)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($users as $user) {
            $stmt->execute([
                $user['email'], $user['nickname'], $user['password'],
                $user['nome'], $user['cognome'], $user['anno_nascita'],
                $user['luogo_nascita'], $user['tipo_utente'], $user['affidabilita'] ?? 0
            ]);
        }
        
        echo "👥 Utenti di test creati\n";
    }
    
    private function seedProjects() {
        // Trova un creatore
        $stmt = $this->db->prepare("SELECT id FROM utenti WHERE tipo_utente = 'creatore' LIMIT 1");
        $stmt->execute();
        $creatore = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$creatore) {
            echo "⚠️  Nessun creatore trovato, salto la creazione di progetti di test\n";
            return;
        }
        
        $projects = [
            [
                'nome' => 'Smartwatch Innovativo',
                'descrizione' => 'Un rivoluzionario smartwatch con funzionalità avanzate per il fitness e la salute.',
                'budget_richiesto' => 50000.00,
                'data_limite' => date('Y-m-d', strtotime('+60 days')),
                'tipo' => 'hardware',
                'creatore_id' => $creatore['id']
            ],
            [
                'nome' => 'App Mobile per Educazione',
                'descrizione' => 'Applicazione mobile per l\'apprendimento interattivo dedicata agli studenti.',
                'budget_richiesto' => 30000.00,
                'data_limite' => date('Y-m-d', strtotime('+90 days')),
                'tipo' => 'software',
                'creatore_id' => $creatore['id']
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO progetti (nome, descrizione, budget_richiesto, data_limite, tipo, creatore_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($projects as $project) {
            $stmt->execute([
                $project['nome'], $project['descrizione'], $project['budget_richiesto'],
                $project['data_limite'], $project['tipo'], $project['creatore_id']
            ]);
        }
        
        echo "📊 Progetti di test creati\n";
    }
}

// Carica configurazione ambiente
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Esegui migrazioni
$migrationManager = new MigrationManager();

if (isset($argv[1]) && $argv[1] === 'seed') {
    $migrationManager->runMigrations();
    $migrationManager->seedDatabase();
} else {
    $migrationManager->runMigrations();
}
