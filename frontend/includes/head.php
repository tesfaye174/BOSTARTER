<?php
// Central head include: meta tags, CSS links
if (!defined('BOSTARTER_HEAD_INCLUDED')) {
    define('BOSTARTER_HEAD_INCLUDED', true);
}

// Determine the correct base path for assets
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
                 strpos($_SERVER['PHP_SELF'], '/auth/') !== false;
$basePath = $isInSubfolder ? '../' : '';
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(generate_csrf_token()) ?>">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - BOSTARTER' : 'BOSTARTER' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= $basePath ?>css/app.css" rel="stylesheet">
    <link href="<?= $basePath ?>css/custom.css" rel="stylesheet">
<?php
// Automatically include a page-specific stylesheet if it exists (e.g. css/view.css)
$scriptName = basename($_SERVER['SCRIPT_NAME']);
$pageName = preg_replace('/\.php$/', '', $scriptName);
$cssPath = __DIR__ . '/../css/' . $pageName . '.css';
if (file_exists($cssPath)) {
    echo '    <link href="' . $basePath . 'css/' . htmlspecialchars($pageName) . '.css" rel="stylesheet">' . PHP_EOL;
}
?>
