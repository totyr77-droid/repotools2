<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/tops.php';
$viewer=current_user();$paginaCuenta=true;$paginaTops=true;
$category=isset(tops_categories()[$_GET['categoria']??''])?$_GET['categoria']:'codigos';
$period=in_array($_GET['periodo']??'', ['semana','mes','historico'],true)?$_GET['periodo']:'semana';
$error='';$ranking=['leaders'=>[],'own'=>null];
try{$ranking=tops_ranking(database(),$category,$period,$viewer?(int)$viewer['id']:null);}catch(Throwable $e){$error='No pudimos cargar el ranking. Intentá más tarde.';}
header('Cache-Control: no-store');
if(isset($_GET['fragment'])){header('Content-Type: text/html; charset=utf-8');if($error)http_response_code(503);include __DIR__.'/vistas/tops-ranking.vista.php';exit;}
include __DIR__.'/vistas/header.vista.php';
?>
<main class="tops-main"><section class="tops-panel">
<h1><i class="fa-solid fa-ranking-star" aria-hidden="true"></i> TOPs</h1>
<form id="topsFilters" action="tops.php" method="get">
<div class="tops-periods" role="group" aria-label="Período"><?php foreach(['semana'=>'Semana','mes'=>'Mes','historico'=>'Histórico'] as $value=>$label): ?><label><input type="radio" name="periodo" value="<?= $value ?>"<?= $period===$value?' checked':'' ?>><span><?= $label ?></span></label><?php endforeach; ?></div>
<label class="tops-category">Categoría <select name="categoria"><?php foreach(tops_categories() as $value=>[$label,$description]): ?><option value="<?= $value ?>" data-description="<?= escape($description) ?>"<?= $category===$value?' selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<noscript><button type="submit">Ver ranking</button></noscript>
</form>
<p id="topsDescription"><?= escape(tops_categories()[$category][1]) ?></p>
<div id="topsRanking" aria-live="polite"><?php include __DIR__.'/vistas/tops-ranking.vista.php'; ?></div>
<?php if(!$viewer): ?><div class="tops-join"><p>Participá con tu cuenta.</p><div><a class="tops-join-primary" href="cuenta.php?mode=registro&amp;next=tops">Crear cuenta</a><a href="cuenta.php?next=tops">Ingresar</a></div></div><?php endif; ?>
</section></main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
