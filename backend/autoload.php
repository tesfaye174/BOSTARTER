<?php

spl_autoload_register(function ($class) {
    // Progetto namespace prefix
    $prefix = 'BOSTARTER\\Backend\\';

    // Directory base per il namespace prefix
    // __DIR__ si riferisce alla directory corrente (backend)
    $base_dir = __DIR__ . '/';

    // La classe usa il namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, passa al prossimo autoloader registrato
        return;
    }

    // Ottieni il nome relativo della classe
    $relative_class = substr($class, $len);

    // Sostituisci il namespace prefix con la directory base,
    // sostituisci i separatori di namespace con i separatori di directory
    // nel nome relativo della classe, aggiungi .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Se il file esiste, includilo
    if (file_exists($file)) {
        require $file;
    }
});
?>