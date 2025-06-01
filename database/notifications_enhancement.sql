-- BOSTARTER Notifications Enhancement
-- Additional tables and indexes for advanced notification features

USE bostarter;

-- Notification settings table for user preferences
CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_enabled BOOLEAN DEFAULT TRUE,
    push_enabled BOOLEAN DEFAULT TRUE,
    websocket_enabled BOOLEAN DEFAULT TRUE,
    email_frequency ENUM('instant', 'hourly', 'daily', 'weekly') DEFAULT 'daily',
    notification_types JSON DEFAULT NULL,
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_email_enabled (email_enabled),
    INDEX idx_push_enabled (push_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification logs table for analytics and debugging
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_id INT,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    delivery_method ENUM('websocket', 'email', 'push', 'sms') NOT NULL,
    status ENUM('sent', 'delivered', 'failed', 'bounced') DEFAULT 'sent',
    error_message TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_delivery_method (delivery_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email queue table for batch email processing
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_id INT,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    template VARCHAR(100),
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT,
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_attempts (attempts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WebSocket connections table for tracking active connections
CREATE TABLE IF NOT EXISTS websocket_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    connection_id VARCHAR(100) NOT NULL,
    room VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('connected', 'disconnected') DEFAULT 'connected',
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_connection_id (connection_id),
    INDEX idx_room (room),
    INDEX idx_status (status),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification templates table for reusable email templates
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL,
    subject VARCHAR(255),
    body_text TEXT,
    body_html TEXT,
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add metadata column to notifications table if it doesn't exist
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS metadata JSON DEFAULT NULL AFTER related_id,
ADD COLUMN IF NOT EXISTS priority ENUM('low', 'normal', 'high') DEFAULT 'normal' AFTER type,
ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL AFTER updated_at;

-- Add indexes for better performance
ALTER TABLE notifications 
ADD INDEX IF NOT EXISTS idx_priority (priority),
ADD INDEX IF NOT EXISTS idx_expires_at (expires_at),
ADD INDEX IF NOT EXISTS idx_user_read (user_id, is_read),
ADD INDEX IF NOT EXISTS idx_type_created (type, created_at);

-- Insert default notification settings for existing users
INSERT IGNORE INTO notification_settings (user_id)
SELECT id FROM utenti WHERE stato = 'attivo';

-- Insert default notification templates
INSERT IGNORE INTO notification_templates (name, type, subject, body_text, body_html, variables) VALUES
('project_backed', 'project_backed', 'Your project received a new backer!', 
 'Great news! {{backer_name}} just backed your project "{{project_title}}" with {{amount}}.',
 '<h2>Great news!</h2><p><strong>{{backer_name}}</strong> just backed your project "<strong>{{project_title}}</strong>" with <strong>{{amount}}</strong>.</p>',
 '["backer_name", "project_title", "amount"]'),

('project_comment', 'project_comment', 'New comment on your project',
 '{{commenter_name}} left a comment on your project "{{project_title}}": {{comment_text}}',
 '<h2>New Comment</h2><p><strong>{{commenter_name}}</strong> left a comment on your project "<strong>{{project_title}}</strong>":</p><blockquote>{{comment_text}}</blockquote>',
 '["commenter_name", "project_title", "comment_text"]'),

('project_update', 'project_update', 'Project update: {{project_title}}',
 'The project "{{project_title}}" you backed has a new update: {{update_title}}',
 '<h2>Project Update</h2><p>The project "<strong>{{project_title}}</strong>" you backed has a new update:</p><h3>{{update_title}}</h3><p>{{update_content}}</p>',
 '["project_title", "update_title", "update_content"]'),

('goal_reached', 'goal_reached', 'Congratulations! Goal reached for {{project_title}}',
 'Amazing! Your project "{{project_title}}" has reached its funding goal of {{goal_amount}}!',
 '<h2>🎉 Congratulations!</h2><p>Amazing! Your project "<strong>{{project_title}}</strong>" has reached its funding goal of <strong>{{goal_amount}}</strong>!</p>',
 '["project_title", "goal_amount"]'),

('application_status', 'application_status', 'Application status update',
 'Your application for "{{project_title}}" has been {{status}}.',
 '<h2>Application Status Update</h2><p>Your application for "<strong>{{project_title}}</strong>" has been <strong>{{status}}</strong>.</p>',
 '["project_title", "status"]');

-- Create stored procedure for cleaning old notifications
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanOldNotifications()
BEGIN
    -- Delete expired notifications
    DELETE FROM notifications 
    WHERE expires_at IS NOT NULL AND expires_at < NOW();
    
    -- Delete notifications older than 90 days for read notifications
    DELETE FROM notifications 
    WHERE is_read = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Delete old notification logs (keep for 1 year)
    DELETE FROM notification_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Clean up old websocket connections
    DELETE FROM websocket_connections 
    WHERE status = 'disconnected' AND last_activity < DATE_SUB(NOW(), INTERVAL 1 DAY);
    
    -- Clean up old email queue entries
    DELETE FROM email_queue 
    WHERE status IN ('sent', 'failed') AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //
DELIMITER ;

-- Create event to run cleanup daily
CREATE EVENT IF NOT EXISTS daily_notification_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL CleanOldNotifications();

-- Create view for notification analytics
CREATE OR REPLACE VIEW notification_analytics AS
SELECT 
    DATE(nl.created_at) AS date,
    nl.type,
    nl.delivery_method,
    nl.status,
    COUNT(*) AS count,
    AVG(TIMESTAMPDIFF(SECOND, nl.created_at, nl.delivered_at)) AS avg_delivery_time_seconds
FROM notification_logs nl
WHERE nl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(nl.created_at), nl.type, nl.delivery_method, nl.status;

-- Create view for user notification summary
CREATE OR REPLACE VIEW user_notification_summary AS
SELECT 
    u.id AS user_id,
    u.nickname,
    COUNT(n.id) AS total_notifications,
    SUM(CASE WHEN n.is_read = FALSE THEN 1 ELSE 0 END) AS unread_notifications,
    MAX(n.created_at) AS last_notification,
    ns.email_enabled,
    ns.push_enabled,
    ns.websocket_enabled
FROM utenti u
LEFT JOIN notifications n ON u.id = n.user_id
LEFT JOIN notification_settings ns ON u.id = ns.user_id
WHERE u.stato = 'attivo'
GROUP BY u.id, u.nickname, ns.email_enabled, ns.push_enabled, ns.websocket_enabled;
