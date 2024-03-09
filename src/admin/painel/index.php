<?php
    session_start();

    if (!isset($_SESSION['usuarioLogado'])) {
        header('Location: ../index.php');
    }

    require(__DIR__ . '/../../includes/inc.database.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pix Verifier</title>
    <link rel="icon" type="image/webp" href="../../logo.webp">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <header>
        <nav class="navbar bg-success-subtle">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <img src="../../logo.webp" alt="Pix Verifier" width="50" height="auto" class="d-inline-block">
                    <strong>Pix Verifier</strong>
                </a>
                <span class="navbar-text">
                    Usuário Logado: <?= $_SESSION['usuarioLogado']['username'] ?>
                    <a class="btn btn-secondary btn-sm" href="../logout.php">Sair</a>
                </span>
            </div>
        </nav>
    </header>

    <div class="container mt-5" style="max-width: 700px; min-width: 700px;">
        <ul class="nav nav-tabs" id="tabMenu" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Home</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-tab-pane" type="button" role="tab" aria-controls="api-tab-pane" aria-selected="false">API</button>
            </li>            
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="certificates-tab" data-bs-toggle="tab" data-bs-target="#certificates-tab-pane" type="button" role="tab" aria-controls="certificates-tab-pane" aria-selected="false">Certificados</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="user-tab" data-bs-toggle="tab" data-bs-target="#user-tab-pane" type="button" role="tab" aria-controls="user-tab-pane" aria-selected="false">Usuário</button>
            </li>
        </ul>
        <div class="tab-content border border-top-0 rounded-bottom" id="tabContent" style="height:400px;">
            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                <iframe src="./views/home.php" frameborder="0" width="100%" height="390"></iframe>
            </div>
            <div class="tab-pane fade" id="api-tab-pane" role="tabpanel" aria-labelledby="api-tab" tabindex="0">
                <iframe src="./views/api-config.php" frameborder="0" width="100%" height="390"></iframe>
            </div>
            <div class="tab-pane fade" id="certificates-tab-pane" role="tabpanel" aria-labelledby="certificates-tab" tabindex="0">
                <iframe src="./views/certificates.php" frameborder="0" width="100%" height="390"></iframe>
            </div>
            <div class="tab-pane fade" id="user-tab-pane" role="tabpanel" aria-labelledby="user-tab" tabindex="0">
                <iframe src="./views/user.php" frameborder="0" width="100%" height="390"></iframe>
            </div>
        </div>
    </div>

    <?php include_once(__DIR__ . '/../../includes/inc.footer.php') ?>

    <script src="../../js/bootstrap.bundle.min.js"></script>
</body>

</html>