<?php

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

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
header('Content-Type: application/json');

setcookie("PHPSESSID", session_id(), [
    'expires' => time() + 60 * 60 * 24 * 30,
    'path' => '/',
    'domain' => '.' . $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https',
    'samesite' => 'None'
]);

// Respond with a 200 OK status for preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri);
$requestPath = trim($requestPath['path'], '/');
$splitRequestUri = explode('/', $requestPath);
$controllerName = ucfirst($splitRequestUri[0]) . 'Controller';
$srcPath = __DIR__ . '/src/controllers/' . $controllerName . '.php';

// Check if the requested script exists
if (file_exists($srcPath)) {
    try {
        require_once 'src/controllers/BaseController.php';
        require_once 'src/controllers/RestfulController.php';
        require_once $srcPath;

        $controller = new $controllerName();
        $actionName = null;
        $params = [];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $actionName = count($splitRequestUri) < 2 ? 'index' : 'get';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actionName = count($splitRequestUri) === 2 ? 'update' : 'create';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT' && count($splitRequestUri) === 2) {
            $actionName = 'update';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $actionName = 'delete';
        }

        for ($i = 1; $i < count($splitRequestUri); $i++) {
            $words = explode('-', $splitRequestUri[$i]);
            $camelCase = strtolower(array_shift($words));
            foreach ($words as $word) {
                $camelCase .= ucfirst($word);
            }

            if ($i === 1 && method_exists($controller, $camelCase . 'Action')) {
                $actionName = $camelCase;
            } elseif (count($splitRequestUri) === 2) {
                $params = [$splitRequestUri[1]];
            } elseif ($i > 1) {
                $params = array_slice($splitRequestUri, 2);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throwError(400, 'Invalid JSON');
            }
            $params[] = $data;
        }

        $reflection = new ReflectionMethod($controllerName, $actionName . 'Action');
        if (count($params) != $reflection->getNumberOfParameters()) {
            throwError(400, 'Invalid API call');
        }

        $controller->{$actionName . 'Action'}(...$params);
    } catch (\Exception $e) {
        http_response_code($e->getCode());
        throwError(500, $e->getMessage());
        exit;
    }
} else {
    throwError(404, "404 Not Found");
}
