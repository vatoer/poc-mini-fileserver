<?php

// Include the common functions file
require_once 'common.php';

// Main script
header('Content-Type: application/json');
//echo generateToken('eoffice');
//exit;
$username = authenticate();

function handleUpload($file ,$inout) {        

    $filePath='NON_EXISTING_FILE_PATH';

    if($inout!='masuk' && $inout!='keluar'){
        header('HTTP/1.0 404 Not Found');
        logRequestInfo('Invalid in/out parameter');
        exit;
    }

    $filename = $file['name'];

    if($inout == 'masuk'){
        $filePath = FILE_MASUK_PATH . DIRECTORY_SEPARATOR . $filename;
    } else {
        $filePath = FILE_KELUAR_PATH . DIRECTORY_SEPARATOR . $filename;
    }

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(["message" => "File uploaded successfully"]);
        logRequestInfo("File uploaded: " . $filename);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to upload file"]);
        logRequestInfo("Failed to upload file: " . $filename);
    }
}

// Check if the request method is POST and the file is uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['inout'])) {
    handleUpload($_FILES['file'],$_POST['inout']);
} else {
    http_response_code(405);
    echo json_encode(["message" => "Only POST method is allowed"]);
    logRequestInfo('Only POST method is allowed');
    exit;
}