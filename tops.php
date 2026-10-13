<?php
require_once __DIR__ . '/includes/auth.php';
$viewer = current_user();
$viewerId = $viewer ? (int) $viewer['id'] : null;
$paginaCuenta = true;
$paginaTops = true;
$leaders = [];
$stats = ['unique_codes' => 0, 'total_scans' => 0];
$error = '';
$category = in_array($_GET['categoria'] ?? '', ['reportes', 'aportes'], true) ? $_GET['categoria'] : 'escaneos';
$titles = ['escaneos' => 'Más códigos escaneados', 'reportes' => 'Más reportes enviados', 'aportes' => 'Más aportes confirmados'];
try {
    $db = database();
    if ($category !== 'escaneos') {
        $table = $category === 'reportes' ? 'product_reports' : 'report_rewards';
        $leaders = $db->query("SELECT u.id, u.name, u.public_name, COUNT(*) AS unique_codes, COUNT(*) AS total_scans FROM users u JOIN {$table} r ON r.user_id = u.id GROUP BY u.id, u.name, u.public_name ORDER BY unique_codes DESC, u.id ASC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
        if ($viewerId !== null) { $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = ?"); $stmt->execute([$viewerId]); $stats['unique_codes'] = (int) $stmt->fetchColumn(); }
    } else {
    $leaders = $db->query('SELECT u.id, u.name, u.public_name, COUNT(DISTINCT s.code) AS unique_codes, COUNT(*) AS total_scans FROM users u JOIN scan_events s ON s.user_id = u.id GROUP BY u.id, u.name, u.public_name ORDER BY unique_codes DESC, u.id ASC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
    if ($viewerId !== null) {
        $stmt = $db->prepare('SELECT COUNT(DISTINCT code) AS unique_codes, COUNT(*) AS total_scans FROM scan_events WHERE user_id = ?');
        $stmt->execute([$viewerId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    }
} catch (PDOException $exception) {
    $error = 'No pudimos cargar el ranking. Intenta más tarde.';
}
include __DIR__ . '/vistas/header.vista.php';
?>
<main class="tops-main">
  <section class="tops-panel">
    <p class="app-resumen__eyebrow">TOPs · Histórico</p>
    <nav class="tops-categories" aria-label="Categorías de TOPs"><a href="tops.php"<?= $category === 'escaneos' ? ' aria-current="page"' : '' ?>>Escaneos</a><a href="tops.php?categoria=reportes"<?= $category === 'reportes' ? ' aria-current="page"' : '' ?>>Reportes</a><a href="tops.php?categoria=aportes"<?= $category === 'aportes' ? ' aria-current="page"' : '' ?>>Aportes confirmados</a></nav>
    <h1><?= escape($titles[$category]) ?></h1>
    <?php if ($category === 'escaneos'): ?>
    <p>Cada código diferente que escaneás suma un punto en este TOP. Si volvés a escanear el mismo, aumenta tu total de escaneos, pero no suma otro punto.</p>
    <?php else: ?><p><?= $category === 'reportes' ? 'Cada reporte enviado suma uno.' : 'Cada corrección aprobada suma un punto.' ?></p><?php endif; ?>
    <?php if ($viewer): ?>
    <div class="tops-stats"><p><strong><?= (int) $stats['unique_codes'] ?></strong> <?= $category === 'escaneos' ? 'códigos únicos' : ($category === 'reportes' ? 'reportes enviados' : 'aportes confirmados') ?></p><?php if ($category === 'escaneos'): ?><p><strong><?= (int) $stats['total_scans'] ?></strong> escaneos totales</p><?php endif; ?></div>
    <?php else: ?>
    <div class="tops-join"><p>Sumá puntos con tu cuenta.</p><div><a class="tops-join-primary" href="cuenta.php?mode=registro&amp;next=tops">Crear cuenta</a><a href="cuenta.php?next=tops">Ingresar</a></div></div>
    <?php endif; ?>
    <p><?= $category === 'escaneos' ? 'Solo cuentan escaneos con sesión iniciada. Borrar la lista no borra tus puntos.' : 'Solo participan cuentas registradas.' ?></p>
    <?php if ($error): ?><p role="alert"><?= escape($error) ?></p>
    <?php elseif (!$leaders): ?><p class="tops-empty">Todavía no hay participantes.</p>
    <?php else: ?>
    <div class="tops-table-wrap"><table class="tops-table"><thead><tr><th scope="col">Puesto</th><th scope="col">Usuario</th><th scope="col"><?= $category === 'escaneos' ? 'Códigos únicos' : ($category === 'reportes' ? 'Reportes' : 'Puntos') ?></th><?php if ($category === 'escaneos'): ?><th scope="col">Escaneos totales</th><?php endif; ?></tr></thead><tbody>
    <?php $position = 0; $previous = null; foreach ($leaders as $index => $leader):
        if ($previous !== (int) $leader['unique_codes']) $position = $index + 1;
        $previous = (int) $leader['unique_codes']; ?>
      <tr<?= (int) $leader['id'] === $viewerId ? ' class="tops-me"' : '' ?>><td><span class="tops-position<?= $position <= 3 ? ' tops-position-' . $position : '' ?>"><?= $position ?></span></td><th scope="row"><?= escape(get_public_user_name($leader)) ?><?php if ((int) $leader['id'] === $viewerId): ?> <span class="tops-you">Vos</span><?php endif; ?></th><td><?= (int) $leader['unique_codes'] ?></td><?php if ($category === 'escaneos'): ?><td><?= (int) $leader['total_scans'] ?></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
  </section>
</main>
<?php include __DIR__ . '/vistas/footer.vista.php'; ?>
