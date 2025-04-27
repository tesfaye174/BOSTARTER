<?php

namespace BOSTARTER\Backend\Models;

use BOSTARTER\Backend\Utils\Database;
use PDO;

class ProjectModel {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Trova un progetto per nome.
     *
     * @param string $nome Nome univoco del progetto.
     * @return array|false Dati del progetto o false se non trovato.
     */
    public function findByName(string $nome) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Progetto WHERE nome = :nome");
            $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in findByName (Project): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuovo progetto.
     *
     * @param array $data Dati del progetto [nome, descrizione, budget, data_limite, creatore_email, tipo]
     * @return bool True in caso di successo, false altrimenti.
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO Progetto (nome, descrizione, budget, data_limite, creatore_email, tipo)
                VALUES (:nome, :descrizione, :budget, :data_limite, :creatore_email, :tipo)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':descrizione', $data['descrizione']);
            $stmt->bindParam(':budget', $data['budget']); // PDO gestisce decimali come stringhe
            $stmt->bindParam(':data_limite', $data['data_limite']);
            $stmt->bindParam(':creatore_email', $data['creatore_email']);
            $stmt->bindParam(':tipo', $data['tipo']); // 'hardware' o 'software'
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Errore DB in create (Project): " . $e->getMessage());
            // Potrebbe fallire per chiave duplicata (nome progetto) o foreign key (creatore)
            return false;
        }
    }

    /**
     * Ottiene tutti i progetti (con paginazione opzionale).
     *
     * @param int $limit Numero di progetti per pagina.
     * @param int $offset Offset per la paginazione.
     * @return array Lista di progetti.
     */
    public function getAll(int $limit = 10, int $offset = 0): array {
        $sql = "SELECT p.*, u.nickname AS creatore_nickname,
                       COALESCE(SUM(f.importo), 0) AS totale_finanziato
                FROM Progetto p
                JOIN Utente u ON p.creatore_email = u.email
                LEFT JOIN Finanziamento f ON p.nome = f.progetto_nome
                GROUP BY p.nome
                ORDER BY p.data_inserimento DESC
                LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getAll (Project): " . $e->getMessage());
            return [];
        }
    }

     /**
     * Conta il numero totale di progetti.
     *
     * @return int Numero totale di progetti.
     */
    public function countAll(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM Progetto");
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Errore DB in countAll (Project): " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Aggiunge una foto a un progetto.
     *
     * @param string $progettoNome
     * @param string $urlFoto
     * @return bool
     */
    public function addPhoto(string $progettoNome, string $urlFoto): bool {
        $sql = "INSERT INTO Foto_Progetto (progetto_nome, url_foto) VALUES (:progetto_nome, :url_foto)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->bindParam(':url_foto', $urlFoto);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Errore DB in addPhoto (Project): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene le foto di un progetto.
     *
     * @param string $progettoNome
     * @return array
     */
    public function getPhotos(string $progettoNome): array {
        $sql = "SELECT id, url_foto FROM Foto_Progetto WHERE progetto_nome = :progetto_nome";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getPhotos (Project): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aggiunge una reward a un progetto.
     *
     * @param array $data [codice, progetto_nome, descrizione, url_foto]
     * @return bool
     */
    public function addReward(array $data): bool {
        $sql = "INSERT INTO Reward (codice, progetto_nome, descrizione, url_foto)
                VALUES (:codice, :progetto_nome, :descrizione, :url_foto)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':codice', $data['codice']);
            $stmt->bindParam(':progetto_nome', $data['progetto_nome']);
            $stmt->bindParam(':descrizione', $data['descrizione']);
            $stmt->bindParam(':url_foto', $data['url_foto']); // Può essere NULL
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Errore DB in addReward (Project): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene le rewards di un progetto.
     *
     * @param string $progettoNome
     * @return array
     */
    public function getRewards(string $progettoNome): array {
        $sql = "SELECT codice, descrizione, url_foto FROM Reward WHERE progetto_nome = :progetto_nome";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getRewards (Project): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aggiunge un componente hardware a un progetto.
     *
     * @param array $data [progetto_nome, nome_componente, descrizione, prezzo, quantita]
     * @return bool
     */
    public function addHardwareComponent(array $data): bool {
        $sql = "INSERT INTO Componente_Hardware (progetto_nome, nome_componente, descrizione, prezzo, quantita)
                VALUES (:progetto_nome, :nome_componente, :descrizione, :prezzo, :quantita)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $data['progetto_nome']);
            $stmt->bindParam(':nome_componente', $data['nome_componente']);
            $stmt->bindParam(':descrizione', $data['descrizione']);
            $stmt->bindParam(':prezzo', $data['prezzo']);
            $stmt->bindParam(':quantita', $data['quantita'], PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Errore DB in addHardwareComponent (Project): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene i componenti hardware di un progetto.
     *
     * @param string $progettoNome
     * @return array
     */
    public function getHardwareComponents(string $progettoNome): array {
        $sql = "SELECT nome_componente, descrizione, prezzo, quantita FROM Componente_Hardware WHERE progetto_nome = :progetto_nome";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getHardwareComponents (Project): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aggiunge un profilo software a un progetto.
     *
     * @param string $progettoNome
     * @param string $nomeProfilo
     * @return int|false ID del profilo inserito o false in caso di errore.
     */
    public function addSoftwareProfile(string $progettoNome, string $nomeProfilo) {
        $sql = "INSERT INTO Profilo_Software (progetto_nome, nome_profilo) VALUES (:progetto_nome, :nome_profilo)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->bindParam(':nome_profilo', $nomeProfilo);
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            error_log("Errore DB in addSoftwareProfile (Project): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aggiunge una skill richiesta a un profilo software.
     *
     * @param int $profiloId
     * @param string $competenzaNome
     * @param int $livelloRichiesto
     * @return bool
     */
    public function addProfileSkill(int $profiloId, string $competenzaNome, int $livelloRichiesto): bool {
        $sql = "INSERT INTO Skill_Profilo (profilo_id, competenza_nome, livello_richiesto)
                VALUES (:profilo_id, :competenza_nome, :livello_richiesto)
                ON DUPLICATE KEY UPDATE livello_richiesto = :livello_richiesto";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':profilo_id', $profiloId, PDO::PARAM_INT);
            $stmt->bindParam(':competenza_nome', $competenzaNome);
            $stmt->bindParam(':livello_richiesto', $livelloRichiesto, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Errore DB in addProfileSkill (Project): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene i profili software di un progetto con le relative skill richieste.
     *
     * @param string $progettoNome
     * @return array Struttura nidificata [profilo_id => ['nome_profilo' => ..., 'skills' => [...]]]
     */
    public function getSoftwareProfilesWithSkills(string $progettoNome): array {
        $sql = "SELECT ps.id AS profilo_id, ps.nome_profilo, skp.competenza_nome, skp.livello_richiesto
                FROM Profilo_Software ps
                LEFT JOIN Skill_Profilo skp ON ps.id = skp.profilo_id
                WHERE ps.progetto_nome = :progetto_nome
                ORDER BY ps.id, skp.competenza_nome";
        $profiles = [];
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $row) {
                $profileId = $row['profilo_id'];
                if (!isset($profiles[$profileId])) {
                    $profiles[$profileId] = [
                        'nome_profilo' => $row['nome_profilo'],
                        'skills' => []
                    ];
                }
                if ($row['competenza_nome']) { // Aggiungi skill solo se esiste
                    $profiles[$profileId]['skills'][] = [
                        'competenza' => $row['competenza_nome'],
                        'livello' => $row['livello_richiesto']
                    ];
                }
            }
            return $profiles;
        } catch (\PDOException $e) {
            error_log("Errore DB in getSoftwareProfilesWithSkills (Project): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ottiene i dettagli completi di un progetto (info base, creatore, foto, rewards, finanziamenti, componenti/profili).
     *
     * @param string $nome Nome del progetto.
     * @return array|false Dati completi del progetto o false se non trovato.
     */
    public function getFullProjectDetails(string $nome) {
        $project = $this->findByName($nome);
        if (!$project) {
            return false;
        }

        // Aggiungi informazioni aggiuntive
        $project['foto'] = $this->getPhotos($nome);
        $project['rewards'] = $this->getRewards($nome);
        $project['finanziamenti'] = $this->getFundings($nome); // Metodo da implementare
        $project['totale_finanziato'] = $this->getTotalFunding($nome); // Metodo da implementare

        // Aggiungi dettagli specifici per tipo
        if ($project['tipo'] === 'hardware') {
            $project['componenti'] = $this->getHardwareComponents($nome);
        } elseif ($project['tipo'] === 'software') {
            $project['profili'] = $this->getSoftwareProfilesWithSkills($nome);
        }

        // Aggiungi info creatore
        $userModel = new UserModel(); // Potrebbe essere iniettato via DI
        $creatorInfo = $userModel->findByEmail($project['creatore_email']);
        $project['creatore_nickname'] = $creatorInfo ? $creatorInfo['nickname'] : 'Sconosciuto';

        return $project;
    }

    /**
     * Ottiene tutti i finanziamenti per un progetto.
     *
     * @param string $progettoNome
     * @return array
     */
    public function getFundings(string $progettoNome): array {
        $sql = "SELECT f.id, f.utente_email, u.nickname, f.importo, f.data, f.reward_codice
                FROM Finanziamento f
                JOIN Utente u ON f.utente_email = u.email
                WHERE f.progetto_nome = :progetto_nome
                ORDER BY f.data DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getFundings (Project): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcola il totale finanziato per un progetto.
     *
     * @param string $progettoNome
     * @return float
     */
    public function getTotalFunding(string $progettoNome): float {
        $sql = "SELECT COALESCE(SUM(importo), 0) AS totale FROM Finanziamento WHERE progetto_nome = :progetto_nome";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            return (float) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Errore DB in getTotalFunding (Project): " . $e->getMessage());
            return 0.0;
        }
    }

    // TODO: Aggiungere metodi per aggiornare/eliminare progetti, gestire stati, etc.

}
?>