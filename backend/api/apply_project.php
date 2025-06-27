<?php
/**
 * API Application to Project - BOSTARTER
 * 
 * Gestisce le candidature degli utenti ai progetti con controllo skill matching.
 * Verifica che l'utente possegga le competenze richieste per il profilo selezionato.
 * 
 * @author BOSTARTER Team
 * @version 2.1.0 - Versione migliorata con validazione centralizzata
 */

// Headers CORS e sicurezza per API REST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestione richieste preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Verifica metodo HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

// Inizializzazione sessione per controlli di sicurezza
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mongo_config.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/ApiResponse.php';
require_once __DIR__ . '/../services/MongoLogger.php';

// Inizializzazione logger e risposta standard
$mongoLogger = new MongoLogger();
$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

try {
    // Lettura e validazione input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ApiResponse::invalidInput('Invalid JSON input');
    }
    
    // Controllo autenticazione utente (opzionale ma raccomandato)
    $authenticated_user_id = $_SESSION['user_id'] ?? null;
    $input_user_id = $input['user_id'] ?? null;
    
    // Se l'utente è autenticato, deve corrispondere all'user_id della richiesta
    if ($authenticated_user_id && $input_user_id && $authenticated_user_id != $input_user_id) {
        $mongoLogger->logAction('unauthorized_application_attempt', [
            'authenticated_user' => $authenticated_user_id,
            'requested_user' => $input_user_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        ApiResponse::unauthorized('You can only apply for yourself');
    }
    
    // Validazione centralizzata dei parametri usando Validator
    $validator = new Validator();
    
    // Validazione user_id
    $validator->obbligatorio('user_id', $input['user_id'] ?? '')
             ->intero()
             ->minimo(1);
    
    // Validazione project_id
    $validator->obbligatorio('project_id', $input['project_id'] ?? '')
             ->intero()
             ->minimo(1);
    
    // Validazione profilo_id
    $validator->obbligatorio('profilo_id', $input['profilo_id'] ?? '')
             ->intero()
             ->minimo(1);
    
    if (!$validator->eValido()) {
        $mongoLogger->logAction('invalid_application_data', [
            'errors' => $validator->ottieniErrori(),
            'input' => $input
        ]);
        ApiResponse::invalidInput($validator->ottieniErrori());
    }
    
    // Sanitizzazione dati validati
    $user_id = intval($input['user_id']);
    $project_id = intval($input['project_id']);
    $profilo_id = intval($input['profilo_id']);

    // Connessione database
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Log tentativo di candidatura
    $mongoLogger->logAction('application_attempt', [
        'user_id' => $user_id,
        'project_id' => $project_id,
        'profilo_id' => $profilo_id,
        'timestamp' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Verifica esistenza dell'utente
    $stmt_user_check = $conn->prepare("SELECT id, nome, cognome FROM utenti WHERE id = ? AND attivo = 1");
    $stmt_user_check->execute([$user_id]);
    $user_exists = $stmt_user_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_exists) {
        $mongoLogger->logAction('application_failed_user_not_found', ['user_id' => $user_id]);
        ApiResponse::notFound('User not found or inactive');
    }
    
    // Verifica esistenza del progetto
    $stmt_project_check = $conn->prepare("SELECT id, nome, stato FROM progetti WHERE id = ? AND stato = 'aperto'");
    $stmt_project_check->execute([$project_id]);
    $project_exists = $stmt_project_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$project_exists) {
        $mongoLogger->logAction('application_failed_project_not_found', ['project_id' => $project_id]);
        ApiResponse::notFound('Project not found or not accepting applications');
    }
    
    // Verifica se l'utente ha già fatto candidatura per questo progetto/profilo
    $stmt_existing = $conn->prepare("SELECT id FROM candidature WHERE utente_id = ? AND progetto_id = ? AND profilo_id = ?");
    $stmt_existing->execute([$user_id, $project_id, $profilo_id]);
    if ($stmt_existing->fetch()) {
        $mongoLogger->logAction('application_failed_already_applied', [
            'user_id' => $user_id,
            'project_id' => $project_id,
            'profilo_id' => $profilo_id
        ]);
        ApiResponse::conflict('You have already applied to this project profile');
    }

    // --- Skill Matching Logic Ottimizzato ---
    // 1. Recupero requisiti di competenza per il profilo selezionato
    $profile_req_sql = "SELECT c.nome AS skill_name, srp.livello_richiesto
                        FROM skill_richieste_profilo srp
                        JOIN competenze c ON srp.competenza_id = c.id
                        WHERE srp.profilo_id = ?";
    
    $stmt_req = $conn->prepare($profile_req_sql);
    if (!$stmt_req) {
        $mongoLogger->logAction('database_error', [
            'operation' => 'prepare_profile_requirements',
            'error' => $conn->errorInfo()[2],
            'profilo_id' => $profilo_id
        ]);
        ApiResponse::serverError('Database error during skill verification');
    }
    
    $stmt_req->execute([$profilo_id]);
    $profile_requirements = [];
    while ($row = $stmt_req->fetch(PDO::FETCH_ASSOC)) {
        $profile_requirements[$row['skill_name']] = $row['livello_richiesto'];
    }

    // 2. Recupero competenze dell'utente
    $user_skills_sql = "SELECT c.nome AS skill_name, su.livello
                        FROM skill_utente su
                        JOIN competenze c ON su.competenza_id = c.id
                        WHERE su.utente_id = ?";
    
    $stmt_user_skills = $conn->prepare($user_skills_sql);
    if (!$stmt_user_skills) {
        $mongoLogger->logAction('database_error', [
            'operation' => 'prepare_user_skills',
            'error' => $conn->errorInfo()[2],
            'user_id' => $user_id
        ]);
        ApiResponse::serverError('Database error during user skill verification');
    }
    
    $stmt_user_skills->execute([$user_id]);
    $user_skills = [];
    while ($row = $stmt_user_skills->fetch(PDO::FETCH_ASSOC)) {
        $user_skills[$row['skill_name']] = $row['livello'];
    }

    // 3. Analisi e confronto competenze
    $skills_match = true;
    $missing_skills = [];
    $insufficient_skills = [];
    $skill_coverage_percentage = 0;

    if (!empty($profile_requirements)) {
        $total_required_skills = count($profile_requirements);
        $matched_skills = 0;
        
        foreach ($profile_requirements as $req_skill_name => $req_level) {
            if (!isset($user_skills[$req_skill_name])) {
                $skills_match = false;
                $missing_skills[] = $req_skill_name;
            } elseif ($user_skills[$req_skill_name] < $req_level) {
                $skills_match = false;
                $insufficient_skills[] = [
                    'skill' => $req_skill_name,
                    'user_level' => $user_skills[$req_skill_name],
                    'required_level' => $req_level
                ];
            } else {
                $matched_skills++;
            }
        }
        
        $skill_coverage_percentage = ($matched_skills / $total_required_skills) * 100;
    } else {
        // Se non ci sono requisiti di skill, la candidatura è automaticamente valida
        $skill_coverage_percentage = 100;
    }
    
    // Log dell'analisi delle competenze
    $mongoLogger->logAction('skill_analysis_completed', [
        'user_id' => $user_id,
        'project_id' => $project_id,
        'profilo_id' => $profilo_id,
        'skills_match' => $skills_match,
        'coverage_percentage' => $skill_coverage_percentage,
        'missing_skills_count' => count($missing_skills),
        'insufficient_skills_count' => count($insufficient_skills)
    ]);
    // --- Fine Skill Matching Logic ---

    if ($skills_match) {
        // Transazione per garantire integrità dei dati
        $conn->beginTransaction();
        
        try {
            // Chiamata alla stored procedure per la candidatura
            $stmt_apply = $conn->prepare("CALL candidati_progetto(?, ?, ?)");
            if (!$stmt_apply) {
                throw new Exception('Failed to prepare application statement: ' . $conn->errorInfo()[2]);
            }
            
            $stmt_apply->execute([$user_id, $project_id, $profilo_id]);
            $result_apply = $stmt_apply->fetch(PDO::FETCH_ASSOC);
            
            if ($result_apply && isset($result_apply['application_id'])) {
                $application_id = $result_apply['application_id'];
                
                // Commit della transazione
                $conn->commit();
                
                // Log successo
                $mongoLogger->logAction('application_successful', [
                    'user_id' => $user_id,
                    'user_name' => $user_exists['nome'] . ' ' . $user_exists['cognome'],
                    'project_id' => $project_id,
                    'project_name' => $project_exists['nome'],
                    'profilo_id' => $profilo_id,
                    'application_id' => $application_id,
                    'skill_coverage' => $skill_coverage_percentage,
                    'timestamp' => time()
                ]);
                
                // Risposta di successo
                ApiResponse::success([
                    'application_id' => $application_id,
                    'skill_coverage_percentage' => round($skill_coverage_percentage, 2),
                    'message' => 'Application submitted successfully'
                ], 'Your application has been submitted successfully!');
                
            } else {
                throw new Exception('Application submitted but failed to retrieve application ID');
            }
            
        } catch (Exception $e) {
            // Rollback in caso di errore
            $conn->rollback();
            
            $errorInfo = $stmt_apply ? $stmt_apply->errorInfo() : $conn->errorInfo();
            
            if ($errorInfo[1] == 1062) { // Duplicate entry
                $mongoLogger->logAction('application_failed_duplicate', [
                    'user_id' => $user_id,
                    'project_id' => $project_id,
                    'profilo_id' => $profilo_id,
                    'error' => $errorInfo[2]
                ]);
                ApiResponse::conflict('You have already applied to this project profile');
                
            } else if (strpos($errorInfo[2], "FOREIGN KEY") !== false) {
                $mongoLogger->logAction('application_failed_foreign_key', [
                    'user_id' => $user_id,
                    'project_id' => $project_id,
                    'profilo_id' => $profilo_id,
                    'error' => $errorInfo[2]
                ]);
                ApiResponse::invalidInput('Invalid user, project or profile ID');
                
            } else {
                $mongoLogger->logAction('application_failed_database_error', [
                    'user_id' => $user_id,
                    'project_id' => $project_id,
                    'profilo_id' => $profilo_id,
                    'error' => $e->getMessage()
                ]);
                ApiResponse::serverError('Failed to submit application due to database error');
            }
        }
        
    } else {
        // Skills non sufficienti - fornisci feedback dettagliato
        $mongoLogger->logAction('application_failed_insufficient_skills', [
            'user_id' => $user_id,
            'project_id' => $project_id,
            'profilo_id' => $profilo_id,
            'missing_skills' => $missing_skills,
            'insufficient_skills' => $insufficient_skills,
            'coverage_percentage' => $skill_coverage_percentage
        ]);
        
        $details = [];
        if (!empty($missing_skills)) {
            $details['missing_skills'] = $missing_skills;
        }
        if (!empty($insufficient_skills)) {
            $details['insufficient_skill_levels'] = $insufficient_skills;
        }
        $details['skill_coverage_percentage'] = round($skill_coverage_percentage, 2);
        
        ApiResponse::unprocessableEntity('Skill requirements not met', $details);
    }

} catch (PDOException $e) {
    // Errori specifici del database
    $mongoLogger->logAction('database_exception', [
        'user_id' => $user_id ?? null,
        'project_id' => $project_id ?? null,
        'profilo_id' => $profilo_id ?? null,
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Rollback se la transazione è ancora attiva
    if ($conn && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    ApiResponse::serverError('Database error occurred during application process');
    
} catch (Exception $e) {
    // Altri errori generici
    $mongoLogger->logAction('general_exception', [
        'user_id' => $user_id ?? null,
        'project_id' => $project_id ?? null,
        'profilo_id' => $profilo_id ?? null,
        'error_message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Rollback se la transazione è ancora attiva
    if (isset($conn) && $conn && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    ApiResponse::serverError('An unexpected error occurred during application process');
}

// Nota: PDO connections are automatically closed when the script ends
// No need for manual connection closure

?>
