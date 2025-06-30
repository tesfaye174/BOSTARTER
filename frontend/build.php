#!/usr/bin/env php
<?php
chdir(__DIR__);
require_once __DIR__ . '/utils/BuildSystem.php';
echo "\n";
echo "██████   ██████  ███████ ████████  █████  ██████  ████████ ███████ ██████\n";
echo "██   ██ ██    ██ ██         ██    ██   ██ ██   ██    ██    ██      ██   ██\n";
echo "██████  ██    ██ ███████    ██    ███████ ██████     ██    █████   ██████\n";
echo "██   ██ ██    ██      ██    ██    ██   ██ ██   ██    ██    ██      ██   ██\n";
echo "██████   ██████  ███████    ██    ██   ██ ██   ██    ██    ███████ ██   ██\n";
echo "\n";
echo "🚀 Build System v2.0\n";
echo "===================\n\n";
$options = getopt('hcw', ['help', 'clean', 'watch', 'production']);
if (isset($options['h']) || isset($options['help'])) {
    showHelp();
    exit(0);
}
if (isset($options['production'])) {
    define('PRODUCTION', true);
    echo "🏭 Modalità PRODUZIONE attivata\n\n";
}
if (isset($options['c']) || isset($options['clean'])) {
    cleanBuild();
}
if (isset($options['w']) || isset($options['watch'])) {
    watchMode();
} else {
    runBuild();
}
function showHelp(): void {
    echo "Utilizzo: php build.php [opzioni]\n\n";
    echo "Opzioni:\n";
    echo "  -h, --help        Mostra questo aiuto\n";
    echo "  -c, --clean       Pulisce cache e build precedenti\n";
    echo "  -w, --watch       Modalità watch per rebuild automatico\n";
    echo "  --production      Build per produzione (minificazione ottimizzata)\n\n";
    echo "Esempi:\n";
    echo "  php build.php                    # Build standard\n";
    echo "  php build.php --clean           # Pulisce e rebuilda\n";
    echo "  php build.php --watch           # Watch mode\n";
    echo "  php build.php --production       # Build per produzione\n\n";
}
function cleanBuild(): void {
    echo "🧹 Pulizia cache e build precedenti...\n";
    $directories = [
        __DIR__ . '/cache/',
        __DIR__ . '/build/'
    ];
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
        }
    }
    echo "✅ Pulizia completata\n\n";
}
function runBuild(): void {
    try {
        $startTime = microtime(true);
        echo "🔨 Avvio processo di build...\n";
        $results = BuildSystem::build();
        if ($results['success']) {
            echo "\n📊 STATISTICHE BUILD:\n";
            echo "==================\n";
            echo "⏱️  Tempo totale: {$results['build_time']}ms\n";
            if (!empty($results['css'])) {
                echo "\n📄 CSS:\n";
                foreach ($results['css'] as $bundle => $info) {
                    $size = formatBytes($info['size']);
                    echo "   • {$bundle}: {$info['file']} ({$size})\n";
                }
            }
            if (!empty($results['js'])) {
                echo "\n⚡ JavaScript:\n";
                foreach ($results['js'] as $bundle => $info) {
                    $size = formatBytes($info['size']);
                    echo "   • {$bundle}: {$info['file']} ({$size})\n";
                }
            }
            if (!empty($results['images'])) {
                echo "\n🖼️  Immagini ottimizzate: " . count($results['images']) . "\n";
            }
            echo "\n🎉 Build completato con successo!\n";
        } else {
            echo "❌ Build fallito\n";
            exit(1);
        }
    } catch (Exception $e) {
        echo "❌ Errore durante il build: " . $e->getMessage() . "\n";
        exit(1);
    }
}
function watchMode(): void {
    echo "👀 Modalità watch attivata\n";
    echo "Monitoraggio cambiamenti nei file...\n";
    echo "Premi Ctrl+C per uscire\n\n";
    $lastBuild = 0;
    while (true) {
        if (BuildSystem::needsRebuild()) {
            $now = time();
            if ($now - $lastBuild > 2) {
                echo "\n🔄 Rilevati cambiamenti, avvio rebuild...\n";
                echo "Timestamp: " . date('H:i:s') . "\n";
                try {
                    BuildSystem::build();
                    echo "✅ Rebuild completato\n";
                } catch (Exception $e) {
                    echo "❌ Errore rebuild: " . $e->getMessage() . "\n";
                }
                $lastBuild = $now;
                echo "\n👀 Continuo il monitoraggio...\n";
            }
        }
        sleep(1);
    }
}
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function() {
        echo "\n\n👋 Build system terminato\n";
        exit(0);
    });
}
