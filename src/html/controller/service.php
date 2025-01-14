<?php

require_once(__DIR__ . '/../models/LogHandler.php');
require_once(__DIR__ . '/../includes/inc.functions.php');
require_once(__DIR__ . '/../models/Supervisord.php');

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

    $pixService = new Supervisord("pix-service");

    switch ($inputRequest) {
        case 'getStatus':
            
            if ($pixService->isRunning()) {
                sendResponse(['status' => 'running'], 200);
            } else {
                sendResponse(['status' => 'stopped'], 200);
            }
        break;

        case 'getProcess':
            
            $processData = $pixService->getProcessStatus();
            sendResponse($processData, 200);
            break;

        case 'startService':
            
            if ($pixService->startProcess()) {
                sendResponse(['message' => 'ok'], 200);
            } else {
                sendResponse(['message' => 'Can\'t start service'], 400);
            }
            break;

        case 'stopService':

            if ($pixService->stopProcess()) {
                sendResponse(['message' => 'ok'], 200);
            } else {
                sendResponse(['message' => 'Can\'t stop service'], 400);
            }
            break;

        default:
            sendResponse(['message' => 'Unknow request, please verify', 'code' => 'SRV10.1'], 400);
        break;
    }

} else {
    sendResponse(['message' => 'Missing request payload', 'code' => 'SRV04'], 400);
}