<?php
/**
 * =====================================================
 * BOSTARTER - AUTOLOADER PERSONALIZZATO
 * =====================================================
 * 
 * Sistema di autoload ottimizzato per la piattaforma BOSTARTER.
 * Carica automaticamente le classi necessarie senza dipendenze esterne.
 * 
 * @author BOSTARTER Team
 * @version 2.0
 * @description Autoloader leggero e performante
 */

// =====================================================
// CONFIGURAZIONE AUTOLOADER
// =====================================================

// Mappa delle directory per namespace
$autoload_namespaces = [
    'BOSTARTER\\Models\\' => __DIR__ . '/models/',
    'BOSTARTER\\Services\\' => __DIR__ . '/services/',
    'BOSTARTER\\Utils\\' => __DIR__ . '/utils/',
    'BOSTARTER\\Config\\' => __DIR__ . '/config/'
];

// Mappa delle classi specifiche
$autoload_classmap = [
    'Database' => __DIR__ . '/config/database.php',
    'User' => __DIR__ . '/models/User.php',
    'Project' => __DIR__ . '/models/Project.php',
    'AuthService' => __DIR__ . '/services/AuthService.php',
    'FileLoggerSingleton' => __DIR__ . '/services/SimpleLogger.php',
    'MongoLoggerSingleton' => __DIR__ . '/services/SimpleLogger.php',
    'MongoLogger' => __DIR__ . '/services/SimpleLogger.php',
    'SimpleLogger' => __DIR__ . '/services/SimpleLogger.php',
    'ApiResponse' => __DIR__ . '/utils/ApiResponse.php',
    'ErrorHandler' => __DIR__ . '/utils/ErrorHandler.php',
    'MessageManager' => __DIR__ . '/utils/MessageManager.php',
    'RoleManager' => __DIR__ . '/utils/RoleManager.php',
    'Security' => __DIR__ . '/utils/Security.php',
    'Validator' => __DIR__ . '/utils/Validator.php',
    'SecurityConfig' => __DIR__ . '/config/SecurityConfig.php'
];

/**
 * Autoloader principale per classi PSR-4
 * 
 * @param string $className Nome completo della classe
 * @return bool True se la classe è stata caricata
 */
function bostarter_autoload_psr4($className) {
    global $autoload_namespaces;
    
    foreach ($autoload_namespaces as $namespace => $directory) {
        if (strpos($className, $namespace) === 0) {
            $relativeClass = substr($className, strlen($namespace));
            $file = $directory . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Autoloader per classi nella mappa specifica
 * 
 * @param string $className Nome della classe
 * @return bool True se la classe è stata caricata
 */
function bostarter_autoload_classmap($className) {
    global $autoload_classmap;
    
    if (isset($autoload_classmap[$className])) {
        $file = $autoload_classmap[$className];
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
}

/**
 * Autoloader combinato
 * 
 * @param string $className Nome della classe
 */
function bostarter_autoload($className) {
    // Prova prima con la mappa delle classi (più veloce)
    if (bostarter_autoload_classmap($className)) {
        return;
    }
    
    // Poi con PSR-4
    if (bostarter_autoload_psr4($className)) {
        return;
    }
    
    // Log del tentativo fallito per debug
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("BOSTARTER Autoloader: Classe non trovata: $className");
    }
}

// =====================================================
// REGISTRAZIONE AUTOLOADER
// =====================================================

// Registra l'autoloader
spl_autoload_register('bostarter_autoload');

// Carica sempre la configurazione principale
require_once __DIR__ . '/config/app_config.php';

/**
 * Helper per verificare se una classe è disponibile
 * 
 * @param string $className Nome della classe
 * @return bool True se la classe è disponibile
 */
function class_available($className) {
    return class_exists($className, true);
}

/**
 * Helper per caricare manualmente un file
 * 
 * @param string $relativePath Path relativo alla directory backend
 * @return bool True se il file è stato caricato
 */
function load_file($relativePath) {
    $fullPath = __DIR__ . '/' . ltrim($relativePath, '/');
    
    if (file_exists($fullPath)) {
        require_once $fullPath;
        return true;
    }
    
    return false;
}

// =====================================================
// CARICAMENTO COMPONENTI ESSENZIALI
// =====================================================

// Carica componenti critici all'avvio
$essential_components = [
    __DIR__ . '/config/database.php',
    __DIR__ . '/services/SimpleLogger.php',
    __DIR__ . '/utils/ErrorHandler.php'
];

foreach ($essential_components as $component) {
    if (file_exists($component)) {
        require_once $component;
    }
}

// Inizializza error handler se disponibile
if (class_exists('ErrorHandler')) {
    ErrorHandler::initialize();
}

// Log dell'avvio autoloader
if (function_exists('logMessage')) {
    logMessage('DEBUG', 'BOSTARTER Autoloader inizializzato con successo');
}
?>
