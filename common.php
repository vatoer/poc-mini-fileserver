<?php
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;


// Load the .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load configuration
$config = require 'config.php';
define('SECRET_KEY', $_ENV['JWT_SECRET_KEY']);
define('FILE_MASUK_PATH', $_ENV['FILE_MASUK_PATH']);
define('FILE_KELUAR_PATH', $_ENV['FILE_KELUAR_PATH']);
define('ALGORITHM', $config['algorithm']);
define('BASE_DIR', $config['base_dir']);
define('JWT_ISSUER', $config['jwt_issuer']);
define('JWT_AUDIENCE', $config['jwt_audience']);
define('LOG_FILE_PATH', $_ENV['LOG_FILE_PATH']);

// Generate a JWT token (for initial setup, you can run this part separately)
// for machine to machine extend exp menjadi 1 tahun
function generateToken($username) {
    $payload = [
        'iss' => JWT_ISSUER, // Issuer
        'aud' => JWT_AUDIENCE, // Audience
        'iat' => time(), // Issued at
        'nbf' => time(), // Not before
        'exp' => time() + 31536000 , //setahun // 3600, // Expiry (1 hour)
        'data' => [ 'username' => $username ]
    ];
    return JWT::encode($payload, SECRET_KEY, ALGORITHM);
}

// Function to log request information
function logRequestInfo($message) {
    // Get current timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Get client IP address
    $clientIp = $_SERVER['REMOTE_ADDR'];

    // Get headers and filter out sensitive ones
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        unset($headers['Authorization']);
    }

    // Build log message
    // $logMessage = "[$timestamp] - $clientIp - $message - Headers: " . json_encode($headers) . PHP_EOL;
    $logMessage = "[$timestamp] - $clientIp - $message " . PHP_EOL;

    // Write log message to file
    error_log($logMessage, 3, LOG_FILE_PATH);
}


// Authentication
function authenticate() {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['message' => 'Authorization header not found']);
        logRequestInfo('Authorization header not found');
        exit;
    }

    $authHeader = $headers['Authorization'];
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    if (!$jwt) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['message' => 'Token not found in header']);
        logRequestInfo('Token not found in header');
        exit;
    }

    try {      
        $decoded = JWT::decode($jwt, new Key(SECRET_KEY, ALGORITHM));
        logRequestInfo('User authenticated: ' . $decoded->data->username);
        return $decoded->data->username;
    } catch (Exception $e) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['message' => 'Token invalid or expired']);
        logRequestInfo('Token invalid or expired');
        exit;
    }
}

