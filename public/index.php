<?php

session_start();

if (!isset($_SESSION['user'])){
    $_SESSION['guest'] = [
        'id' => session_id(), // unique session ID
        'started_at' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'], // optional
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
}

// public/index.php
header('Content-Type: application/json');
$allowedOrigins = ['http://localhost:3000', 'https://iruhost.com', 'https://www.iruhost.com', 'https://iruap-studio.vercel.app'];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Optional: log rejected origin for debugging
    error_log("CORS blocked origin: " . $origin);
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

$router = new Router();

// Load route definitions
require_once __DIR__ . '/../routes/web.php';

// Handle the request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->resolve($requestUri, $method);

// DEBUG: Log route hit (optional during development)
error_log("[$method] $requestUri");

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

