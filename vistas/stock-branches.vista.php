<section id="stockBranches" class="stock-branches" hidden>
 <details id="branchDetails" hidden><summary><?= stock_icon('branch') ?><span>Sucursales</span><?= stock_icon('chevron') ?></summary>
  <div class="branch-toolbar"><label class="branch-search"><?= stock_icon('search') ?><input id="branchSearch" type="search" placeholder="Buscar sucursal o dirección" aria-label="Buscar sucursal"></label><button id="newBranch" class="library-primary stock-icon" type="button" aria-label="Agregar sucursal" title="Agregar sucursal"><?= stock_icon('plus') ?></button></div>
  <div id="branchList" class="branch-list"></div>
 </details>
 <div class="branch-toolbar branch-stock-filter" hidden><label><?= stock_icon('branch') ?><select id="branchFilter" aria-label="Filtrar mis palets por sucursal"><option value="all">Todas las sucursales</option><option value="none">Sin sucursal</option></select></label></div>
 <p id="branchFilterEmpty" class="stock-muted" hidden>No hay palets en esta sucursal.</p>
 <p id="branchStatus" class="library-status" role="status"></p>
 <details id="stockShareDetails" hidden><summary><?= stock_icon('share') ?><span>Enlaces compartidos</span><?= stock_icon('chevron') ?></summary></details>
</section>
