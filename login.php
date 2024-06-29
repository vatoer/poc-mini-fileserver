<?php
// Include the common functions file
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use Dotenv\Dotenv;
require_once 'common.php';

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

// Check if the username and password are correct
if ($data->username === $valid_username && password_verify($data->password, USER_SECRET_KEY)) {
    $jwt =  generateToken($data->username);
    logRequestInfo("Successful login.");
    http_response_code(200);
    echo json_encode([
        'message' => 'Successful login.',
        'jwt' => $jwt
    ]);
} else {
    logRequestInfo("Unauthorized access.");
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access"]);
}
