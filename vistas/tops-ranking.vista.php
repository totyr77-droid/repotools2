<?php if(in_array($category,['actividad','sucursales'],true)): ?><p class="settings-hint">Cuenta las operaciones registradas desde la activación de esta estadística.</p><?php endif; ?>
<?php if ($error): ?><p role="alert"><?= escape($error) ?></p>
<?php elseif (!$ranking['leaders']): ?><p class="tops-empty">Todavía no hay actividad <?= $period==='semana'?'esta semana':($period==='mes'?'este mes':'registrada') ?>.</p>
<?php else: ?>
<div class="tops-table-wrap"><table class="tops-table"><thead><tr><th scope="col">Puesto</th><th scope="col"><?= $category==='sucursales'?'Sucursal':'Usuario' ?></th><th scope="col"><?= escape(tops_categories()[$category][0]) ?></th></tr></thead><tbody>
<?php foreach($ranking['leaders'] as $row): ?><tr<?= $row['me']?' class="tops-me"':'' ?>><td><span class="tops-position<?= $row['position']<=3?' tops-position-'.$row['position']:'' ?>"><?= $row['position']<=3?'<i class="fa-solid fa-medal" aria-hidden="true"></i> ':'' ?><?= $row['position'] ?></span></td><th scope="row"><?= escape($row['name']) ?><?= $row['me']?' <span class="tops-you">Vos</span>':'' ?></th><td><?= $row['value'] ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if ($ranking['own']): $row=$ranking['own']; ?><p class="tops-own">Tu período: <strong><?= $row['position']?'#'.$row['position'].' · ':'' ?><?= escape($row['name']) ?> · <?= $row['value'] ?></strong></p><?php endif; ?>
<?php endif; ?>

<?php if(!$error&&!$ranking['leaders']&&$ranking['own']): ?><p class="tops-own">Tu período: <strong>0 <?= $category==='referidos'?'referidos confirmados':'operaciones' ?></strong></p><?php endif; ?>
