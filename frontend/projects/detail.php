<?php
session_start();
require_once '../../backend/config/database.php';
require_once '../../backend/utils/NavigationHelper.php';
require_once '../../backend/services/MongoLogger.php';

$database = Database::getInstance();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    NavigationHelper::redirect('projects');
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
    NavigationHelper::redirect('projects');
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

// Get software skills if it's a software project
$required_skills = [];
$can_apply = false;
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
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo NavigationHelper::url('home'); ?>">
                <i class="fas fa-rocket"></i> BOSTARTER
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="<?php echo NavigationHelper::url('create_project'); ?>">Create Project</a>
                    <a class="nav-link" href="<?php echo NavigationHelper::url('logout'); ?>">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="<?php echo NavigationHelper::url('login'); ?>">Login</a>
                    <a class="nav-link" href="<?php echo NavigationHelper::url('register'); ?>">Register</a>
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
                                    <a href="<?php echo NavigationHelper::url('fund_project', ['id' => $project_id]); ?>" class="btn btn-success btn-lg me-md-2">
                                        <i class="fas fa-heart"></i> Support This Project
                                    </a>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_id'] == $project['creator_id']): ?>
                                    <a href="<?php echo NavigationHelper::url('manage_rewards', ['id' => $project_id]); ?>" class="btn btn-primary me-md-2">
                                        <i class="fas fa-gift"></i> Manage Rewards
                                    </a>
                                <?php endif; ?>
                                <?php if ($can_apply && !empty($required_skills)): ?>
                                    <a href="<?php echo NavigationHelper::url('apply_project', ['id' => $project_id]); ?>" class="btn btn-info">
                                        <i class="fas fa-user-plus"></i> Apply to Help
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <a href="<?php echo NavigationHelper::url('login'); ?>" class="btn btn-success btn-lg">
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
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-microchip me-2"></i>
                                            <div>
                                                <strong><?php echo htmlspecialchars($component['component_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($component['description']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Comments Section -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-comments"></i> Comments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="<?php echo NavigationHelper::url('add_comment'); ?>" method="post" class="mb-4">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                <div class="mb-3">
                                    <textarea name="content" class="form-control" rows="3" 
                                              placeholder="Share your thoughts..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Post Comment</button>
                            </form>
                        <?php else: ?>
                            <p class="text-center mb-4">
                                <a href="<?php echo NavigationHelper::url('login'); ?>" class="btn btn-outline-primary">
                                    Login to Comment
                                </a>
                            </p>
                        <?php endif; ?>

                        <!-- Display Comments -->
                        <?php if (!empty($all_comments)): ?>
                            <?php foreach ($all_comments as $comment): ?>
                                <div class="mb-3 <?php echo $comment['is_reply'] ? 'ms-4' : ''; ?>">
                                    <div class="d-flex gap-2">
                                        <div>
                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div>
                                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                                <small class="text-muted ms-2">
                                                    <?php echo date('M j, Y', strtotime($comment['created_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                            <?php if (isset($_SESSION['user_id']) && !$comment['is_reply']): ?>
                                                <button class="btn btn-sm btn-link p-0 reply-btn" 
                                                        data-comment-id="<?php echo $comment['comment_id']; ?>">
                                                    Reply
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">No comments yet. Be the first to share your thoughts!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-md-4">
                <!-- Creator Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6><i class="fas fa-user"></i> Project Creator</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">
                            <strong><?php echo htmlspecialchars($project['creator_name']); ?></strong>
                            <br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($project['creator_email']); ?>
                            </small>
                        </p>
                        <hr>
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
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">$<?php echo number_format($reward['min_amount'], 2); ?></span>
                                        <?php if ($project['status'] === 'open' && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $project['creator_id']): ?>
                                            <a href="<?php echo NavigationHelper::url('fund_project', ['id' => $project_id, 'reward' => $reward['reward_id']]); ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                Select
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Share Project -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-share-alt"></i> Share Project</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($project['title']); ?>" 
                               class="btn btn-outline-info" target="_blank">
                                <i class="fab fa-twitter"></i> Share on Twitter
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="fab fa-facebook"></i> Share on Facebook
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($project['title']); ?>" 
                               class="btn btn-outline-secondary" target="_blank">
                                <i class="fab fa-linkedin"></i> Share on LinkedIn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Reply functionality
        document.querySelectorAll('.reply-btn').forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const replyForm = document.createElement('form');
                replyForm.action = '<?php echo NavigationHelper::url('add_comment'); ?>';
                replyForm.method = 'post';
                replyForm.className = 'mt-2';
                replyForm.innerHTML = `
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    <input type="hidden" name="parent_comment_id" value="${commentId}">
                    <div class="mb-2">
                        <textarea name="content" class="form-control form-control-sm" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Reply</button>
                    <button type="button" class="btn btn-sm btn-link cancel-reply">Cancel</button>
                `;
                
                // Remove any existing reply forms
                document.querySelectorAll('.reply-form').forEach(form => form.remove());
                this.parentElement.appendChild(replyForm);
                
                replyForm.querySelector('.cancel-reply').addEventListener('click', () => {
                    replyForm.remove();
                });
            });
        });
    </script>
</body>
</html>

<?php require_once '../components/footer.php'; ?>
