<?php
   
    session_start();

    if (!isset($_SESSION['usuarioLogado'])) {
        header('Location: ../../../admin/');
    }

    require('../../../includes/inc.database.php');

    if (isset($_POST['txtPass']) && isset($_POST['txtPass2'])) {
        unset($returnMsg);
    
        $senha1 = $_POST['txtPass'];
        $senha2 = $_POST['txtPass2'];
    
        if (strlen($senha1) < 8 || strlen($senha2) < 8) {
            $returnMsg = "As senhas precisam ter mais de 8 caracteres";
        } else {
            if ($senha1 != $senha2) {
                $returnMsg = "As senhas não coincidem";
            } else {
                $hashedPass = password_hash($senha2, PASSWORD_BCRYPT);
    
                $stmt = $database->prepare("UPDATE users SET user_pwd = :newpassword WHERE user_id = :userid");
                $stmt->bindParam(":newpassword", $hashedPass);
                $stmt->bindParam(":userid", $_SESSION['usuarioLogado']['uid']);
    
                if ($stmt->execute()) {
                    $returnMsg = "Senha alterada com sucesso!";
                } else {
                    $returnMsg = "Erro ao alterar a senha. Por favor, tente novamente mais tarde.";
                }
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
    <h4 class="m-3">Mudar Senha</h4>

    <?php if (isset($returnMsg)) : ?>
        <div class="container mt-1">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= $returnMsg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <form action="user.php" class="m-3" method="POST">
        <div class="input-group">
            <span class="input-group-text" id="basic-addon1">Senha</span>
            <input type="password" class="form-control" name="txtPass" placeholder="Digite a nova senha" required>
        </div>
        <div class="input-group mt-2">
            <span class="input-group-text" id="basic-addon1">Senha</span>
            <input type="password" class="form-control" name="txtPass2" placeholder="Repita a nova senha" required>
        </div>

        <input type="submit" class="btn btn-success mt-2 d-flex justify-content-center" value="Salvar">
    </form>

    <script src="../../../js/bootstrap.min.js"></script>
    </body>
</html>