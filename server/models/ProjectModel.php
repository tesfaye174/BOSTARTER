<?php
namespace Models;

use PDO;
use PDOException;
use RuntimeException;
use MongoDB\Client;

class ProjectModel {
    private $db;
    private $mongoClient;
    private $mongoCollection;

    public function __construct() {
        $this->db = require_once __DIR__ . '/../../config/database.php';
        
        // Initialize MongoDB connection using MongoDBManager
        $mongoManager = \Config\MongoDBManager::getInstance();
        $this->mongoClient = $mongoManager->getClient();
        $this->mongoCollection = $mongoManager->getCollection('project_events');
    }

    public function createProject(array $data): array {
        try {
            $this->db->beginTransaction();

            // Validazione dati
            if (empty($data['nome']) || empty($data['descrizione']) || 
                empty($data['budget']) || empty($data['data_limite']) || 
                empty($data['email_creatore']) || empty($data['tipo'])) {
                throw new RuntimeException('Dati progetto incompleti');
            }

            // Inserimento progetto
            $stmt = $this->db->prepare('CALL InsertProject(?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $data['nome'],
                $data['descrizione'],
                $data['budget'],
                $data['data_limite'],
                $data['email_creatore'],
                $data['tipo']
            ]);

            // Inserimento foto
            if (!empty($data['foto'])) {
                foreach ($data['foto'] as $foto) {
                    $stmt = $this->db->prepare('INSERT INTO FotoProgetto (nome_progetto, url_foto) VALUES (?, ?)');
                    $stmt->execute([$data['nome'], $foto]);
                }
            }

            // Inserimento componenti per progetti hardware
            if ($data['tipo'] === 'hardware' && !empty($data['componenti'])) {
                foreach ($data['componenti'] as $componente) {
                    $stmt = $this->db->prepare('INSERT INTO Componente (nome, descrizione, prezzo, quantita, progetto_nome) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $componente['nome'],
                        $componente['descrizione'],
                        $componente['prezzo'],
                        $componente['quantita'],
                        $data['nome']
                    ]);
                }
            }

            // Inserimento profili per progetti software
            if ($data['tipo'] === 'software' && !empty($data['profili'])) {
                foreach ($data['profili'] as $profilo) {
                    $stmt = $this->db->prepare('INSERT INTO ProfiloRichiesto (nome, progetto_nome) VALUES (?, ?)');
                    $stmt->execute([$profilo['nome'], $data['nome']]);
                    $profiloId = $this->db->lastInsertId();

                    // Inserimento skill richieste per il profilo
                    foreach ($profilo['skills'] as $skill) {
                        $stmt = $this->db->prepare('INSERT INTO SkillProfilo (profilo_id, competenza_nome, livello) VALUES (?, ?, ?)');
                        $stmt->execute([
                            $profiloId,
                            $skill['competenza'],
                            $skill['livello']
                        ]);
                    }
                }
            }

            // Log evento
            $this->logProjectEvent('project_created', $data['nome'], [
                'tipo' => $data['tipo'],
                'creatore' => $data['email_creatore']
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Progetto creato con successo'];

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new RuntimeException('Errore durante la creazione del progetto: ' . $e->getMessage());
        }
    }

    public function addFunding(array $data): array {
        try {
            // Validazione dati
            if (empty($data['email_utente']) || empty($data['progetto_nome']) || 
                empty($data['importo']) || empty($data['reward_codice'])) {
                throw new RuntimeException('Dati finanziamento incompleti');
            }

            // Inserimento finanziamento
            $stmt = $this->db->prepare('CALL InsertFunding(?, ?, ?, ?)');
            $stmt->execute([
                $data['email_utente'],
                $data['progetto_nome'],
                $data['importo'],
                $data['reward_codice']
            ]);

            // Log evento
            $this->logProjectEvent('funding_added', $data['progetto_nome'], [
                'utente' => $data['email_utente'],
                'importo' => $data['importo']
            ]);

            return ['success' => true, 'message' => 'Finanziamento aggiunto con successo'];

        } catch (PDOException $e) {
            throw new RuntimeException('Errore durante l\'aggiunta del finanziamento: ' . $e->getMessage());
        }
    }

    public function getProjectDetails(string $nome): array {
        try {
            // Recupero dettagli progetto
            $stmt = $this->db->prepare(
                'SELECT p.*, u.nickname as creatore_nickname, 
                        (SELECT SUM(importo) FROM Finanziamento WHERE progetto_nome = p.nome) as totale_finanziato
                 FROM Progetto p
                 JOIN Utente u ON p.email_creatore = u.email
                 WHERE p.nome = ?'
            );
            $stmt->execute([$nome]);
            $progetto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$progetto) {
                throw new RuntimeException('Progetto non trovato');
            }

            // Recupero foto
            $stmt = $this->db->prepare('SELECT url_foto FROM FotoProgetto WHERE nome_progetto = ?');
            $stmt->execute([$nome]);
            $progetto['foto'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Recupero rewards
            $stmt = $this->db->prepare('SELECT * FROM Reward WHERE progetto_nome = ?');
            $stmt->execute([$nome]);
            $progetto['rewards'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Recupero dettagli specifici per tipo progetto
            if ($progetto['tipo'] === 'hardware') {
                $stmt = $this->db->prepare('SELECT * FROM Componente WHERE progetto_nome = ?');
                $stmt->execute([$nome]);
                $progetto['componenti'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->db->prepare(
                    'SELECT pr.*, sp.competenza_nome, sp.livello
                     FROM ProfiloRichiesto pr
                     LEFT JOIN SkillProfilo sp ON pr.id = sp.profilo_id
                     WHERE pr.progetto_nome = ?'
                );
                $stmt->execute([$nome]);
                $profili = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Organizza i profili e le loro skill
                $progettoSkills = [];
                foreach ($profili as $profilo) {
                    if (!isset($progettoSkills[$profilo['id']])) {
                        $progettoSkills[$profilo['id']] = [
                            'nome' => $profilo['nome'],
                            'skills' => []
                        ];
                    }
                    if ($profilo['competenza_nome']) {
                        $progettoSkills[$profilo['id']]['skills'][] = [
                            'competenza' => $profilo['competenza_nome'],
                            'livello' => $profilo['livello']
                        ];
                    }
                }
                $progetto['profili'] = array_values($progettoSkills);
            }

            return $progetto;

        } catch (PDOException $e) {
            throw new RuntimeException('Errore durante il recupero dei dettagli del progetto: ' . $e->getMessage());
        }
    }

    private function logProjectEvent(string $event, string $projectName, array $data = []): void {
        try {
            $this->mongoCollection->insertOne([
                'event' => $event,
                'project_name' => $projectName,
                'data' => $data,
                'timestamp' => new \MongoDB\BSON\UTCDateTime()
            ]);
        } catch (\Exception $e) {
            error_log("MongoDB logging error: {$e->getMessage()}");
        }
    }
}