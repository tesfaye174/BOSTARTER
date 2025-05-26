<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class File {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Carica un nuovo file
     */
    public function upload($data) {
        try {
            if (!isset($_FILES['file'])) {
                throw new Exception('Nessun file caricato');
            }
            
            $file = $_FILES['file'];
            
            // Verifica il tipo di file
            $allowedTypes = explode(',', ALLOWED_IMAGE_TYPES);
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Tipo di file non supportato');
            }
            
            // Verifica la dimensione del file
            if ($file['size'] > MAX_FILE_SIZE) {
                throw new Exception('File troppo grande');
            }
            
            // Genera un nome file unico
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $filepath = UPLOAD_DIR . '/' . $filename;
            
            // Sposta il file nella directory di upload
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Errore durante il caricamento del file');
            }
            
            // Inserisce il file nel database
            $stmt = $this->conn->prepare("
                INSERT INTO file (
                    nome_originale, nome_file, tipo, dimensione,
                    percorso, progetto_id, utente_id, data_upload
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $file['name'],
                $filename,
                $file['type'],
                $file['size'],
                $filepath,
                $data['progetto_id'] ?? null,
                $data['utente_id']
            ]);
            
            return [
                'success' => true,
                'file_id' => $this->conn->lastInsertId(),
                'filename' => $filename,
                'message' => 'File caricato con successo'
            ];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ottiene i file di un progetto
     */
    public function getProjectFiles($projectId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT f.*, u.nickname as uploader_nickname
                FROM file f
                JOIN utenti u ON f.utente_id = u.id
                WHERE f.progetto_id = ?
                ORDER BY f.data_upload DESC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottiene i file di un utente
     */
    public function getUserFiles($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT f.*, p.titolo as progetto_titolo
                FROM file f
                LEFT JOIN progetti p ON f.progetto_id = p.id
                WHERE f.utente_id = ?
                ORDER BY f.data_upload DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    /**
     * Elimina un file
     */
    public function delete($fileId, $userId) {
        try {
            // Ottiene le informazioni del file
            $stmt = $this->conn->prepare("
                SELECT *
                FROM file
                WHERE id = ?
            ");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();
            
            if (!$file) {
                throw new Exception('File non trovato');
            }
            
            // Verifica se l'utente è autorizzato
            if ($file['utente_id'] != $userId) {
                throw new Exception('Non autorizzato');
            }
            
            // Elimina il file fisico
            if (file_exists($file['percorso'])) {
                unlink($file['percorso']);
            }
            
            // Elimina il record dal database
            $stmt = $this->conn->prepare("
                DELETE FROM file
                WHERE id = ?
            ");
            $stmt->execute([$fileId]);
            
            return [
                'success' => true,
                'message' => 'File eliminato con successo'
            ];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ottiene le statistiche dei file
     */
    public function getStats($projectId = null) {
        try {
            $where = $projectId ? "WHERE progetto_id = ?" : "";
            $params = $projectId ? [$projectId] : [];
            
            // Ottiene il numero totale di file
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as num_file,
                SUM(dimensione) as dimensione_totale,
                COUNT(DISTINCT utente_id) as num_utenti
                FROM file
                $where
            ");
            $stmt->execute($params);
            $stats = $stmt->fetch();
            
            // Ottiene i file per tipo
            $stmt = $this->conn->prepare("
                SELECT tipo,
                COUNT(*) as num_file,
                SUM(dimensione) as dimensione_totale
                FROM file
                $where
                GROUP BY tipo
                ORDER BY num_file DESC
            ");
            $stmt->execute($params);
            $stats['file_per_tipo'] = $stmt->fetchAll();
            
            // Ottiene i file per mese
            $stmt = $this->conn->prepare("
                SELECT DATE_FORMAT(data_upload, '%Y-%m') as mese,
                COUNT(*) as num_file,
                SUM(dimensione) as dimensione_totale
                FROM file
                $where
                GROUP BY DATE_FORMAT(data_upload, '%Y-%m')
                ORDER BY mese DESC
                LIMIT 12
            ");
            $stmt->execute($params);
            $stats['file_per_mese'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica se un file esiste
     */
    public function exists($fileId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id
                FROM file
                WHERE id = ?
            ");
            $stmt->execute([$fileId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Ottiene il percorso di un file
     */
    public function getPath($fileId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT percorso
                FROM file
                WHERE id = ?
            ");
            $stmt->execute([$fileId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
} 