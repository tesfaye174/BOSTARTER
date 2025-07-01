#!/usr/bin/env php
<?php
chdir(__DIR__);
require_once 'utils/BuildSystem.php';
try {
    $buildSystem = new BuildSystem('build-settings.json');
    $clean = in_array('--clean', $argv);
    $production = in_array('--production', $argv);
    $watch = in_array('--watch', $argv);
    if ($watch) {
        echo "Watching for changes... Press Ctrl+C to stop.\n";
        while (true) {
            $buildSystem->build($clean);
            sleep(2);
            $clean = false; // Only clean on first run
        }
    } else {
        $buildSystem->build($clean);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

