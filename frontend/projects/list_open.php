<?php
session_start();
require_once '../../backend/config/database.php';
require_once '../../backend/services/MongoLogger.php';

$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

// Log project browsing activity
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$mongoLogger->logActivity($user_id, 'projects_browse', [
    'timestamp' => date('Y-m-d H:i:s'),
    'filters' => [
        'type' => $_GET['type'] ?? null,
        'search' => $_GET['search'] ?? null,
        'sort' => $_GET['sort'] ?? 'newest'
    ],
    'page' => $_GET['page'] ?? 1
]);

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Filter setup
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build WHERE clause
$where_conditions = ["p.status = 'open'"];
$params = [];

if ($type_filter) {
    $where_conditions[] = "p.project_type = :type";
    $params[':type'] = $type_filter;
}

if ($search) {
    $where_conditions[] = "(p.title LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Build ORDER BY clause
$order_clause = match($sort) {
    'oldest' => 'p.created_at ASC',
    'deadline' => 'p.deadline ASC',
    'goal_low' => 'p.funding_goal ASC',
    'goal_high' => 'p.funding_goal DESC',
    'progress' => 'funding_percentage DESC',
    default => 'p.created_at DESC'
};

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM PROJECTS p 
                LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id 
                WHERE $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_projects = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_projects / $per_page);

// Get projects with funding info
$projects_query = "SELECT p.*, u.username as creator_name, u.email as creator_email,
                          COALESCE(pf.total_funded, 0) as total_funded,
                          COALESCE(pf.funding_percentage, 0) as funding_percentage,
                          COALESCE(pf.backers_count, 0) as backers_count,
                          DATEDIFF(p.deadline, NOW()) as days_left
                   FROM PROJECTS p
                   JOIN USERS u ON p.creator_id = u.user_id
                   LEFT JOIN PROJECT_FUNDING_VIEW pf ON p.project_id = pf.project_id
                   WHERE $where_clause
                   ORDER BY $order_clause
                   LIMIT :offset, :per_page";

$projects_stmt = $db->prepare($projects_query);
foreach ($params as $key => $value) {
    $projects_stmt->bindValue($key, $value);
}
$projects_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$projects_stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$projects_stmt->execute();
$projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Projects - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .project-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .project-card:hover {
            transform: translateY(-5px);
        }
        .progress-thin {
            height: 6px;
        }
        .badge-days {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .project-type-badge {
            position: absolute;
            top: 10px;
            left: 10px;
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
        <!-- Header and Search -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-list"></i> Browse Projects</h2>
                <p class="text-muted">Discover amazing hardware and software projects to support</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Project
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search projects...">
                    </div>
                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-control" id="type" name="type">
                            <option value="">All Types</option>
                            <option value="hardware" <?php echo $type_filter === 'hardware' ? 'selected' : ''; ?>>
                                Hardware
                            </option>
                            <option value="software" <?php echo $type_filter === 'software' ? 'selected' : ''; ?>>
                                Software
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-control" id="sort" name="sort">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                                Newest First
                            </option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>
                                Oldest First
                            </option>
                            <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>
                                Deadline Soon
                            </option>
                            <option value="goal_low" <?php echo $sort === 'goal_low' ? 'selected' : ''; ?>>
                                Lowest Goal
                            </option>
                            <option value="goal_high" <?php echo $sort === 'goal_high' ? 'selected' : ''; ?>>
                                Highest Goal
                            </option>
                            <option value="progress" <?php echo $sort === 'progress' ? 'selected' : ''; ?>>
                                Most Funded
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="list_open.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="row mb-3">
            <div class="col">
                <p class="text-muted">
                    Showing <?php echo count($projects); ?> of <?php echo $total_projects; ?> projects
                    <?php if ($search): ?>
                        for "<?php echo htmlspecialchars($search); ?>"
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Projects Grid -->
        <?php if (empty($projects)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>No projects found</h4>
                <p class="text-muted">Try adjusting your search criteria or 
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="create.php">create the first project</a>!
                    <?php else: ?>
                        <a href="../auth/register.php">register</a> to create a project!
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($projects as $project): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card project-card position-relative">
                            <!-- Project Type Badge -->
                            <span class="badge project-type-badge <?php echo $project['project_type'] === 'hardware' ? 'bg-warning' : 'bg-info'; ?>">
                                <i class="fas fa-<?php echo $project['project_type'] === 'hardware' ? 'cog' : 'code'; ?>"></i>
                                <?php echo ucfirst($project['project_type']); ?>
                            </span>

                            <!-- Days Left Badge -->
                            <?php 
                            $days_class = 'bg-success';
                            if ($project['days_left'] <= 7) $days_class = 'bg-danger';
                            elseif ($project['days_left'] <= 30) $days_class = 'bg-warning';
                            ?>
                            <span class="badge badge-days <?php echo $days_class; ?>">
                                <?php echo $project['days_left']; ?> days left
                            </span>

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                <p class="card-text text-muted small">
                                    by <?php echo htmlspecialchars($project['creator_name']); ?>
                                </p>
                                <p class="card-text flex-grow-1">
                                    <?php echo htmlspecialchars(substr($project['description'], 0, 120)) . '...'; ?>
                                </p>
                                
                                <!-- Funding Progress -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">$<?php echo number_format($project['total_funded'], 2); ?></span>
                                        <span class="text-muted"><?php echo round($project['funding_percentage'], 1); ?>%</span>
                                    </div>
                                    <div class="progress progress-thin">
                                        <div class="progress-bar" style="width: <?php echo min(100, $project['funding_percentage']); ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1 small text-muted">
                                        <span>Goal: $<?php echo number_format($project['funding_goal'], 2); ?></span>
                                        <span><?php echo $project['backers_count']; ?> backers</span>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="detail.php?id=<?php echo $project['project_id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $project['creator_id']): ?>
                                        <a href="fund.php?id=<?php echo $project['project_id']; ?>" 
                                           class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-heart"></i> Support
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Projects pagination">
                    <ul class="pagination justify-content-center">
                        <!-- Previous -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Page numbers -->
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- Next -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>