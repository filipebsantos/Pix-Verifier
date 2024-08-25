<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pix Verifier</title>
    <link rel="stylesheet" href="../../../css/bootstrap.min.css">
    <script src="../../../js/jquery-3.6.4.min.js"></script>
</head>
<body>
    <div class="container-fluid mt-2">
        <div class="row">
            <div class="col-9">
                <span id="serviceName">Serviço: </span>
                <br>
                <span id="status">Status: </span> - <span id="description" class="fw-light"></span>
            </div>
            <div class="col-3  d-flex align-items-center justify-content-evenly">
                <button id="startService" type="button" class="btn btn-success btn-sm" onclick="startProcess()">Iniciar</button>
                <button id="stopService" type="button" class="btn btn-danger btn-sm" onclick="stopProcess()">Parar</button>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-2">
        <textarea id="logTextArea" class="form-control" rows="12" readonly disabled></textarea>
    </div>

    <script>
        // Função para atualizar os dados usando Ajax
        function updateData() {
            $.ajax({
                url: '../../../controller/supervisor.php', // Substitua pelo caminho correto do seu script PHP
                type: 'GET',
                dataType: 'json',
                success: function(responseData) {
                    $('#serviceName').text('Serviço: ' + responseData.serviceName);
                    $('#status').text('Status: ' + responseData.status);
                    $('#description').text(responseData.description);
                    $('#logTextArea').val(responseData.lastLines);
                }
            });
        }

        // Função para iniciar o processo
        function startProcess() {
            $.post('../../../controller/supervisor.php', { start: true }, function() {
                updateData(); // Atualiza os dados após a ação
            });
        }

        // Função para parar o processo
        function stopProcess() {
            $.post('../../../controller/supervisor.php', { stop: true }, function() {
                updateData(); // Atualiza os dados após a ação
            });
        }

        // Atualiza os dados a cada 5 segundos (5000 milissegundos)
        setInterval(updateData, 5000);

        // Chama a função de atualização ao carregar a página
        $(document).ready(function() {
            updateData();
        });
    </script>
</body>
</html>
