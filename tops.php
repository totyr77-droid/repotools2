<?php
require_once __DIR__ . '/includes/auth.php';
require_account('tops');
$paginaCuenta = true;
$leaders = [];
$stats = ['unique_codes' => 0, 'total_scans' => 0];
$error = '';
try {
    $db = database();
    $leaders = $db->query('SELECT u.id, u.name, COUNT(DISTINCT s.code) AS unique_codes, COUNT(*) AS total_scans FROM users u JOIN scan_events s ON s.user_id = u.id GROUP BY u.id, u.name ORDER BY unique_codes DESC, u.id ASC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare('SELECT COUNT(DISTINCT code) AS unique_codes, COUNT(*) AS total_scans FROM scan_events WHERE user_id = ?');
    $stmt->execute([(int) current_user()['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    $error = 'No pudimos cargar el ranking. Intenta más tarde.';
}
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="tops-main">
  <section class="tops-panel">
    <p class="app-resumen__eyebrow">TOPs · Acumulado hasta hoy</p>
    <h1>Más códigos escaneados</h1>
    <p>Cada código distinto suma una vez por persona. Repetirlo aumenta tus escaneos totales, pero no tu puntuación en el TOP.</p>
    <div class="tops-stats"><p><strong><?= (int) $stats['unique_codes'] ?></strong> códigos únicos</p><p><strong><?= (int) $stats['total_scans'] ?></strong> escaneos totales</p></div>
    <p>Contamos los escaneos realizados con sesión iniciada desde que se habilitó TOPs. Borrar tu lista no reinicia tus estadísticas.</p>
    <?php if ($error): ?><p role="alert"><?= escape($error) ?></p>
    <?php elseif (!$leaders): ?><p class="tops-empty">Todavía no hay participantes. Escanea tu primer código para aparecer.</p>
    <?php else: ?>
    <div class="tops-table-wrap"><table class="tops-table"><thead><tr><th scope="col">Puesto</th><th scope="col">Usuario</th><th scope="col">Códigos únicos</th><th scope="col">Escaneos totales</th></tr></thead><tbody>
    <?php $position = 0; $previous = null; foreach ($leaders as $index => $leader):
        if ($previous !== (int) $leader['unique_codes']) $position = $index + 1;
        $previous = (int) $leader['unique_codes']; ?>
      <tr<?= (int) $leader['id'] === (int) current_user()['id'] ? ' class="tops-me"' : '' ?>><td><?= $position ?></td><th scope="row"><?= escape($leader['name']) ?><?= (int) $leader['id'] === (int) current_user()['id'] ? ' (vos)' : '' ?></th><td><?= (int) $leader['unique_codes'] ?></td><td><?= (int) $leader['total_scans'] ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
