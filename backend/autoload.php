<?php


// CONFIGURAZIONE AUTOLOADER


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
    'MongoLoggerSingleton' => __DIR__ . '/services/MongoLogger.php',
    'MongoLogger' => __DIR__ . '/services/MongoLogger.php',
    'SimpleLogger' => __DIR__ . '/services/SimpleLogger.php',
    'ApiResponse' => __DIR__ . '/utils/ApiResponse.php',
    'MessageManager' => __DIR__ . '/utils/MessageManager.php',
    'RoleManager' => __DIR__ . '/utils/RoleManager.php',
    'Security' => __DIR__ . '/utils/Security.php',
    'Validator' => __DIR__ . '/utils/Validator.php',
    'SecurityConfig' => __DIR__ . '/config/SecurityConfig.php'
];

// Carica classi PSR-4 per namespace
// Restituisce true se il file è stato incluso
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

// Carica classi dalla mappa statica (shortcut per prestazioni)
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

// Autoloader combinato: prova classmap, poi PSR-4
function bostarter_autoload($className) {
    // Usa la classmap prima (più veloce)
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

// Registra autoloader e carica configurazioni essenziali
spl_autoload_register('bostarter_autoload');

// Carica configurazione applicazione
require_once __DIR__ . '/config/app_config.php';

// Controlla se una classe esiste (scatenando l'autoload se necessario)
function class_available($className) {
    return class_exists($className, true);
}

// Carica manualmente un file relativo al backend
function load_file($relativePath) {
    $fullPath = __DIR__ . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath)) {
        require_once $fullPath;
        return true;
    }
    return false;
}

// Carica componenti essenziali all'avvio
$essential_components = [
    __DIR__ . '/config/database.php',
    __DIR__ . '/services/SimpleLogger.php'
];

foreach ($essential_components as $component) {
    if (file_exists($component)) require_once $component;
}

// Segnala avvio autoloader se esiste il logger
if (function_exists('logMessage')) logMessage('DEBUG', 'BOSTARTER Autoloader inizializzato');
?>