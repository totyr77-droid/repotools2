<style><?= file_get_contents(__DIR__ . '/../css/page-loader.css') ?></style>
<noscript><style>html.is-loading::before{display:none}#repotoolsPageLoader{display:none}</style></noscript>
<script>window.repotoolsLoaderLogo=<?= json_encode('data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/../img/onboarding-logo.png'))) ?>;
<?= file_get_contents(__DIR__ . '/../js/page-loader.js') ?></script>
