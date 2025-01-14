<?php
    require_once(__DIR__ . "/../models/LogHandler.php");

    define('JWT_SECRET_KEY', 'b3f5a2c4e8d9f0b7a1c2e3d4f5b6a7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3g4');

    function formatCnpjCpf($value)
    {
        $CPF_LENGTH = 11;
        $cnpj_cpf = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === $CPF_LENGTH) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }

    function getCertificateData($filename, $filePath) {
        $fullPath = $filePath . $filename;
    
        // Verifique se o arquivo existe
        if (!file_exists($fullPath)) {
            LogHandler::stdlog("Certificate not found: $fullPath", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }
    
        // Tente ler o conteúdo do arquivo
        $certContent = file_get_contents($fullPath);
        if ($certContent === false) {
            LogHandler::stdlog("Error while trying to read file content: $fullPath", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }
    
        // Tente fazer o parse do certificado
        $certRawData = openssl_x509_parse($certContent);
        if ($certRawData === false) {
            while ($msg = openssl_error_string()) {
                LogHandler::stdlog("OpenSSL Error: $msg", LogHandler::ERROR_TYPE_ERROR);
            }
            LogHandler::stdlog("Error while trying to read certificate content: $fullPath", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }
    
        // Monte os dados do certificado
        $certData = [
            "CN" => $certRawData['subject']['CN'] ?? "CN não disponível",
            "VALID_TO" => isset($certRawData['validTo_time_t']) 
                ? date('d/m/Y H:i:s', $certRawData['validTo_time_t']) 
                : "Validade não encontrada"
        ];
    
        return $certData;
    }
    

    function checkPrivateKey($certificateFileName, $certificateKeyFileName, $filePath) {

        // Verifique se o arquivo existe
        if (!file_exists($filePath . $certificateFileName)) {
            LogHandler::stdlog("Certificate not found: $certificateFileName", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }

        if (!file_exists($filePath . $certificateKeyFileName)) {
            LogHandler::stdlog("Certificate Key not found: $certificateKeyFileName", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }
    
        if (openssl_x509_check_private_key(file_get_contents($filePath . $certificateFileName), file_get_contents($filePath . $certificateKeyFileName))){
            return true;
        } else {
            return false;
        }
    }

    function sendResponse(array $response, int $httpCode) {
        http_response_code($httpCode);
        header("Content-Type: application/json");
        echo json_encode($response);
    }

    function safeUrlBase64encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function safeUrlBase64decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    function encodeJWT(array $payload) : string 
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $encodedHeader = safeUrlBase64encode(json_encode($header));

        $encodedPayload = safeUrlBase64encode(json_encode($payload));

        $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", JWT_SECRET_KEY, true);
        $encodedSignature = safeUrlBase64encode($signature);

        return "$encodedHeader.$encodedPayload.$encodedSignature";
    }

    function verifyJWT(string $jwtToken)
    {
        $tokenParts = explode('.', $jwtToken);
        if (count($tokenParts) !== 3) {
            return false; // Invalid format
        }

        [$tokenHeader, $tokenPayload, $tokenSignature] = $tokenParts;
        $signature = hash_hmac('sha256', "$tokenHeader.$tokenPayload", JWT_SECRET_KEY, true);

        if(!hash_equals($tokenSignature, safeUrlBase64encode($signature))) {
            LogHandler::stdlog("Invalid signature for token [". $jwtToken ."]", LogHandler::ERROR_TYPE_WARNING);
            return false; // Invalid signature
        }

        $payload = json_decode(safeUrlBase64decode($tokenPayload), true);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Expired token
        }

        return $payload;
    }

    function checkToken(string $token) 
    {

        if(verifyJWT($token)) {
            return true;
        } else {
            return false;
        }
    }

    function decodeCertificateFile(string $filename ,string $encodedFile, string $path) 
    {
        $decodedFile = base64_decode($encodedFile);
        if ($decodedFile === false) {return false;}

        $filePath = $path . $filename;
        if (file_put_contents($filePath, $decodedFile) !== false) {
            return true;
        } else {
            return false;
        }
    }