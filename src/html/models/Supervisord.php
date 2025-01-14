<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/LogHandler.php');

class Supervisord
{
    private $supervisor;
    private $process;

    public function __construct(string $processName)
    {
        $guzzleClient = new \GuzzleHttp\Client([
            'auth' => ['pixverifier', 'cdjIIdh19CtfZmaxKMAu3AbttDBsFsZDVtH4mLLE6A2qIPj8iVDM125qAHKpF8VK'],
        ]);

        $client = new fXmlRpc\Client(
            'http://127.0.0.1:9001/RPC2',
            new fXmlRpc\Transport\PsrTransport(
                new GuzzleHttp\Psr7\HttpFactory(),
                $guzzleClient
            )
        );

        $this->supervisor = new Supervisor\Supervisor($client);
        $this->process = $this->supervisor->getProcess($processName);
    }

    public function getProcessStatus() : array
    {   
        try {
            $processState = $this->process->getPayload();
        } catch (\Supervisor\Exception\Fault\SpawnErrorException $supervisorError) {
            LogHandler::stdlog("[Pix Service WEB]: " . $supervisorError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
        }

        switch ($processState['state']) {
            case '0':
                $status = "Parado";
                break;
            case '10':
                $status = "Iniciando";
                break;
            case '20':
                $status = "Em execução";
                break;
            case '30':
                $status = "Reiniciando";
                break;
            case '40':
                $status = "Parando";
                break;
            case '100':
                $status = "Interrompido";
                break;
            case '200':
                $status = "Erro fatal";
                break;
            case '1000':
                $status = "Desconhecido";
                break;
            default:
                $status = "Desconhecido";
        }

        return [
            "status" => $status,
            "description" => $processState['description']
        ];
    }

    public function isRunning() : bool
    {
        return $this->process->isRunning();
    }

    public function startProcess() : bool
    {   
        try {
            return $this->supervisor->startProcess($this->process->getPayload()['name']);
        } catch (\Throwable $supervisorError) {
            LogHandler::stdlog($supervisorError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
        }
        
    }

    public function stopProcess() : bool
    {
        try {
            return $this->supervisor->stopProcess($this->process->getPayload()['name']);
        } catch (\Throwable $supervisorError) {
            LogHandler::stdlog($supervisorError->getMessage(), LogHandler::ERROR_TYPE_CRITICAL);
        }
    }

    public function getAllProcesses() {
        return $this->supervisor->getAllProcessInfo();
    }

}
