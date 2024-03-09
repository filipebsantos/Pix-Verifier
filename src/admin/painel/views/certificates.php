<?php
    session_start();

    if(!isset($_SESSION['usuarioLogado'])){
        header('Location: ../../../admin/');
    }

    require(__DIR__ . '/../../../includes/inc.database.php');
    require(__DIR__ . '/../../../includes/inc.functions.php');

    if (isset($_FILES) && count($_FILES) > 0){
        if (isset($_FILES['fileCrt']) && $_FILES['fileCrt']['error'] == 0) {
            saveCertificateFile($_FILES['fileCrt'], 'inter_cert.crt');
        }

        if (isset($_FILES['fileCrtKey']) && $_FILES['fileCrtKey']['error'] == 0) {
            saveCertificateFile($_FILES['fileCrtKey'], 'inter_cert.key');
        }

        if (isset($_FILES['fileCrtCA']) && $_FILES['fileCrtCA']['error'] == 0) {
            saveCertificateFile($_FILES['fileCrtCA'], 'inter_ca.crt');
        }
    }

    $inter_cert = getCertificateData('inter_cert.crt');
    $inter_ca = getCertificateData('inter_ca.crt');
    
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/services/certs/inter/inter_cert.key')) {
        $cert_key_exists = true;
        $validCertKey = checkPrivateKey();
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

    <?php if (isset($returnMsg)) : ?>
        <div class="container mt-1">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?= $returnMsg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container-fluid mt-2">
        <form action="certificates.php" method="POST" enctype="multipart/form-data">
            <div class="mb-2">
                <?php if ($inter_cert != false): ?>
                    <label for="fileCrt" class="form-label" style="font-size: smaller;"><b>Emitido para:</b> <?= $inter_cert['CN'] ?><br><b>Válido até:</b> <?= $inter_cert['VALID_TO'] ?></label>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-group-text">Certificado</span>
                    <input type="file" name="fileCrt" class="form-control" accept=".crt">
                </div>
                <div class="form-text" id="basic-addon4">Somente arquivos do tipo .crt são permitos.</div>
            </div>

            <div class="mb-2">
                <?php if (isset($cert_key_exists)): ?>
                    <label for="fileCrt" class="form-label" style="font-size: smaller;"><?= $validCertKey ? '&#9989; Chave válida para o certificado.' : '&#10060; Chave inválida para o certificado.' ?></label>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-group-text">Chave do Certificado</span>
                    <input type="file" name="fileCrtKey" class="form-control" accept=".key">
                </div>
                <div class="form-text" id="basic-addon4">Somente arquivos do tipo .key são permitos.</div>
            </div>

            <div class="mb-2">
                <?php if ($inter_ca != false): ?>
                    <label for="fileCrt" class="form-label" style="font-size: smaller;"><b>Emitido para:</b> <?= $inter_ca['CN'] ?><br><b>Válido até:</b> <?= $inter_ca['VALID_TO'] ?></label>
                <?php endif; ?>
                <div class="input-group">
                    <span class="input-group-text">Certificado CA</span>
                    <input type="file" name="fileCrtCA" class="form-control" accept=".crt">
                </div>
                <div class="form-text" id="basic-addon4">Somente arquivos do tipo .crt são permitos.</div>
            </div>

            <input type="submit" class="btn btn-success" value="Enviar">
        </form>
    </div>
    <script src="../../../js/bootstrap.min.js"></script>
</body>
</html>