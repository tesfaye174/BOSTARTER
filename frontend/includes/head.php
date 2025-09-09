<?php
// Central head include: meta tags, CSS links
if (!defined('BOSTARTER_HEAD_INCLUDED')) {
    define('BOSTARTER_HEAD_INCLUDED', true);
}

// Determine the correct base path for assets
$isInSubfolder = strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
                 strpos($_SERVER['PHP_SELF'], '/auth/') !== false;
$basePath = $isInSubfolder ? '../' : '';

// Function to get the correct asset path
function asset($path) {
    $base = '/BOSTARTER/frontend/';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - BOSTARTER' : 'BOSTARTER' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVottefsqZBpTv==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Custom CSS -->
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/custom.css') ?>" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php
    $scriptName = basename($_SERVER['SCRIPT_NAME']);
    $pageName = preg_replace('/\.php$/', '', $scriptName);
    $cssPath = __DIR__ . '/../css/' . $pageName . '.css';
    if (file_exists($cssPath)) {
        echo '<link href="' . asset('css/' . $pageName . '.css') . '" rel="stylesheet">' . PHP_EOL;
    }
    ?>
    
    <style>
        :root {
            --bostarter-primary: #2563eb;
            --bostarter-secondary: #7c3aed;
            --bostarter-accent: #059669;
            --bostarter-dark: #1f2937;
            --bostarter-light: #f9fafb;
        }
        
        body {
            padding-top: 76px; /* Height of fixed navbar */
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--bostarter-primary);
            border-color: var(--bostarter-primary);
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        
        .text-gradient-secondary {
            background: linear-gradient(90deg, var(--bostarter-secondary), #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body>
