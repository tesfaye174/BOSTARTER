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

// Get project details with funding info
$project_query = "SELECT p.*, u.username as creator_name,
                         COALESCE(pf.total_funded, 0) as total_funded,
                         COALESCE(pf.funding_percentage, 0) as funding_percentage,
                         COALESCE(pf.backers_count, 0) as backers_count,
                         DATEDIFF(p.deadline, NOW()) as days_left
                  FROM PROJECTS p
                  JOIN USERS u ON p.creator_id = u.user_id
                  LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
                  WHERE p.project_id = :project_id AND p.status = 'open'";

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
    $error = "You cannot fund your own project.";
}

// Get project rewards
$rewards_query = "SELECT * FROM REWARDS WHERE project_id = :project_id ORDER BY min_amount ASC";
$rewards_stmt = $db->prepare($rewards_query);
$rewards_stmt->bindParam(':project_id', $project_id);
$rewards_stmt->execute();
$rewards = $rewards_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's previous funding for this project
$user_funding_query = "SELECT SUM(amount) as total_funded FROM FUNDINGS 
                       WHERE project_id = :project_id AND user_id = :user_id";
$user_funding_stmt = $db->prepare($user_funding_query);
$user_funding_stmt->bindParam(':project_id', $project_id);
$user_funding_stmt->bindParam(':user_id', $_SESSION['user_id']);
$user_funding_stmt->execute();
$user_previous_funding = $user_funding_stmt->fetch(PDO::FETCH_ASSOC)['total_funded'] ?? 0;

if ($_POST && !$error) {
    try {
        $amount = (float)$_POST['amount'];
        $reward_id = !empty($_POST['reward_id']) ? (int)$_POST['reward_id'] : null;
        
        if ($amount <= 0) {
            throw new Exception("Funding amount must be positive.");
        }
        
        if ($amount < 1) {
            throw new Exception("Minimum funding amount is $1.");
        }
        
        // Validate reward selection
        if ($reward_id) {
            $reward_check = "SELECT min_amount FROM REWARDS WHERE reward_id = :reward_id AND project_id = :project_id";
            $reward_stmt = $db->prepare($reward_check);
            $reward_stmt->bindParam(':reward_id', $reward_id);
            $reward_stmt->bindParam(':project_id', $project_id);
            $reward_stmt->execute();
            $reward = $reward_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reward) {
                throw new Exception("Invalid reward selected.");
            }
            
            if ($amount < $reward['min_amount']) {
                throw new Exception("Amount must be at least $" . $reward['min_amount'] . " for this reward.");
            }
        }
        
        $db->beginTransaction();
        
        // Insert funding record
        $insert_query = "INSERT INTO FUNDINGS (project_id, user_id, amount, reward_id, funding_date) 
                        VALUES (:project_id, :user_id, :amount, :reward_id, NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':project_id', $project_id);
        $insert_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $insert_stmt->bindParam(':amount', $amount);
        $insert_stmt->bindParam(':reward_id', $reward_id);
        $insert_stmt->execute();
        
        $db->commit();
          // MongoDB logging
        try {
            $mongoLogger->logActivity($_SESSION['user_id'], 'project_funded', [
                'project_id' => $project_id,
                'amount' => $amount,
                'reward_id' => $reward_id
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("MongoDB logging failed: " . $e->getMessage());
        }
        
        $message = "Thank you for your support! Your funding has been recorded.";
        
        // Refresh project data
        $project_stmt->execute();
        $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Refresh user funding
        $user_funding_stmt->execute();
        $user_previous_funding = $user_funding_stmt->fetch(PDO::FETCH_ASSOC)['total_funded'] ?? 0;
        
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
    <title>Fund Project - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reward-card {
            cursor: pointer;
            transition: all 0.2s;
        }
        .reward-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .reward-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9ff;
        }
        .progress-thick {
            height: 20px;
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
            <!-- Project Summary -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-heart"></i> Support This Project</h4>
                    </div>
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($project['title']); ?></h5>
                        <p class="text-muted">by <?php echo htmlspecialchars($project['creator_name']); ?></p>
                        
                        <!-- Funding Progress -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold fs-4 text-success">$<?php echo number_format($project['total_funded'], 2); ?></span>
                                <span class="text-muted"><?php echo round($project['funding_percentage'], 1); ?>% funded</span>
                            </div>
                            <div class="progress progress-thick mb-2">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, $project['funding_percentage']); ?>%"></div>
                            </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <strong>$<?php echo number_format($project['funding_goal'], 2); ?></strong>
                                    <br><small class="text-muted">Goal</small>
                                </div>
                                <div class="col-4">
                                    <strong><?php echo $project['backers_count']; ?></strong>
                                    <br><small class="text-muted">Backers</small>
                                </div>
                                <div class="col-4">
                                    <strong><?php echo $project['days_left']; ?></strong>
                                    <br><small class="text-muted">Days Left</small>
                                </div>
                            </div>
                        </div>

                        <?php if ($user_previous_funding > 0): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You have previously funded this project with $<?php echo number_format($user_previous_funding, 2); ?>
                            </div>
                        <?php endif; ?>

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

                        <?php if (!$error || $error !== "You cannot fund your own project."): ?>
                            <form method="POST" id="fundingForm">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Funding Amount ($) *</label>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           min="1" step="0.01" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                                </div>

                                <input type="hidden" name="reward_id" id="reward_id" value="">

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-heart"></i> Fund This Project
                                    </button>
                                    <a href="detail.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                        Back to Project
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Rewards Sidebar -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-gift"></i> Rewards</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rewards)): ?>
                            <p class="text-muted">No rewards offered for this project.</p>
                        <?php else: ?>
                            <div class="mb-3">
                                <div class="reward-card card p-3 mb-2" data-reward-id="" data-min-amount="1">
                                    <h6 class="mb-1">No Reward</h6>
                                    <p class="text-muted small mb-0">Support without expecting anything in return</p>
                                    <strong class="text-success">Minimum: $1</strong>
                                </div>
                            </div>
                            
                            <?php foreach ($rewards as $reward): ?>
                                <div class="reward-card card p-3 mb-2" 
                                     data-reward-id="<?php echo $reward['reward_id']; ?>" 
                                     data-min-amount="<?php echo $reward['min_amount']; ?>">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($reward['title']); ?></h6>
                                    <p class="text-muted small"><?php echo htmlspecialchars($reward['description']); ?></p>
                                    <strong class="text-success">Minimum: $<?php echo number_format($reward['min_amount'], 2); ?></strong>
                                    <?php if ($reward['estimated_delivery']): ?>
                                        <small class="text-muted d-block">Estimated delivery: <?php echo date('M Y', strtotime($reward['estimated_delivery'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Info -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle"></i> Project Info</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Type:</strong> 
                            <span class="badge <?php echo $project['project_type'] === 'hardware' ? 'bg-warning' : 'bg-info'; ?>">
                                <i class="fas fa-<?php echo $project['project_type'] === 'hardware' ? 'cog' : 'code'; ?>"></i>
                                <?php echo ucfirst($project['project_type']); ?>
                            </span>
                        </p>
                        <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($project['created_at'])); ?></p>
                        <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($project['deadline'])); ?></p>
                        <p class="mb-0"><strong>Status:</strong> 
                            <span class="badge bg-success"><?php echo ucfirst($project['status']); ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rewardCards = document.querySelectorAll('.reward-card');
            const amountInput = document.getElementById('amount');
            const rewardIdInput = document.getElementById('reward_id');

            rewardCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selection from all cards
                    rewardCards.forEach(c => c.classList.remove('selected'));
                    
                    // Select this card
                    this.classList.add('selected');
                    
                    // Set reward ID and minimum amount
                    const rewardId = this.dataset.rewardId;
                    const minAmount = parseFloat(this.dataset.minAmount);
                    
                    rewardIdInput.value = rewardId;
                    
                    // Update minimum amount if current amount is less than required
                    if (parseFloat(amountInput.value) < minAmount || !amountInput.value) {
                        amountInput.value = minAmount.toFixed(2);
                    }
                    amountInput.min = minAmount;
                });
            });

            // Validate amount when reward is selected
            amountInput.addEventListener('input', function() {
                const selectedCard = document.querySelector('.reward-card.selected');
                if (selectedCard) {
                    const minAmount = parseFloat(selectedCard.dataset.minAmount);
                    const currentAmount = parseFloat(this.value);
                    
                    if (currentAmount < minAmount) {
                        this.setCustomValidity(`Minimum amount for selected reward is $${minAmount.toFixed(2)}`);
                    } else {
                        this.setCustomValidity('');
                    }
                }
            });
        });
    </script>
</body>
</html>