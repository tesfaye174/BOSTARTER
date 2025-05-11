<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\SkillModel;
use BOSTARTER\Backend\Utils\Auth; // Per la gestione dell'autenticazione
use BOSTARTER\Backend\Utils\Logger; // Per il logging delle operazioni

class SkillController {
    private $skillModel;
    private $logger;

    public function __construct() {
        $this->skillModel = new SkillModel();
        $this->logger = new Logger('SkillController');
    }

    /**
     * Gestisce la richiesta GET /api/skills
     * Restituisce tutte le skills.
     */
    /**
     * @api {get} /api/skills Ottiene tutte le skills
     * @apiName GetAllSkills
     * @apiGroup Skills
     * @apiSuccess {Boolean} success Stato della richiesta
     * @apiSuccess {Array} skills Lista delle skills
     */
    public function getAllSkills() {
        try {
            $this->logger->info('Richiesta di recupero di tutte le skills');
            $skills = $this->skillModel->getAll();
            
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'skills' => $skills,
                'total' => count($skills)
            ]);
            
            $this->logger->info('Skills recuperate con successo', ['count' => count($skills)]);
        } catch (\Exception $e) {
            $this->logger->error('Errore nel recupero delle skills', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Errore nel recupero delle skills: ' . $e->getMessage(),
                'error_code' => 'SKILLS_FETCH_ERROR'
            ]);
        }
    }

    /**
     * Gestisce la richiesta POST /api/skills
     * Crea una nuova skill (potrebbe richiedere privilegi di amministratore).
     */
    /**
     * @api {post} /api/skills Crea una nuova skill
     * @apiName CreateSkill
     * @apiGroup Skills
     * @apiParam {String} name Nome della skill
     * @apiSuccess {Boolean} success Stato della richiesta
     * @apiSuccess {Number} skill_id ID della skill creata
     */
    public function createSkill() {
        // Verifica autenticazione e autorizzazione
        if (!Auth::isAdmin()) {
            $this->logger->warning('Tentativo di creazione skill non autorizzato');
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Accesso negato. Richiesti privilegi di amministratore',
                'error_code' => 'UNAUTHORIZED_ACCESS'
            ]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $this->logger->info('Richiesta di creazione nuova skill', ['data' => $data]);

        // Validazione input
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $this->logger->warning('Tentativo di creazione skill con nome non valido', ['name' => $data['name']]);
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Nome della skill mancante o troppo corto (minimo 2 caratteri)',
                'error_code' => 'INVALID_SKILL_NAME'
            ]);
            return;
        }

        try {
            // Sanitizzazione input
            $skillName = trim($data['name']);
            
            // Controlla se la skill esiste già
            $existingSkill = $this->skillModel->findByName($skillName);
            if ($existingSkill) {
                $this->logger->info('Tentativo di creazione skill duplicata', ['name' => $skillName]);
                http_response_code(409); // Conflict
                echo json_encode([
                    'success' => false, 
                    'message' => 'Skill già esistente',
                    'error_code' => 'DUPLICATE_SKILL'
                ]);
                return;
            }

            $skillId = $this->skillModel->create($skillName);

            if ($skillId) {
                $this->logger->info('Skill creata con successo', ['skill_id' => $skillId, 'name' => $skillName]);
                http_response_code(201); // Created
                echo json_encode([
                    'success' => true, 
                    'message' => 'Skill creata con successo',
                    'skill_id' => $skillId,
                    'skill' => [
                        'id' => $skillId,
                        'name' => $skillName
                    ]
                ]);
            } else {
                $this->logger->error('Errore nella creazione della skill', ['name' => $skillName]);
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Errore nella creazione della skill',
                    'error_code' => 'SKILL_CREATE_ERROR'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore del server durante la creazione della skill', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Errore del server durante la creazione della skill',
                'error_code' => 'SERVER_ERROR'
            ]);
        }
    }

    /**
     * Gestisce la richiesta GET /api/skills/{id}
     * Restituisce una skill specifica.
     * @param array $params Parametri dall'URL (es. ['id' => 123])
     */
    /**
     * @api {get} /api/skills/{id} Ottiene una skill specifica
     * @apiName GetSkill
     * @apiGroup Skills
     * @apiParam {Number} id ID della skill
     * @apiSuccess {Boolean} success Stato della richiesta
     * @apiSuccess {Object} skill Dettagli della skill
     */
    public function getSkill($params) {
        try {
            if (empty($params['id']) || !is_numeric($params['id'])) {
                $this->logger->warning('ID skill non valido', ['id' => $params['id'] ?? null]);
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID skill non valido',
                    'error_code' => 'INVALID_SKILL_ID'
                ]);
                return;
            }

            $skill = $this->skillModel->getById($params['id']);
            
            if (!$skill) {
                $this->logger->info('Skill non trovata', ['id' => $params['id']]);
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Skill non trovata',
                    'error_code' => 'SKILL_NOT_FOUND'
                ]);
                return;
            }

            $this->logger->info('Skill recuperata con successo', ['id' => $params['id']]);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'skill' => $skill
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Errore nel recupero della skill', [
                'id' => $params['id'],
                'error' => $e->getMessage()
            ]);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Errore nel recupero della skill',
                'error_code' => 'SKILL_FETCH_ERROR'
            ]);
        }
    }

    /**
     * Gestisce la richiesta PUT /api/skills/{id}
     * Aggiorna una skill esistente (richiede privilegi di amministratore).
     * @param array $params Parametri dall'URL (es. ['id' => 123])
     */
    /**
     * @api {put} /api/skills/{id} Aggiorna una skill
     * @apiName UpdateSkill
     * @apiGroup Skills
     * @apiParam {Number} id ID della skill
     * @apiParam {String} name Nuovo nome della skill
     * @apiSuccess {Boolean} success Stato della richiesta
     * @apiSuccess {Object} skill Dettagli della skill aggiornata
     */
    public function updateSkill($params) {
        if (!Auth::isAdmin()) {
            $this->logger->warning('Tentativo di aggiornamento skill non autorizzato');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Accesso negato. Richiesti privilegi di amministratore',
                'error_code' => 'UNAUTHORIZED_ACCESS'
            ]);
            return;
        }

        try {
            if (empty($params['id']) || !is_numeric($params['id'])) {
                $this->logger->warning('ID skill non valido', ['id' => $params['id'] ?? null]);
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID skill non valido',
                    'error_code' => 'INVALID_SKILL_ID'
                ]);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
                $this->logger->warning('Nome skill non valido', ['name' => $data['name'] ?? null]);
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Nome della skill mancante o troppo corto (minimo 2 caratteri)',
                    'error_code' => 'INVALID_SKILL_NAME'
                ]);
                return;
            }

            $skillName = trim($data['name']);
            
            // Verifica esistenza skill
            $existingSkill = $this->skillModel->getById($params['id']);
            if (!$existingSkill) {
                $this->logger->info('Skill non trovata per aggiornamento', ['id' => $params['id']]);
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Skill non trovata',
                    'error_code' => 'SKILL_NOT_FOUND'
                ]);
                return;
            }

            // Verifica duplicati (escludendo la skill corrente)
            $duplicateSkill = $this->skillModel->findByName($skillName);
            if ($duplicateSkill && $duplicateSkill['id'] != $params['id']) {
                $this->logger->info('Tentativo di aggiornamento con nome duplicato', ['name' => $skillName]);
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Esiste già una skill con questo nome',
                    'error_code' => 'DUPLICATE_SKILL'
                ]);
                return;
            }

            $updated = $this->skillModel->update($params['id'], $skillName);
            
            if ($updated) {
                $updatedSkill = $this->skillModel->getById($params['id']);
                $this->logger->info('Skill aggiornata con successo', [
                    'id' => $params['id'],
                    'name' => $skillName
                ]);
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Skill aggiornata con successo',
                    'skill' => $updatedSkill
                ]);
            } else {
                $this->logger->error('Errore nell\'aggiornamento della skill', [
                    'id' => $params['id'],
                    'name' => $skillName
                ]);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Errore nell\'aggiornamento della skill',
                    'error_code' => 'SKILL_UPDATE_ERROR'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore del server durante l\'aggiornamento della skill', [
                'id' => $params['id'],
                'error' => $e->getMessage()
            ]);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Errore del server durante l\'aggiornamento della skill',
                'error_code' => 'SERVER_ERROR'
            ]);
        }
    }

    /**
     * Gestisce la richiesta DELETE /api/skills/{id}
     * Elimina una skill (richiede privilegi di amministratore).
     * @param array $params Parametri dall'URL (es. ['id' => 123])
     */
    /**
     * @api {delete} /api/skills/{id} Elimina una skill
     * @apiName DeleteSkill
     * @apiGroup Skills
     * @apiParam {Number} id ID della skill
     * @apiSuccess {Boolean} success Stato della richiesta
     */
    public function deleteSkill($params) {
        if (!Auth::isAdmin()) {
            $this->logger->warning('Tentativo di eliminazione skill non autorizzato');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Accesso negato. Richiesti privilegi di amministratore',
                'error_code' => 'UNAUTHORIZED_ACCESS'
            ]);
            return;
        }

        try {
            if (empty($params['id']) || !is_numeric($params['id'])) {
                $this->logger->warning('ID skill non valido', ['id' => $params['id'] ?? null]);
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID skill non valido',
                    'error_code' => 'INVALID_SKILL_ID'
                ]);
                return;
            }

            // Verifica esistenza skill
            $existingSkill = $this->skillModel->getById($params['id']);
            if (!$existingSkill) {
                $this->logger->info('Skill non trovata per eliminazione', ['id' => $params['id']]);
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Skill non trovata',
                    'error_code' => 'SKILL_NOT_FOUND'
                ]);
                return;
            }

            $deleted = $this->skillModel->delete($params['id']);
            
            if ($deleted) {
                $this->logger->info('Skill eliminata con successo', ['id' => $params['id']]);
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Skill eliminata con successo'
                ]);
            } else {
                $this->logger->error('Errore nell\'eliminazione della skill', ['id' => $params['id']]);
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Errore nell\'eliminazione della skill',
                    'error_code' => 'SKILL_DELETE_ERROR'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Errore del server durante l\'eliminazione della skill', [
                'id' => $params['id'],
                'error' => $e->getMessage()
            ]);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Errore del server durante l\'eliminazione della skill',
                'error_code' => 'SERVER_ERROR'
            ]);
        }
    }
}