<?php
/**
 * SECURITY AUDIT SCRIPT FOR BOSTARTER
 * Run this script to check security configurations
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/SecurityConfig.php';

function checkSecurityIssues() {
    $issues = [];
    $warnings = [];
    $recommendations = [];
    
    // Check database password
    if (DB_PASS === '') {
        $issues[] = "🚨 CRITICAL: Database password is empty! Set a strong password in production.";
    }
      // Check HTTPS
    $isLocalhost = ($_SERVER['HTTP_HOST'] ?? 'localhost') === 'localhost';
    if (!SecurityConfig::isHTTPS() && !$isLocalhost) {
        $issues[] = "🚨 CRITICAL: HTTPS not enabled in production environment.";
    }
    
    // Check session configuration
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (!ini_get('session.cookie_httponly')) {
            $warnings[] = "⚠️  WARNING: session.cookie_httponly should be enabled.";
        }
        if (!ini_get('session.use_strict_mode')) {
            $warnings[] = "⚠️  WARNING: session.use_strict_mode should be enabled.";
        }
    }
    
    // Check file permissions
    $sensitive_files = [
        __DIR__ . '/config.php',
        __DIR__ . '/database.php',
        __DIR__ . '/SecurityConfig.php'
    ];
    
    foreach ($sensitive_files as $file) {
        if (file_exists($file) && substr(sprintf('%o', fileperms($file)), -4) > '0644') {
            $warnings[] = "⚠️  WARNING: File {$file} has overly permissive permissions.";
        }
    }
    
    // Check for development flags
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
        $warnings[] = "⚠️  WARNING: DEVELOPMENT_MODE is enabled. Disable in production.";
    }
    
    // Recommendations
    $recommendations[] = "💡 RECOMMENDATION: Use environment variables for sensitive configuration.";
    $recommendations[] = "💡 RECOMMENDATION: Implement rate limiting for all API endpoints.";
    $recommendations[] = "💡 RECOMMENDATION: Set up automated security monitoring.";
    $recommendations[] = "💡 RECOMMENDATION: Regular security audits and dependency updates.";
    
    return [
        'issues' => $issues,
        'warnings' => $warnings,
        'recommendations' => $recommendations
    ];
}

function generateSecurityReport() {
    $report = checkSecurityIssues();
    
    echo "=== BOSTARTER SECURITY AUDIT REPORT ===\n\n";
    echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (!empty($report['issues'])) {
        echo "🚨 CRITICAL ISSUES:\n";
        foreach ($report['issues'] as $issue) {
            echo "  - {$issue}\n";
        }
        echo "\n";
    }
    
    if (!empty($report['warnings'])) {
        echo "⚠️  WARNINGS:\n";
        foreach ($report['warnings'] as $warning) {
            echo "  - {$warning}\n";
        }
        echo "\n";
    }
    
    if (!empty($report['recommendations'])) {
        echo "💡 RECOMMENDATIONS:\n";
        foreach ($report['recommendations'] as $rec) {
            echo "  - {$rec}\n";
        }
        echo "\n";
    }
    
    if (empty($report['issues']) && empty($report['warnings'])) {
        echo "✅ No critical security issues found!\n\n";
    }
    
    echo "=== END OF REPORT ===\n";
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    generateSecurityReport();
}
?>
