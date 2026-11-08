<?php
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$pathSegments = explode('/', trim($scriptName, '/'));
$firstSegment = strtolower($pathSegments[0] ?? '');
$appRootDirectories = ['auth', 'config', 'database', 'images', 'includes', 'vendor', 'views'];
$appBaseUrl = $firstSegment !== '' && !in_array($firstSegment, $appRootDirectories, true)
    ? '/' . rawurlencode($pathSegments[0])
    : '';

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', $appBaseUrl);
}

$host = getenv('HMS_DB_HOST') ?: 'localhost';
$dbname = getenv('HMS_DB_NAME') ?: 'charles_hms';
$username = getenv('HMS_DB_USER') ?: 'root';
$password = getenv('HMS_DB_PASSWORD') ?: '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
