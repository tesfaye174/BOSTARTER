<?php
session_start();

// MongoDB logging before destroying session
if (isset($_SESSION['user_id'])) {
    try {
        require_once '../../backend/services/MongoLogger.php';
        $mongoLogger = new MongoLogger();
        $mongoLogger->logActivity($_SESSION['user_id'], 'user_logout', [
            'logout_time' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("MongoDB logging failed: " . $e->getMessage());
    }
}

// Distruggi la sessione
session_destroy();

// Redirect alla home page
header('Location: ../index.php');
exit;