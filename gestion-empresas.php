<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/branch-team.php';
require_once __DIR__.'/includes/stock-icons.php';
if(!current_user()){header('Location: cuenta.php?next=gestion-empresas');exit;}
$paginaGestionEmpresas=true;
if(!team_can_assign(database(),(int)current_user()['id'])){http_response_code(403);$publicPageTitle='Acceso restringido';include 'vistas/header.vista.php';echo '<main class="library-main"><h1>Acceso restringido</h1><p>Esta gestión corresponde a managers de Repotools.</p><a href="index.php">Volver a Inicio</a></main>';include 'vistas/footer.vista.php';exit;}
$paginaGestionEmpresas=true;$publicPageTitle='Empresas y sucursales';include 'vistas/header.vista.php';
?>
<main class="library-main team-page company-manager" data-company-manager data-csrf="<?= escape(csrf_token()) ?>">
<div class="team-page-title"><h1><?= stock_icon('branch') ?>Empresas y sucursales</h1><a href="index.php" class="library-secondary company-home"><?= stock_icon('home') ?>Inicio</a></div>
<p class="team-hint">Gestión de Repotools · El responsable administra los accesos de su equipo.</p>
<p id="companyStatus" role="status" hidden></p>
<div class="company-toolbar"><label class="company-search"><?= stock_icon('search') ?><input id="companySearch" type="search" placeholder="Empresa, responsable o sucursal" aria-label="Buscar empresas" maxlength="100"></label><button type="button" class="library-primary" id="companyNew"><?= stock_icon('plus') ?>Asignar empresa</button><button type="button" class="team-text" id="companyRefresh" aria-label="Actualizar empresas" title="Actualizar empresas"><?= stock_icon('refresh') ?></button></div>
<dialog id="companyAssignmentDialog" class="company-assignment-dialog" aria-labelledby="companyAssignmentTitle"><div class="team-section-title"><h2 id="companyAssignmentTitle">Asignar empresa</h2><button type="button" class="team-text" id="companyAssignmentClose" aria-label="Cerrar asignación"><?= stock_icon('close') ?></button></div><p id="companyAssignmentStatus" role="status" hidden></p><?php include __DIR__.'/vistas/branch-team-assignment.vista.php'; ?></dialog>
<dialog id="companyBranchDialog" class="company-assignment-dialog" aria-labelledby="companyBranchTitle"><div class="team-section-title"><h2 id="companyBranchTitle">Seleccionar sucursal</h2><button type="button" class="team-text" id="companyBranchBack" aria-label="Volver a empresa"><?= stock_icon('close') ?></button></div><p id="companyBranchAssignmentStatus" role="status" hidden></p><div id="companyBranchDialogBody"></div></dialog>
<div class="company-layout"><section aria-label="Empresas"><p id="companyListStatus" role="status">Cargando empresas…</p><div id="companyList"></div><button type="button" class="team-text" id="companyMore" hidden>Ver más empresas</button></section>
<section id="companyDetail" class="company-detail" aria-label="Empresa seleccionada" hidden tabindex="-1">
<div class="team-section-title"><h2 id="companyTitle"></h2><button type="button" class="team-text" id="companyClose" aria-label="Cerrar detalle"><?= stock_icon('close') ?></button></div>
<p id="companyOwner" class="company-owner"></p>
<details id="companyEdit"><summary><?= stock_icon('settings') ?>Nombre de la empresa</summary><form id="companyNameForm" class="team-inline"><label>Nombre<input name="name" maxlength="100" required></label><button type="submit" class="library-secondary">Guardar</button></form></details>
<div class="team-section-title company-branches-heading"><h2><?= stock_icon('branch') ?>Sucursales</h2><button type="button" class="team-text" id="companyAddBranch"><?= stock_icon('plus') ?>Añadir sucursal</button></div>
<p id="companyBranchesStatus" role="status"></p><div id="companyBranches"></div><button type="button" class="team-text" id="companyMoreBranches" hidden>Ver más sucursales</button>
<details id="companyBranchEdit" hidden><summary><?= stock_icon('settings') ?><span id="companyBranchEditTitle">Configurar sucursal</span></summary><form id="companyBranchForm"><label>Nombre<input name="name" maxlength="100" required></label><label>Dirección<input name="address" maxlength="160" required></label><div class="team-inline"><label>Localidad<input name="locality" maxlength="100" required></label><label>Provincia<input name="province" maxlength="80" required></label></div><label>Descripción <small>(opcional)</small><input name="description" maxlength="300"></label><div class="company-form-actions"><button type="button" class="team-text" id="companyBranchCancel">Cancelar</button><button type="submit" class="library-primary">Guardar sucursal</button></div></form></details>
<button type="button" class="team-text team-danger" id="companyDelete">Eliminar empresa</button>
</section></div>
<dialog id="companyDeleteDialog" class="stock-dialog company-delete-dialog"><h2 id="companyDeleteTitle">Eliminar empresa</h2><p>Se quitarán la empresa y los accesos de su equipo. Las sucursales del directorio, los palets y la mercadería se conservan.</p><div class="company-form-actions"><button type="button" class="library-secondary" id="companyDeleteCancel">Cancelar</button><button type="button" class="library-primary company-delete-confirm" id="companyDeleteConfirm">Eliminar empresa</button></div></dialog>
</main>
<?php include 'vistas/footer.vista.php'; ?>
