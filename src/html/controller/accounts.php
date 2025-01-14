<?php

    require_once(__DIR__ . "/../models/AccountDao.php");
    require(__DIR__ . '/../includes/inc.functions.php');

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        sendResponse(['message' => 'Invalid request method, only POST is accepted.', 'code' => 'AC01'], 400);
        exit;
    }

    if (!isset($_SERVER["CONTENT_TYPE"]) || ($_SERVER["CONTENT_TYPE"] !== "application/json")) {
        sendResponse(['message' => 'Invalid Content-Type', 'code' => 'AC02'], 400);
        exit;
    }

    $rawData = file_get_contents("php://input");
    $json = json_decode($rawData, true);

    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {

        sendResponse(['message' => 'Invalid JSON.', 'code' => 'AC03'], 400);
        exit;
    }

    if(isset($json['action'])) {
        $inputAction = filter_var($json['action'], FILTER_SANITIZE_SPECIAL_CHARS);

        if ($inputAction === false) {
            sendResponse(["message" => "The problem it's not you, it's me! :("], 500);
            LogHandler::stdlog("Client IP '". $_SERVER['REMOTE_ADDR'] ."' sent an invalid action to server", LogHandler::ERROR_TYPE_WARNING);
            exit;   
        }

        $accountDao = new AccountDAO();

        switch ($json['action']) {
            case 'fetchAvailableAccounts':

                try{            
                    $accounts = $accountDao->fetchAccounts();
                } catch (DAOException $daoError) {
                    LogHandler::stdlog("A database error occurred at 'fetchAvailableAccounts'", LogHandler::ERROR_TYPE_ERROR);
                    sendResponse(["message" => "It's not you! :("], 500);
                    exit;
                }
                
                $resp = ["totalaccounts" => count($accounts)];
                foreach ($accounts as $acc) {
                    $resp[$acc['accountid']] = [
                        "name" => $acc['accountname'],
                        "bank" => $acc['bankid'],
                        "bankName" => $acc['bankname'],
                        "branchNumber" => $acc['branchnumber'],
                        "accountNumber" => $acc['accountnumber']
                    ];
                };

                sendResponse($resp, 200);
            break;

            case 'createNewAccount':
                $authorization = $_COOKIE['jwtToken'] ?? '';
                
                if ($authorization != '') {

                    if (checkToken($authorization)) {
                        $bankId = filter_var($json['bankId'], FILTER_VALIDATE_INT);
                        $branchNumber = filter_var($json['branchNumber'], FILTER_SANITIZE_SPECIAL_CHARS);
                        $accountNumber = filter_var($json['accountNumber'], FILTER_SANITIZE_SPECIAL_CHARS);
                        $accountName = filter_var($json['accountName'], FILTER_SANITIZE_SPECIAL_CHARS);
        
                        if ($bankId && $branchNumber && $accountNumber) {
                            
                            try{
        
                                $accountDao->newAccount($bankId, trim($branchNumber), trim($accountNumber), trim($accountName));
                            } catch (DAOException $daoError) {
                                
                                switch ($daoError->getCode()){
                                    case 10:
                                        LogHandler::stdlog("A database error occurred at 'createNewAccount'", LogHandler::ERROR_TYPE_CRITICAL);
                                        sendResponse(["message" => "It's not you! :("], 500);
                                    break;

                                    case 11:
                                        sendResponse(["message" => "This account number or account name already exists", "code" => "AC10.3"], 400);
                                    break;
                                }
                                exit;
                            }
        
                            $respPayload = [
                                "bankId" => $bankId,
                                "branchNumber" => $branchNumber,
                                "accountNumber" => $accountNumber,
                                "accountName" => $accountName
                            ];
        
                            sendResponse($respPayload, 201);
                        } else {
                            
                            LogHandler::stdlog("[createNewAccount] Client '". $_SERVER['REMOTE_ADDR'] ."' sent an invalid payload", LogHandler::ERROR_TYPE_WARNING);
                            sendResponse(["message" => "Invalid Payload", 'code' => 'AC10'], 400);
                        }

                    } else {
                        LogHandler::stdlog("[createNewAccount] '". $_SERVER['REMOTE_ADDR'] ."' authentication failed", LogHandler::ERROR_TYPE_WARNING);
                        sendResponse(["message" => "Authentication failed", "code" => "AC10.2"], 400);
                        exit;
                    }

                } else {
                    LogHandler::stdlog("[createNewAccount] '". $_SERVER['REMOTE_ADDR'] ."' authentication missing", LogHandler::ERROR_TYPE_WARNING);
                    sendResponse(["message" => "Authentication missing", "code" => "AC10.1"], 400);
                    exit;
                }
            break;

            case 'deleteAccount':
                $authorization = $_COOKIE['jwtToken'] ?? '';

                if ($authorization != '') {
                    if (checkToken($authorization)) {
                        $accountId = filter_var($json['accountId'], FILTER_VALIDATE_INT);

                        if ($accountId) {
                            
                            try{
                                $accountDao->deleteAccount($accountId);
                            } catch (DAOException $daoError) {
                                LogHandler::stdlog("A database error occurred at 'createNewAccount'", LogHandler::ERROR_TYPE_CRITICAL);
                                sendResponse(["message" => "It's not you! :("], 500);
                            }

                            sendResponse(["message" => "Account deleted"], 200);
                        } else {
                            LogHandler::stdlog("[deleteAccount] Client '". $_SERVER['REMOTE_ADDR'] ."' sent an invalid payload", LogHandler::ERROR_TYPE_WARNING);
                            sendResponse(["message" => "Invalid Payload", 'code' => 'AC20'], 400);
                            exit;
                        }
                    } else {
                        LogHandler::stdlog("[deleteAccount] '". $_SERVER['REMOTE_ADDR'] ."' authentication failed", LogHandler::ERROR_TYPE_WARNING);
                        sendResponse(["message" => "Authentication failed", "code" => "AC20.2"], 400);
                        exit;
                    }
                } else {
                    LogHandler::stdlog("[deleteAccount] '". $_SERVER['REMOTE_ADDR'] ."' authentication missing", LogHandler::ERROR_TYPE_WARNING);
                    sendResponse(["message" => "Authentication missing", "code" => "AC20.1"], 400);
                    exit;
                }
            break;

            case 'fetchAccountDetail':
                $authorization = $_COOKIE['jwtToken'] ?? '';

                if ($authorization != '') {
                    if (checkToken($authorization)) {
                        $accountId = filter_var($json['accountId'], FILTER_VALIDATE_INT);

                        if ($accountId) {
                            
                            try{
                                $accountDetail = $accountDao->fetchAccountDetail($accountId);
                            } catch (DAOException $daoError) {
                                LogHandler::stdlog("A database error occurred at 'createNewAccount'", LogHandler::ERROR_TYPE_CRITICAL);
                                sendResponse(["message" => "It's not you! :("], 500);
                            }
                            
                            if ($accountDetail) {

                                if (!is_null($accountDetail['certfile'])){
                                    $certInfo = getCertificateData($accountDetail['certfile'], '/var/www/services/certs/inter/');

                                    if ($certInfo) {
                                        $certificate = $certInfo['CN'] . " - " . $certInfo['VALID_TO'];
                                    } else {
                                        $certificate = 'Não foi possivel carregar o certificado';
                                    }
                                } else {
                                    $certificate = null;
                                }

                                if (!is_null($accountDetail['certfile']) && !is_null($accountDetail['certkeyfile'])) {
                                    $checkCert = checkPrivateKey($accountDetail['certfile'], $accountDetail['certkeyfile'], '/var/www/services/certs/inter/');

                                    if ($checkCert) {
                                        $validCertificate = "Assinatura válida";
                                    } else {
                                        $validCertificate = "Assinatura inválida";
                                    }
                                } else {
                                    $validCertificate = null;
                                }

                                $payload = [
                                    "accountName" => $accountDetail['accountname'],
                                    "clientId" => $accountDetail['clientid'],
                                    "clientSecret" => $accountDetail['clientsecret'],
                                    "certFile" => $certificate,
                                    "certKeyFile" => $validCertificate,
                                    "ignoredSenders" => $accountDetail['ignoredsenders']
                                ];
                            } else {
                                $payload = [
                                    "accountName" => '',
                                    "clientId" => '',
                                    "clientSecret" => '',
                                    "certFile" => '',
                                    "certKeyFile" => '',
                                    "ignoredSenders" => ''
                                ];
                            }

                            sendResponse($payload, 200);
                            
                        } else {
                            LogHandler::stdlog("[fetchAccountDetail] Client '". $_SERVER['REMOTE_ADDR'] ."' sent an invalid payload", LogHandler::ERROR_TYPE_WARNING);
                            sendResponse(["message" => "Invalid Payload", 'code' => 'AC30'], 400);
                            exit;
                        }
                    } else {
                        LogHandler::stdlog("[fetchAccountDetail] '". $_SERVER['REMOTE_ADDR'] ."' authentication failed", LogHandler::ERROR_TYPE_WARNING);
                        sendResponse(["message" => "Authentication failed", "code" => "AC30.2"], 400);
                        exit;
                    }
                } else {
                    LogHandler::stdlog("[fetchAccountDetail] '". $_SERVER['REMOTE_ADDR'] ."' authentication missing", LogHandler::ERROR_TYPE_WARNING);
                    sendResponse(["message" => "Authentication missing", "code" => "AC30.1"], 400);
                    exit;
                }
            break;

            case 'updateAccountDetail':
                $authorization = $_COOKIE['jwtToken'] ?? '';

                if ($authorization != '') {
                    if (checkToken($authorization)) {
                        $accountId = filter_var($json['accountId'], FILTER_VALIDATE_INT);
                        $clientId = filter_var($json['clientId'], FILTER_SANITIZE_SPECIAL_CHARS);
                        $clientSecret = filter_var($json['clientSecret'], FILTER_SANITIZE_SPECIAL_CHARS);
                        $ignoredSenders = filter_var($json['ignoredSenders'], FILTER_SANITIZE_SPECIAL_CHARS);
                        $certFile = filter_var($json['certFile'], FILTER_SANITIZE_SPECIAL_CHARS);
                        $certKeyFile = filter_var($json['certKeyFile'], FILTER_SANITIZE_SPECIAL_CHARS);

                        if ($accountId) {
                            
                            if (isset($certFile)) {
                                $certFileName = bin2hex(random_bytes(16)) . ".crt";
                            } else {
                                $certFileName = '';
                            }

                            if (isset($certKeyFile)) {
                                $certKeyFileName = bin2hex(random_bytes(16)) . ".key";
                            } else {
                                $certKeyFileName = '';
                            }

                            if (!preg_match('/^(\d+)(;\d+)*$/', $ignoredSenders)) {
                                LogHandler::stdlog("Ignored sender list with wrong format for account id '". $accountId ."'", LogHandler::ERROR_TYPE_ERROR);
                                sendResponse(["message" => "Ignored sender list with wrong format", "code" => "AC40.5"], 400);
                                exit;
                            }

                            try{    
                                $accountDao->updateAccountDetail($accountId, $clientId, $clientSecret, $certFileName, $certKeyFileName, $ignoredSenders);
                            } catch (DAOException $daoError) {
                                LogHandler::stdlog("A database error occurred at 'createNewAccount'", LogHandler::ERROR_TYPE_CRITICAL);
                                sendResponse(["message" => "It's not you! :("], 500);
                                exit;
                            }

                            if ($certFileName !== '') {
                                if (!decodeCertificateFile($certFileName, $certFile, '/var/www/services/certs/inter/')){
                                    LogHandler::stdlog("Failed to save certificate file to disk for account id '". $accountId ."'", LogHandler::ERROR_TYPE_ERROR);
                                    sendResponse(["message" => "Failed to save certificate file to disk, try upload again.", "code" => "AC40.3"], 400);
                                    exit;
                                }
                            }

                            if ($certKeyFileName !== '') {
                                if (!decodeCertificateFile($certKeyFileName, $certKeyFile, '/var/www/services/certs/inter/')){
                                    LogHandler::stdlog("Failed to save certificate key file to disk for account id '". $accountId ."'", LogHandler::ERROR_TYPE_ERROR);
                                    sendResponse(["message" => "Failed to save certificate key file to disk, try upload again.", "code" => "AC40.4"], 400);
                                    exit;
                                }
                            }

                            sendResponse(["message" => "Updated"], 200);
                            
                        } else {
                            LogHandler::stdlog("[updateAccountDetail] Client '". $_SERVER['REMOTE_ADDR'] ."' sent an invalid payload", LogHandler::ERROR_TYPE_WARNING);
                            sendResponse(["message" => "Invalid Payload", 'code' => 'AC40'], 400);
                            exit;
                        }
                    } else {
                        LogHandler::stdlog("[updateAccountDetail] '". $_SERVER['REMOTE_ADDR'] ."' authentication failed", LogHandler::ERROR_TYPE_WARNING);
                        sendResponse(["message" => "Authentication failed", "code" => "AC40.2"], 400);
                        exit;
                    }
                } else {
                    LogHandler::stdlog("[updateAccountDetail] '". $_SERVER['REMOTE_ADDR'] ."' authentication missing", LogHandler::ERROR_TYPE_WARNING);
                    sendResponse(["message" => "Authentication missing", "code" => "AC40.1"], 400);
                    exit;
                }
            break;

            default:
                sendResponse(["message" => "Undefined action", 'code' => 'AC99'], 400);
        }
    } else {
        sendResponse(["message" => "Missing action event", "code" => "AC04"], 400);
    }
?>