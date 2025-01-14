<?php

    require_once(__DIR__ . "/../models/LogHandler.php");
    require_once(__DIR__ . "/../models/Dao.php");
    require_once(__DIR__ . "/../includes/inc.functions.php");

    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $user = new DAO();
    
    try{
        $userData = $user->getUserData($username);
    } catch (DAOException $daoError) {
        LogHandler::stdlog("An error occurred while trying to loggin user '". $username ."'", LogHandler::ERROR_TYPE_CRITICAL);
        setcookie('loginAttempt', 'Erro ao consultar banco de dados', 0, '/admin');
        header('Location: index.html');
    }

    if (is_array($userData)) {

        $verifyPassword = password_verify($password, $userData['pwd']);

        if ($verifyPassword) {
            $exp = time() + 1800;

            $payload = [
                'sub' => [
                    'id' => (string)$userData['user_id'],
                    'useraccess' => (string)$userData['useraccess']
                ],
                'iat' => (string)time(),
                'exp' => (string)$exp
            ];

            $userToken = encodeJWT($payload);

            setcookie('jwtToken', $userToken, [
                'expires' => time() + 1800,
                'path' => '/',
                'secure' => false,          
                'httponly' => true,        
                'samesite' => 'Lax'         
            ]);
            header('Location: home.php');

        } else {
            setcookie('loginAttempt', 'Senha incorreta', 0, '/admin');
            header('Location: index.html');
        }
    } else {
        setcookie('loginAttempt', 'Usuário não encontrado', 0, '/admin');
        header('Location: index.html');           
    }