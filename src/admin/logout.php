<?php
    session_start();
    unset($_SESSION['usuarioLogado']);

    header('Location: ../index.php');
?>