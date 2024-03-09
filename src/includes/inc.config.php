<?php
    // Obtem a timezone padrão do sistema
    date_default_timezone_set(getenv('TZ'));

    // Dados de conexão com o banco de dados
    define('db_server', getenv('DB_HOST') ?: 'localhost');
    define('db_name', getenv('DB_NAME') ?: 'pix');
    define('db_user', getenv('DB_USER') ?: 'postgres');
    define('db_pass', getenv('DB_PASS') ?: 'postgres');

    define('appVersion', '1.2');
?>