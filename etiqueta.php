<?php
require_once __DIR__.'/includes/auth.php'; require_account(); require_once __DIR__.'/includes/stock.php'; require_once __DIR__.'/includes/stock-icons.php';
$raw=$_GET['codigos']??$_GET['codigo']??''; $codes=is_string($raw)?array_values(array_unique(explode(',',$raw))):[]; $labels=[]; $error='';
try {
    if (!$codes||count($codes)>100) throw new CatalogException('Elige entre 1 y 100 etiquetas.',422);
    $db=database(); $db->beginTransaction();
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='pgsql') $db->query('SELECT pg_advisory_xact_lock(72684623)'); else $db->exec('UPDATE stock_mutex SET revision=revision+1 WHERE id=1');
    stock_expire($db);
    foreach($codes as $code){$p=stock_detail($db,$code,(int)current_user()['id']);$label=stock_label($db,$code);if(!$p['can_edit']&&(!$p['available']||(int)$label['reserved_by']!==(int)current_user()['id']))throw new CatalogException('No tienes permiso para imprimir esta etiqueta.',403);$labels[]=['code'=>$code,'activated'=>$p['activated']??0,'reserved_until'=>$label['reserved_until'],'prepared_until'=>$p['prepared_until']??0];}
    $db->commit();
} catch(Throwable $exception){if(isset($db)&&$db->inTransaction())$db->rollBack();http_response_code($exception instanceof CatalogException?$exception->status:503);$error=$exception instanceof CatalogException?$exception->getMessage():'No pudimos cargar las etiquetas.';}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Etiquetas de palets · Repotools</title><link rel="stylesheet" href="css/pallet-label.css?v=20261010-stock1"></head><body><nav class="label-toolbar"><a href="stock.php">Volver a Stock</a><button id="printLabels" type="button" aria-label="Imprimir etiquetas" title="Imprimir etiquetas" disabled><?= stock_icon('print') ?></button></nav><p id="labelStatus" role="status"><?= escape($error?:'Preparando etiquetas…') ?></p><main id="labelSheets"></main><script>window.repotoolsLabels=<?= json_encode($labels,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script><script src="js/vendor/qrcode.min.js"></script><script src="js/pallet-label.js?v=20261010-stock1"></script><script src="js/label-page.js?v=20261010-stock1"></script></body></html>
