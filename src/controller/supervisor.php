<?php
require(__DIR__ . '/../vendor/autoload.php');

// Create Guzzle HTTP client
$guzzleClient = new \GuzzleHttp\Client([
    'auth' => ['pixverifier', 'cdjIIdh19CtfZmaxKMAu3AbttDBsFsZDVtH4mLLE6A2qIPj8iVDM125qAHKpF8VK'],
]);

// Pass the URL and the Guzzle client to the fXmlRpc Client
$client = new fXmlRpc\Client(
    'http://127.0.0.1:9001/RPC2',
    new fXmlRpc\Transport\PsrTransport(
        new GuzzleHttp\Psr7\HttpFactory(),
        $guzzleClient
    )
);

// Pass the client to the Supervisor library.
$supervisor = new Supervisor\Supervisor($client);

// Returns Process object
$process = $supervisor->getProcess('pix-service');

// Function to get the status text based on state code
function getStatusText($stateCode) {
    switch ($stateCode) {
        case '0':
            return "Parado";
        case '10':
            return "Iniciando";
        case '20':
            return "Em Execução";
        case '30':
            return "Reiniciando";
        case '40':
            return "Parando";
        case '100':
            return "Interrompido";
        case '200':
            return "Erro Fatal";
        case '1000':
            return "Desconhecido";
        default:
            return "Desconhecido";
    }
}

// Ajax handling
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Ajax request received
    
    // Check if the request is specifically for isRunning
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['isRunning'])) {
        // Return only isRunning status
        header('Content-Type: application/json');
        echo json_encode(['isRunning' => $process->isRunning()]);
        exit; // End script after Ajax response
    }

    // Manipulation of form actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['start'])) {
            $supervisor->startProcess('pix-service', false);
        } elseif (isset($_POST['stop']) && $process->isRunning()) {
            $supervisor->stopProcess('pix-service');
        }   
    }

    // Get log data
    $logDir = $_SERVER['DOCUMENT_ROOT'] . '/services/logs';
    $logFileName = 'pix-service.log';
    $files = glob($logDir . '/' . $logFileName);

    if (!empty($files)) {
        // Sort files by modification date (most recent first)
        array_multisort(array_map('filemtime', $files), SORT_DESC, $files);

        // Get the path of the latest file
        $latestFile = reset($files);

        // Read the last 20 lines of the file
        $lastLines = implode('', array_slice(file($latestFile), -11));
    } else {
        $lastLines = null;
    }

    // Prepare data to be sent as JSON response
    $responseData = [
        'serviceName' => $process->getPayload()['name'],
        'status' => getStatusText($process->getPayload()['state']),
        'description' => $process->getPayload()['description'],
        'lastLines' => $lastLines,
    ];

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($responseData);
    exit; // End script after Ajax response
}
?>