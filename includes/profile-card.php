<?php
require_once __DIR__.'/achievements.php';
require_once __DIR__.'/stock-icons.php';
function profile_card(PDO $db,int $id,bool $own=false): void {
    $q=$db->prepare('SELECT id,name,public_name,company,role,created_at,email_verified_at FROM users WHERE id=?');$q->execute([$id]);$person=$q->fetch();if(!$person)return;
    $name=get_public_user_name($person);$initials='';foreach(array_slice(preg_split('/\s+/u',trim($name)),0,2) as $part)$initials.=mb_substr($part,0,1);
    $data=achievement_snapshot($db,$id,$own);$joined=(new DateTimeImmutable($person['created_at'],new DateTimeZone('UTC')))->format('m/Y');
    $roles=['moderador'=>['MOD','Moderador','shield'],'administrador'=>['ADM','Administrador','settings'],'manager'=>['MNG','Manager','crown']];
    ?>
    <section class="profile-showcase" data-achievements="<?= escape(json_encode($data,JSON_UNESCAPED_UNICODE)) ?>" aria-label="<?= $own?'Mi perfil':'Perfil de usuario' ?>">
      <div class="profile-identity"><span class="profile-avatar" aria-hidden="true"><?= escape(mb_strtoupper($initials)) ?></span><div class="profile-identity-text"><div class="profile-name-line"><h2 data-profile-name><?= escape($name) ?></h2><?php if($person['email_verified_at']): ?><button type="button" class="profile-verified" aria-label="Correo verificado" title="Correo verificado" aria-expanded="false"><?= stock_icon('verified') ?></button><?php endif; ?><?php if(!$own&&isset($roles[$person['role']])): $r=$roles[$person['role']]; ?><span class="account-role account-role-<?= escape($person['role']) ?>" aria-label="<?= $r[1] ?>"><?= stock_icon($r[2]) ?><?= $r[0] ?></span><?php endif; ?></div><p class="profile-membership">Miembro desde <?= escape($joined) ?></p><?php if(!$own&&trim((string)$person['company'])!==''): ?><p class="profile-membership"><?= escape($person['company']) ?></p><?php endif; ?><p class="profile-verification-info" hidden>Correo verificado</p></div></div>
      <div class="profile-totals"><div><strong data-profile-pallets><?= $data['pallets'] ?></strong><span>Palets creados</span></div><div><strong data-profile-days><?= $data['days'] ?></strong><span>Días activos</span></div><div><strong data-profile-unlocked><?= $data['unlocked'] ?></strong><span>Insignias</span></div></div>
      <section class="profile-achievements"><div class="profile-section-line"><h3><?= $own?'Mis insignias':'Insignias' ?></h3><button class="profile-all" type="button" aria-expanded="false">Ver todas <?= stock_icon('chevron') ?></button></div><template class="profile-lock-template"><?= stock_icon('lock') ?></template><div class="profile-featured"></div><p class="profile-empty"<?= $data['unlocked']?' hidden':'' ?>>Todavía <?= $own?'no tenés':'no tiene' ?> insignias. Cada acción cuenta.</p><div class="profile-next"<?= $own?'':' hidden' ?>></div><div class="profile-collection" hidden></div></section>
    </section>
    <?php
}
