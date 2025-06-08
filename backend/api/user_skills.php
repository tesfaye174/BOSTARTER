<?php
// User Skills API - Manage personal skills
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MongoLogger.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Auth.php';

session_start();

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        ApiResponse::unauthorized('Authentication required');
    }

    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $db = Database::getInstance()->getConnection();
    $mongoLogger = new MongoLogger();

    switch ($method) {
        case 'GET':
            // Get user's skills
            $stmt = $db->prepare("
                SELECT s.id, s.nome as skill_name, s.categoria, s.descrizione,
                       us.livello_competenza, us.anni_esperienza, us.data_aggiunta
                FROM competenze s
                JOIN competenze_utenti us ON s.id = us.competenza_id
                WHERE us.utente_id = ?
                ORDER BY s.categoria, s.nome
            ");
            $stmt->execute([$user_id]);
            $skills = $stmt->fetchAll();

            // Group by category
            $skillsByCategory = [];
            foreach ($skills as $skill) {
                $skillsByCategory[$skill['categoria']][] = $skill;
            }

            $mongoLogger->logActivity($user_id, 'skills_viewed', [
                'skills_count' => count($skills)
            ]);

            ApiResponse::success([
                'skills' => $skills,
                'skills_by_category' => $skillsByCategory,
                'total_skills' => count($skills)
            ]);
            break;

        case 'POST':
            // Add skill to user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ApiResponse::error('Invalid JSON format');
            }

            $validator = new Validator();
            $validator->required('skill_id', $input['skill_id'] ?? '');
            $validator->required('livello_competenza', $input['livello_competenza'] ?? '');
            if (!$validator->isValid()) {
                ApiResponse::invalidInput($validator->getErrors());
            }

            $skill_id = (int)$input['skill_id'];
            $livello = $input['livello_competenza'];
            $anni_esperienza = (int)($input['anni_esperienza'] ?? 0);

            // Check if skill exists
            $stmt = $db->prepare("SELECT id, nome FROM competenze WHERE id = ?");
            $stmt->execute([$skill_id]);
            $skill = $stmt->fetch();

            if (!$skill) {
                ApiResponse::notFound('Skill not found');
            }

            // Check if user already has this skill
            $stmt = $db->prepare("SELECT id FROM competenze_utenti WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $skill_id]);
            
            if ($stmt->fetch()) {
                ApiResponse::error('You already have this skill', 409);
            }

            // Add skill to user
            $stmt = $db->prepare("
                INSERT INTO competenze_utenti (utente_id, competenza_id, livello_competenza, anni_esperienza, data_aggiunta)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $skill_id, $livello, $anni_esperienza]);

            $mongoLogger->logActivity($user_id, 'skill_added', [
                'skill_id' => $skill_id,
                'skill_name' => $skill['nome'],
                'level' => $livello,
                'years_experience' => $anni_esperienza
            ]);

            ApiResponse::success([
                'skill_id' => $skill_id,
                'skill_name' => $skill['nome'],
                'level' => $livello
            ], 'Skill added successfully');
            break;

        case 'PUT':
            // Update skill level
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ApiResponse::error('Invalid JSON format');
            }

            $validator = new Validator();
            $validator->required('skill_id', $input['skill_id'] ?? '');
            $validator->required('livello_competenza', $input['livello_competenza'] ?? '');
            if (!$validator->isValid()) {
                ApiResponse::invalidInput($validator->getErrors());
            }

            $skill_id = (int)$input['skill_id'];
            $livello = $input['livello_competenza'];
            $anni_esperienza = (int)($input['anni_esperienza'] ?? 0);

            // Check if user has this skill
            $stmt = $db->prepare("SELECT id FROM competenze_utenti WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $skill_id]);
            
            if (!$stmt->fetch()) {
                ApiResponse::notFound('Skill not found for this user');
            }

            // Update skill
            $stmt = $db->prepare("
                UPDATE competenze_utenti 
                SET livello_competenza = ?, anni_esperienza = ?
                WHERE utente_id = ? AND competenza_id = ?
            ");
            $stmt->execute([$livello, $anni_esperienza, $user_id, $skill_id]);

            $mongoLogger->logActivity($user_id, 'skill_updated', [
                'skill_id' => $skill_id,
                'new_level' => $livello,
                'years_experience' => $anni_esperienza
            ]);

            ApiResponse::success(null, 'Skill updated successfully');
            break;

        case 'DELETE':
            // Remove skill from user
            $skill_id = (int)($_GET['skill_id'] ?? 0);
            
            if (!$skill_id) {
                ApiResponse::error('Skill ID is required');
            }

            // Check if user has this skill
            $stmt = $db->prepare("SELECT id FROM competenze_utenti WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $skill_id]);
            
            if (!$stmt->fetch()) {
                ApiResponse::notFound('Skill not found for this user');
            }

            // Remove skill
            $stmt = $db->prepare("DELETE FROM competenze_utenti WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $skill_id]);

            $mongoLogger->logActivity($user_id, 'skill_removed', [
                'skill_id' => $skill_id
            ]);

            ApiResponse::success(null, 'Skill removed successfully');
            break;

        default:
            ApiResponse::error('Method not allowed', 405);
    }

} catch (PDOException $e) {
    error_log("Database error in user_skills.php: " . $e->getMessage());
    ApiResponse::serverError('Database error occurred');
} catch (Exception $e) {
    error_log("Error in user_skills.php: " . $e->getMessage());
    ApiResponse::serverError('An unexpected error occurred');
}
