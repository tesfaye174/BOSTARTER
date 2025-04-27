<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\SkillModel;

class SkillController {
    private $skillModel;

    public function __construct() {
        $this->skillModel = new SkillModel();
    }

    /**
     * Gestisce la richiesta GET /api/skills
     * Restituisce tutte le skills.
     */
    public function getAllSkills() {
        try {
            $skills = $this->skillModel->getAll();
            http_response_code(200);
            echo json_encode(['success' => true, 'skills' => $skills]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle skills: ' . $e->getMessage()]);
        }
    }

    /**
     * Gestisce la richiesta POST /api/skills
     * Crea una nuova skill (potrebbe richiedere privilegi di amministratore).
     */
    public function createSkill() {
        // Verifica autenticazione e autorizzazione (es. solo admin)
        // if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        //     http_response_code(403);
        //     echo json_encode(['success' => false, 'message' => 'Accesso negato']);
        //     return;
        // }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome della skill mancante']);
            return;
        }

        try {
            // Controlla se la skill esiste già
            $existingSkill = $this->skillModel->findByName($data['name']);
            if ($existingSkill) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Skill già esistente']);
                return;
            }

            $skillId = $this->skillModel->create($data['name']);

            if ($skillId) {
                http_response_code(201); // Created
                echo json_encode(['success' => true, 'message' => 'Skill creata con successo', 'skill_id' => $skillId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore nella creazione della skill']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore del server: ' . $e->getMessage()]);
        }
    }

    // Potresti aggiungere altri metodi per GET /api/skills/{id}, PUT /api/skills/{id}, DELETE /api/skills/{id}
}