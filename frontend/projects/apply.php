<?php
session_start();
require_once '../../backend/config/database.php';
require_once '../../backend/services/MongoLogger.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = Database::getInstance();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

if (!$project_id) {
    header("Location: list_open.php");
    exit();
}

// Get project details
$project_query = "SELECT p.*, u.username as creator_name, sp.required_skills, sp.max_contributors
                  FROM PROJECTS p
                  JOIN USERS u ON p.creator_id = u.user_id
                  LEFT JOIN SOFTWARE_PROFILES sp ON p.project_id = sp.project_id
                  WHERE p.project_id = :project_id AND p.project_type = 'software' AND p.status = 'open'";

$project_stmt = $db->prepare($project_query);
$project_stmt->bindParam(':project_id', $project_id);
$project_stmt->execute();
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: list_open.php");
    exit();
}

// Check if user is the creator
if ($_SESSION['user_id'] == $project['creator_id']) {
    header("Location: detail.php?id=$project_id");
    exit();
}

// Get required skills
$required_skills = [];
if ($project['required_skills']) {
    $skill_ids = json_decode($project['required_skills'], true);    if ($skill_ids) {
        $skills_query = "SELECT id as skill_id, nome as skill_name, categoria as category FROM competenze WHERE id IN (" . implode(',', array_fill(0, count($skill_ids), '?')) . ") ORDER BY categoria, nome";
        $skills_stmt = $db->prepare($skills_query);
        $skills_stmt->execute($skill_ids);
        $required_skills = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Get user's existing applications
$user_applications_query = "SELECT profilo_id as skill_id, stato as status FROM candidature 
                           WHERE progetto_id = :project_id AND utente_id = :user_id";
$user_app_stmt = $db->prepare($user_applications_query);
$user_app_stmt->bindParam(':project_id', $project_id);
$user_app_stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_app_stmt->execute();
$user_applications = $user_app_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_POST) {
    try {
        // Validazione centralizzata usando Validator
        require_once '../../backend/utils/Validator.php';
        
        $applicationData = [
            'skill_id' => (int)($_POST['skill_id'] ?? 0),
            'motivation' => trim($_POST['motivation'] ?? ''),
            'experience_years' => (int)($_POST['experience_years'] ?? 0),
            'portfolio_url' => trim($_POST['portfolio_url'] ?? '')
        ];
        
        $validationResult = Validator::validateApplication($applicationData);
        if ($validationResult !== true) {
            $errorMessages = is_array($validationResult) ? implode(', ', $validationResult) : $validationResult;
            throw new Exception($errorMessages);
        }
        
        $skill_id = $applicationData['skill_id'];
        $motivation = $applicationData['motivation'];
        $experience_years = $applicationData['experience_years'];
        $portfolio_url = $applicationData['portfolio_url'];
        
        // Check if skill is required for this project
        if (!in_array($skill_id, json_decode($project['required_skills'], true))) {
            throw new Exception("Selected skill is not required for this project.");
        }
        
        // Check if user already applied for this skill
        if (isset($user_applications[$skill_id])) {
            throw new Exception("You have already applied for this skill.");
        }
          // Insert application
        $insert_query = "INSERT INTO candidature (utente_id, progetto_id, profilo_id, data_candidatura) 
                        VALUES (:user_id, :project_id, :skill_id, NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $insert_stmt->bindParam(':project_id', $project_id);
        $insert_stmt->bindParam(':skill_id', $skill_id);
        $insert_stmt->execute();
          // MongoDB logging
        try {
            $mongoLogger->logActivity($_SESSION['user_id'], 'application_submitted', [
                'project_id' => $project_id,
                'skill_id' => $skill_id,
                'experience_years' => $experience_years
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("MongoDB logging failed: " . $e->getMessage());
        }
        
        $message = "Application submitted successfully! The project creator will review your application.";
        
        // Refresh user applications
        $user_app_stmt->execute();
        $user_applications = $user_app_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply to Help - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .skill-card {
            cursor: pointer;
            transition: all 0.2s;
        }
        .skill-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .skill-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9ff;
        }
        .skill-card.applied {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-rocket"></i> BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="list_open.php">Browse Projects</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-plus"></i> Apply to Help with Project</h4>
                        <p class="mb-0 text-muted">
                            <strong><?php echo htmlspecialchars($project['title']); ?></strong> 
                            by <?php echo htmlspecialchars($project['creator_name']); ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="applicationForm">
                            <div class="mb-4">
                                <label class="form-label">Select Skill to Apply For *</label>
                                <div class="row">
                                    <?php 
                                    $current_category = '';
                                    foreach ($required_skills as $skill): 
                                        $is_applied = isset($user_applications[$skill['skill_id']]);
                                        $application_status = $is_applied ? $user_applications[$skill['skill_id']] : '';
                                        
                                        if ($skill['category'] !== $current_category):
                                            if ($current_category !== '') echo '</div><div class="row mt-3">';
                                            $current_category = $skill['category'];
                                            echo '<div class="col-12"><h6 class="text-muted">' . htmlspecialchars($current_category) . '</h6></div>';
                                        endif;
                                    ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="skill-card card p-3 <?php echo $is_applied ? 'applied' : ''; ?>" 
                                                 data-skill-id="<?php echo $skill['skill_id']; ?>"
                                                 <?php echo $is_applied ? '' : 'onclick="selectSkill(this)"'; ?>>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($skill['skill_name']); ?></strong>
                                                        <?php if ($is_applied): ?>
                                                            <br><small class="badge bg-<?php echo $application_status === 'accepted' ? 'success' : ($application_status === 'pending' ? 'warning' : 'danger'); ?>">
                                                                <?php echo ucfirst($application_status); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!$is_applied): ?>
                                                        <input type="radio" name="skill_id" value="<?php echo $skill['skill_id']; ?>" class="form-check-input" required>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="motivation" class="form-label">Why do you want to help with this project? *</label>
                                <textarea class="form-control" id="motivation" name="motivation" 
                                          rows="5" required minlength="50" maxlength="1000"
                                          placeholder="Explain your motivation, relevant experience, and how you can contribute to this project..."><?php echo htmlspecialchars($_POST['motivation'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    Minimum 50 characters, maximum 1000. <span id="char-count">0</span>/1000
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="experience_years" class="form-label">Years of Experience</label>
                                <select class="form-control" id="experience_years" name="experience_years">
                                    <option value="0">Less than 1 year</option>
                                    <option value="1" <?php echo ($_POST['experience_years'] ?? 0) == 1 ? 'selected' : ''; ?>>1-2 years</option>
                                    <option value="3" <?php echo ($_POST['experience_years'] ?? 0) == 3 ? 'selected' : ''; ?>>3-5 years</option>
                                    <option value="6" <?php echo ($_POST['experience_years'] ?? 0) == 6 ? 'selected' : ''; ?>>6-10 years</option>
                                    <option value="11" <?php echo ($_POST['experience_years'] ?? 0) == 11 ? 'selected' : ''; ?>>10+ years</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="portfolio_url" class="form-label">Portfolio/GitHub URL (Optional)</label>
                                <input type="url" class="form-control" id="portfolio_url" name="portfolio_url" 
                                       value="<?php echo htmlspecialchars($_POST['portfolio_url'] ?? ''); ?>"
                                       placeholder="https://github.com/yourusername or https://yourportfolio.com">
                            </div>

                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Application
                                </button>
                                <a href="detail.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Project
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle"></i> Project Details</h6>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars(substr($project['description'], 0, 200))); ?>...</p>
                        <hr>
                        <p><strong>Max Contributors:</strong> <?php echo $project['max_contributors']; ?></p>
                        <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($project['deadline'])); ?></p>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-lightbulb"></i> Application Tips</h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li>Be specific about your relevant experience</li>
                            <li>Explain how you can contribute to the project</li>
                            <li>Include links to your work if available</li>
                            <li>Show enthusiasm for the project</li>
                            <li>Be honest about your skill level</li>
                        </ul>
                    </div>
                </div>

                <?php if (!empty($user_applications)): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6><i class="fas fa-clipboard-list"></i> Your Applications</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($user_applications as $skill_id => $status): ?>
                                <?php
                                $skill_name = '';
                                foreach ($required_skills as $skill) {
                                    if ($skill['skill_id'] == $skill_id) {
                                        $skill_name = $skill['skill_name'];
                                        break;
                                    }
                                }
                                ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small><?php echo htmlspecialchars($skill_name); ?></small>
                                    <span class="badge bg-<?php echo $status === 'accepted' ? 'success' : ($status === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectSkill(card) {
            // Remove selection from all cards
            document.querySelectorAll('.skill-card').forEach(c => c.classList.remove('selected'));
            
            // Select this card
            card.classList.add('selected');
            
            // Check the radio button
            const radio = card.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        }

        // Character counter for motivation
        document.addEventListener('DOMContentLoaded', function() {
            const motivationTextarea = document.getElementById('motivation');
            const charCount = document.getElementById('char-count');
            
            function updateCharCount() {
                const count = motivationTextarea.value.length;
                charCount.textContent = count;
                
                if (count < 50) {
                    charCount.className = 'text-danger';
                } else if (count > 900) {
                    charCount.className = 'text-warning';
                } else {
                    charCount.className = 'text-success';
                }
            }
            
            motivationTextarea.addEventListener('input', updateCharCount);
            updateCharCount(); // Initial count
        });
    </script>
</body>
</html>
