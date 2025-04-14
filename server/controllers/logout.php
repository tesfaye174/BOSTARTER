<?php
// Initialize the session
require_once __DIR__ . '/config/security.php';
initializeSession();

// Get user nickname for logging
$nickname = $_SESSION['nickname'] ?? 'Unknown user';

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Log the logout event
require_once 'es1.php';
logEvent("User logged out: $nickname");

// Redirect to login page
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:;');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("location: login.php");
exit;
?>