<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mongo_config.php'; // Ensure this is present and placeholder creation is removed
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../services/MongoLogger.php';

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $response['message'] = 'Invalid JSON input.';
    echo json_encode($response);
    exit;
}

$user_id = $input['user_id'] ?? null;
$project_id = $input['project_id'] ?? null;
$profilo_id = $input['profilo_id'] ?? null;

// Validazione centralizzata input usando metodi static
if (!$user_id || !is_numeric($user_id) || intval($user_id) <= 0) {
    $response['message'] = 'Valid User ID is required.';
    echo json_encode($response);
    exit;
}
$user_id = intval($user_id);

if (!$project_id || !is_numeric($project_id) || intval($project_id) <= 0) {
    $response['message'] = 'Valid Project ID is required.';
    echo json_encode($response);
    exit;
}
$project_id = intval($project_id);

if (!$profilo_id || !is_numeric($profilo_id) || intval($profilo_id) <= 0) {
    $response['message'] = 'Valid Profilo ID is required.';
    echo json_encode($response);
    exit;
}
$profilo_id = intval($profilo_id);

// Helper per compatibilità con la traccia: log_to_mongodb
function log_to_mongodb($action, $details = []) {
    static $logger = null;
    if ($logger === null) {
        $logger = new MongoLogger();
    }
    // Log di default come azione generica
    $logger->logAction($action, $details);
}

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // --- Skill Matching Logic ---
    // 1. Fetch required skills for the selected profile
    $profile_req_sql = "SELECT c.nome AS skill_name, srp.livello_richiesto
                          FROM skill_richieste_profilo srp
                          JOIN competenze c ON srp.competenza_id = c.id
                          WHERE srp.profilo_id = ?";
    $stmt_req = $conn->prepare($profile_req_sql);
    if (!$stmt_req) {
        $response['message'] = 'Failed to prepare statement for profile requirements: ' . $conn->errorInfo()[2];
        log_to_mongodb('MySQL prep failed (profile_req)', ['error' => $conn->errorInfo()[2], 'profilo_id' => $profilo_id]);
        echo json_encode($response);
        exit;
    }
    $stmt_req->bindParam(1, $profilo_id, PDO::PARAM_INT);
    $stmt_req->execute();
    $profile_requirements = [];
    while ($row = $stmt_req->fetch(PDO::FETCH_ASSOC)) {
        $profile_requirements[$row['skill_name']] = $row['livello_richiesto'];
    }
    $stmt_req = null;

    // 2. Fetch user skills
    $user_skills_sql = "SELECT c.nome AS skill_name, su.livello
                        FROM skill_utente su
                        JOIN competenze c ON su.competenza_id = c.id
                        WHERE su.utente_id = ?";
    $stmt_user_skills = $conn->prepare($user_skills_sql);
    if (!$stmt_user_skills) {
        $response['message'] = 'Failed to prepare statement for user skills: ' . $conn->errorInfo()[2];
        log_to_mongodb('MySQL prep failed (user_skills)', ['error' => $conn->errorInfo()[2], 'user_id' => $user_id]);
        echo json_encode($response);
        exit;
    }
    $stmt_user_skills->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt_user_skills->execute();
    $user_skills = [];
    while ($row = $stmt_user_skills->fetch(PDO::FETCH_ASSOC)) {
        $user_skills[$row['skill_name']] = $row['livello'];
    }
    $stmt_user_skills = null;

    // 3. Compare skills
    $skills_match = true;
    $missing_skills = [];
    $insufficient_skills = [];

    if (!empty($profile_requirements)) {
        foreach ($profile_requirements as $req_skill_name => $req_level) {
            if (!isset($user_skills[$req_skill_name])) {
                $skills_match = false;
                $missing_skills[] = $req_skill_name;
            } elseif ($user_skills[$req_skill_name] < $req_level) {
                $skills_match = false;
                $insufficient_skills[] = ['skill' => $req_skill_name, 'user_level' => $user_skills[$req_skill_name], 'required_level' => $req_level];
            }
        }
    }
    // --- End Skill Matching Logic ---

    if ($skills_match) {
        // Call the stored procedure to apply (conform schema: utente_id, progetto_id, profilo_id)
        $stmt_apply = $conn->prepare("CALL candidati_progetto(?, ?, ?)");
        if (!$stmt_apply) {
            $response['message'] = 'Failed to prepare statement for application: ' . $conn->errorInfo()[2];
            log_to_mongodb('MySQL prep failed (apply_to_project)', ['error' => $conn->errorInfo()[2], 'user_id' => $user_id, 'project_id' => $project_id, 'profilo_id' => $profilo_id]);
            echo json_encode($response);
            exit;
        }
        $stmt_apply->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt_apply->bindParam(2, $project_id, PDO::PARAM_INT);
        $stmt_apply->bindParam(3, $profilo_id, PDO::PARAM_INT);
        if ($stmt_apply->execute()) {
            $result_apply = $stmt_apply->fetch(PDO::FETCH_ASSOC);
            if ($result_apply && isset($result_apply['application_id'])) {
                $application_id = $result_apply['application_id'];
                $response['status'] = 'success';
                $response['message'] = 'Application submitted successfully.';
                $response['application_id'] = $application_id;
                log_to_mongodb('User application successful', ['user_id' => $user_id, 'project_id' => $project_id, 'profilo_id' => $profilo_id, 'application_id' => $application_id]);
            } else {
                $response['message'] = 'Application submitted, but failed to retrieve application ID.';
                log_to_mongodb('apply_to_project SP did not return application_id', ['user_id' => $user_id, 'project_id' => $project_id, 'profilo_id' => $profilo_id]);
            }
        } else {
            $errorInfo = $stmt_apply->errorInfo();
            if ($errorInfo[1] == 1062) { // Duplicate entry
                $response['message'] = 'Application failed: You have already applied to this project/profile.';
                log_to_mongodb('User re-application attempt failed (already applied)', ['user_id' => $user_id, 'project_id' => $project_id, 'profilo_id' => $profilo_id, 'error' => $errorInfo[2]]);
            } else if (strpos($errorInfo[2], "FOREIGN KEY") !== false) {
                $response['message'] = 'Application failed: Invalid user, project or profile ID.';
                log_to_mongodb('User application failed (FK constraint)', ['user_id' => $user_id, 'project_id' => $project_id, 'profilo_id' => $profilo_id, 'error' => $errorInfo[2]]);
            } else {
                $response['message'] = 'Failed to submit application: ' . $errorInfo[2];
                log_to_mongodb('apply_to_project SP execution failed', ['user_id' => $user_id, 'project_id' => $project_id, 'profilo_id' => $profilo_id, 'error' => $errorInfo[2]]);
            }
        }
        $stmt_apply = null;
    } else {
        $response['status'] = 'failure';
        $response['message'] = 'Skill requirements not met.';
        if (!empty($missing_skills)) {
            $response['details']['missing_skills'] = $missing_skills;
        }
        if (!empty($insufficient_skills)) {
            $response['details']['insufficient_skill_levels'] = $insufficient_skills;
        }
        log_to_mongodb('User application failed: skill mismatch', ['user_id' => $user_id, 'project_id' => $project_id, 'profilo_id' => $profilo_id, 'missing' => $missing_skills, 'insufficient' => $insufficient_skills]);
    }

} catch (Exception $e) {
    $response['message'] = 'An exception occurred: ' . $e->getMessage();
    log_to_mongodb('Exception during apply_project', ['user_id' => $user_id, 'project_id' => $project_id, 'exception' => $e->getMessage()]);
} finally {
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);

?>
