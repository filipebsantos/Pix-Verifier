<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuarioLogado'])) {
    header('Location: ../../../admin/');
}

require('../../../includes/inc.database.php');

// Recuperar os valores do banco de dados
$query = "SELECT clientid, clientsecret FROM inter";
$result = $database->query($query);
$configuracoes = $result->fetch(PDO::FETCH_ASSOC);

// Inicializar variáveis com valores do banco de dados ou vazios
$clientId = ($configuracoes) ? $configuracoes['clientid'] : '';
$clientSecret = ($configuracoes) ? $configuracoes['clientsecret'] : '';
// Adicione outros campos conforme necessário

// Recuperar o valor de 'ignoredList' da tabela 'settings'
$querySettings = "SELECT value FROM settings WHERE key = 'ignoredList'";
$resultSettings = $database->query($querySettings);
$ignoredList = ($resultSettings) ? $resultSettings->fetchColumn() : '';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter os valores do formulário
    $clientId = $_POST['txtClientId'];
    $clientSecret = $_POST['txtClientSecret'];
    $ignoredList = $_POST['txtListaIgnorados'];

    // Validar a lista de ignorados
    if (!empty($ignoredList) && !preg_match('/^\d+(,\d+)*$/', $ignoredList)) {
        // Se a validação falhar, armazena a mensagem de erro
        $returnMsg = "A lista de ignorados deve conter apenas números separados por vírgula.";
    } else {    
        try {
            // Fazer o INSERT ou UPDATE no banco de dados
            if ($configuracoes) {
                // Se já existir, fazer o UPDATE
                $query = "UPDATE inter SET clientid = :clientId, clientsecret = :clientSecret";
                $stmt = $database->prepare($query);
                $stmt->execute([':clientId' => $clientId, ':clientSecret' => $clientSecret]);

            } else {
                // Se não existir, fazer o INSERT
                $query = "INSERT INTO inter (clientid, clientsecret) VALUES (:clientId, :clientSecret)";
                $stmt = $database->prepare($query);
                $stmt->execute([':clientId' => $clientId, ':clientSecret' => $clientSecret]);

            }

            // Fazer o INSERT ou UPDATE no banco de dados
            if ($resultSettings) {
                // Se já existir, fazer o UPDATE
                $querySettings = "UPDATE settings SET value = :ignoredList WHERE key = 'ignoredList'";
                $stmtSettings = $database->prepare($querySettings);
                $stmtSettings->execute([':ignoredList' => $ignoredList]);
            } else {
                // Se não existir, fazer o INSERT
                $querySettings = "INSERT INTO settings ('key', 'value') VALUES ('ignoredList', :ignoredList)";
                $stmtSettings = $database->prepare($querySettings);
                $stmtSettings->execute([':ignoredList' => $ignoredList]);
            }

            $returnMsg = "Dados salvos com sucesso!";

        } catch (PDOException $erro) {
            $returnMsg = "Falha: " . $erro->getMessage();
        }
    }

}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pix Verifier</title>
    <link rel="stylesheet" href="../../../css/bootstrap.min.css">
</head>
<body>
    
    <?php if (isset($returnMsg)) : ?>
        <div class="container mt-1">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= $returnMsg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container-fluid mt-2">
        <form action="api-config.php" method="POST">
            <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1">Client ID</span>
                <input type="text" class="form-control" name="txtClientId" value="<?= $clientId ?>" required>
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1">Client Secret</span>
                <input type="text" class="form-control" name="txtClientSecret" value="<?= $clientSecret ?>" required>
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text">Lista de Ignorados</span>
                <textarea class="form-control" aria-label="ListaIgnorados" name="txtListaIgnorados" placeholder="Digite os CPF e/ou CNPJs que deseja ignorar sem pontuação e separados por virgulas"><?= $ignoredList ?></textarea>
            </div>

            <input type="submit" class="btn btn-success" value="Salvar">
        </form>
    </div>

    <script src="../../../js/bootstrap.min.js"></script>
</body>
</html>