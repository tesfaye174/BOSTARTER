<?php
require_once '../config/config.php';
require_once '../config/mongodb.php';

use Config\Logger;

// Log an event
Logger::getInstance()->log('user_action', [
    'action' => 'project_created',
    'project_id' => 123,
    'details' => 'New software project created'
], 1);

// Log an error
Logger::getInstance()->log('error', [
    'message' => 'Database connection failed',
    'code' => 500,
    'trace' => debug_backtrace()
]);