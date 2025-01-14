<?php
    require_once(__DIR__ . '/../models/TransactionsDao.php');
    require_once(__DIR__ . '/../includes/inc.functions.php');

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        sendResponse(['message' => 'Invalid request method, only POST is accepted.', 'code' => 'TR01'], 400);
        exit;
    }
    
    if (!isset($_SERVER["CONTENT_TYPE"]) || ($_SERVER["CONTENT_TYPE"] !== "application/json")) {
    
        sendResponse(['message' => 'Invalid Content-Type.', 'code' => 'TR02'], 400);
        exit;
    }
    
    $rawData = file_get_contents("php://input");
    $json = json_decode($rawData, true);
    
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {

        sendResponse(['message' => 'Invalid JSON.', 'code' => 'TR03'], 400);
        exit;
    }

    if (isset($json['action'])) {
        $inputAction = filter_var($json['action'], FILTER_SANITIZE_SPECIAL_CHARS);

        if ($inputAction === false) {
            sendResponse(["message" => "The problem it's not you, it's me! :("], 500);
            LogHandler::stdlog("Client IP '". $_SERVER['REMOTE_ADDR'] ."' sent an invalid action to server", LogHandler::ERROR_TYPE_WARNING);
            exit;   
        }

        // Create Transaction Object
        $transactionsDao = new TransactionsDAO();

        switch ($json['action']) {
            case 'fetchTransactions':
                $accountID = filter_var($json['accountId'], FILTER_VALIDATE_INT);
                $startDate = filter_var($json['startDate'], FILTER_SANITIZE_SPECIAL_CHARS);
                $endDate = filter_var($json['endDate'], FILTER_SANITIZE_SPECIAL_CHARS);
                $page = filter_var($json['page'], FILTER_VALIDATE_INT);

                if ($accountID === false) {
                    LogHandler::stdlog("Client '" . $_SERVER['REMOTE_ADDR'] . "' miss account id to fetch transaction list", LogHandler::ERROR_TYPE_ERROR);
                    sendResponse(["message" => "Missing account ID", "code" => "TR10"], 400);
                    exit;
                }

                if ($startDate === false || !strtotime($startDate)) {
                    $startDate = date('Y-m-d');
                }

                if ($endDate === false || !strtotime($endDate)) {
                    $endDate = date('Y-m-d');
                }

                $currentDate = strtotime(date('Y-m-d'));
                $startDateTimestamp = strtotime($startDate);
                $endDateTimestamp = strtotime($endDate);

                if ($startDateTimestamp > $currentDate) {
                    sendResponse(["message" => "Start date cannot be greater than the current date", "code" => "TR10.1"], 400);
                    exit;
                }

                if ($endDateTimestamp > $currentDate) {
                    sendResponse(["message" => "End date cannot be greater than the current date", "code" => "TR10.2"], 400);
                    exit;
                }

                if ($startDateTimestamp > $endDateTimestamp) {
                    sendResponse(["message" => "Start date cannot be greater than end date", "code" => "TR10.3"], 400);
                    exit;
                }

                if ($page === false) {
                    $page = 1;
                }

                try{
                    $transactions = $transactionsDao->fetchTransactionList($startDate, $endDate, $accountID, $page);
                } catch (DAOException $daoError) {
                    sendResponse(["message" => "The problem it's not you, it's me! :("], 500);
                    LogHandler::stdlog("A database error occurred at 'fetchLastTransactions'", LogHandler::ERROR_TYPE_ERROR);
                    exit;
                }

                sendResponse($transactions, 200);
                break;
            
            case 'fetchTransactionData':
                $e2eid = filter_var($json['e2eid'], FILTER_SANITIZE_SPECIAL_CHARS);

                if ($e2eid === false) {
                    LogHandler::stdlog("Client '" . $_SERVER['REMOTE_ADDR'] . "' miss e2eid for transation detail", LogHandler::ERROR_TYPE_ERROR);
                    sendResponse(["message" => "Missing E2EID", "code" => "TR20"], 400);
                    exit;
                }

                try{
                    $transactions = $transactionsDao->fetchPixTransaction($e2eid);
                } catch (DAOException $daoError) {
                    sendResponse(["message" => "The problem it's not you, it's me! :("], 500);
                    LogHandler::stdlog("A database error occurred at 'fetchTransactionData'", LogHandler::ERROR_TYPE_ERROR);
                    exit;
                }

                sendResponse($transactions, 200);
                break;
            
            case 'filterBySender':
                $payerdoc = filter_var($json['payerdoc'], FILTER_SANITIZE_NUMBER_INT);
                $accountID = filter_var($json['accountId'], FILTER_VALIDATE_INT);

                if ($payerdoc === false || $accountID === false) {
                    LogHandler::stdlog("Client '" . $_SERVER['REMOTE_ADDR'] . "' miss payer doc or account id to filter by sender", LogHandler::ERROR_TYPE_ERROR);
                    sendResponse(["message" => "Missing payload", "code" => "TR30"], 400);
                    exit;  
                }

                try{
                    $transactions = $transactionsDao->filterTransactionBySender($accountID, $payerdoc);
                } catch (DAOException $daoError) {
                    sendResponse(["message" => "The problem it's not you, it's me! :("], 500);
                    LogHandler::stdlog("A database error occurred at 'fetchTransactionData'", LogHandler::ERROR_TYPE_ERROR);
                    exit;                    
                }

                sendResponse($transactions, 200);
                break;
            
            case 'fetchLastTransactions':
                $accountID = filter_var($json['accountId'], FILTER_VALIDATE_INT);
                $timestamp = filter_var($json['timestamp'], FILTER_SANITIZE_SPECIAL_CHARS);
                
                if ($timestamp === false || $accountID === false) {
                    LogHandler::stdlog("Client '" . $_SERVER['REMOTE_ADDR'] . "' miss account id or timestamp", LogHandler::ERROR_TYPE_ERROR);
                    sendResponse(["message" => "Missing payload", "code" => "T40"], 400);
                    exit;  
                }

                if (!strtotime($timestamp)) {
                    LogHandler::stdlog("Client '" . $_SERVER['REMOTE_ADDR'] . "' sent a invalid timestamp", LogHandler::ERROR_TYPE_ERROR);
                    sendResponse(["message" => "Invalid timestamp", "code" => "T40.1"], 400);
                    exit; 
                }

                try {
                    $transactions = $transactionsDao->fetchLastTransactions($accountID, $timestamp);
                } catch (DAOException $daoError) {
                    sendResponse(["message" => "The problem it's not you, it's me! :("], 500);
                    LogHandler::stdlog("A database error occurred at 'fetchLastTransactions'", LogHandler::ERROR_TYPE_ERROR);
                    exit;
                }

                sendResponse($transactions, 200);
                break;
            
            default:
                sendResponse(["message" => "Undefined action", 'code' => 'TR04'], 400);
        }
    } else {
        sendResponse(["message" => "Missing action event", "code" => "TR05"], 400);
    }