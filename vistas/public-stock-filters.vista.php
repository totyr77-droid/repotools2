<?php if(current_user()): ?>
<div id="publicStockTools" class="public-stock-tools">
 <label class="public-stock-search"><?= stock_icon('search') ?><input id="publicStockSearch" type="search" maxlength="100" placeholder="Buscar producto, EAN o palet" aria-label="Buscar producto, EAN o palet"></label>
 <details id="publicStockFilters"><summary class="library-secondary"><?= stock_icon('filter') ?><span>Filtros</span></summary><div id="publicStockFilterFields"><label>Sucursal<select id="publicStockBranch"><option value="">Todas las sucursales</option></select></label><label id="publicStockTagLabel" hidden>Etiqueta personal<select id="publicStockTag"><option value="">Todas las etiquetas</option></select></label><label>Orden<select id="publicStockSort"><option value="expiry">Vencimiento más cercano</option><option value="code">Número de palet</option></select></label><button id="clearPublicStockFilters" class="library-secondary" type="button">Limpiar</button></div></details>
</div>
<?php endif; ?>
