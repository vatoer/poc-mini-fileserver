<?php
require 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use Dotenv\Dotenv;


// Load the .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load configuration
$config = require 'config.php';
define('SECRET_KEY', $_ENV['JWT_SECRET_KEY']);
define('ALGORITHM', $config['algorithm']);
define('BASE_DIR', $config['base_dir']);
define('JWT_ISSUER', $config['jwt_issuer']);
define('JWT_AUDIENCE', $config['jwt_audience']);

// Generate a JWT token (for initial setup, you can run this part separately)
function generateToken($username) {
    $payload = [
        'iss' => JWT_ISSUER, // Issuer
        'aud' => JWT_AUDIENCE, // Audience
        'iat' => time(), // Issued at
        'nbf' => time(), // Not before
        'exp' => time() + 3600, // Expiry (1 hour)
        'data' => [ 'username' => $username ]
    ];
    return JWT::encode($payload, SECRET_KEY, ALGORITHM);
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Only POST method is allowed"]);
    exit;
}

if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Username and password are required"]);
    exit;
}

// Dummy credentials for example purposes
$valid_username = 'eoffice';
$valid_password = 'password';

$hashedPassword = $_ENV['USER_SECRET_KEY'];

if ($data->username === $valid_username && password_verify($data->password, $hashedPassword)) {

    $jwt =  generateToken($data->username);

    http_response_code(200);
    echo json_encode([
        'message' => 'Successful login.',
        'jwt' => $jwt
    ]);
} else {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access"]);
}
