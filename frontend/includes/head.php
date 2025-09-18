<?php
/**
 * Header Comune BOSTARTER - Versione Moderna e Ottimizzata
 *
 * Include comune per tutte le pagine:
 * - Meta tags e charset ottimizzati
 * - CSS ottimizzato e consolidato
 * - Font moderna (Inter)
 * - Dark mode integrato
 * - Performance ottimizzata
 */

// Prevenzione inclusioni multiple
if (!defined('BOSTARTER_HEAD_INCLUDED')) {
    define('BOSTARTER_HEAD_INCLUDED', true);
}

// Determina il path corretto per gli asset basato sulla posizione del file
$currentPath = $_SERVER['PHP_SELF'];
$basePath = '';

if (strpos($currentPath, '/admin/') !== false) {
    $basePath = '../../';
} elseif (strpos($currentPath, '/auth/') !== false) {
    $basePath = '../../';
} elseif (strpos($currentPath, '/includes/') !== false) {
    $basePath = '../../';
} else {
    $basePath = '../';
}

/**
 * Funzione per ottenere il path corretto degli asset
 */
function asset($path, $basePath = '') {
    return $basePath . 'assets/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BOSTARTER - Piattaforma di crowdfunding per progetti creativi e innovativi">
    <meta name="keywords" content="crowdfunding, progetti, creativi, innovazione, finanziamento">
    <meta name="author" content="BOSTARTER Team">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - BOSTARTER' : 'BOSTARTER - Piattaforma di Crowdfunding'; ?></title>

    <!-- Preconnect per performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Font moderna ottimizzata -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- CSS Ottimizzato e Consolidato -->
    <link href="<?php echo asset('css/bostarter-optimized.min.css', $basePath); ?>" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/favicon.ico', $basePath); ?>">

    <!-- Custom meta tags per SEO -->
    <meta property="og:title" content="BOSTARTER - Piattaforma di Crowdfunding">
    <meta property="og:description" content="Sostieni creatori ambiziosi e porta idee innovative alla realtà">
    <meta property="og:image" content="<?php echo $basePath; ?>assets/images/og-image.jpg">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
</head>
<body>
