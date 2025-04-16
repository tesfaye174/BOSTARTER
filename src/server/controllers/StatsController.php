<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../MongoDB/mongodb.php';

use Config\Logger;

// Controller per la gestione delle statistiche della piattaforma e dei progetti
class StatsController extends BaseController {
    /**
     * Restituisce i top creator ordinati per affidabilità e progetti completati.
     * @return void
     */
    public function getTopCreators() {
        // Get top creators by reliability
        $stmt = $this->db->executeQuery(
            "SELECT u.user_id, u.nickname, cu.reliability, cu.project_count,
                    (SELECT COUNT(DISTINCT p.project_id) 
                     FROM projects p 
                     JOIN funding f ON p.project_id = f.project_id 
                     WHERE p.creator_id = u.user_id AND p.status = 'closed') as completed_projects
             FROM users u
             JOIN creator_users cu ON u.user_id = cu.user_id
             ORDER BY cu.reliability DESC, completed_projects DESC
             LIMIT 3"
        );
        $creators = $stmt->fetchAll();
        $this->json([
            'top_creators' => $creators
        ]);
    }
    /**
     * Restituisce i top progetti più vicini al completamento.
     * @return void
     */
    public function getTopProjects() {
        // Get top projects closest to completion
        $stmt = $this->db->executeQuery(
            "SELECT 
                p.project_id, p.name, p.budget, p.deadline, p.project_type,
                u.nickname as creator_name,
                (SELECT COUNT(*) FROM funding f WHERE f.project_id = p.project_id) as backers_count,
                (SELECT COALESCE(SUM(f.amount), 0) FROM funding f WHERE f.project_id = p.project_id) as funded_amount,
                (SELECT photo_path FROM project_photos pp WHERE pp.project_id = p.project_id LIMIT 1) as cover_photo
            FROM projects p
            JOIN users u ON p.creator_id = u.user_id
            WHERE p.status = 'open'
            GROUP BY p.project_id
            ORDER BY (funded_amount / p.budget) DESC
            LIMIT 3"
        );
        $projects = $stmt->fetchAll();
        // Calculate additional metrics
        foreach ($projects as &$project) {
            $project['funded_percent'] = $project['budget'] > 0 
                ? round(($project['funded_amount'] / $project['budget']) * 100, 2) 
                : 0;
            $project['days_left'] = max(0, ceil((strtotime($project['deadline']) - time()) / 86400));
        }
        $this->json([
            'top_projects' => $projects
        ]);
    }
    
    public function getTopFunders() {
        // Get top funders by total amount funded
        $stmt = $this->db->executeQuery(
            "SELECT 
                u.user_id, u.nickname,
                COUNT(DISTINCT f.project_id) as projects_funded,
                SUM(f.amount) as total_funded
            FROM users u
            JOIN funding f ON u.user_id = f.user_id
            GROUP BY u.user_id
            ORDER BY total_funded DESC
            LIMIT 3"
        );
        
        $funders = $stmt->fetchAll();
        
        $this->json([
            'top_funders' => $funders
        ]);
    }
    
    public function getProjectStats($projectId) {
        // Ensure project ID is valid
        if (!is_numeric($projectId)) {
            $this->error('Invalid project ID');
        }
        
        // Check if project exists
        $stmt = $this->db->executeQuery(
            "SELECT p.*, u.nickname as creator_name
             FROM projects p
             JOIN users u ON p.creator_id = u.user_id
             WHERE p.project_id = ?",
            [$projectId]
        );
        
        $project = $stmt->fetch();
        
        if (!$project) {
            $this->error('Project not found', 404);
        }
        
        // Get funding statistics
        $stmt = $this->db->executeQuery(
            "SELECT 
                COUNT(DISTINCT f.user_id) as unique_backers,
                COUNT(f.funding_id) as total_contributions,
                SUM(f.amount) as total_funded,
                AVG(f.amount) as average_contribution,
                MAX(f.amount) as largest_contribution,
                MIN(f.amount) as smallest_contribution
             FROM funding f
             WHERE f.project_id = ?",
            [$projectId]
        );
        
        $fundingStats = $stmt->fetch();
        
        // Get funding timeline (daily)
        $stmt = $this->db->executeQuery(
            "SELECT 
                DATE(f.funded_at) as date,
                COUNT(f.funding_id) as contributions,
                SUM(f.amount) as amount
             FROM funding f
             WHERE f.project_id = ?
             GROUP BY DATE(f.funded_at)
             ORDER BY date",
            [$projectId]
        );
        
        $fundingTimeline = $stmt->fetchAll();
        
        // Get reward distribution
        $stmt = $this->db->executeQuery(
            "SELECT 
                r.reward_id, r.code, r.description,
                COUNT(f.funding_id) as backers,
                SUM(f.amount) as total_amount
             FROM rewards r
             LEFT JOIN funding f ON r.reward_id = f.reward_id
             WHERE r.project_id = ?
             GROUP BY r.reward_id
             ORDER BY total_amount DESC",
            [$projectId]
        );
        
        $rewardDistribution = $stmt->fetchAll();
        
        // Get comment statistics
        $stmt = $this->db->executeQuery(
            "SELECT 
                COUNT(*) as total_comments,
                SUM(CASE WHEN response IS NOT NULL THEN 1 ELSE 0 END) as responded_comments,
                AVG(TIMESTAMPDIFF(HOUR, created_at, response_at)) as avg_response_time_hours
             FROM comments
             WHERE project_id = ?",
            [$projectId]
        );
        
        $commentStats = $stmt->fetch();
        
        // Get project view count from MongoDB if user is authenticated
        $viewCount = 0;
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $project['creator_id']) {
            try {
                // Fix: Access MongoDB collection through the database instance instead of logger
                $collection = $this->db->getMongoDB();
                $viewCount = $collection->countDocuments([
                    'event' => 'project_view',
                    'data.project_id' => (int)$projectId
                ]);
            } catch (Exception $e) {
                // Silently fail if MongoDB query fails
                error_log("MongoDB query failed: " . $e->getMessage());
            }
        }
        
        // Calculate funding percentage
        $fundingPercentage = 0;
        if ($project['budget'] > 0 && isset($fundingStats['total_funded'])) {
            $fundingPercentage = round(($fundingStats['total_funded'] / $project['budget']) * 100, 2);
        }
        
        // Calculate days left or days since completion
        $daysLeft = 0;
        $isExpired = false;
        
        if (strtotime($project['deadline']) > time()) {
            $daysLeft = ceil((strtotime($project['deadline']) - time()) / 86400);
        } else {
            $daysLeft = floor((time() - strtotime($project['deadline'])) / 86400);
            $isExpired = true;
        }
        
        // Compile all statistics
        $stats = [
            'project' => [
                'id' => $project['project_id'],
                'name' => $project['name'],
                'creator' => $project['creator_name'],
                'type' => $project['project_type'],
                'status' => $project['status'],
                'budget' => $project['budget'],
                'deadline' => $project['deadline'],
                'days_left' => $daysLeft,
                'is_expired' => $isExpired,
                'created_at' => $project['created_at']
            ],
            'funding' => [
                'total_funded' => $fundingStats['total_funded'] ?? 0,
                'funding_percentage' => $fundingPercentage,
                'unique_backers' => $fundingStats['unique_backers'] ?? 0,
                'total_contributions' => $fundingStats['total_contributions'] ?? 0,
                'average_contribution' => $fundingStats['average_contribution'] ?? 0,
                'largest_contribution' => $fundingStats['largest_contribution'] ?? 0,
                'smallest_contribution' => $fundingStats['smallest_contribution'] ?? 0
            ],
            'timeline' => $fundingTimeline,
            'rewards' => $rewardDistribution,
            'comments' => [
                'total' => $commentStats['total_comments'] ?? 0,
                'responded' => $commentStats['responded_comments'] ?? 0,
                'response_rate' => $commentStats['total_comments'] > 0 
                    ? round(($commentStats['responded_comments'] / $commentStats['total_comments']) * 100, 2) 
                    : 0,
                'avg_response_time_hours' => $commentStats['avg_response_time_hours'] ?? 0
            ],
            'views' => $viewCount
        ];
        
        // Log stats access if user is authenticated
        if (isset($_SESSION['user_id'])) {
            $this->logger->log('project_stats_view', [
                'project_id' => $projectId,
                'project_name' => $project['name']
            ], $_SESSION['user_id']);
        }
        
        $this->json($stats);
    }
    
    public function getPlatformStats() {
        // Only admin users can access platform stats
        $userId = $this->requireAdmin();
        
        // Get overall platform statistics
        $stats = [];
        
        // Total users
        $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Total creators
        $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM creator_users");
        $stats['total_creators'] = $stmt->fetch()['count'];
        
        // Total projects
        $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM projects");
        $stats['total_projects'] = $stmt->fetch()['count'];
        
        // Active projects
        $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM projects WHERE status = 'open'");
        $stats['active_projects'] = $stmt->fetch()['count'];
        
        // Completed projects
        $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM projects WHERE status = 'closed'");
        $stats['completed_projects'] = $stmt->fetch()['count'];
        
        // Total funding
        $stmt = $this->db->executeQuery("SELECT COALESCE(SUM(amount), 0) as total FROM funding");
        $stats['total_funding'] = $stmt->fetch()['total'];
        
        // Average funding per project
        $stmt = $this->db->executeQuery(
            "SELECT AVG(total) as average FROM (
                SELECT p.project_id, COALESCE(SUM(f.amount), 0) as total
                FROM projects p
                LEFT JOIN funding f ON p.project_id = f.project_id
                GROUP BY p.project_id
            ) as project_totals"
        );
        $stats['avg_funding_per_project'] = $stmt->fetch()['average'];
        
        // Success rate
        $stmt = $this->db->executeQuery(
            "SELECT 
                COUNT(*) as total_closed,
                SUM(CASE WHEN total_funded >= budget THEN 1 ELSE 0 END) as successful
             FROM (
                SELECT 
                    p.project_id, 
                    p.budget,
                    COALESCE(SUM(f.amount), 0) as total_funded
                FROM projects p
                LEFT JOIN funding f ON p.project_id = f.project_id
                WHERE p.status = 'closed'
                GROUP BY p.project_id
             ) as closed_projects"
        );
        $successRate = $stmt->fetch();
        $stats['success_rate'] = $successRate['total_closed'] > 0 
            ? round(($successRate['successful'] / $successRate['total_closed']) * 100, 2) 
            : 0;
        
        // Project type distribution
        $stmt = $this->db->executeQuery(
            "SELECT 
                project_type,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM projects), 2) as percentage
             FROM projects
             GROUP BY project_type"
        );
        $stats['project_types'] = $stmt->fetchAll();
        
        // Monthly growth (last 6 months)
        $stmt = $this->db->executeQuery(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_users,
                (SELECT COUNT(*) FROM creator_users cu JOIN users u ON cu.user_id = u.user_id WHERE DATE_FORMAT(u.created_at, '%Y-%m') = month) as new_creators,
                (SELECT COUNT(*) FROM projects WHERE DATE_FORMAT(created_at, '%Y-%m') = month) as new_projects,
                (SELECT COALESCE(SUM(amount), 0) FROM funding WHERE DATE_FORMAT(funded_at, '%Y-%m') = month) as funding
             FROM users
             WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
             GROUP BY month
             ORDER BY month"
        );
        $stats['monthly_growth'] = $stmt->fetchAll();
        
        // Log platform stats access
        $this->logger->log('platform_stats_view', [], $userId);
        
        $this->json($stats);
    }
}
?>