<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MongoLogger.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../utils/Validator.php';
session_start();
try {
    if (!isset($_SESSION['user_id'])) {
        ApiResponse::unauthorized('Authentication required');
    }
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $db = Database::getInstance()->getConnection();
    $mongoLogger = new MongoLogger();
    switch ($method) {
        case 'GET':            
            $stmt = $db->prepare("
                SELECT s.id, s.nome as skill_name, s.categoria, s.descrizione,
                       us.livello as livello_competenza, 0 as anni_esperienza, us.created_at as data_aggiunta
                FROM competenze s
                JOIN skill_utente us ON s.id = us.competenza_id
                WHERE us.utente_id = ?
                ORDER BY s.categoria, s.nome
            ");
            $stmt->execute([$user_id]);
            $skills = $stmt->fetchAll();
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
            $stmt = $db->prepare("SELECT id, nome FROM competenze WHERE id = ?");
            $stmt->execute([$skill_id]);
            $skill = $stmt->fetch();
            if (!$skill) {
                ApiResponse::notFound('Skill not found');
            }            
            $stmt = $db->prepare("SELECT id FROM skill_utente WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $skill_id]);
            if ($stmt->fetch()) {
                ApiResponse::error('You already have this skill', 409);
            }
            $stmt = $db->prepare("
                INSERT INTO skill_utente (utente_id, competenza_id, livello)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $skill_id, $livello]);
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
            $stmt = $db->prepare("SELECT id FROM skill_utente WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $skill_id]);
            if (!$stmt->fetch()) {
                ApiResponse::notFound('Skill not found for this user');
            }
            $stmt = $db->prepare("
                UPDATE skill_utente 
                SET livello = ?
                WHERE utente_id = ? AND competenza_id = ?
            ");
            $stmt->execute([$livello, $user_id, $skill_id]);
            $mongoLogger->logActivity($user_id, 'skill_updated', [
                'skill_id' => $skill_id,
                'new_level' => $livello,
                'years_experience' => $anni_esperienza
            ]);
            ApiResponse::success(null, 'Skill updated successfully');
            break;
        case 'DELETE':
            $skill_id = (int)($_GET['skill_id'] ?? 0);
            if (!$skill_id) {
                ApiResponse::error('Skill ID is required');
            }            
            $stmt = $db->prepare("SELECT id FROM skill_utente WHERE utente_id = ? AND competenza_id = ?");
            $stmt->execute([$user_id, $skill_id]);
            if (!$stmt->fetch()) {
                ApiResponse::notFound('Skill not found for this user');
            }
            $stmt = $db->prepare("DELETE FROM skill_utente WHERE utente_id = ? AND competenza_id = ?");
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
    error_log("Database error in user_skill.php: " . $e->getMessage());
    ApiResponse::serverError('Database error occurred');
} catch (Exception $e) {
    error_log("Error in user_skill.php: " . $e->getMessage());
    ApiResponse::serverError('An unexpected error occurred');
}

