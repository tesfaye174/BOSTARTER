<?php

class Candidatura {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea una nuova candidatura
     */
    public function create($utenteId, $profiloId, $motivazione) {
        try {
            // Verifica che il profilo esista e sia per progetto software
            $stmt = $this->db->prepare(
                SELECT pr.*, p.tipo, p.stato
                FROM profili_richiesti pr
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE pr.id = ? AND pr.is_active = TRUE
            );
            $stmt->execute([$profiloId]);
            $profilo = $stmt->fetch();
            
            if (!$profilo) {
                return ['success' => false, 'error' => 'Profilo non trovato o non attivo'];
            }
            
            if ($profilo['tipo'] !== 'software') {
                return ['success' => false, 'error' => 'Candidature solo per progetti software'];
            }
            
            if ($profilo['stato'] !== 'aperto') {
                return ['success' => false, 'error' => 'Progetto non aperto alle candidature'];
            }
            
            // Verifica che non ci sia già una candidatura
            $stmt = $this->db->prepare("SELECT id FROM candidature WHERE utente_id = ? AND profilo_id = ?");
            $stmt->execute([$utenteId, $profiloId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Candidatura già inviata per questo profilo'];
            }
            
            // Usa stored procedure per verifica skill e inserimento
            $stmt = $this->db->prepare("CALL sp_candidati_profilo(?, ?, ?)");
            $stmt->execute([$utenteId, $profiloId, $motivazione]);
            
            return ['success' => true, 'message' => 'Candidatura inviata con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera candidature per profilo
     */
    public function getByProfilo($profiloId, $creatoreId = null) {
        try {
            $sql = 
                SELECT 
                    c.*,
                    u.nickname,
                    u.nome,
                    u.cognome,
                    u.email,
                    pr.nome as profilo_nome,
                    p.nome as progetto_nome
                FROM candidature c
                JOIN utenti u ON c.utente_id = u.id
                JOIN profili_richiesti pr ON c.profilo_id = pr.id
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE c.profilo_id = ?
            ;
            
            $params = [$profiloId];
            
            // Se specificato creatore, filtra per progetto
            if ($creatoreId) {
                $sql .= " AND p.creatore_id = ?";
                $params[] = $creatoreId;
            }
            
            $sql .= " ORDER BY c.data_candidatura DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera candidature di un utente
     */
    public function getByUtente($utenteId) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    c.*,
                    pr.nome as profilo_nome,
                    p.nome as progetto_nome,
                    p.tipo as progetto_tipo,
                    p.stato as progetto_stato
                FROM candidature c
                JOIN profili_richiesti pr ON c.profilo_id = pr.id
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE c.utente_id = ?
                ORDER BY c.data_candidatura DESC
            );
            $stmt->execute([$utenteId]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Aggiorna stato candidatura
     */
    public function updateStatus($candidaturaId, $stato, $valutatoreId) {
        try {
            if (!in_array($stato, ['accettata', 'rifiutata', 'in_valutazione'])) {
                return ['success' => false, 'error' => 'Stato non valido'];
            }
            
            // Verifica permessi
            $stmt = $this->db->prepare(
                SELECT c.*, p.creatore_id
                FROM candidature c
                JOIN profili_richiesti pr ON c.profilo_id = pr.id
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE c.id = ?
            );
            $stmt->execute([$candidaturaId]);
            $candidatura = $stmt->fetch();
            
            if (!$candidatura) {
                return ['success' => false, 'error' => 'Candidatura non trovata'];
            }
            
            // Aggiorna stato
            $stmt = $this->db->prepare(
                UPDATE candidature 
                SET stato = ?, data_valutazione = NOW(), valutatore_id = ?
                WHERE id = ?
            );
            $stmt->execute([$stato, $valutatoreId, $candidaturaId]);
            
            // Se accettata, aggiorna posizioni occupate
            if ($stato === 'accettata') {
                $stmt = $this->db->prepare(
                    UPDATE profili_richiesti 
                    SET posizioni_occupate = posizioni_occupate + 1
                    WHERE id = ?
                );
                $stmt->execute([$candidatura['profilo_id']]);
            }
            
            return ['success' => true, 'stato' => $stato];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cancella candidatura
     */
    public function delete($candidaturaId, $utenteId, $isAdmin = false) {
        try {
            // Verifica permessi
            $stmt = $this->db->prepare("SELECT utente_id FROM candidature WHERE id = ?");
            $stmt->execute([$candidaturaId]);
            $candidatura = $stmt->fetch();
            
            if (!$candidatura) {
                return ['success' => false, 'error' => 'Candidatura non trovata'];
            }
            
            if ($candidatura['utente_id'] != $utenteId && !$isAdmin) {
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM candidature WHERE id = ?");
            $stmt->execute([$candidaturaId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verifica se utente può candidarsi a profilo
     */
    public function canApply($utenteId, $profiloId) {
        try {
            // Recupera skill richieste dal profilo
            $stmt = $this->db->prepare(
                SELECT sp.competenza_id, sp.livello_richiesto
                FROM skill_profili sp
                WHERE sp.profilo_id = ?
            );
            $stmt->execute([$profiloId]);
            $skillRichiesti = $stmt->fetchAll();
            
            if (empty($skillRichiesti)) {
                return ['success' => false, 'error' => 'Profilo senza skill richieste'];
            }
            
            // Verifica skill utente
            foreach ($skillRichiesti as $skill) {
                $stmt = $this->db->prepare(
                    SELECT livello FROM skill_utente 
                    WHERE utente_id = ? AND competenza_id = ?
                );
                $stmt->execute([$utenteId, $skill['competenza_id']]);
                $skillUtente = $stmt->fetch();
                
                if (!$skillUtente || $skillUtente['livello'] < $skill['livello_richiesto']) {
                    return [
                        'success' => false, 
                        'error' => 'Skill insufficienti per questo profilo'
                    ];
                }
            }
            
            return ['success' => true, 'can_apply' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche candidature per progetto
     */
    public function getStatsByProgetto($progettoId) {
        try {
            $stmt = $this->db->prepare(
                SELECT 
                    pr.nome as profilo_nome,
                    COUNT(c.id) as totale_candidature,
                    COUNT(CASE WHEN c.stato = 'accettata' THEN 1 END) as accettate,
                    COUNT(CASE WHEN c.stato = 'rifiutata' THEN 1 END) as rifiutate,
                    COUNT(CASE WHEN c.stato = 'in_valutazione' THEN 1 END) as in_valutazione,
                    pr.numero_posizioni,
                    pr.posizioni_occupate
                FROM profili_richiesti pr
                LEFT JOIN candidature c ON pr.id = c.profilo_id
                WHERE pr.progetto_id = ?
                GROUP BY pr.id, pr.nome, pr.numero_posizioni, pr.posizioni_occupate
            );
            $stmt->execute([$progettoId]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>