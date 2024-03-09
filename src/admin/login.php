<?php
    session_start();
    require(__DIR__ . '/../includes/inc.database.php');

    if(isset($_POST['txtUsuario']) && isset($_POST['txtSenha'])) {
        $usuario = trim($_POST['txtUsuario']);
        $senha = $_POST['txtSenha'];

        $stmt = $database->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindParam(':username', $usuario);
        $stmt->execute();

        $resultado = $stmt->fetch();
        if($resultado){
            $checaSenha = password_verify($senha, $resultado['user_pwd']);

            if($checaSenha){
                
                $usuarioLogado = [
                    'username' => $resultado['username'],
                    'role' => $resultado['role'],
                    'uid' => $resultado['user_id']
                ];

                $_SESSION['usuarioLogado'] = $usuarioLogado;
                header('Location: ./painel/');

            } else {
                header('Location: index.php?msg=failed');
            }
        } else {
            header('Location: index.php?msg=notfound');
        }     
    } else {
        header('Location: index.php');
    }

?>