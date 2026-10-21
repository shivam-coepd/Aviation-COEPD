<?php
declare(strict_types=1);

/* ---------------------------------------------------------
   Database configuration
   Update these details after creating the MySQL database
   in hPanel / Hostinger.
---------------------------------------------------------*/

// define('DB_HOST', 'localhost');
define('DB_HOST', '193.203.184.189');
define('DB_NAME', 'u233781988_aviation');
define('DB_USER', 'u233781988_aviation');
define('DB_PASS', 'Coepd@#2026');
define('DB_CHARSET', 'utf8mb4');

/* ---------------------------------------------------------
   Application settings
---------------------------------------------------------*/

define('SITE_NAME', 'COEPD');
define('ADMIN_SESSION_NAME', 'AVIATION_PM_ADMIN');

/* ---------------------------------------------------------
   Error handling
   Keep display_errors disabled on the live website.
---------------------------------------------------------*/

$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
$isLocal = (
    str_contains($host, 'localhost') ||
    str_contains($host, '193.203.184.189') ||
    (php_sapi_name() === 'cli' && !getenv('HOSTINGER_ENV'))
);

define('ENVIRONMENT', $isLocal ? 'local' : 'production');

if (ENVIRONMENT === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

date_default_timezone_set('Asia/Kolkata');

/* ---------------------------------------------------------
   Database connection using PDO
---------------------------------------------------------*/

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (ENVIRONMENT === 'local') {
                die('Database connection failed: ' . $e->getMessage());
            }

            http_response_code(500);
            die('We are unable to process your request at the moment. Please try again later.');
        }
    }

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
