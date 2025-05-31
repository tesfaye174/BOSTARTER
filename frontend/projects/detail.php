<?php
session_start();
require_once '../../backend/config/database.php';
require_once '../../backend/services/MongoLogger.php';

$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header("Location: list_open.php");
    exit();
}

// Get project details
$project_query = "SELECT p.*, u.username as creator_name, u.email as creator_email,
                         COALESCE(pf.total_funded, 0) as total_funded,
                         COALESCE(pf.funding_percentage, 0) as funding_percentage,
                         COALESCE(pf.backers_count, 0) as backers_count,
                         DATEDIFF(p.deadline, NOW()) as days_left
                  FROM PROJECTS p
                  JOIN USERS u ON p.creator_id = u.user_id
                  LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
                  WHERE p.project_id = :project_id";

$project_stmt = $db->prepare($project_query);
$project_stmt->bindParam(':project_id', $project_id);
$project_stmt->execute();
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: list_open.php");
    exit();
}

// Log project view activity
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$mongoLogger->logActivity($user_id, 'project_view', [
    'timestamp' => date('Y-m-d H:i:s'),
    'project_id' => $project_id,
    'project_title' => $project['title'],
    'project_type' => $project['project_type'],
    'creator_id' => $project['creator_id']
]);

// Get hardware components if hardware project
$hardware_components = [];
if ($project['project_type'] === 'hardware') {
    $components_query = "SELECT * FROM HARDWARE_COMPONENTS WHERE project_id = :project_id ORDER BY component_name";
    $components_stmt = $db->prepare($components_query);
    $components_stmt->bindParam(':project_id', $project_id);
    $components_stmt->execute();
    $hardware_components = $components_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get software profile if software project
$software_profile = null;
$required_skills = [];
if ($project['project_type'] === 'software') {
    $profile_query = "SELECT * FROM SOFTWARE_PROFILES WHERE project_id = :project_id";
    $profile_stmt = $db->prepare($profile_query);
    $profile_stmt->bindParam(':project_id', $project_id);
    $profile_stmt->execute();
    $software_profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($software_profile && $software_profile['required_skills']) {
        $skill_ids = json_decode($software_profile['required_skills'], true);
        if ($skill_ids) {
            $skills_query = "SELECT skill_name, category FROM SKILLS WHERE skill_id IN (" . implode(',', array_fill(0, count($skill_ids), '?')) . ")";
            $skills_stmt = $db->prepare($skills_query);
            $skills_stmt->execute($skill_ids);
            $required_skills = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// Get project rewards
$rewards_query = "SELECT * FROM REWARDS WHERE project_id = :project_id ORDER BY min_amount ASC";
$rewards_stmt = $db->prepare($rewards_query);
$rewards_stmt->bindParam(':project_id', $project_id);
$rewards_stmt->execute();
$rewards = $rewards_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get project comments with replies
$comments_query = "SELECT c.*, u.username, 
                          CASE WHEN c.parent_comment_id IS NULL THEN c.comment_id ELSE c.parent_comment_id END as thread_id,
                          c.parent_comment_id IS NOT NULL as is_reply
                   FROM COMMENTS c
                   JOIN USERS u ON c.user_id = u.user_id
                   WHERE c.project_id = :project_id
                   ORDER BY thread_id, c.parent_comment_id IS NULL DESC, c.created_at ASC";
$comments_stmt = $db->prepare($comments_query);
$comments_stmt->bindParam(':project_id', $project_id);
$comments_stmt->execute();
$all_comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize comments into threads
$comments = [];
$replies = [];
foreach ($all_comments as $comment) {
    if ($comment['is_reply']) {
        $replies[$comment['parent_comment_id']][] = $comment;
    } else {
        $comments[] = $comment;
    }
}

// Get applications for software projects
$applications = [];
if ($project['project_type'] === 'software') {
    $applications_query = "SELECT c.*, u.username, s.skill_name
                          FROM CANDIDATURE c
                          JOIN USERS u ON c.user_id = u.user_id
                          JOIN SKILLS s ON c.skill_id = s.skill_id
                          WHERE c.project_id = :project_id
                          ORDER BY c.application_date DESC";
    $applications_stmt = $db->prepare($applications_query);
    $applications_stmt->bindParam(':project_id', $project_id);
    $applications_stmt->execute();
    $applications = $applications_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if current user can apply (for software projects)
$can_apply = false;
$user_applications = [];
if (isset($_SESSION['user_id']) && $project['project_type'] === 'software' && 
    $_SESSION['user_id'] != $project['creator_id'] && $project['status'] === 'open') {
    
    $can_apply = true;
    
    // Get user's existing applications
    $user_app_query = "SELECT skill_id, status FROM CANDIDATURE 
                       WHERE project_id = :project_id AND user_id = :user_id";
    $user_app_stmt = $db->prepare($user_app_query);
    $user_app_stmt->bindParam(':project_id', $project_id);
    $user_app_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $user_app_stmt->execute();
    $user_applications = $user_app_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .progress-thick { height: 20px; }
        .comment-thread { border-left: 3px solid #e9ecef; }
        .reply-comment { margin-left: 2rem; border-left: 2px solid #dee2e6; }
        .skill-badge { margin: 2px; }
        .component-item { background: #f8f9fa; border-radius: 8px; }
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="create.php">Create Project</a>
                    <a class="nav-link" href="../auth/logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="../auth/login.php">Login</a>
                    <a class="nav-link" href="../auth/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Project Header -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center mb-3">
                            <div class="col">
                                <span class="badge <?php echo $project['project_type'] === 'hardware' ? 'bg-warning' : 'bg-info'; ?> mb-2">
                                    <i class="fas fa-<?php echo $project['project_type'] === 'hardware' ? 'cog' : 'code'; ?>"></i>
                                    <?php echo ucfirst($project['project_type']); ?>
                                </span>
                                <h1 class="mb-2"><?php echo htmlspecialchars($project['title']); ?></h1>
                                <p class="text-muted">by <?php echo htmlspecialchars($project['creator_name']); ?></p>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-<?php echo $project['status'] === 'open' ? 'success' : ($project['status'] === 'funded' ? 'primary' : 'secondary'); ?> fs-6">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                        </div>

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
                                    <strong><?php echo max(0, $project['days_left']); ?></strong>
                                    <br><small class="text-muted">Days Left</small>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <?php if ($_SESSION['user_id'] != $project['creator_id'] && $project['status'] === 'open'): ?>
                                    <a href="fund.php?id=<?php echo $project_id; ?>" class="btn btn-success btn-lg me-md-2">
                                        <i class="fas fa-heart"></i> Support This Project
                                    </a>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_id'] == $project['creator_id']): ?>
                                    <a href="add_reward.php?id=<?php echo $project_id; ?>" class="btn btn-primary me-md-2">
                                        <i class="fas fa-gift"></i> Manage Rewards
                                    </a>
                                <?php endif; ?>
                                <?php if ($can_apply && !empty($required_skills)): ?>
                                    <a href="apply.php?id=<?php echo $project_id; ?>" class="btn btn-info">
                                        <i class="fas fa-user-plus"></i> Apply to Help
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <a href="../auth/login.php" class="btn btn-success btn-lg">
                                    Login to Support This Project
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Description -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> About This Project</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                    </div>
                </div>

                <!-- Hardware Components -->
                <?php if ($project['project_type'] === 'hardware' && !empty($hardware_components)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-cogs"></i> Hardware Components</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($hardware_components as $component): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="component-item p-3">
                                            <h6><?php echo htmlspecialchars($component['component_name']); ?></h6>
                                            <p class="mb-1">Quantity: <strong><?php echo $component['quantity']; ?></strong></p>
                                            <p class="mb-0">Unit Cost: <strong>$<?php echo number_format($component['unit_cost'], 2); ?></strong></p>
                                            <small class="text-muted">Total: $<?php echo number_format($component['quantity'] * $component['unit_cost'], 2); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Software Skills -->
                <?php if ($project['project_type'] === 'software' && !empty($required_skills)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-code"></i> Required Skills</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            $skills_by_category = [];
                            foreach ($required_skills as $skill) {
                                $skills_by_category[$skill['category']][] = $skill['skill_name'];
                            }
                            ?>
                            <?php foreach ($skills_by_category as $category => $skills): ?>
                                <h6><?php echo htmlspecialchars($category); ?></h6>
                                <div class="mb-3">
                                    <?php foreach ($skills as $skill): ?>
                                        <span class="badge bg-secondary skill-badge"><?php echo htmlspecialchars($skill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($software_profile): ?>
                                <p class="mb-0">
                                    <strong>Max Contributors:</strong> <?php echo $software_profile['max_contributors']; ?>
                                    <?php if (isset($project['current_contributors'])): ?>
                                        (<?php echo $project['current_contributors']; ?> accepted)
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Comments Section -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-comments"></i> Comments (<?php echo count($comments); ?>)</h5>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="comment.php?id=<?php echo $project_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Comment
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted text-center py-4">No comments yet. Be the first to comment!</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-thread ps-3 mb-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                            <small class="text-muted ms-2"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></small>
                                        </div>
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <a href="reply_comment.php?id=<?php echo $comment['comment_id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-reply"></i> Reply
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                    
                                    <!-- Replies -->
                                    <?php if (isset($replies[$comment['comment_id']])): ?>
                                        <?php foreach ($replies[$comment['comment_id']] as $reply): ?>
                                            <div class="reply-comment ps-3 pt-3 mt-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($reply['username']); ?></strong>
                                                        <small class="text-muted ms-2"><?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Project Info -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-calendar"></i> Project Timeline</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($project['created_at'])); ?></p>
                        <p><strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($project['deadline'])); ?></p>
                        <p class="mb-0">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php echo $project['status'] === 'open' ? 'success' : ($project['status'] === 'funded' ? 'primary' : 'secondary'); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Rewards -->
                <?php if (!empty($rewards)): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6><i class="fas fa-gift"></i> Rewards</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($rewards as $reward): ?>
                                <div class="border rounded p-3 mb-2">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($reward['title']); ?></h6>
                                    <p class="small text-muted mb-2"><?php echo htmlspecialchars($reward['description']); ?></p>
                                    <strong class="text-success">$<?php echo number_format($reward['min_amount'], 2); ?>+</strong>
                                    <?php if ($reward['estimated_delivery']): ?>
                                        <br><small class="text-muted">Est. delivery: <?php echo date('M Y', strtotime($reward['estimated_delivery'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Applications (for software projects) -->
                <?php if ($project['project_type'] === 'software' && (($_SESSION['user_id'] ?? 0) == $project['creator_id'] || !empty($applications))): ?>
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-users"></i> Applications (<?php echo count($applications); ?>)</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($applications)): ?>
                                <p class="text-muted small">No applications yet.</p>
                            <?php else: ?>
                                <?php foreach (array_slice($applications, 0, 5) as $application): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($application['username']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($application['skill_name']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $application['status'] === 'accepted' ? 'success' : ($application['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($applications) > 5): ?>
                                    <small class="text-muted">And <?php echo count($applications) - 5; ?> more...</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
