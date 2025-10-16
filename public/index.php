<?php

session_start();

// Create guest session if user not logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['guest'] = [
        'id' => session_id(), // unique session ID
        'started_at' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
}

// ------------------------------------------------------------
// 🔐 CORS CONFIGURATION
// ------------------------------------------------------------
header('Content-Type: application/json');

$allowedOrigins = [
    'http://localhost:3000',
    'https://iruhost.com',
    'https://www.iruhost.com',
    'https://iruap-studio.vercel.app'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
} else {
    // Log invalid or missing origins for debugging
    error_log('CORS blocked origin: ' . ($origin ?: 'NO ORIGIN HEADER'));
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ------------------------------------------------------------
// 🧩 BOOTSTRAP APPLICATION
// ------------------------------------------------------------
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize Router
use App\Core\Router;

$router = new Router();

// Load route definitions
require_once __DIR__ . '/../routes/web.php';

// Resolve and handle request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->resolve($requestUri, $method);

// Optional: Log route hit for debugging
error_log("[$method] $requestUri");
