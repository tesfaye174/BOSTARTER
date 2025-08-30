<?php
if (!defined('BOSTARTER_SCRIPTS_INCLUDED')) define('BOSTARTER_SCRIPTS_INCLUDED', true);

// Determine the correct base path for assets
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
                 strpos($_SERVER['PHP_SELF'], '/auth/') !== false;
$basePath = $isInSubfolder ? '../' : '';
?>
<!-- Core JS libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?>js/core.js"></script>
<script src="<?= $basePath ?>js/app.js"></script>
<script src="<?= $basePath ?>js/messages.js"></script>
<?php
// Automatically include a page-specific script if it exists (e.g. js/view.js)
$scriptName = basename($_SERVER['SCRIPT_NAME']);
$pageName = preg_replace('/\.php$/', '', $scriptName);
$jsPath = __DIR__ . '/../js/' . $pageName . '.js';
if (file_exists($jsPath)) {
    echo '    <script src="' . $basePath . 'js/' . htmlspecialchars($pageName) . '.js"></script>' . PHP_EOL;
}
?>
