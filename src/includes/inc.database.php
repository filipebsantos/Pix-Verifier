<?php

    require(__DIR__ . '/inc.config.php');

    try {
        $database = new PDO('pgsql:host=' . db_server . ';port=5432;dbname=' . db_name . ';user=' . db_user . ';password=' . db_pass . '');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header('Location: ./database.html');
    }

?>