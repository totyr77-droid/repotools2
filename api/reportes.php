<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reports.php';
require_once __DIR__ . '/../includes/report-log-filters.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function report_response(int $status, array $body): void { http_response_code($status); echo json_encode($body, JSON_UNESCAPED_UNICODE); exit; }
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) report_response(405, ['error' => 'Solicitud no válida.']);
$user = current_user();
if ($method === 'POST' && !valid_csrf()) report_response(403, ['error' => 'La sesión venció. Recarga la página.']);
$csrf = csrf_token(); session_write_close();
try {
    $db = database(); $moderator = can_edit_catalog($db, $user);
    $audit = report_can_audit($db, $user);
    $excludedUser=report_is_manager($db,$user)?0:(int)($user['id']??0);
    if($method==='GET'&&($_GET['view']??'')==='general'){
        if(!report_is_manager($db,$user))throw new CatalogException('Solo managers pueden consultar el panel general.',403);
        $today=(new DateTimeImmutable('now',new DateTimeZone('America/Buenos_Aires')))->format('Y-m-d');
        $near=(new DateTimeImmutable($today))->modify('+30 days')->format('Y-m-d');
        $stats=$db->query("SELECT (SELECT COUNT(*) FROM users) AS users,(SELECT COUNT(*) FROM pallets WHERE released=0 AND activated=1) AS pallets,(SELECT COUNT(*) FROM products) AS products,(SELECT COUNT(*) FROM stock_branches) AS branches,(SELECT COUNT(*) FROM product_reports WHERE status='pending') AS pending,(SELECT COUNT(*) FROM product_reports WHERE status='approved') AS approved,(SELECT COUNT(*) FROM product_reports WHERE status='rejected') AS rejected,(SELECT COUNT(*) FROM stock_shares WHERE active=1) AS shares,(SELECT COUNT(*) FROM report_blocks) AS blocked")->fetch();
        $expiry=$db->prepare("SELECT COALESCE(SUM(CASE WHEN b.expires<? THEN b.units ELSE 0 END),0) AS expired,COALESCE(SUM(CASE WHEN b.expires>=? AND b.expires<=? THEN b.units ELSE 0 END),0) AS expiring FROM pallet_products b JOIN pallets p ON p.id=b.pallet_id WHERE p.released=0 AND p.activated=1 AND b.expires<>''");$expiry->execute([$today,$today,$near]);
        $stats=array_map('intval',array_merge($stats,$expiry->fetch()));
        report_response(200,['stats'=>$stats,'roles'=>$db->query('SELECT role,COUNT(*) AS total FROM users GROUP BY role ORDER BY role')->fetchAll(),'csrf'=>$csrf]);
    }
    if ($method === 'GET' && in_array($_GET['view'] ?? '', ['history', 'logs'], true)) {
        if (!$audit) throw new CatalogException('Solo administradores y managers pueden consultar esta sección.', 403);
        $page = filter_var($_GET['page'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000]]);
        if ($page === false) throw new CatalogException('Página no válida.', 422);
        $offset = $page * 50;
        if ($_GET['view'] === 'history') {
            $items = $db->query("SELECT r.id, r.ean, r.status, r.review_note, r.reviewed_at, r.original_data, r.proposed_data, COALESCE(u.name, 'Invitado') AS submitter_name, v.name AS reviewer_name FROM product_reports r LEFT JOIN users u ON u.id = r.user_id LEFT JOIN users v ON v.id = r.reviewed_by WHERE r.status IN ('approved','rejected') ORDER BY r.reviewed_at DESC, r.id DESC LIMIT 51 OFFSET {$offset}")->fetchAll();
            foreach ($items as &$item) { $item['changes'] = report_proposed_changes(json_decode($item['original_data'], true), json_decode($item['proposed_data'], true) ?? []); unset($item['original_data'], $item['proposed_data']); } unset($item);
        } else {
            $filters=report_log_filters();$filter=$_GET['filter']??'';
            if(!is_string($filter)||($filter!==''&&!isset($filters[$filter])))throw new CatalogException('Filtro no válido.',422);
            $params=[];$where='';
            if($filter!==''){$definition=$filters[$filter];$where=' WHERE event_type=?';$params[]=$definition['type'];if(isset($definition['action'])){$where.=' AND action=?';$params[]=$definition['action'];}}
            $actionWhere=$where;$actionParams=$params;
            $actor=$_GET['actor']??'';
            if(!is_string($actor)||($actor!==''&&(!ctype_digit($actor)||(int)$actor<1)))throw new CatalogException('Usuario no válido.',422);
            if($actor!==''){$where.=($where===''?' WHERE ':' AND ').'actor_id=?';$params[]=(int)$actor;}
            $directEdit=catalog_direct_edit_condition($db,'e.after_data');
            $events = "(
                SELECT 'product_edit' AS event_type, u.name AS actor, e.ean AS target, e.created_at, NULL AS report_id, 'edited' AS action, e.id AS event_id, u.id AS actor_id FROM product_edits e JOIN users u ON u.id = e.user_id WHERE {$directEdit}
                UNION ALL SELECT 'report_review', u.name, r.ean, r.reviewed_at, r.id, r.status, r.id, u.id FROM product_reports r JOIN users u ON u.id = r.reviewed_by WHERE r.reviewed_at IS NOT NULL
                UNION ALL SELECT 'report_block', a.name, u.name, e.created_at, NULL, e.action, e.id, a.id FROM report_block_events e JOIN users a ON a.id = e.actor_id JOIN users u ON u.id = e.user_id
                UNION ALL SELECT 'pallet_admin', u.name, e.code, e.created_at, NULL, e.action, e.id, u.id FROM pallet_admin_events e JOIN users u ON u.id = e.user_id
            UNION ALL SELECT 'user_role', a.name, u.name, e.created_at, NULL, e.old_role || ' → ' || e.new_role, e.id, a.id FROM user_role_events e JOIN users a ON a.id=e.actor_id JOIN users u ON u.id=e.user_id
            UNION ALL SELECT 'user_profile', a.name, u.name, e.created_at, NULL, 'profile_updated', e.id, a.id FROM user_profile_events e JOIN users a ON a.id=e.actor_id JOIN users u ON u.id=e.user_id
            UNION ALL SELECT 'branch', a.name, e.branch_name, e.created_at, NULL, e.action, e.id, a.id FROM branch_events e JOIN users a ON a.id=e.actor_id
            ) events";
            $actorsQuery=$db->prepare("SELECT DISTINCT actor_id AS id, actor AS name FROM {$events}{$actionWhere} ORDER BY name, id");$actorsQuery->execute($actionParams);$actors=$actorsQuery->fetchAll();
            $query=$db->prepare("SELECT * FROM {$events}{$where} ORDER BY created_at DESC, event_type, event_id DESC LIMIT 51 OFFSET {$offset}");
            $query->execute($params);$items=$query->fetchAll();
        }
        report_response(200, ['actors' => $actors ?? [], 'items' => array_slice($items, 0, 50), 'has_more' => count($items) > 50, 'can_audit' => true, 'csrf' => $csrf]);
    }
    if ($method === 'GET' && isset($_GET['pending_count'])) {
        if (!$moderator) throw new CatalogException('No tienes permiso para revisar reportes.', 403);
        $count = $db->prepare("SELECT COUNT(*) FROM product_reports WHERE status = 'pending' AND (user_id IS NULL OR user_id <> ?)"); $count->execute([$excludedUser]);
        report_response(200, ['pending_count' => (int) $count->fetchColumn()]);
    }
    $owner = report_owner($user, $_POST['owner_token'] ?? $_SERVER['HTTP_X_REPORT_OWNER'] ?? '');
    if ($method === 'POST') {
        if (($_POST['action'] ?? '') === 'review') {
            if (!$user) throw new CatalogException('Inicia sesión para revisar reportes.', 401);
            $report = review_product_report($db, $user, (int) ($_POST['id'] ?? 0), $_POST);
        } elseif (in_array($_POST['action'] ?? '', ['block_reports', 'unblock_reports'], true)) {
            if (!$user) throw new CatalogException('Inicia sesión para gestionar reportes.', 401);
            $report = report_set_block($db, $user, (int) ($_POST['id'] ?? 0), $_POST['action'] === 'block_reports');
        } else {
            if (!in_array($_POST['action'] ?? '', ['', 'submit'], true)) throw new CatalogException('Solicitud no válida.', 422);
            report_require_submission($db, $user);
            $photos = [];
            foreach (['ean', 'product'] as $kind) if ($jpeg = prepare_report_photo($_FILES['photo_' . $kind] ?? [])) $photos[$kind] = $jpeg;
            $report = submit_product_report($db, $user, $owner, $_POST, $photos);
        }
        report_response(200, ['report' => $report]);
    }
    if (isset($_GET['id'])) {
        $report = report_detail($db, (int) $_GET['id'], $owner, $moderator);
        if (isset($_GET['photo'])) {
            $kind = $_GET['photo'];
            if (!is_string($kind) || !in_array($kind, ['ean', 'product'], true)) throw new CatalogException('Foto no disponible.', 404);
            $stmt = $db->prepare('SELECT object_key FROM report_photos WHERE report_id = ? AND kind = ?'); $stmt->execute([$report['id'], $kind]);
            $key = $stmt->fetchColumn(); if (!$key) throw new CatalogException('Foto no disponible.', 404);
            $bytes = report_object('GET', $key); header('Content-Type: image/jpeg'); echo $bytes; exit;
        }
        $report=report_resolve_reference_photo($db,$report);
        report_response(200, ['report' => $report, 'csrf' => $csrf, 'can_review' => $moderator]);
    }
    $queue = $moderator && ($_GET['queue'] ?? '') === '1';
    $stmt = $db->prepare('SELECT r.id, r.ean, r.description, r.status, r.created_at, r.review_note' . ($queue ? ", COALESCE(u.name, 'Invitado') AS submitter_name" : '') . ' FROM product_reports r' . ($queue ? ' LEFT JOIN users u ON u.id = r.user_id' : '') . ' WHERE ' . ($queue ? "r.status = 'pending' AND (r.user_id IS NULL OR r.user_id <> ?)" : 'r.owner_key = ?') . ' ORDER BY r.id DESC LIMIT 50'); $stmt->execute($queue ? [$excludedUser] : [$owner]);
    $reports = $stmt->fetchAll(); $blocked = [];
    if ($queue) $blocked = $db->query('SELECT u.name, u.email, (SELECT MAX(r.id) FROM product_reports r WHERE r.user_id = u.id) AS report_id FROM report_blocks b JOIN users u ON u.id = b.user_id WHERE u.id <> ' . $excludedUser . ' ORDER BY b.created_at DESC')->fetchAll();
    report_response(200, ['reports' => $reports, 'blocked_reports' => $blocked, 'csrf' => $csrf, 'can_review' => $moderator, 'can_submit' => !report_is_blocked($db, $user), 'photos_enabled' => report_storage_ready(), 'registered' => $user !== null]);
} catch (CatalogException $error) { report_response($error->status, ['error' => $error->getMessage()]); }
catch (Throwable $error) { error_log('Repotools reports error: ' . get_class($error) . ' code=' . $error->getCode()); report_response(503, ['error' => 'No pudimos acceder a los reportes. Intenta nuevamente.']); }
