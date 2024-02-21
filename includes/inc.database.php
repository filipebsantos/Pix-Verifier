<?php
    //Banco de Dados
    $db_server = '';
    $db_name = '';
    $db_user = '';
    $db_pass = '';

    try {
        $database = new PDO('pgsql:host=' . $db_server . ';port=5432;dbname=' . $db_name . ';user=' . $db_user . ';password=' . $db_pass . '');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $msgError = $e->getCode();
    }