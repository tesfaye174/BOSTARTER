<?php
header('Content-Type: application/json');

require_once '../config/db_config.php';
require_once '../config/mongo_config.php'; // Ensure this is present and placeholder creation is removed

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $response['message'] = 'Invalid JSON input.';
    echo json_encode($response);
    exit;
}

$user_id = $input['user_id'] ?? null;
$project_id = $input['project_id'] ?? null;

// Validate input
if (!isset($user_id) || !is_numeric($user_id) || intval($user_id) <= 0) {
    $response['message'] = 'Valid User ID is required.';
    echo json_encode($response);
    exit;
}
$user_id = intval($user_id);

if (!isset($project_id) || !is_numeric($project_id) || intval($project_id) <= 0) {
    $response['message'] = 'Valid Project ID is required.';
    echo json_encode($response);
    exit;
}
$project_id = intval($project_id);

// $conn = null; // Not needed for initialization
try {
    $conn = get_db_connection(); // Call directly. It will die on error.
    // Redundant check can be removed:
    // if ($conn === null || $conn->connect_error) {
    //     $response['message'] = 'Database connection failed.';
    //     log_to_mongodb('Database connection failed in apply_project.php');
    //     echo json_encode($response);
    //     exit;
    // }

    // --- Skill Matching Logic ---
    // 1. Fetch project requirements
    $project_req_sql = "SELECT s.skill_name, pr.required_level
                        FROM project_requirements pr
                        JOIN skills s ON pr.skill_id = s.skill_id
                        WHERE pr.project_id = ?";
    $stmt_req = $conn->prepare($project_req_sql);
    if (!$stmt_req) {
        $response['message'] = 'Failed to prepare statement for project requirements: ' . $conn->error;
        log_to_mongodb('MySQL prep failed (project_req)', ['error' => $conn->error, 'project_id' => $project_id]);
        echo json_encode($response);
        exit;
    }
    $stmt_req->bind_param("i", $project_id);
    $stmt_req->execute();
    $result_req = $stmt_req->get_result();
    $project_requirements = [];
    while ($row = $result_req->fetch_assoc()) {
        $project_requirements[$row['skill_name']] = $row['required_level'];
    }
    $stmt_req->close();

    if (empty($project_requirements)) {
        // If a project has NO skill requirements, any user can apply.
        // Or, business rule could be: "Cannot apply to projects with no listed skill requirements".
        // Current assumption: Allow application if no skills are listed as required.
        // If this needs to change, add a check here.
        // For now, we proceed to application if no skills are required.
    }

    // 2. Fetch user skills
    $user_skills_sql = "SELECT s.skill_name, us.level
                        FROM user_skills us
                        JOIN skills s ON us.skill_id = s.skill_id
                        WHERE us.user_id = ?";
    $stmt_user_skills = $conn->prepare($user_skills_sql);
    if (!$stmt_user_skills) {
        $response['message'] = 'Failed to prepare statement for user skills: ' . $conn->error;
        log_to_mongodb('MySQL prep failed (user_skills)', ['error' => $conn->error, 'user_id' => $user_id]);
        echo json_encode($response);
        exit;
    }
    $stmt_user_skills->bind_param("i", $user_id);
    $stmt_user_skills->execute();
    $result_user_skills = $stmt_user_skills->get_result();
    $user_skills = [];
    while ($row = $result_user_skills->fetch_assoc()) {
        $user_skills[$row['skill_name']] = $row['level'];
    }
    $stmt_user_skills->close();

    // 3. Compare skills
    $skills_match = true;
    $missing_skills = [];
    $insufficient_skills = [];

    if (!empty($project_requirements)) { // Only check if there are requirements
        foreach ($project_requirements as $req_skill_name => $req_level) {
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
        // Call the stored procedure to apply
        $stmt_apply = $conn->prepare("CALL apply_to_project(?, ?)");
        if (!$stmt_apply) {
            $response['message'] = 'Failed to prepare statement for application: ' . $conn->error;
            log_to_mongodb('MySQL prep failed (apply_to_project)', ['error' => $conn->error, 'user_id' => $user_id, 'project_id' => $project_id]);
            echo json_encode($response);
            exit;
        }
        $stmt_apply->bind_param("ii", $user_id, $project_id);
        if ($stmt_apply->execute()) {
            $result_apply = $stmt_apply->get_result();
            if ($result_apply && $row_apply = $result_apply->fetch_assoc()) {
                $application_id = $row_apply['application_id'];
                $response['status'] = 'success';
                $response['message'] = 'Application submitted successfully.';
                $response['application_id'] = $application_id;
                log_to_mongodb('User application successful', ['user_id' => $user_id, 'project_id' => $project_id, 'application_id' => $application_id]);
            } else {
                $response['message'] = 'Application submitted, but failed to retrieve application ID.';
                log_to_mongodb('apply_to_project SP did not return application_id', ['user_id' => $user_id, 'project_id' => $project_id]);
            }
        } else {
             // Handle specific errors, e.g., foreign key constraints or unique constraint if already applied
            if ($conn->errno == 1062) { // Duplicate entry
                $response['message'] = 'Application failed: You have already applied to this project.';
                 log_to_mongodb('User re-application attempt failed (already applied)', ['user_id' => $user_id, 'project_id' => $project_id, 'error' => $stmt_apply->error]);
            } else if (strpos($stmt_apply->error, "FOREIGN KEY") !== false) {
                 $response['message'] = 'Application failed: Invalid user or project ID.';
                 log_to_mongodb('User application failed (FK constraint)', ['user_id' => $user_id, 'project_id' => $project_id, 'error' => $stmt_apply->error]);
            }
            else {
                $response['message'] = 'Failed to submit application: ' . $stmt_apply->error;
                log_to_mongodb('apply_to_project SP execution failed', ['user_id' => $user_id, 'project_id' => $project_id, 'error' => $stmt_apply->error]);
            }
        }
        if(isset($stmt_apply)) $stmt_apply->close();
    } else {
        $response['status'] = 'failure'; // Explicitly 'failure' for skill mismatch
        $response['message'] = 'Skill requirements not met.';
        if (!empty($missing_skills)) {
            $response['details']['missing_skills'] = $missing_skills;
        }
        if (!empty($insufficient_skills)) {
            $response['details']['insufficient_skill_levels'] = $insufficient_skills;
        }
        log_to_mongodb('User application failed: skill mismatch', ['user_id' => $user_id, 'project_id' => $project_id, 'missing' => $missing_skills, 'insufficient' => $insufficient_skills]);
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
