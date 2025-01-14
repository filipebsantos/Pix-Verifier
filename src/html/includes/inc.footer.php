<?php include_once(__DIR__ . '/inc.config.php'); ?>

<footer class="py-3 my-4 border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-left mb-3 mb-md-0">
                <a href="/" class="text-body-secondary text-decoration-none">
                    <svg class="bi" width="30" height="24"><use xlink:href="#bootstrap"/></svg>
                </a>
                <span class="text-body-secondary">&copy; <?= date("Y") ?> Pix Verifier - <span class="fw-light text-body-tertiary">v<?= appVersion ?></span></span>
            </div>

            <div class="col-md-6 text-md-end">
                <span class="text-body-secondary">Desenvolvido por <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="http://filipebezerra.dev.br" target="_blank" rel="noopener noreferrer">filipebezerra&#60;/dev&#62;&#60;br&#62;</a></span>
                <div>
                    <span class="fw-light text-body-tertiary">Essa solução te ajudou? Considere uma <a class="link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover" href="https://filipebezerra.dev.br/apoio/" target="_blank" rel="noopener noreferrer">doação</a></span>
                </div>
            </div>
        </div>
    </div>
</footer>