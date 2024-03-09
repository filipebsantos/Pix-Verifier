<?php

    function formatCnpjCpf($value)
    {
        $CPF_LENGTH = 11;
        $cnpj_cpf = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === $CPF_LENGTH) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }

    function saveCertificateFile($postFile, $name) {
        if (is_uploaded_file($postFile['tmp_name'])){
            $certPath = $_SERVER['DOCUMENT_ROOT'] . '/services/certs/inter/';
            
            if (move_uploaded_file($postFile['tmp_name'], $certPath . $name)){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function getCertificateData($name){
        $certPath = $_SERVER['DOCUMENT_ROOT'] . '/services/certs/inter/';

        if (file_exists($certPath . $name)){
            $certRawData = openssl_x509_parse(file_get_contents($certPath . $name));
            $certData = [
                "CN" => $certRawData['subject']['CN'],
                "ISSUER" => $certRawData['issuer']['CN'],
                "VALID_TO" => date('d/m/Y H:i:s', $certRawData['validTo_time_t'])
            ];
            return $certData;
        } else {
            return false;
        }
    }

    function checkPrivateKey(){
        $certPath = $_SERVER['DOCUMENT_ROOT'] . '/services/certs/inter/';
        $cert_file = $certPath . 'inter_cert.crt';
        $cert_key = $certPath . 'inter_cert.key';

        if (file_exists($cert_file) && file_exists($cert_key)) {

            if (openssl_x509_check_private_key(file_get_contents($cert_file), file_get_contents($cert_key))){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }