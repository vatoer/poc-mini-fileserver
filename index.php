<?php
require 'vendor/autoload.php';
// Include the common functions file
require_once 'common.php';

// List files
function listFiles() {
    $files = scandir(BASE_DIR);
    $files = array_diff($files, array('.', '..'));
    return $files;
}

// Serve file for download
function serveFile($filename,$inout) {
    logRequestInfo('serveFile: ' . $filename);

    $filepath='NON_EXISTING_FILE_PATH';

    if($inout!='masuk' && $inout!='keluar'){
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['message' => 'Invalid in/out parameter']);
        logRequestInfo('serveFile: Invalid in/out parameter' . $filename);
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
        logRequestInfo('serveFile: complete');
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['message' => 'File not found']);
        logRequestInfo('message File not found');
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
    logRequestInfo("method is not allowed");
    exit;
}

//{
//     $files = listFiles();
//     echo json_encode($files);
// }

