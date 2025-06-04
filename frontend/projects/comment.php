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
$project_query = "SELECT title, creator_id FROM PROJECTS WHERE project_id = :project_id";
$project_stmt = $db->prepare($project_query);
$project_stmt->bindParam(':project_id', $project_id);
$project_stmt->execute();
$project = $project_stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: list_open.php");
    exit();
}

if ($_POST) {
    try {
        $content = trim($_POST['content']);
        
        if (empty($content)) {
            throw new Exception("Comment content is required.");
        }
        
        if (strlen($content) > 1000) {
            throw new Exception("Comment must be less than 1000 characters.");
        }
        
        // Insert comment
        $insert_query = "INSERT INTO COMMENTS (project_id, user_id, content, created_at) 
                        VALUES (:project_id, :user_id, :content, NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':project_id', $project_id);
        $insert_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $insert_stmt->bindParam(':content', $content);
        $insert_stmt->execute();
          // MongoDB logging
        try {
            $mongoLogger->logActivity($_SESSION['user_id'], 'comment_added', [
                'project_id' => $project_id,
                'content_length' => strlen($content)
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("MongoDB logging failed: " . $e->getMessage());
        }
        
        $message = "Comment posted successfully!";
        header("Location: detail.php?id=$project_id");
        exit();
        
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
    <title>Add Comment - BOSTARTER</title>
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
                        <h4><i class="fas fa-comment"></i> Add Comment</h4>
                        <p class="mb-0 text-muted">Commenting on: <strong><?php echo htmlspecialchars($project['title']); ?></strong></p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="commentForm">
                            <div class="mb-3">
                                <label for="content" class="form-label">Your Comment *</label>
                                <textarea class="form-control" id="content" name="content" 
                                          rows="6" maxlength="1000" required 
                                          placeholder="Share your thoughts about this project..."><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                <div class="form-text">
                                    <span id="char-count">0</span>/1000 characters
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-comment"></i> Post Comment
                                </button>
                                <a href="detail.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Project
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contentTextarea = document.getElementById('content');
            const charCount = document.getElementById('char-count');
            
            function updateCharCount() {
                const count = contentTextarea.value.length;
                charCount.textContent = count;
                charCount.className = count > 900 ? 'text-warning' : (count > 950 ? 'text-danger' : '');
            }
            
            contentTextarea.addEventListener('input', updateCharCount);
            updateCharCount(); // Initial count
        });
    </script>
</body>
</html>