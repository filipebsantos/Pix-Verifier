<?php

class LogHandler
{
    // Debug type messages
    const ERROR_TYPE_INFO = 'INFO';
    const ERROR_TYPE_WARNING = 'WARNING';
    const ERROR_TYPE_ERROR = 'ERROR';
    const ERROR_TYPE_CRITICAL = 'CRITICAL';

    // Debug type colors for terminal print
    private const TYPE_INFO_COLOR = "\033[1;46m";
    private const TYPE_WARNING_COLOR = "\033[1;43m";
    private const TYPE_ERROR_COLOR = "\033[1;41m";
    private const RESET_TO_DEFAULT_COLOR = "\033[0m";

    public static function stdlog(string $text, string $type): void
    {
        switch ($type) {
            case self::ERROR_TYPE_INFO;
                $color = self::TYPE_INFO_COLOR;
                break;

            case self::ERROR_TYPE_WARNING;
                $color = self::TYPE_WARNING_COLOR;
                break;

            case self::ERROR_TYPE_ERROR;
            case self::ERROR_TYPE_CRITICAL;
                $color = self::TYPE_ERROR_COLOR;
                break;
                
            default:
                $color = self::RESET_TO_DEFAULT_COLOR;
        }

        $stdoutMsg = '[' . "\033[1;32m Pix-Verifier WEB \033[0m" . '][' . $color . $type . "\033[0m] " . $text . "\n";

        $stdout = fopen('php://stdout', 'w');
        fwrite($stdout, $stdoutMsg);
        fclose($stdout);
    }
}