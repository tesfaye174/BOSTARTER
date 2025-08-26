<?php
require_once __DIR__ . '/../config/database.php';

class Candidatura {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $requiredFields = ['utente_id', 'progetto_id', 'messaggio'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['success' => false, 'error' => "Campo $field obbligatorio"];
                }
            }
            
            // Verifica che il progetto esista ed è di tipo software
            $stmt = $this->db->prepare("
                SELECT id, tipo, stato, data_scadenza 
                FROM progetti 
                WHERE id = ?
            ");
            $stmt->execute([$data['progetto_id']]);
            $progetto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$progetto) {
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            if ($progetto['tipo'] !== 'software') {
                return ['success' => false, 'error' => 'Le candidature sono solo per progetti software'];
            }
            
            if ($progetto['stato'] !== 'attivo') {
                return ['success' => false, 'error' => 'Il progetto non è più attivo'];
            }
            
            if ($progetto['data_scadenza'] && strtotime($progetto['data_scadenza']) < time()) {
                return ['success' => false, 'error' => 'Il progetto è scaduto'];
            }
            
            // Verifica che l'utente non abbia già una candidatura
            $stmt = $this->db->prepare("
                SELECT id FROM candidature 
                WHERE utente_id = ? AND progetto_id = ?
            ");
            $stmt->execute([$data['utente_id'], $data['progetto_id']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Candidatura già presente per questo progetto'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO candidature (utente_id, progetto_id, messaggio, data_candidatura, stato) 
                VALUES (?, ?, ?, NOW(), 'in_attesa')
            ");
            $stmt->execute([$data['utente_id'], $data['progetto_id'], $data['messaggio']]);
            
            return [
                'success' => true,
                'candidatura_id' => $this->db->lastInsertId(),
                'message' => 'Candidatura inviata con successo'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'invio candidatura: ' . $e->getMessage()];
        }
    }
    
    public function updateStato($candidaturaId, $creatoreId, $stato, $motivazione = null) {
        try {
            $statiValidi = ['accettata', 'rifiutata'];
            if (!in_array($stato, $statiValidi)) {
                return ['success' => false, 'error' => 'Stato non valido'];
            }
            
            // Verifica che il creatore sia autorizzato
            $stmt = $this->db->prepare("
                SELECT c.id, p.creatore_id 
                FROM candidature c 
                JOIN progetti p ON c.progetto_id = p.id 
                WHERE c.id = ? AND c.stato = 'in_attesa'
            ");
            $stmt->execute([$candidaturaId]);
            $candidatura = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$candidatura) {
                return ['success' => false, 'error' => 'Candidatura non trovata o già processata'];
            }
            
            if ($candidatura['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Solo il creatore del progetto può valutare le candidature'];
            }
            
            $stmt = $this->db->prepare("
                UPDATE candidature 
                SET stato = ?, motivazione = ?, data_valutazione = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$stato, $motivazione, $candidaturaId]);
            
            return ['success' => true, 'message' => 'Candidatura aggiornata con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'aggiornamento candidatura: ' . $e->getMessage()];
        }
    }
    
    public function getByProject($progettoId, $creatoreId = null) {
        try {
            // Verifica autorizzazione se specificato creatore_id
            if ($creatoreId) {
                $stmt = $this->db->prepare("SELECT creatore_id FROM progetti WHERE id = ?");
                $stmt->execute([$progettoId]);
                $progetto = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$progetto || $progetto['creatore_id'] != $creatoreId) {
                    return ['success' => false, 'error' => 'Non autorizzato a visualizzare le candidature'];
                }
            }
            
            $stmt = $this->db->prepare("
                SELECT c.*, u.nickname, u.email,
                       GROUP_CONCAT(DISTINCT comp.nome) as competenze
                FROM candidature c
                JOIN utenti u ON c.utente_id = u.id
                LEFT JOIN skill_utente su ON u.id = su.utente_id
                LEFT JOIN competenze comp ON su.competenza_id = comp.id
                WHERE c.progetto_id = ?
                GROUP BY c.id
                ORDER BY c.data_candidatura DESC
            ");
            $stmt->execute([$progettoId]);
            $candidature = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'candidature' => $candidature];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero candidature: ' . $e->getMessage()];
        }
    }
    
    public function getByUser($utenteId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, p.titolo, p.descrizione_breve
                FROM candidature c
                JOIN progetti p ON c.progetto_id = p.id
                WHERE c.utente_id = ?
                ORDER BY c.data_candidatura DESC
            ");
            $stmt->execute([$utenteId]);
            $candidature = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'candidature' => $candidature];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero candidature utente: ' . $e->getMessage()];
        }
    }
    
    public function delete($candidaturaId, $utenteId) {
        try {
            // Verifica che la candidatura sia dell'utente e in attesa
            $stmt = $this->db->prepare("
                SELECT id FROM candidature 
                WHERE id = ? AND utente_id = ? AND stato = 'in_attesa'
            ");
            $stmt->execute([$candidaturaId, $utenteId]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'error' => 'Candidatura non trovata o non modificabile'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM candidature WHERE id = ?");
            $stmt->execute([$candidaturaId]);
            
            return ['success' => true, 'message' => 'Candidatura ritirata con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nella rimozione candidatura: ' . $e->getMessage()];
        }
    }
}
?>
