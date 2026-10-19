<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/branch-team.php';
require_once __DIR__.'/includes/stock-icons.php';
$token=$_GET['invitacion']??($_SESSION['branch_team_invitation']??'');
if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))$token='';
if($token)$_SESSION['branch_team_invitation']=$token;
if(!current_user()){header('Location: cuenta.php?next=sucursales');exit;}
$invite=$token?stock_query(database(),'SELECT b.name,i.role,i.revoked,i.used_by,i.expires FROM branch_team_invites i JOIN stock_branches b ON b.id=i.branch_id WHERE i.token_hash=?',[hash('sha256',$token)])->fetch():false;
$teamPendingInvite=$invite&&!$invite['revoked']&&!$invite['used_by']&&$invite['expires']>time();
if(!$teamPendingInvite)unset($_SESSION['branch_team_invitation']);
if(!$teamPendingInvite&&!team_can_assign(database(),(int)current_user()['id'])&&!team_locations(database(),(int)current_user()['id'])){header('Location: index.php');exit;}
header('Referrer-Policy: no-referrer');
$paginaEquipo=true;$publicPageTitle='Mi equipo';include 'vistas/header.vista.php';
?>
<main class="library-main team-page" data-team-page data-csrf="<?= escape(csrf_token()) ?>">
<div class="team-page-title"><h1><?= stock_icon('branch') ?>Mi equipo</h1><a href="index.php">Inicio</a></div>
<p id="teamStatus" role="status">Cargando sucursales…</p>
<?php if($token): ?>
<section id="teamInvitation" class="team-section">
<?php if($invite&&!$invite['revoked']&&!$invite['used_by']&&$invite['expires']>time()): ?>
<h2>Invitación a <?= escape($invite['name']) ?></h2><p>Acceso como <?= $invite['role']==='manager'?'encargado':'operador' ?>. Usás tu misma cuenta.</p>
<button type="button" class="library-primary" id="teamAccept" data-token="<?= escape($token) ?>">Aceptar invitación</button>
<?php else: ?><p>La invitación venció o ya fue utilizada. Pedí una nueva.</p><?php unset($_SESSION['branch_team_invitation']);endif; ?>
</section><?php endif; ?>
<label>Sucursal activa<select id="teamLocation"><option value="0">Uso personal</option></select></label>
<p id="teamRole" class="team-hint"></p>
<div id="teamOverview" hidden>
<section class="team-section"><div class="team-section-title"><h2>Palets de la sucursal</h2><button type="button" class="team-text" id="teamRefresh">Actualizar</button></div><div id="teamPallets"></div><button type="button" class="team-text" id="teamMore" hidden>Ver más palets</button></section>
<section class="team-section"><h2>Personas autorizadas</h2><p class="team-hint">El administrador conserva el control del espacio. Cada vínculo corresponde a esta sucursal.</p><div id="teamMembers"></div>
<form id="teamInviteForm" class="team-inline"><label>Nuevo acceso<select name="role"><option value="operator">Operador</option><option value="manager" id="teamManagerOption">Encargado</option></select></label><button type="submit" class="library-secondary">Crear invitación</button></form>
<div id="teamInviteResult" hidden><label>Enlace de un uso · vence en 3 días<input id="teamInviteLink" readonly></label><button type="button" class="team-text" id="teamCopy">Copiar enlace</button><button type="button" class="team-text" id="teamShare">Compartir</button><div id="teamInviteQR" aria-label="QR de invitación"></div></div>
<details><summary>Invitaciones pendientes</summary><div id="teamInvites"></div></details></section>
<section class="team-section"><h2>Últimos movimientos</h2><div id="teamMovements"></div><details><summary>Accesos y asignaciones</summary><div id="teamEvents"></div></details></section>
</div>
<p id="teamOperatorHint" hidden>Podés trabajar con los palets desde Escanear palet en Inicio. El encargado consulta el inventario completo.</p>
<button type="button" class="team-text team-danger" id="teamLeave" hidden>Desvincularme de esta sucursal</button>
<details class="team-section" id="teamSetup" hidden><summary>Asignar empresa</summary><p>Asignación exclusiva de managers de Repotools. La persona responsable usa su cuenta habitual.</p>
<form id="teamCreateForm"><label>Empresa<select id="teamAssignWorkspace"><option value="0">Nueva empresa</option></select></label><label id="teamOwnerLabel">Correo de la persona responsable<input name="owner_email" type="email" maxlength="254" value="<?= escape(current_user()['email']) ?>" required></label><label id="teamWorkspaceLabel">Nombre de la empresa <small>(opcional)</small><input name="workspace_name" maxlength="100" placeholder="Ej.: Mi equipo"></label><label>Sucursal<select name="branch_id" id="teamDirectory"><option value="0">Crear una sucursal</option></select></label>
<div id="teamNewBranch"><label>Nombre<input name="name" maxlength="100" required></label><label>Dirección<input name="address" maxlength="160" required></label><div class="team-inline"><label>Localidad<input name="locality" maxlength="100" required></label><label>Provincia<input name="province" maxlength="80" required></label></div></div>
<button type="submit" class="library-primary" id="teamCreateButton">Crear equipo</button><p class="team-hint">La sucursal debe estar libre de otro equipo. Esta asignación no cambia el rango general de la persona responsable.</p></form></details>
<dialog id="teamConfirm" class="stock-dialog"><h2 id="teamConfirmTitle">Quitar acceso</h2><p id="teamConfirmText"></p><div class="team-inline"><button type="button" class="library-secondary" id="teamConfirmCancel">Cancelar</button><button type="button" class="library-primary" id="teamConfirmYes">Confirmar</button></div></dialog>
</main>
<?php include 'vistas/footer.vista.php'; ?>
