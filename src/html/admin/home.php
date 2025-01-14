<?php
    if (!isset($_COOKIE['jwtToken'])) header('Location: index.html');

    include_once(__DIR__ . "/../includes/inc.config.php");

    $page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="shortcut icon" href="../assets/imgs/favicon.ico" type="image/x-icon">
    <title>Pix Verifier</title>
</head>

<body>
<div class="d-flex flex-column" style="height: 100vh;"> 
    <header>
        <nav class="navbar navbar-expand-lg bg-success-subtle">
            <div class="container-fluid">
                <a href="/" class="navbar-brand">
                    <img src="../assets/imgs/pix-verifier.png" width="250" height="auto" alt="">
                </a>
                <div class="navbar-nav align-items-center column-gap-3">
                    <a href="./logout.php" id="btnAdm" class="btn btn-outline-primary">Sair</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content (Sidebar + Main Section) -->
    <div class="d-flex flex-grow-1">
        <!-- Sidebar -->
        <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark" style="width: 280px;">
            <a href="/admin/home.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <svg class="bi pe-none me-2" width="40" height="32">
                    <use xlink:href="#bootstrap"></use>
                </svg>
                <span class="fs-4">Menu</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="?page=dashboard" class="nav-link <?= $page === 'dashboard' ? 'active' : 'text-white' ?>" aria-current="page">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-home">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                            <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                            <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                        </svg>
                        Início
                    </a>
                </li>
                <li>
                    <a href="?page=accounts" class="nav-link <?= $page === 'accounts' ? 'active' : 'text-white' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building-bank">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 21l18 0" />
                            <path d="M3 10l18 0" />
                            <path d="M5 6l7 -3l7 3" />
                            <path d="M4 10l0 11" />
                            <path d="M20 10l0 11" />
                            <path d="M8 14l0 3" />
                            <path d="M12 14l0 3" />
                            <path d="M16 14l0 3" />
                        </svg>
                        Contas
                    </a>
                </li>
                <li>
                    <a href="?page=logs" class="nav-link <?= $page === 'logs' ? 'active' : 'text-white' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-text">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                            <path d="M9 9l1 0" />
                            <path d="M9 13l6 0" />
                            <path d="M9 17l6 0" />
                        </svg>
                        Logs
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <main class="flex-fill p-3">
            <?php
                $filename = $_SERVER['DOCUMENT_ROOT'] . '/admin/pages/' . $page . '.html';
                
                if (file_exists($filename)) {
                    include($filename);
                } else {
                    echo "<h1>404</h1>";
                }
            ?>
        </main>
    </div>
</div>
</body>

</html>