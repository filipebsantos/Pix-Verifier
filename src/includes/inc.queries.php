<?php

    if (!isset($pagina_atual)){
        $pagina_atual = 1;
    }

    $resultadoPorPagina = 10;
    $offset = ($pagina_atual - 1) * $resultadoPorPagina;

    if (isset($byDate)) {
        // Obtem quantidade de registros
        $statment = $database->prepare("SELECT COUNT(*) FROM receivedpix WHERE DATE_TRUNC('day', datainclusao) BETWEEN :dataInicio AND :dataFim");
        $statment->bindValue(":dataInicio", date_format($dataInicio, "Y-m-d"));
        $statment->bindValue(":dataFim", date_format($dataFim, "Y-m-d"));
        if ($statment->execute()){
            $qtdeRegistros = $statment->fetch();
            
            // Paginação dos registros
            $statment = $database->prepare("SELECT * FROM receivedpix WHERE DATE_TRUNC('day', datainclusao) BETWEEN :dataInicio AND :dataFim ORDER BY datainclusao ASC LIMIT :resultadoPorPagina OFFSET :offset");
            $statment->bindValue(":dataInicio", date_format($dataInicio, "Y-m-d"));
            $statment->bindValue(":dataFim", date_format($dataFim, "Y-m-d"));
            $statment->bindParam(":resultadoPorPagina", $resultadoPorPagina);
            $statment->bindParam(":offset", $offset);
            $statment->execute();
            $resultado = $statment->fetchAll();

            $captionTable = "Exibindo Pix recebido de " . date_format($dataInicio, "d/m/Y") . " até " . date_format($dataFim, "d/m/Y");
        }
        
    } else if (isset($bySearch)) {
        $querySearch = $txtSearch . "%";

        // Obtem quantidade de registros
        $statment = $database->prepare("SELECT COUNT(*) FROM receivedpix WHERE $optSearch LIKE :txtSearch");
        $statment->bindParam(":txtSearch", $querySearch);
        if ($statment->execute()) {
            $qtdeRegistros = $statment->fetch(); 

            // Paginação dos registros
            $statment = $database->prepare("SELECT * FROM receivedpix WHERE $optSearch LIKE :txtSearch ORDER BY datainclusao DESC LIMIT :resultadoPorPagina OFFSET :offset");
            $statment->bindParam(":txtSearch", $querySearch);
            $statment->bindParam(":resultadoPorPagina", $resultadoPorPagina);
            $statment->bindParam(":offset", $offset);
            if ($statment->execute()) {
                $resultado = $statment->fetchAll();

                if ($optSearch === "cpfcnpjpagador") {
                    $captionTable = "Resultado da busca por CPF/CNPJ contendo " . $txtSearch;
                } else if ($optSearch === "e2eid") {
                    $captionTable = "Resultado da busca por E2EID contendo " . $txtSearch;
                }
            }
        }                      
    } else {
        // Agaga o cookie de paginação se existir
        if (isset($_COOKIE['searchPagination'])){
            setcookie('searchPagination', "", time() - 3600);
        }

        // Obtem quantidade de registros
        $statment = $database->prepare("SELECT COUNT(*) FROM receivedpix WHERE DATE_TRUNC('day', datainclusao) = :today");
        $statment->bindValue(":today", date("Y-m-d"));
        if ($statment->execute()) {
            $qtdeRegistros = $statment->fetch();
            
            // Paginação dos registros
            $statment = $database->prepare("SELECT * FROM receivedpix WHERE DATE_TRUNC('day', datainclusao) = :today ORDER BY datainclusao DESC LIMIT :resultadoPorPagina OFFSET :offset");
            $statment->bindValue(":today", date("Y-m-d"));
            $statment->bindParam(":resultadoPorPagina", $resultadoPorPagina);
            $statment->bindParam(":offset", $offset);
            $statment->execute();
            $resultado = $statment->fetchAll();

        } 
    }

    // Quantidade de páginas
    if ($qtdeRegistros['count'] > $resultadoPorPagina) {
        $qtdePaginas = ceil($qtdeRegistros['count'] / $resultadoPorPagina);

        // Salva o cookie para auxiliar na paginação
        if (isset($byDate)){
            $cookieArray = ["type" => "byDate", "dataInicio" => date_format($dataInicio, "Y-m-d"), "dataFim" => date_format($dataFim, "Y-m-d")];
            setcookie("searchPagination", json_encode($cookieArray));
        }

        if (isset($bySearch)) {
            $cookieArray = ["type" => "bySearch", "txtSearch" => $txtSearch, "optSearch" => $optSearch];
            setcookie("searchPagination", json_encode($cookieArray));
        }
    } else {
        $qtdePaginas = 1;
    }

    $qtdeMaxPaginas = 10; // Quantidade máxima de paginas exibidas na paginação
    
    // Define a página inical da paginação
    $paginaInicial = $pagina_atual - (ceil($qtdeMaxPaginas / 2));
    
    if($paginaInicial < 1){
        $paginaInicial = 1;
    }

    // Define a página final da paginação
    $paginaFinal = $paginaInicial + ($qtdeMaxPaginas - 1);

    if($paginaFinal > $qtdePaginas) {
        $paginaFinal = $pagina_atual + ($qtdePaginas - $pagina_atual);
    }

?>