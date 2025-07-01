<?php
define("DB_HOST", "localhost");
define("DB_NAME", "bostarter");
define("DB_USER", "root");
define("DB_PASS", "");
define("JWT_SECRET", "bostarter_secret_key_2025");
define("SESSION_LIFETIME", 3600);
define("UPLOAD_MAX_SIZE", 5 * 1024 * 1024);
define("UPLOAD_ALLOWED_TYPES", ["jpg", "jpeg", "png", "gif", "pdf"]);
define("SMTP_HOST", "localhost");
define("SMTP_PORT", 587);
define("SMTP_USER", "");
define("SMTP_PASS", "");
define("DEBUG_MODE", true);
date_default_timezone_set("Europe/Rome");
?>
