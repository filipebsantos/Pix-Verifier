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

require('./includes/inc.database.php');
require('./includes/inc.functions.php');

$consulta = $database->query("SELECT value FROM settings WHERE key = 'ULTIMA_ATUALIZACAO'");
$resultado = $consulta->fetch();
$captionTable = "Última verificação: " . date("d/m/Y H:i:s", strtotime($resultado['value']));
$consulta->closeCursor();


if (isset($_POST['filter'])) {

    // Agaga o cookie de paginação se existir
    if (isset($_COOKIE['searchPagination'])){
        setcookie('searchPagination', "", time() - 3600);
    }
    
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

if (isset($_GET['pagina'])) {
    $pagina_atual = $_GET['pagina'];

    if (isset($_COOKIE['searchPagination'])) {
        $cookieValues = json_decode($_COOKIE['searchPagination'], true);

        switch ($cookieValues['type']) {
            case 'byDate':
                $dataInicio = date_create($cookieValues['dataInicio']);
                $dataFim = date_create($cookieValues['dataFim']);
                $byDate = true;
            break;

            case 'bySearch':
                $optSearch = $cookieValues['optSearch'];
                $txtSearch = $cookieValues['txtSearch'];
                $bySearch = true;
            break;
        }
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
                    <form action="pix.php" class="row" method="POST">
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
                    <form action="pix.php" class="row d-flex justify-content-end" method="POST">
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

            <?php require('./includes/inc.queries.php'); ?>

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
        <!-- Paginação -->
        <div class="container d-flex justify-content-center">
            <?php if($qtdePaginas > 1): ?>
            <nav aria-label="Páginas">
                <ul class="pagination">
                    <li class="page-item <?= ($paginaInicial - 1) < 1 ? "disabled" : "" ?>">
                        <a class="page-link" href="<?= ($paginaInicial - 1) < 1 ? "#" : "pix.php?pagina=" . $paginaInicial - 1; ?>" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a>
                    </li>
                    
                    <?php for($pagina = $paginaInicial; $pagina <= $paginaFinal; $pagina++): ?>
                        <li class="page-item <?php if($pagina == $pagina_atual): ?> active <?php endif; ?>"><a class="page-link" href="pix.php?pagina=<?= $pagina ?>"><?= $pagina ?></a></li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= ($paginaFinal + 1) > $qtdePaginas ? "disabled" : "" ?>">
                        <a class="page-link" href="<?= ($paginaFinal + 1) > $qtdePaginas ? "#" : "pix.php?pagina=" . $paginaFinal + 1; ?>" aria-label="Próximo"><span aria-hidden="true">&raquo;</span></a>
                    </li>
                </ul>
            </nav>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

</html>