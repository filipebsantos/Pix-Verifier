<?php
    session_start();

    if (isset($_SESSION['usuarioLogado'])){
        header('Location: ./painel/');
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pix Verifier</title>
    <link rel="icon" type="image/webp" href="../logo.webp">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php
        if (isset($_GET['msg'])) {
            switch ($_GET['msg']) {
                case 'notfound':
                    $msgText = 'Usuário não encontrado.';
                    break;
                case 'failed':
                    $msgText = 'Senha inválida.';
                    break;
            }
        }
    ?>

    <header>
        <nav class="navbar bg-success-subtle">
            <div class="container-fluid">
                <a class="navbar-brand" href="../index.php">
                    <img src="../logo.webp" alt="Pix Verifier" width="50" height="auto" class="d-inline-block">
                    <strong>Pix Verifier</strong>
                </a>
                <span class="navbar-text">
                    Painel Administrativo
                </span>
            </div>
        </nav>
    </header>

    <div class="container mt-5 mb-5 p-5 border rounded shadow" style="max-width: 500px;">

        <?php if (isset($msgText)) : ?>
            <div class="container mt-2">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $msgText ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <input type="hidden" name="txtUsuario" value="admin">
            <div class="input-group mb-3">
                <span class="input-group-text">Usuário</span>
                <input class="form-control" type="text" value="admin" disabled readonly>
            </div>
            
            <div class="input-group mb-3">
                <span class="input-group-text">Senha</span>
                <input class="form-control" type="password" name="txtSenha" required>
            </div>

            <div class="d-flex justify-content-center">
                <input class="btn btn-success" type="submit" value="Login">
            </div>
        </form>
    </div>

    <?php include(__DIR__ . '/../includes/inc.footer.php'); ?>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>