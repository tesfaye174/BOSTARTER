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

// Log project creation page access
$mongoLogger->logActivity($_SESSION['user_id'], 'project_create_page_access', [
    'timestamp' => date('Y-m-d H:i:s')
]);

$message = '';
$error = '';

// Get skills for software projects
$skills_query = "SELECT id as skill_id, nome as skill_name, 'Generale' as category FROM competenze ORDER BY nome";
$skills_stmt = $db->prepare($skills_query);
$skills_stmt->execute();
$skills = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_POST) {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $project_type = $_POST['project_type'];
        $funding_goal = (float)$_POST['funding_goal'];
        $deadline = $_POST['deadline'];
        
        // Validation
        if (empty($title) || empty($description) || empty($project_type) || $funding_goal <= 0) {
            throw new Exception("All fields are required and funding goal must be positive.");
        }
        
        if (strtotime($deadline) <= time()) {
            throw new Exception("Deadline must be in the future.");
        }
        
        $db->beginTransaction();
        
        // Insert project
        $insert_query = "INSERT INTO PROJECTS (creator_id, title, description, project_type, funding_goal, deadline, status) 
                        VALUES (:creator_id, :title, :description, :project_type, :funding_goal, :deadline, 'open')";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':creator_id', $_SESSION['user_id']);
        $insert_stmt->bindParam(':title', $title);
        $insert_stmt->bindParam(':description', $description);
        $insert_stmt->bindParam(':project_type', $project_type);
        $insert_stmt->bindParam(':funding_goal', $funding_goal);
        $insert_stmt->bindParam(':deadline', $deadline);
        $insert_stmt->execute();
        
        $project_id = $db->lastInsertId();
        
        // Handle project-specific data
        if ($project_type === 'hardware') {
            if (!empty($_POST['components'])) {
                $components = json_decode($_POST['components'], true);
                if ($components) {                    $component_query = "INSERT INTO componenti_hardware (progetto_id, nome_componente, quantita, costo_unitario) 
                                     VALUES (:project_id, :component_name, :quantity, :unit_cost)";
                    $component_stmt = $db->prepare($component_query);
                    
                    foreach ($components as $component) {
                        $component_stmt->bindParam(':project_id', $project_id);
                        $component_stmt->bindParam(':component_name', $component['name']);
                        $component_stmt->bindParam(':quantity', $component['quantity']);
                        $component_stmt->bindParam(':unit_cost', $component['cost']);
                        $component_stmt->execute();
                    }
                }
            }
        } else { // software            $required_skills = isset($_POST['required_skills']) ? $_POST['required_skills'] : [];
            if (!empty($required_skills)) {
                $profile_query = "INSERT INTO profili_software (progetto_id, competenze_richieste, max_contributori) 
                                VALUES (:project_id, :required_skills, :max_contributors)";
                $profile_stmt = $db->prepare($profile_query);
                $profile_stmt->bindParam(':project_id', $project_id);
                $profile_stmt->bindParam(':required_skills', json_encode($required_skills));
                $profile_stmt->bindParam(':max_contributors', $_POST['max_contributors'] ?? 5);
                $profile_stmt->execute();
            }
        }
        
        $db->commit();
          // MongoDB logging
        try {
            $mongoLogger->logActivity($_SESSION['user_id'], 'project_created', [
                'project_id' => $project_id,
                'project_type' => $project_type,
                'funding_goal' => $funding_goal,
                'title' => $title
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("MongoDB logging failed: " . $e->getMessage());
        }
        
        $message = "Project created successfully!";
        header("Location: detail.php?id=$project_id");
        exit();
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus-circle"></i> Create New Project</h3>
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

                        <form method="POST" id="createProjectForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Project Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="project_type" class="form-label">Project Type *</label>
                                <select class="form-control" id="project_type" name="project_type" required>
                                    <option value="">Select Type</option>
                                    <option value="hardware" <?php echo ($_POST['project_type'] ?? '') === 'hardware' ? 'selected' : ''; ?>>
                                        Hardware
                                    </option>
                                    <option value="software" <?php echo ($_POST['project_type'] ?? '') === 'software' ? 'selected' : ''; ?>>
                                        Software
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="funding_goal" class="form-label">Funding Goal ($) *</label>
                                <input type="number" class="form-control" id="funding_goal" name="funding_goal" 
                                       min="1" step="0.01" value="<?php echo htmlspecialchars($_POST['funding_goal'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="deadline" class="form-label">Deadline *</label>
                                <input type="date" class="form-control" id="deadline" name="deadline" 
                                       value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>" required>
                            </div>

                            <!-- Hardware-specific fields -->
                            <div id="hardware-fields" style="display: none;">
                                <h5>Hardware Components</h5>
                                <div id="components-container">
                                    <div class="component-row mb-2">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" placeholder="Component Name">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" class="form-control" placeholder="Quantity" min="1">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" class="form-control" placeholder="Unit Cost" min="0" step="0.01">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger remove-component">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary" id="add-component">Add Component</button>
                                <input type="hidden" name="components" id="components-data">
                            </div>

                            <!-- Software-specific fields -->
                            <div id="software-fields" style="display: none;">
                                <h5>Required Skills</h5>
                                <div class="mb-3">
                                    <?php 
                                    $current_category = '';
                                    foreach ($skills as $skill): 
                                        if ($skill['category'] !== $current_category):
                                            if ($current_category !== '') echo '</div>';
                                            $current_category = $skill['category'];
                                            echo '<h6 class="mt-3">' . htmlspecialchars($current_category) . '</h6>';
                                            echo '<div class="row">';
                                        endif;
                                    ?>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="required_skills[]" value="<?php echo $skill['skill_id']; ?>"
                                                       id="skill_<?php echo $skill['skill_id']; ?>">
                                                <label class="form-check-label" for="skill_<?php echo $skill['skill_id']; ?>">
                                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($current_category !== '') echo '</div>'; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="max_contributors" class="form-label">Maximum Contributors</label>
                                    <input type="number" class="form-control" id="max_contributors" 
                                           name="max_contributors" min="1" max="20" value="5">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-rocket"></i> Create Project
                                </button>
                                <a href="list_open.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('project_type').addEventListener('change', function() {
            const type = this.value;
            document.getElementById('hardware-fields').style.display = type === 'hardware' ? 'block' : 'none';
            document.getElementById('software-fields').style.display = type === 'software' ? 'block' : 'none';
        });

        // Hardware components management
        document.getElementById('add-component').addEventListener('click', function() {
            const container = document.getElementById('components-container');
            const newRow = container.querySelector('.component-row').cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            container.appendChild(newRow);
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-component')) {
                const rows = document.querySelectorAll('.component-row');
                if (rows.length > 1) {
                    e.target.closest('.component-row').remove();
                }
            }
        });

        document.getElementById('createProjectForm').addEventListener('submit', function(e) {
            const type = document.getElementById('project_type').value;
            
            if (type === 'hardware') {
                const components = [];
                document.querySelectorAll('.component-row').forEach(row => {
                    const inputs = row.querySelectorAll('input');
                    if (inputs[0].value && inputs[1].value && inputs[2].value) {
                        components.push({
                            name: inputs[0].value,
                            quantity: parseInt(inputs[1].value),
                            cost: parseFloat(inputs[2].value)
                        });
                    }
                });
                document.getElementById('components-data').value = JSON.stringify(components);
            }
        });

        // Set minimum date to tomorrow
        document.getElementById('deadline').min = new Date(Date.now() + 24*60*60*1000).toISOString().split('T')[0];
    </script>
</body>
</html>