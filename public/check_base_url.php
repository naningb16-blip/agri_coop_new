<?php
// Simple diagnostic to check BASE_URL configuration
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$info = [
    'success' => true,
    'message' => 'BASE_URL Configuration Check',
    'base_url_constant' => defined('BASE_URL') ? BASE_URL : 'NOT DEFINED',
    'base_url_env' => getenv('BASE_URL') ?: 'NOT SET',
    'db_host_constant' => defined('DB_HOST') ? DB_HOST : 'NOT DEFINED',
    'db_host_env' => getenv('DB_HOST') ?: 'NOT SET',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'UNKNOWN',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'UNKNOWN',
    'php_version' => PHP_VERSION,
    'all_env_vars' => array_filter($_ENV, function($key) {
        return strpos($key, 'DB_') === 0 || strpos($key, 'BASE_') === 0;
    }, ARRAY_FILTER_USE_KEY)
];

echo json_encode($info, JSON_PRETTY_PRINT);
