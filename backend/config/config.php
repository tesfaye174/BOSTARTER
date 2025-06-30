<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.cookie_httponly", 1);
    ini_set("session.cookie_secure", 0);
    ini_set("session.use_only_cookies", 1);
    session_start();
}
define("DB_HOST", "localhost");
define("DB_NAME", "bostarter_compliant");
define("DB_USER", "root");
define("DB_PASS", "");
define("JWT_SECRET", "bostarter_secret_key_2025");
define("SESSION_LIFETIME", 3600);
date_default_timezone_set("Europe/Rome");
?>
