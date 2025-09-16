<?php
/**
 * Script JavaScript Comuni BOSTARTER
 *
 * Include per tutte le pagine:
 * - Bootstrap JS bundle
 * - Script core dell'applicazione
 * - Script messaggi di notifica
 * - Script specifico della pagina (se esiste)
 * 
 */

// Prevenzione inclusioni multiple
if (!defined('BOSTARTER_SCRIPTS_INCLUDED')) {
    define('BOSTARTER_SCRIPTS_INCLUDED', true);
}

// Determina il path corretto per gli asset
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
                 strpos($_SERVER['PHP_SELF'], '/auth/') !== false;
$basePath = $isInSubfolder ? '../' : '';
?>

<!-- Librerie JavaScript Core -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?>js/core.js"></script>
<script src="<?= $basePath ?>js/app.js"></script>
<script src="<?= $basePath ?>js/messages.js"></script>

<?php
// Include automaticamente script specifico della pagina
$scriptName = basename($_SERVER['SCRIPT_NAME']);
$pageName = preg_replace('/\.php$/', '', $scriptName);
$jsPath = __DIR__ . '/../js/' . $pageName . '.js';

if (file_exists($jsPath)) {
    echo '    <script src="' . $basePath . 'js/' . htmlspecialchars($pageName) . '.js"></script>' . PHP_EOL;
}
?>
