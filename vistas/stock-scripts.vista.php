<?php if(empty($stockEmbedded)): ?>


<script src="js/ean-scanner.js?v=<?= substr(sha1_file(__DIR__.'/../js/ean-scanner.js'),0,12) ?>"></script>
<script src="js/product-info.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php endif; ?>
<script src="js/vendor/qrcode.min.js"></script>
<script src="js/stock-utils.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/pallet-label.js?v=<?= substr(sha1_file(__DIR__.'/../js/pallet-label.js'),0,12) ?>"></script>
<script src="js/pallet-label-pdf.js?v=<?= substr(sha1_file(__DIR__.'/../js/pallet-label-pdf.js'),0,12) ?>"></script>
<script src="js/label-files.js?v=<?= substr(sha1_file(__DIR__.'/../js/label-files.js'),0,12) ?>"></script>

<script src="js/stock-preparation.js?v=<?= substr(sha1_file(__DIR__.'/../js/stock-preparation.js'),0,12) ?>"></script>
<script src="js/date-keypad.js?v=<?= substr(sha1_file(__DIR__.'/../js/date-keypad.js'),0,12) ?>"></script>
<script src="js/product-view-tools.js?v=<?= substr(sha1_file(__DIR__.'/../js/product-view-tools.js'),0,12) ?>"></script>
<script src="js/stock-live.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/stock-pallet-list.js?v=<?= substr(sha1_file(__DIR__.'/../js/stock-pallet-list.js'),0,12) ?>"></script>
<script src="js/stock.js?v=<?= substr(sha1_file(__DIR__.'/../js/stock.js'),0,12) ?>"></script>
<script src="js/stock-summary.js?v=<?= (int)app_release()['version'] ?>"></script>
<?php if(empty($stockEmbedded)): ?><link rel="stylesheet" href="css/inventory-goods.css?v=<?= (int)app_release()['version'] ?>"><script src="js/inventory-goods.js?v=<?= (int)app_release()['version'] ?>"></script><script src="js/stock-goods.js?v=<?= (int)app_release()['version'] ?>"></script><?php endif; ?>
<script src="js/public-pallet.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/branch-maps.js?v=<?= (int)app_release()['version'] ?>"></script>
<script src="js/stock-filter-popovers.js?v=<?= substr(sha1_file(__DIR__.'/../js/stock-filter-popovers.js'),0,12) ?>"></script>
<script src="js/stock-tags.js?v=<?= substr(sha1_file(__DIR__.'/../js/stock-tags.js'),0,12) ?>"></script>
<script src="js/stock-branches.js?v=<?= substr(sha1_file(__DIR__.'/../js/stock-branches.js'),0,12) ?>"></script>

<script src="js/team-pallet-requests.js?v=<?= (int)app_release()['version'] ?>"></script>
