<div id="auditRecordsControls" data-default-section="<?= $navRole==='manager'?'goods':'moderation' ?>" hidden>
<p class="audit-scope-note"><?= $navRole==='moderador'?'Solo tus acciones.':($navRole==='administrador'?'Tus acciones y las de moderadores.':'Registros de todo RepoTools.') ?></p>
<nav id="reportLogSections" class="reports-state-tabs audit-section-tabs" aria-label="Tipos de registros">
<?php foreach(['users'=>['Usuarios','user'],'goods'=>['Mercadería','package'],'moderation'=>['Moderación','shield'],'management'=>['Gestión','branch']] as $key=>[$label,$symbol]): if($key==='users'&&$navRole!=='manager')continue; ?><button type="button" data-audit-section="<?= $key ?>" aria-pressed="<?= $key==='goods'?'true':'false' ?>"><?= stock_icon($symbol) ?><?= $label ?></button><?php endforeach; ?>
</nav>
<div id="reportLogFilters"><div class="audit-toolbar"><label class="audit-search"><?= stock_icon('search') ?><input data-audit-filter="q" type="search" maxlength="100" placeholder="Persona, palet o EAN" aria-label="Buscar en los registros"></label>
<details id="auditFilters" class="audit-filter-menu"><summary class="library-secondary"><?= stock_icon('filter') ?><span>Filtros</span></summary><div class="audit-filter-fields">
<label>Acción<select id="reportLogFilter"><option value="">Todas las acciones</option><?php foreach(report_log_filters() as $key=>$filter): ?><option value="<?= escape($key) ?>"><?= escape($filter['label']) ?></option><?php endforeach; ?></select></label>
<label>Persona<select id="reportLogActor"><option value="">Todas las personas</option></select></label>
<label>Sucursal<select id="auditBranchFilter" data-audit-filter="branch"><option value="">Todas las sucursales</option></select></label>
<fieldset class="audit-date-range"><legend>Fecha</legend><label>Desde<input data-audit-filter="from" type="date"></label><label>Hasta<input data-audit-filter="to" type="date"></label></fieldset>
<label>Rango<select data-audit-filter="role"><option value="">Todos</option><?php foreach(($navRole==='manager'?['usuario','moderador','administrador','manager']:($navRole==='administrador'?['moderador','administrador']:['moderador'])) as $role): ?><option><?= $role ?></option><?php endforeach; ?></select></label>
<label>Resultado<select data-audit-filter="result"><option value="">Todos</option><option value="confirmed">Confirmado</option><option value="denied">Sin permiso</option><option value="conflict">En conflicto</option><option value="rejected">Rechazado</option></select></label>
<button type="button" id="clearAuditFilters" class="library-secondary"><?= stock_icon('close') ?>Limpiar filtros</button>
</div></details></div><p id="auditSectionHint" class="stock-muted"></p>
</div></div>
<dialog id="auditRecordDialog" class="library-dialog" aria-labelledby="auditRecordTitle"><div class="library-heading"><h2 id="auditRecordTitle"><?= stock_icon('clock') ?>Detalle de la operación</h2><button type="button" class="library-secondary app-close" aria-label="Cerrar detalle" data-close-audit><?= stock_icon('close') ?></button></div><p id="auditRecordStatus" role="status" class="library-status"></p><div id="auditRecordContent"></div></dialog>

<span id="auditRecordIcons" hidden><?php foreach(['package-plus','package-minus','trash','transfer','settings','shield','lock','check','close','user-plus','user','users','branch','mail','link','share','logout','qr','eye','clock','pallet','barcode','crown','info'] as $symbol): ?><span data-audit-icon="<?= $symbol ?>"><?= stock_icon($symbol) ?></span><?php endforeach; ?></span>
