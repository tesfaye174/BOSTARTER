<?php
session_start();
require_once '../../backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = Database::getInstance();
$db = $database->getConnection();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

if (!$project_id) {
    header("Location: list_open.php");
    exit();
}

// Get project details and verify ownership
$project_query = "SELECT nome as title, creatore_id as creator_id, stato as status FROM progetti WHERE id = :project_id";
$project_stmt = $db->prepare($project_query);
$project_stmt->bindParam(':project_id', $project_id);
$project_stmt->execute();
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project || $project['creator_id'] != $_SESSION['user_id']) {
    header("Location: list_open.php");
    exit();
}

// Get existing rewards
$rewards_query = "SELECT * FROM reward WHERE progetto_id = :project_id ORDER BY importo_minimo ASC";
$rewards_stmt = $db->prepare($rewards_query);
$rewards_stmt->bindParam(':project_id', $project_id);
$rewards_stmt->execute();
$rewards = $rewards_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $min_amount = (float)$_POST['min_amount'];
                $estimated_delivery = !empty($_POST['estimated_delivery']) ? $_POST['estimated_delivery'] : null;
                
                if (empty($title) || empty($description) || $min_amount <= 0) {
                    throw new Exception("All fields are required and minimum amount must be positive.");
                }
                  // Check for duplicate minimum amounts
                $check_query = "SELECT COUNT(*) FROM reward WHERE progetto_id = :project_id AND importo_minimo = :min_amount";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':project_id', $project_id);
                $check_stmt->bindParam(':min_amount', $min_amount);
                $check_stmt->execute();
                
                if ($check_stmt->fetchColumn() > 0) {
                    throw new Exception("A reward with this minimum amount already exists.");
                }
                
                $insert_query = "INSERT INTO reward (progetto_id, titolo, descrizione, importo_minimo, data_consegna) 
                                VALUES (:project_id, :title, :description, :min_amount, :estimated_delivery)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(':project_id', $project_id);
                $insert_stmt->bindParam(':title', $title);
                $insert_stmt->bindParam(':description', $description);
                $insert_stmt->bindParam(':min_amount', $min_amount);
                $insert_stmt->bindParam(':estimated_delivery', $estimated_delivery);
                $insert_stmt->execute();
                
                $message = "Reward added successfully!";
                
            } elseif ($_POST['action'] === 'delete') {
                $reward_id = (int)$_POST['reward_id'];
                  // Check if reward has been selected by backers
                $funding_check = "SELECT COUNT(*) FROM finanziamenti WHERE reward_id = :reward_id";
                $funding_stmt = $db->prepare($funding_check);
                $funding_stmt->bindParam(':reward_id', $reward_id);
                $funding_stmt->execute();
                
                if ($funding_stmt->fetchColumn() > 0) {
                    throw new Exception("Cannot delete reward that has been selected by backers.");
                }
                
                $delete_query = "DELETE FROM reward WHERE id = :reward_id AND progetto_id = :project_id";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->bindParam(':reward_id', $reward_id);
                $delete_stmt->bindParam(':project_id', $project_id);
                $delete_stmt->execute();
                
                $message = "Reward deleted successfully!";
            }
            
            // Refresh rewards list
            $rewards_stmt->execute();
            $rewards = $rewards_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
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
    <title>Manage Rewards - BOSTARTER</title>
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
        <div class="row">
            <!-- Add Reward Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Add New Reward</h5>
                        <p class="mb-0 text-muted">For: <strong><?php echo htmlspecialchars($project['title']); ?></strong></p>
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

                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Reward Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="min_amount" class="form-label">Minimum Amount ($) *</label>
                                <input type="number" class="form-control" id="min_amount" name="min_amount" 
                                       min="1" step="0.01" value="<?php echo htmlspecialchars($_POST['min_amount'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="estimated_delivery" class="form-label">Estimated Delivery (Optional)</label>
                                <input type="date" class="form-control" id="estimated_delivery" name="estimated_delivery" 
                                       value="<?php echo htmlspecialchars($_POST['estimated_delivery'] ?? ''); ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Reward
                                </button>
                                <a href="detail.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Project
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Existing Rewards -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-gift"></i> Current Rewards (<?php echo count($rewards); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rewards)): ?>
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-gift fa-2x mb-2"></i><br>
                                No rewards created yet.<br>
                                <small>Add your first reward to encourage backers!</small>
                            </p>
                        <?php else: ?>
                            <?php foreach ($rewards as $reward): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">                                            <h6 class="mb-1"><?php echo htmlspecialchars($reward['titolo']); ?></h6>
                                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($reward['descrizione']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-success">$<?php echo number_format($reward['importo_minimo'], 2); ?>+</strong>
                                                <?php if ($reward['data_consegna']): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i> 
                                                        <?php echo date('M Y', strtotime($reward['data_consegna'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this reward?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <?php                                    // Check if reward has backers
                                    $backers_query = "SELECT COUNT(*) as backer_count FROM finanziamenti WHERE reward_id = :reward_id";
                                    $backers_stmt = $db->prepare($backers_query);
                                    $backers_stmt->bindParam(':reward_id', $reward['id']);
                                    $backers_stmt->execute();
                                    $backer_count = $backers_stmt->fetch(PDO::FETCH_ASSOC)['backer_count'];
                                    ?>
                                    
                                    <?php if ($backer_count > 0): ?>
                                        <div class="mt-2">
                                            <small class="badge bg-info">
                                                <i class="fas fa-users"></i> <?php echo $backer_count; ?> backer(s)
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reward Guidelines -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-lightbulb"></i> Reward Guidelines</h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li>Make rewards attractive and relevant to your project</li>
                            <li>Set realistic delivery dates</li>
                            <li>Consider production and shipping costs</li>
                            <li>Lower amounts should offer simpler rewards</li>
                            <li>Cannot delete rewards with existing backers</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum delivery date to next month
        document.getElementById('estimated_delivery').min = new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0];
    </script>
</body>
</html>