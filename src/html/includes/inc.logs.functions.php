<?php
    require_once(__DIR__ . '/../models/LogHandler.php');

    function getLogFiles() {
        $logDir = '/var/www/services/logs/';

        $files = array_values(array_diff(scandir($logDir), ['.', '..', '.keep']));
        
        return $files;
    }

    function tailFile($file, $lines = 50) {
        $filePath = '/var/www/services/logs/' . $file;

        if (!file_exists($filePath)) {
            LogHandler::stdlog("Failed to open $filePath", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }

        $buffer = 4096;
        $f = fopen($filePath, "r");
        fseek($f, 0, SEEK_END);
        $pos = ftell($f);
        $data = '';
        $lineCount = 0;
    
        while ($lineCount < $lines && $pos > 0) {
            $read = min($buffer, $pos);
            $pos -= $read;
            fseek($f, $pos);
            $data = fread($f, $read) . $data;
            $lineCount = substr_count($data, "\n");
        }
    
        fclose($f);

        if (empty($data)) $data = 'Arquivo de log vazio...';

        $dataLines = explode("\n", $data);
        $lastLines = array_slice($dataLines, -$lines);
        
        return implode("\n", $lastLines);
    }

    function compressLogFile($file, $outputZip) {
        $filePath = '/var/www/services/logs/' . $file;
        if (!file_exists($filePath)) {
            LogHandler::stdlog("Failed to open $filePath", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($outputZip, ZipArchive::CREATE) !== true) {
            LogHandler::stdlog("Can't create and open $outputZip", LogHandler::ERROR_TYPE_ERROR);
            return false;
        }

        $zip->addFile($filePath, basename($filePath));
        $zip->close();

        return $outputZip;
    }
    