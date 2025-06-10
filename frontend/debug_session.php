<?php
session_start();

echo "<h1>Debug Sessione BOSTARTER</h1>";
echo "<h2>Stato Sessione:</h2>";
echo "<pre>";
echo "Session Status: " . session_status() . " (1=disabled, 2=enabled, 3=none)\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "</pre>";

echo "<h2>Variabili Sessione:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Test Metodi Autenticazione:</h2>";

require_once '../backend/utils/NavigationHelper.php';
require_once '../backend/controllers/AuthController.php';

echo "<pre>";
echo "NavigationHelper::isLoggedIn(): " . (NavigationHelper::isLoggedIn() ? 'TRUE' : 'FALSE') . "\n";

try {
    $authController = new \BOSTARTER\Controllers\GestoreAutenticazione();
    echo "AuthController::controllaSeLoggato(): " . ($authController->controllaSeLoggato() ? 'TRUE' : 'FALSE') . "\n";
} catch (Exception $e) {
    echo "Errore AuthController: " . $e->getMessage() . "\n";
}
echo "</pre>";

echo "<h2>Cookie:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Server Info:</h2>";
echo "<pre>";
echo "Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "</pre>";

echo '<h2>Azioni:</h2>';
echo '<a href="auth/login.php">Vai al Login</a> | ';
echo '<a href="dashboard.php">Vai alla Dashboard</a> | ';
echo '<a href="index.php">Vai alla Home</a> | ';
echo '<a href="?clear=1">Pulisci Sessione</a>';

if (isset($_GET['clear'])) {
    session_unset();
    session_destroy();
    echo "<p style='color: green;'><strong>Sessione pulita! Ricarica la pagina.</strong></p>";
}
?>
