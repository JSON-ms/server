<?php

session_start();

require 'utils.php';
require 'vendor/autoload.php'; // Include the Composer autoload file

use Dotenv\Dotenv;

// Load the .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$allowedOrigins = explode(',', $_ENV['ACCESS_CONTROL_ALLOW_ORIGIN']);
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Check if the origin is in the allowed origins
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Jms-Api-Key, X-Jms-Interface-Hash");
header("Access-Control-Allow-Credentials: true");

// Set the content type to application/json
header('Content-Type: application/json');

// Respond with a 200 OK status for preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = trim($requestPath, '/');
$srcPath = __DIR__ . '/src/endpoint/' . $requestPath . '.php';

// Check if the requested script exists
if (file_exists($srcPath)) {
    try {
        include $srcPath;
    } catch (\Exception $e) {
        http_response_code($e->getCode());
        throwError(500, $e->getMessage());
        exit;
    }
} else {
    throwError(404, "404 Not Found");
}
