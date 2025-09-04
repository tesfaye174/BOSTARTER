<?php

class Commento {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea un nuovo commento
     */
    public function create($utenteId, $progettoId, $testo) {
        try {
            // Verifica che il progetto esista e sia attivo
            $stmt = $this->db->prepare("SELECT id, stato FROM progetti WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$progettoId]);
            $progetto = $stmt->fetch();
            
            if (!$progetto) {
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            // Usa stored procedure per inserimento
            $stmt = $this->db->prepare("CALL sp_inserisci_commento(?, ?, ?)");
            $stmt->execute([$utenteId, $progettoId, $testo]);
            
            return ['success' => true, 'message' => 'Commento inserito con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Crea una risposta a un commento
     */
    public function createReply($creatoreId, $commentoId, $testo) {
        try {
            // Verifica che l'utente sia creatore/admin del progetto
            $stmt = $this->db->prepare(
                SELECT c.progetto_id, p.creatore_id
                FROM commenti c
                JOIN progetti p ON c.progetto_id = p.id
                WHERE c.id = ?
            );
            $stmt->execute([$commentoId]);
            $commento = $stmt->fetch();
            
            if (!$commento) {
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            if ($commento['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Solo il creatore può rispondere ai commenti'];
            }
            
            // Verifica che non ci sia già una risposta
            $stmt = $this->db->prepare("SELECT id FROM risposte_commenti WHERE commento_id = ?");
            $stmt->execute([$commentoId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Risposta già presente per questo commento'];
            }
            
            // Usa stored procedure per inserimento risposta
            $stmt = $this->db->prepare("CALL sp_rispondi_commento(?, ?, ?)");
            $stmt->execute([$creatoreId, $commentoId, $testo]);
            
            return ['success' => true, 'message' => 'Risposta inserita con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera commenti per progetto
     */
    public function getByProgetto($progettoId, $limit = null, $offset = 0) {
        try {
            $sql = 
                SELECT 
                    c.id,
                    c.testo,
                    c.data_commento,
                    c.utente_id,
                    u.nickname,
                    u.nome,
                    u.cognome,
                    u.avatar,
                    r.testo as risposta_testo,
                    r.data_risposta,
                    r.creatore_id as risposta_creatore_id,
                    ur.nickname as risposta_creatore_nickname,
                    ur.nome as risposta_creatore_nome,
                    ur.cognome as risposta_creatore_cognome
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                LEFT JOIN utenti ur ON r.creatore_id = ur.id
                WHERE c.progetto_id = ?
                ORDER BY c.data_commento DESC
            ;
            
            if ($limit) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$progettoId, $limit, $offset]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$progettoId]);
            }
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera commenti di un utente
     */
    public function getByUtente($utenteId, $limit = null) {
        try {
            $sql = 
                SELECT 
                    c.id,
                    c.testo,
                    c.data_commento,
                    p.nome as progetto_nome,
                    p.id as progetto_id,
                    p.tipo as progetto_tipo,
                    r.testo as risposta_testo,
                    r.data_risposta
                FROM commenti c
                JOIN progetti p ON c.progetto_id = p.id
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                WHERE c.utente_id = ?
                ORDER BY c.data_commento DESC
            ;
            
            if ($limit) {
                $sql .= " LIMIT ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$utenteId, $limit]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$utenteId]);
            }
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera singolo commento con risposta
     */
    public function getById($commentoId) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    c.*,
                    u.nickname,
                    u.nome,
                    u.cognome,
                    p.nome as progetto_nome,
                    r.testo as risposta_testo,
                    r.data_risposta,
                    r.creatore_id as risposta_creatore_id,
                    ur.nickname as risposta_creatore_nickname
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                JOIN progetti p ON c.progetto_id = p.id
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                LEFT JOIN utenti ur ON r.creatore_id = ur.id
                WHERE c.id = ?
            );
            $stmt->execute([$commentoId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Modifica commento
     */
    public function update($commentoId, $utenteId, $testo, $isAdmin = false) {
        try {
            // Verifica permessi
            $stmt = $this->db->prepare("SELECT utente_id FROM commenti WHERE id = ?");
            $stmt->execute([$commentoId]);
            $commento = $stmt->fetch();
            
            if (!$commento) {
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            if ($commento['utente_id'] != $utenteId && !$isAdmin) {
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            // Aggiorna commento
            $stmt = $this->db->prepare("UPDATE commenti SET testo = ? WHERE id = ?");
            $stmt->execute([$testo, $commentoId]);
            
            return ['success' => true, 'testo' => $testo];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cancella commento
     */
    public function delete($commentoId, $utenteId, $isAdmin = false) {
        try {
            // Verifica permessi
            $stmt = $this->db->prepare("SELECT utente_id FROM commenti WHERE id = ?");
            $stmt->execute([$commentoId]);
            $commento = $stmt->fetch();
            
            if (!$commento) {
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            if ($commento['utente_id'] != $utenteId && !$isAdmin) {
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            // Cancella commento (le risposte vengono cancellate in cascata)
            $stmt = $this->db->prepare("DELETE FROM commenti WHERE id = ?");
            $stmt->execute([$commentoId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche commenti per progetto
     */
    public function getStatsByProgetto($progettoId) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    COUNT(c.id) as totale_commenti,
                    COUNT(r.id) as totale_risposte,
                    COUNT(DISTINCT c.utente_id) as utenti_commentanti,
                    MAX(c.data_commento) as ultimo_commento,
                    MAX(r.data_risposta) as ultima_risposta
                FROM commenti c
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                WHERE c.progetto_id = ?
            );
            $stmt->execute([$progettoId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Commenti recenti per dashboard
     */
    public function getRecent($limit = 10) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    c.id,
                    c.testo,
                    c.data_commento,
                    u.nickname,
                    p.nome as progetto_nome,
                    p.id as progetto_id
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                JOIN progetti p ON c.progetto_id = p.id
                ORDER BY c.data_commento DESC
                LIMIT ?
            );
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>