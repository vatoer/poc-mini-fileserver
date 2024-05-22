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

// Authentication
function authenticate() {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['message' => 'Authorization header not found']);
        exit;
    }

    $authHeader = $headers['Authorization'];
    list($jwt) = sscanf($authHeader, 'Bearer %s');

    if (!$jwt) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['message' => 'Token not found in header']);
        exit;
    }

    try {      
        $decoded = JWT::decode($jwt, new Key(SECRET_KEY, ALGORITHM));
        return $decoded->data->username;
    } catch (Exception $e) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['message' => 'Token invalid or expired']);
        exit;
    }
}

// List files
function listFiles() {
    $files = scandir(BASE_DIR);
    $files = array_diff($files, array('.', '..'));
    return $files;
}

// Serve file for download
function serveFile($filename,$inout) {
    $filepath='NON_EXISTING_FILE_PATH';

    if($inout!='masuk' && $inout!='keluar'){
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['message' => 'Invalid in/out parameter']);
        exit;
    }

    if($inout == 'masuk'){
        $filepath = FILE_MASUK_PATH . DIRECTORY_SEPARATOR . $filename;
    } else {
        $filepath = FILE_KELUAR_PATH . DIRECTORY_SEPARATOR . $filename;
    }

    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filepath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['message' => 'File not found']);
        exit;
    }
}

// Main script
header('Content-Type: application/json');
//echo generateToken('eoffice');
//exit;
$username = authenticate();

if (isset($_GET['file']) && isset($_GET['inout'])) {
    serveFile($_GET['file'],$_GET['inout']);
 } 
else { 
    http_response_code(405);
    echo json_encode(["message" => "method is not allowed"]);
}

//{
//     $files = listFiles();
//     echo json_encode($files);
// }
?>
