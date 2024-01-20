<?php
/********************************************************************************************
 * Esse script gera a página de visualização das transferências recebidas via pix
 * e salva no banco de dados.
 * 
 * Desenvolvido por Filipe Bezerra dos Santos(filipebezerrasantos@gmail.com)
 * https://filipebezerra.dev.br
 * https://github.com/filipebsantos
 *
 *******************************************************************************************/

//Banco de Dados

$db_server = '';
$db_name = '';
$db_user = '';
$db_pass = '';

function formatCnpjCpf($value)
{
    $CPF_LENGTH = 11;
    $cnpj_cpf = preg_replace("/\D/", '', $value);

    if (strlen($cnpj_cpf) === $CPF_LENGTH) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
    }

    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
}

try {
    $database = new PDO('pgsql:host=' . $db_server . ';port=5432;dbname=' . $db_name . ';user=' . $db_user . ';password=' . $db_pass . '');
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msgError = $e->getCode();
}

$consulta = $database->query("SELECT value FROM settings WHERE key = 'ULTIMA_ATUALIZACAO'");
$resultado = $consulta->fetch();
$captionTable = "Última verificação: " . date("d/m/Y H:i:s", strtotime($resultado['value']));
$consulta->closeCursor();


if (isset($_POST['filter'])) {
    switch ($_POST['filter']) {
            // Filtro por data
        case 'byDate':
            // Checa se as datas não estão vazias
            if (($_POST['dataInicio'] != "") && ($_POST['dataFim'] != "")) {
                $dataHoje = new DateTime();
                $dataInicio = date_create($_POST['dataInicio']);
                $dataFim = date_create($_POST['dataFim']);
                $byDate = true;
                // Checa se a data inicial é maior que data final
                $diff = date_diff($dataInicio, $dataFim);
                if ($diff->invert == 1) {
                    $msgError = "A data inicial não pode ser maior que a data final.";
                    unset($byDate);
                }
                // Checa se as data fonecidas são maiores que a data atual
                if ($dataInicio > $dataHoje || $dataFim > $dataHoje) {
                    $msgError = "As datas informadas não podem ser maior que a data atual.";
                    unset($byDate);
                }
            } else {
                $msgError = "Infomar a data início e a data fim.";
            }
            break;
            // Filtro por busca
        case 'bySearch':
            $bySearch = true;
            $optSearch = $_POST['optSearchBy'];
            $txtSearch = $_POST['txtSearchBy'];

            if ($txtSearch == "") {
                $msgError = "Informar o critério de busca.";
                unset($bySearch);
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" lang="ptbr">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pix Verifier</title>
    <link rel="icon" type="image/webp" href="./logo.webp">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</head>

<body>
    <header>
        <nav class="navbar bg-success-subtle">
            <div class="container-fluid">
                <a class="navbar-brand" href="pix.php">
                    <img src="./logo.webp" alt="Pix Verifier" width="50" height="auto" class="d-inline-block">
                    <strong>Pix Verifier</strong>
                </a>
            </div>
        </nav>
    </header>

    <main>
        <div class="container mt-4">
            <div class="row">
                <!-- Filtar por data -->
                <div class="col-6">
                    <form action="" class="row" method="POST">
                        <div class="col-5">
                            <input type="date" class="form-control" name="dataInicio">
                            <div id="emailHelp" class="form-text">Data Início</div>
                        </div>
                        <div class="col-5">
                            <input type="date" class="form-control" name="dataFim">
                            <div id="emailHelp" class="form-text">Data Fim</div>
                        </div>
                        <div class="col-2">
                            <input type="submit" class="btn btn-outline-success" value="Filtrar">
                        </div>
                        <input type="hidden" name="filter" value="byDate">
                    </form>
                </div>
                <!-- Buscar por CPF/CNPJ -->
                <div class="col-6">
                    <form action="" class="row d-flex justify-content-end" method="POST">
                        <div class="col-5">
                            <div class="input-group">
                                <span class="input-group-text">Buscar por:</span>
                                <select class="form-select" name="optSearchBy">
                                    <option value="cpfcnpjpagador">CPF/CNPJ</option>
                                    <option value="e2eid">E2EID</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="txtSearchBy">
                                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <input type="hidden" name="filter" value="bySearch">
                    </form>
                </div>
            </div>
        </div>
        <div class="container mt-4">
            <div class="row d-flex">
                <div class="col d-flex justify-content-end">
                    <a href="pix.php" class="btn btn-outline-success">
                        <i class="bi bi-arrow-clockwise"></i>
                        Atualizar
                    </a>
                </div>
            </div>

            <?php
            if (isset($byDate)) {
                $statment = $database->prepare("SELECT * FROM receivedpix WHERE DATE_TRUNC('day', datainclusao) BETWEEN :dataInicio AND :dataFim ORDER BY datainclusao ASC");
                $statment->bindParam(":dataInicio", date_format($dataInicio, "Y-m-d"));
                $statment->bindParam(":dataFim", date_format($dataFim, "Y-m-d"));
                $statment->execute();
                $resultado = $statment->fetchAll();

                $captionTable = "Exibindo Pix recebido de " . date_format($dataInicio, "d/m/Y") . " até " . date_format($dataFim, "d/m/Y");
            } else if (isset($bySearch)) {
                $querySearch = $txtSearch . "%";
                $statment = $database->prepare("SELECT * FROM receivedpix WHERE $optSearch LIKE :txtSearch ORDER BY datainclusao DESC");
                $statment->bindParam(":txtSearch", $querySearch);
                $statment->execute();
                $resultado = $statment->fetchAll();

                if ($optSearch === "cpfcnpjpagador") {
                    $captionTable = "Resultado da busca por CPF/CNPJ contendo " . $txtSearch;
                } else if ($optSearch === "e2eid") {
                    $captionTable = "Resultado da busca por E2EID contendo " . $txtSearch;
                }
            } else {
                $statment = $database->prepare("SELECT * FROM receivedpix WHERE DATE_TRUNC('day', datainclusao) = :today ORDER BY datainclusao DESC");
                $statment->bindParam(":today", date("Y-m-d"));
                $statment->execute();
                $resultado = $statment->fetchAll();
            }
            ?>
            <!-- Tabela -->
            <div class="table-responsive">
                <table class="table table-striped align-middle table-sm caption-top">
                    <caption><?= $captionTable ?></caption>
                    <thead>
                        <tr>
                            <th scope="col">Valor</th>
                            <th scope="col">Pagador</th>
                            <th scope="col">Data e Hora</th>
                            <th scope="col">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                        <?php foreach ($resultado as $registro) : ?>
                            <tr>
                                <td>R$ <?= str_replace(".", ",", $registro["valor"]) ?></td>
                                <td><?= $registro["nomepagador"] ?></td>
                                <td><?= date("d/m/Y H:i:s", strtotime($registro["datainclusao"])) ?></td>
                                <td><button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#<?= $registro["e2eid"] ?>"><i class="bi bi-search"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Modal -->
            <?php if (count($resultado) > 0) : ?>
                <?php foreach ($resultado as $registro) : ?>
                    <div class="modal fade" id="<?= $registro["e2eid"] ?>" tabindex="-1" aria-labelledby="<?= $registro["e2eid"] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="<?= $registro["e2eid"] ?>">Detalhes do Pix</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <strong>Descrição Pix:</strong> <?= $registro["descricaopix"] ?><br>
                                    <strong>CPF/CNPJ:</strong> <?= formatCnpjCpf($registro["cpfcnpjpagador"]) ?><br>
                                    <strong>Instituição Financeira:</strong> <?= $registro["nomeempresapagador"] ?><br>
                                    <strong>Id da Transação:</strong> <?= $registro["e2eid"] ?><br>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                Nenhum registro encontrado...
            <?php endif; ?>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Verifique se há mensagens de erro na sessão e exiba a mensagem
            <?php if (isset($msgError)) : ?>
                var mensagemErro = '<?php echo $msgError; ?>';
                alert(mensagemErro);
            <?php
                // Limpe a variável de sessão após exibir a mensagem
                unset($msgError);
            endif; ?>
        });
    </script>
</body>

</html>