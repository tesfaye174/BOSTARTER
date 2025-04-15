<?php
// MongoDB Configuration
define('MONGODB_URI', 'mongodb://localhost:27017');
define('MONGODB_DB', 'bostarter_logs');

define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

define('JWT_SECRET', 'your-secret-key');
define('JWT_EXPIRY', 3600); // 1 hour

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>