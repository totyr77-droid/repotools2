<button id="openCommercialPlans" type="button" class="library-secondary"><?= stock_icon('settings') ?>Planes y límites</button>
<dialog id="commercialPlansDialog" class="library-dialog" aria-labelledby="commercialPlansTitle">
  <div class="library-heading"><h2 id="commercialPlansTitle"><?= stock_icon('settings') ?>Planes y límites</h2><button type="button" class="app-close" aria-label="Cerrar planes"><?= stock_icon('close') ?></button></div>
  <p class="stock-muted">En preparación: guardar no genera cobros ni cambia accesos. Campos vacíos: sin límite definido.</p>
  <form id="commercialPlansForm">
    <label>Alcance<select id="commercialPlanScope" aria-label="Alcance del plan"><option value="user">Usuario</option><option value="branch">Sucursal</option><option value="company">Empresa</option></select></label>
    <label>Nombre del plan<input name="name" maxlength="80" required></label>
    <div class="stock-form-row"><label id="commercialPlanUsers">Usuarios<input name="max_users" type="number" inputmode="numeric" min="1" max="1000000" placeholder="Sin definir"></label><label id="commercialPlanBranches">Sucursales<input name="max_branches" type="number" inputmode="numeric" min="1" max="1000000" placeholder="Sin definir"></label></div>
    <div class="stock-form-row"><label>Palets activos<input name="max_pallets" type="number" inputmode="numeric" min="1" max="1000000" placeholder="Sin definir"></label><label>Productos distintos<input name="max_products" type="number" inputmode="numeric" min="1" max="1000000" placeholder="Sin definir"></label></div>
    <button type="submit" class="library-primary">Guardar configuración</button>
  </form>
</dialog>
