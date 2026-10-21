<?php if(current_user()): ?>
<aside id="homeLocationNotice" class="home-location-notice" data-user="<?= (int)current_user()['id'] ?>" aria-labelledby="homeLocationTitle" hidden>
 <div class="home-location-heading"><span class="home-location-icon"><?= stock_icon('map-pin') ?></span><h2 id="homeLocationTitle">Encontrá tu sucursal</h2></div>
 <div id="homeLocationBody"><p>Usá tu ubicación para reconocer la sucursal en la que estás y facilitar la vinculación de tus palets.</p><small>Podés seguir usando RepoTools sin compartirla.</small></div>
 <p id="homeLocationStatus" class="home-location-status" role="status" aria-live="polite"></p>
 <div id="homeLocationActions" class="home-location-actions"><button type="button" id="homeLocationLater" class="library-secondary">Ahora no</button><button type="button" id="homeLocationAllow" class="library-primary"><?= stock_icon('locate') ?><span>Permitir ubicación</span></button></div>
 <span id="homeLocationIcons" hidden><?php foreach(['branch','check','locate'] as $name): ?><span data-location-icon="<?= $name ?>"><?= stock_icon($name) ?></span><?php endforeach; ?></span>
</aside>
<?php endif; ?>
