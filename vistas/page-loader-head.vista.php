<style><?= file_get_contents(__DIR__ . '/../css/page-loader.css') ?></style>
<noscript><style>html.is-loading::before{display:none}#repotoolsPageLoader{display:none}</style></noscript>
<script>window.repotoolsLoaderLogo=<?= json_encode('data:image/svg+xml;base64,' . base64_encode(file_get_contents(__DIR__ . '/../img/brand/repotools-isotipo-transparente.svg'))) ?>;
<?= file_get_contents(__DIR__ . '/../js/page-loader.js') ?></script>
