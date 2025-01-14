<?php

require_once(__DIR__ . '/../models/LogHandler.php');
require_once(__DIR__ . '/../includes/inc.functions.php');
require_once(__DIR__ . '/../includes/inc.logs.functions.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    sendResponse(['message' => 'Invalid request method, only POST is accepted', 'code' => 'SRV01'], 400);
    exit;
}

if (!isset($_SERVER["CONTENT_TYPE"]) || ($_SERVER["CONTENT_TYPE"] !== "application/json")) {

    sendResponse(['message' => 'Invalid Content-Type.', 'code' => 'SRV02'], 400);
    exit;
}

$rawData = file_get_contents("php://input");
$json = json_decode($rawData, true);

if ($json === null && json_last_error() !== JSON_ERROR_NONE) {

    sendResponse(['message' => 'Invalid JSON', 'code' => 'SRV03'], 400);
    exit;
}

if (isset($json['request'])) {
    $inputRequest = filter_var($json['request'], FILTER_SANITIZE_SPECIAL_CHARS);

    if ($inputRequest === false) {
        LogHandler::stdlog("[". $_SERVER['SCRIPT_FILENAME'] ."]Client '" . $_SERVER['REMOTE_ADDR'] . "' sent an invalid request.", LogHandler::ERROR_TYPE_ERROR);
        sendResponse(['message' => 'Invalid request', 'code' => 'SRV10'], 400);
        exit;
    }

    switch ($inputRequest) {
        case 'logtext':
                if (!isset($json['filename'])) {
                    sendResponse(["message" => "Filename not provided", "code" => "LOG10.2"], 400);
                    exit;
                }
                $fileName = filter_var($json['filename'], FILTER_SANITIZE_SPECIAL_CHARS);

                $logText = tailFile($fileName);

                if($logText) {
                    sendResponse(['logContent' => $logText], 200);
                } else {
                    sendResponse(["message" => "Failed to fetch log", "code" => "LOG10.3"], 400);
                }
            break;

        case 'logfile':
            if (!isset($json['filename'])) {
                sendResponse(["message" => "Filename not provided", "code" => "LOG10.2"], 400);
                exit;
            }
        
            $fileName = filter_var($json['filename'], FILTER_SANITIZE_SPECIAL_CHARS);
            $zipFile = '/tmp/' . $fileName . '.zip';
        
            if (compressLogFile($fileName, $zipFile)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);
        
                unlink($zipFile);
                exit;
            } else {
                sendResponse(["message" => "Failed to create ZIP file", "code" => "LOG10.4"], 400);
            }
            break;

        case 'loglist':
                sendResponse(getLogFiles(), 200);
            break;

        default:
            sendResponse(['message' => 'Unknow request, please verify', 'code' => 'LOG10.1'], 400);
            break;
    }

} else {
    sendResponse(['message' => 'Missing request payload', 'code' => 'LOG04'], 400);
}