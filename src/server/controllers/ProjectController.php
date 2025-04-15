<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../MongoDB/mongodb.php';

use Config\Logger;

class ProjectController extends BaseController {
    
    public function listProjects() {
        // Get query parameters
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? 'open';
        $limit = min((int)($_GET['limit'] ?? 10), 50); // Max 50 projects
        $offset = max((int)($_GET['offset'] ?? 0), 0);
        
        // Build query
        $query = "SELECT p.project_id, p.name, p.description, p.budget, p.deadline, p.status, 
                         p.project_type, p.created_at, u.nickname as creator_name,
                         (SELECT COUNT(*) FROM funding f WHERE f.project_id = p.project_id) as backers_count,
                         (SELECT COALESCE(SUM(f.amount), 0) FROM funding f WHERE f.project_id = p.project_id) as funded_amount,
                         (SELECT photo_path FROM project_photos pp WHERE pp.project_id = p.project_id LIMIT 1) as cover_photo
                  FROM projects p
                  JOIN users u ON p.creator_id = u.user_id
                  WHERE 1=1";
        
        $params = [];
        
        if ($type) {
            $query .= " AND p.project_type = ?";
            $params[] = $type;
        }
        
        if ($status) {
            $query .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // Execute query
        $stmt = $this->db->executeQuery($query, $params);
        $projects = $stmt->fetchAll();
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM projects p WHERE 1=1";
        $countParams = [];
        
        if ($type) {
            $countQuery .= " AND p.project_type = ?";
            $countParams[] = $type;
        }
        
        if ($status) {
            $countQuery .= " AND p.status = ?";
            $countParams[] = $status;
        }
        
        $countStmt = $this->db->executeQuery($countQuery, $countParams);
        $totalCount = $countStmt->fetch()['total'];
        
        // Format projects
        foreach ($projects as &$project) {
            $project['funded_percent'] = $project['budget'] > 0 
                ? round(($project['funded_amount'] / $project['budget']) * 100, 2) 
                : 0;
            
            $project['days_left'] = max(0, ceil((strtotime($project['deadline']) - time()) / 86400));
        }
        
        $this->json([
            'projects' => $projects,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    public function viewProject($projectId) {
        // Validate project ID
        if (!is_numeric($projectId)) {
            $this->error('Invalid project ID');
        }
        
        // Get project details
        $stmt = $this->db->executeQuery(
            "SELECT p.*, u.nickname as creator_name, u.user_id as creator_id,
                    (SELECT COUNT(*) FROM funding f WHERE f.project_id = p.project_id) as backers_count,
                    (SELECT COALESCE(SUM(f.amount), 0) FROM funding f WHERE f.project_id = p.project_id) as funded_amount
             FROM projects p
             JOIN users u ON p.creator_id = u.user_id
             WHERE p.project_id = ?",
            [$projectId]
        );
        
        $project = $stmt->fetch();
        
        if (!$project) {
            $this->error('Project not found', 404);
        }
        
        // Calculate funding percentage and days left
        $project['funded_percent'] = $project['budget'] > 0 
            ? round(($project['funded_amount'] / $project['budget']) * 100, 2) 
            : 0;
        
        $project['days_left'] = max(0, ceil((strtotime($project['deadline']) - time()) / 86400));
        
        // Get project photos
        $stmt = $this->db->executeQuery(
            "SELECT photo_id, photo_path FROM project_photos WHERE project_id = ?",
            [$projectId]
        );
        $project['photos'] = $stmt->fetchAll();
        
        // Get project rewards
        $stmt = $this->db->executeQuery(
            "SELECT * FROM rewards WHERE project_id = ?",
            [$projectId]
        );
        $project['rewards'] = $stmt->fetchAll();
        
        // Get project-specific details based on type
        if ($project['project_type'] === 'hardware') {
            $stmt = $this->db->executeQuery(
                "SELECT * FROM hardware_components WHERE project_id = ?",
                [$projectId]
            );
            $project['components'] = $stmt->fetchAll();
        } else if ($project['project_type'] === 'software') {
            $stmt = $this->db->executeQuery(
                "SELECT sp.*, 
                        (SELECT COUNT(*) FROM applications a WHERE a.profile_id = sp.profile_id) as applications_count
                 FROM software_profiles sp
                 WHERE sp.project_id = ?",
                [$projectId]
            );
            $profiles = $stmt->fetchAll();
            
            // Get skills for each profile
            foreach ($profiles as &$profile) {
                $stmt = $this->db->executeQuery(
                    "SELECT ps.level, c.name as skill_name, c.competency_id
                     FROM profile_skills ps
                     JOIN competencies c ON ps.competency_id = c.competency_id
                     WHERE ps.profile_id = ?",
                    [$profile['profile_id']]
                );
                $profile['skills'] = $stmt->fetchAll();
            }
            
            $project['profiles'] = $profiles;
        }
        
        // Get recent comments
        $stmt = $this->db->executeQuery(
            "SELECT c.*, u.nickname as user_name
             FROM comments c
             JOIN users u ON c.user_id = u.user_id
             WHERE c.project_id = ?
             ORDER BY c.created_at DESC
             LIMIT 10",
            [$projectId]
        );
        $project['comments'] = $stmt->fetchAll();
        
        // Log project view if user is logged in
        if (isset($_SESSION['user_id'])) {
            $this->logger->log('project_view', [
                'project_id' => $projectId,
                'project_name' => $project['name']
            ], $_SESSION['user_id']);
        }
        
        $this->json($project);
    }
    
    public function createProject() {
        // Ensure user is a creator
        $userId = $this->requireCreator();
        
        // Get request data
        $data = $this->getRequestBody();
        
        // Validate required fields
        $requiredFields = ['name', 'description', 'budget', 'deadline', 'project_type'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->error("Field '$field' is required");
            }
        }
        
        // Validate project type
        if (!in_array($data['project_type'], ['hardware', 'software'])) {
            $this->error('Invalid project type. Must be "hardware" or "software"');
        }
        
        // Validate budget
        if (!is_numeric($data['budget']) || $data['budget'] <= 0) {
            $this->error('Budget must be a positive number');
        }
        
        // Validate deadline
        $deadline = date('Y-m-d', strtotime($data['deadline']));
        if ($deadline <= date('Y-m-d')) {
            $this->error('Deadline must be in the future');
        }
        
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // Insert project
            $stmt = $this->db->executeQuery(
                "INSERT INTO projects (name, description, creator_id, budget, deadline, project_type) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['description'],
                    $userId,
                    $data['budget'],
                    $deadline,
                    $data['project_type']
                ]
            );
            
            $projectId = $this->db->lastInsertId();
            
            // Process rewards
            if (isset($data['rewards']) && is_array($data['rewards'])) {
                foreach ($data['rewards'] as $reward) {
                    if (!isset($reward['code']) || !isset($reward['description'])) {
                        continue;
                    }
                    
                    $stmt = $this->db->executeQuery(
                        "INSERT INTO rewards (project_id, code, description, photo_path) 
                         VALUES (?, ?, ?, ?)",
                        [
                            $projectId,
                            $reward['code'],
                            $reward['description'],
                            $reward['photo_path'] ?? null
                        ]
                    );
                }
            }
            
            // Process project-specific details
            if ($data['project_type'] === 'hardware' && isset($data['components']) && is_array($data['components'])) {
                foreach ($data['components'] as $component) {
                    if (!isset($component['name']) || !isset($component['price']) || !isset($component['quantity'])) {
                        continue;
                    }
                    
                    $stmt = $this->db->executeQuery(
                        "INSERT INTO hardware_components (project_id, name, description, price, quantity) 
                         VALUES (?, ?, ?, ?, ?)",
                        [
                            $projectId,
                            $component['name'],
                            $component['description'] ?? null,
                            $component['price'],
                            $component['quantity']
                        ]
                    );
                }
            } else if ($data['project_type'] === 'software' && isset($data['profiles']) && is_array($data['profiles'])) {
                foreach ($data['profiles'] as $profile) {
                    if (!isset($profile['name']) || !isset($profile['skills']) || !is_array($profile['skills'])) {
                        continue;
                    }
                    
                    $stmt = $this->db->executeQuery(
                        "INSERT INTO software_profiles (project_id, name) VALUES (?, ?)",
                        [$projectId, $profile['name']]
                    );
                    
                    $profileId = $this->db->lastInsertId();
                    
                    foreach ($profile['skills'] as $skill) {
                        if (!isset($skill['competency_id']) || !isset($skill['level'])) {
                            continue;
                        }
                        
                        $stmt = $this->db->executeQuery(
                            "INSERT INTO profile_skills (profile_id, competency_id, level) VALUES (?, ?, ?)",
                            [$profileId, $skill['competency_id'], $skill['level']]
                        );
                    }
                }
            }
            
            // Process photos
            if (isset($data['photos']) && is_array($data['photos'])) {
                foreach ($data['photos'] as $photoPath) {
                    $stmt = $this->db->executeQuery(
                        "INSERT INTO project_photos (project_id, photo_path) VALUES (?, ?)",
                        [$projectId, $photoPath]
                    );
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log project creation
            $this->logger->log('project_creation', [
                'project_id' => $projectId,
                'project_name' => $data['name'],
                'project_type' => $data['project_type'],
                'budget' => $data['budget']
            ], $userId);
            
            $this->json([
                'success' => true,
                'message' => 'Project created successfully',
                'project_id' => $projectId
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            $this->error('Project creation failed: ' . $e->getMessage());
        }
    }
    
    public function fundProject($projectId) {
        // Ensure user is authenticated
        $userId = $this->requireAuth();
        
        // Validate project ID
        if (!is_numeric($projectId)) {
            $this->error('Invalid project ID');
        }
        
        // Get request data
        $data = $this->getRequestBody();
        
        // Validate required fields
        if (!isset($data['amount']) || !isset($data['reward_id'])) {
            $this->error('Amount and reward_id are required');
        }
        
        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            $this->error('Amount must be a positive number');
        }
        
        // Check if project exists and is open
        $stmt = $this->db->executeQuery(
            "SELECT p.*, r.reward_id, r.code as reward_code
             FROM projects p
             JOIN rewards r ON p.project_id = r.project_id
             WHERE p.project_id = ? AND r.reward_id = ?",
            [$projectId, $data['reward_id']]
        );
        
        $project = $stmt->fetch();
        
        if (!$project) {
            $this->error('Project or reward not found', 404);
        }
        
        if ($project['status'] !== 'open') {
            $this->error('Project is not open for funding');
        }
        
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // Insert funding record
            $stmt = $this->db->executeQuery(
                "INSERT INTO funding (project_id, user_id, amount, reward_id) VALUES (?, ?, ?, ?)",
                [$projectId, $userId, $data['amount'], $data['reward_id']]
            );
            
            $fundingId = $this->db->lastInsertId();
            
            // Check if project is now fully funded
            $stmt = $this->db->executeQuery(
                "SELECT 
                    p.budget,
                    (SELECT COALESCE(SUM(f.amount), 0) FROM funding f WHERE f.project_id = p.project_id) as total_funded
                 FROM projects p
                 WHERE p.project_id = ?",
                [$projectId]
            );
            
            $fundingStatus = $stmt->fetch();
            
            if ($fundingStatus['total_funded'] >= $fundingStatus['budget']) {
                // Update project status to closed
                $stmt = $this->db->executeQuery(
                    "UPDATE projects SET status = 'closed' WHERE project_id = ?",
                    [$projectId]
                );
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log funding
            $this->logger->log('project_funding', [
                'project_id' => $projectId,
                'amount' => $data['amount'],
                'reward_id' => $data['reward_id'],
                'reward_code' => $project['reward_code']
            ], $userId);
            
            $this->json([
                'success' => true,
                'message' => 'Project funded successfully',
                'funding_id' => $fundingId,
                'project_status' => $fundingStatus['total_funded'] >= $fundingStatus['budget'] ? 'closed' : 'open'
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            $this->error('Funding failed: ' . $e->getMessage());
        }
    }
    
    public function addComment($projectId) {
        // Ensure user is authenticated
        $userId = $this->requireAuth();
        
        // Validate project ID
        if (!is_numeric($projectId)) {
            $this->error('Invalid project ID');
        }
        
        // Get request data
        $data = $this->getRequestBody();
        
        // Validate required fields
        if (!isset($data['content']) || empty($data['content'])) {
            $this->error('Comment content is required');
        }
        
        // Check if project exists
        $stmt = $this->db->executeQuery(
            "SELECT project_id, creator_id FROM projects WHERE project_id = ?",
            [$projectId]
        );
        
        $project = $stmt->fetch();
        
        if (!$project) {
            $this->error('Project not found', 404);
        }
        
        try {
            // Insert comment
            $stmt = $this->db->executeQuery(
                "INSERT INTO comments (project_id, user_id, content) VALUES (?, ?, ?)",
                [$projectId, $userId, $data['content']]
            );
            
            $commentId = $this->db->lastInsertId();
            
            // Log comment
            $this->logger->log('project_comment', [
                'project_id' => $projectId,
                'comment_id' => $commentId
            ], $userId);
            
            $this->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment_id' => $commentId
            ]);
            
        } catch (Exception $e) {
            $this->error('Failed to add comment: ' . $e->getMessage());
        }
    }
    
    public function respondToComment($commentId) {
        // Ensure user is authenticated
        $userId = $this->requireAuth();
        
        // Validate comment ID
        if (!is_numeric($commentId)) {
            $this->error('Invalid comment ID');
        }
        
        // Get request data
        $data = $this->getRequestBody();
        
        // Validate required fields
        if (!isset($data['response']) || empty($data['response'])) {
            $this->error('Response content is required');
        }
        
        // Check if comment exists and user is the project creator
        $stmt = $this->db->executeQuery(
            "SELECT c.comment_id, c.project_id, p.creator_id 
             FROM comments c
             JOIN projects p ON c.project_id = p.project_id
             WHERE c.comment_id = ?",
            [$commentId]
        );
        
        $comment = $stmt->fetch();
        
        if (!$comment) {
            $this->error('Comment not found', 404);
        }
        
        if ($comment['creator_id'] != $userId) {
            $this->error('Only the project creator can respond to comments', 403);
        }
        
        try {
            // Update comment with response
            $stmt = $this->db->executeQuery(
                "UPDATE comments SET response = ?, response_at = NOW() WHERE comment_id = ?",
                [$data['response'], $commentId]
            );
            
            // Log response
            $this->logger->log('comment_response', [
                'comment_id' => $commentId,
                'project_id' => $comment['project_id']
            ], $userId);
            
            $this->json([
                'success' => true,
                'message' => 'Response added successfully'
            ]);
            
        } catch (Exception $e) {
            $this->error('Failed to add response: ' . $e->getMessage());
        }
    }
}
?>