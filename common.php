<?php
require 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

function loadEnvironmentVariables($retries = 3)
{
    while ($retries > 0) {
        try {
            $dotenv = Dotenv::createImmutable(__DIR__);
            $dotenv->load();

            // Validate critical environment variables
            $requiredKeys = ['JWT_SECRET_KEY', 'FILE_MASUK_PATH', 'FILE_KELUAR_PATH', 'LOG_FILE_PATH','PLAIN_PASSWORD','USER_SECRET_KEY','BASE_DIR','JWT_ISSUER','JWT_AUDIENCE'];
            foreach ($requiredKeys as $key) {
                if (!isset($_ENV[$key])) {
                    throw new Exception("$key is not set in the environment.");
                }
            }

            // Log successful loading
            error_log("Environment variables loaded successfully.");
            return true;
        } catch (Exception $e) {
            error_log("Failed to load .env file: " . $e->getMessage());
            $retries--;
            if ($retries <= 0) {
                throw new Exception("Could not load environment variables after multiple attempts.");
            }
        }
        // Add a small delay before retrying
        sleep(1);
    }
}

// Load the .env file
loadEnvironmentVariables();

// Load configuration
$config = require 'config.php';

define('ALGORITHM', $config['algorithm']);
define('SECRET_KEY', $_ENV['JWT_SECRET_KEY']);
define('USER_SECRET_KEY', $_ENV['USER_SECRET_KEY']);
define('FILE_MASUK_PATH', $_ENV['FILE_MASUK_PATH']);
define('FILE_KELUAR_PATH', $_ENV['FILE_KELUAR_PATH']);
define('PLAIN_PASSWORD', $_ENV['PLAIN_PASSWORD']);
define('BASE_DIR', $_ENV['BASE_DIR']);
define('JWT_ISSUER', $_ENV['JWT_ISSUER']);
define('JWT_AUDIENCE', $_ENV['JWT_AUDIENCE']);
define('LOG_FILE_PATH', $_ENV['LOG_FILE_PATH']);

// Generate a JWT token (for initial setup, you can run this part separately)
// for machine to machine extend exp menjadi 1 tahun
function generateToken($username)
{
    $payload = [
        'iss' => JWT_ISSUER, // Issuer
        'aud' => JWT_AUDIENCE, // Audience
        'iat' => time(), // Issued at
        'nbf' => time(), // Not before
        'exp' => time() + 31536000, //setahun // 3600, // Expiry (1 hour)
        'data' => ['username' => $username]
    ];
    return JWT::encode($payload, SECRET_KEY, ALGORITHM);
}

// Function to log request information
function logRequestInfo($message)
{
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
function authenticate()
{
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
