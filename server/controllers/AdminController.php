<?php
namespace Controllers;

class AdminController {
    private $db;
    private $auth;

    public function __construct($db, $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function checkAdminAccess() {
        $user = $this->auth->getCurrentUser();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Accesso non autorizzato']);
            exit;
        }
    }

    public function getStats() {
        $this->checkAdminAccess();

        try {
            $stats = [
                'totalUsers' => $this->db->query("SELECT COUNT(*) as count FROM users")[0]['count'],
                'activeProjects' => $this->db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'active'")[0]['count'],
                'totalFunding' => $this->db->query("SELECT COALESCE(SUM(total_funding), 0) as total FROM projects")[0]['total'],
                'completedProjects' => $this->db->query("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'")[0]['count']
            ];

            echo json_encode($stats);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nel recupero delle statistiche']);
        }
    }

    public function getUsers() {
        $this->checkAdminAccess();

        try {
            $users = $this->db->query("SELECT id, nickname, email, role, status FROM users");
            echo json_encode($users);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nel recupero degli utenti']);
        }
    }

    public function updateUser($userId) {
        $this->checkAdminAccess();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati non validi']);
            return;
        }

        try {
            $this->db->query(
                "UPDATE users SET nickname = ?, email = ?, role = ?, status = ? WHERE id = ?",
                [$data['nickname'], $data['email'], $data['role'], $data['status'], $userId]
            );
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nell\'aggiornamento dell\'utente']);
        }
    }

    public function createUser() {
        $this->checkAdminAccess();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati non validi']);
            return;
        }

        try {
            $this->db->query(
                "INSERT INTO users (nickname, email, role, status) VALUES (?, ?, ?, ?)",
                [$data['nickname'], $data['email'], $data['role'], $data['status']]
            );
            echo json_encode(['success' => true, 'id' => $this->db->lastInsertId()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nella creazione dell\'utente']);
        }
    }

    public function deleteUser($userId) {
        $this->checkAdminAccess();

        try {
            $this->db->query("DELETE FROM users WHERE id = ?", [$userId]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nell\'eliminazione dell\'utente']);
        }
    }

    public function getProjects() {
        $this->checkAdminAccess();

        try {
            $projects = $this->db->query(
                "SELECT p.*, u.nickname as creator_nickname 
                FROM projects p 
                JOIN users u ON p.creator_id = u.id"
            );
            echo json_encode($projects);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nel recupero dei progetti']);
        }
    }

    public function updateProject($projectId) {
        $this->checkAdminAccess();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati non validi']);
            return;
        }

        try {
            $this->db->query(
                "UPDATE projects 
                SET name = ?, description = ?, budget = ?, creator_id = ?, status = ?, featured = ? 
                WHERE id = ?",
                [
                    $data['name'],
                    $data['description'],
                    $data['budget'],
                    $data['creator_id'],
                    $data['status'],
                    $data['featured'] ? 1 : 0,
                    $projectId
                ]
            );
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nell\'aggiornamento del progetto']);
        }
    }

    public function createProject() {
        $this->checkAdminAccess();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati non validi']);
            return;
        }

        try {
            $this->db->query(
                "INSERT INTO projects (name, description, budget, creator_id, status, featured) 
                VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['description'],
                    $data['budget'],
                    $data['creator_id'],
                    $data['status'],
                    $data['featured'] ? 1 : 0
                ]
            );
            echo json_encode(['success' => true, 'id' => $this->db->lastInsertId()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nella creazione del progetto']);
        }
    }

    public function deleteProject($projectId) {
        $this->checkAdminAccess();

        try {
            $this->db->query("DELETE FROM projects WHERE id = ?", [$projectId]);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nell\'eliminazione del progetto']);
        }
    }

    public function updateFeaturedStatus($projectId) {
        $this->checkAdminAccess();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['featured'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dati non validi']);
            return;
        }

        try {
            $this->db->query(
                "UPDATE projects SET featured = ? WHERE id = ?",
                [$data['featured'] ? 1 : 0, $projectId]
            );
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore nell\'aggiornamento dello stato in evidenza']);
        }
    }
}