<?php
class ProjectController {
    private $db;
    private $security;

    public function __construct() {
        global $conn;
        $this->db = $conn;
        require_once __DIR__ . '/../../config/security.php';
        $this->security = new Security();
    }

    public function getProjects() {
        $stmt = $this->db->prepare('SELECT p.*, u.name as creator_name, 
            (SELECT SUM(amount) FROM donations WHERE project_id = p.id) as total_funded
            FROM projects p
            LEFT JOIN users u ON p.creator_id = u.id
            WHERE p.status = "active"
            ORDER BY p.created_at DESC');
        $stmt->execute();
        $result = $stmt->get_result();
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $row['total_funded'] = $row['total_funded'] ?? 0;
            $row['progress'] = ($row['total_funded'] / $row['funding_goal']) * 100;
            $projects[] = $row;
        }
        
        return ['projects' => $projects];
    }

    public function createProject($data) {
        // Verifica autenticazione
        $token = $this->security->getAuthToken();
        if (!$token) {
            http_response_code(401);
            return ['error' => 'Autenticazione richiesta'];
        }

        // Validazione dati
        $requiredFields = ['title', 'description', 'funding_goal', 'end_date'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                return ['error' => 'Tutti i campi sono richiesti'];
            }
        }

        $userId = $this->security->getUserIdFromToken($token);
        
        // Verifica se l'utente è un creator
        $stmt = $this->db->prepare('SELECT is_creator FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user['is_creator']) {
            http_response_code(403);
            return ['error' => 'Solo i creator possono creare progetti'];
        }

        // Inserimento progetto
        $stmt = $this->db->prepare('INSERT INTO projects (title, description, funding_goal, end_date, creator_id, status) VALUES (?, ?, ?, ?, ?, "active")');
        $stmt->bind_param('ssdsi', 
            $data['title'],
            $data['description'],
            $data['funding_goal'],
            $data['end_date'],
            $userId
        );

        if (!$stmt->execute()) {
            http_response_code(500);
            return ['error' => 'Errore durante la creazione del progetto'];
        }

        $projectId = $this->db->insert_id;
        
        return [
            'message' => 'Progetto creato con successo',
            'project_id' => $projectId
        ];
    }

    public function getProject($id) {
        $stmt = $this->db->prepare('SELECT p.*, u.name as creator_name, 
            (SELECT SUM(amount) FROM donations WHERE project_id = p.id) as total_funded
            FROM projects p
            LEFT JOIN users u ON p.creator_id = u.id
            WHERE p.id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            return ['error' => 'Progetto non trovato'];
        }
        
        $project = $result->fetch_assoc();
        $project['total_funded'] = $project['total_funded'] ?? 0;
        $project['progress'] = ($project['total_funded'] / $project['funding_goal']) * 100;
        
        return ['project' => $project];
    }
}