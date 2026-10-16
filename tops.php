<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/stock-icons.php';require_once __DIR__.'/includes/tops.php';require_once __DIR__.'/includes/referrals.php';
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
<div class="tops-categories" role="group" aria-label="Categoría"><?php foreach(tops_categories() as $value=>[$label,$description]): ?><label><input type="radio" name="categoria" value="<?= $value ?>" data-description="<?= escape($description) ?>"<?= $category===$value?' checked':'' ?>><span><?= stock_icon($value==='codigos'?'barcode':($value==='reportes'?'report':'user')) ?><?= $label ?></span></label><?php endforeach; ?></div>
<noscript><button type="submit">Ver ranking</button></noscript>
</form>
<?php if($viewer && referrals_enabled()): try { $referralCode=referral_code(database(),(int)$viewer['id']);$referralLink=referral_origin().'/r/'.$referralCode;$q=database()->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_id=? AND state='validated' AND email_verified_at IS NOT NULL");$q->execute([$viewer['id']]);$confirmedTotal=(int)$q->fetchColumn(); ?><aside class="tops-referral"><strong>Tus referidos · <?= $confirmedTotal ?> confirmado<?= $confirmedTotal===1?'':'s' ?></strong><label class="tops-referral-link">Tu enlace<input id="topsReferralLink" value="<?= escape($referralLink) ?>" readonly></label><div><button type="button" class="library-secondary" id="copyTopsReferral"><?= stock_icon('copy') ?>Copiar enlace</button><button type="button" class="library-primary" id="shareTopsReferral"><?= stock_icon('share') ?>Compartir</button></div></aside><?php }catch(Throwable $e){ ?><p>Tu enlace de invitación no está disponible por el momento.</p><?php } endif; ?>
<p id="topsDescription"><?= escape(tops_categories()[$category][1]) ?></p>
<div id="topsRanking" aria-live="polite"><?php include __DIR__.'/vistas/tops-ranking.vista.php'; ?></div>
<?php if(!$viewer): ?><div class="tops-join"><p>Unite para ser parte.</p><div><a class="tops-join-primary" href="cuenta.php?mode=registro&amp;next=tops">Crear cuenta</a><a href="cuenta.php?next=tops">Ingresar</a></div></div><?php endif; ?>
</section></main>
<?php include __DIR__.'/vistas/footer.vista.php'; ?>
