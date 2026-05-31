<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$basePath       = '../';
$currentProgram = (int)($_GET['p'] ?? 1);
$db = getDB();

// Filters
$filterUser   = trim($_GET['user']   ?? '');
$filterAction = trim($_GET['action'] ?? '');
$filterDate   = trim($_GET['date']   ?? '');
$page         = max(1, (int)($_GET['pg'] ?? 1));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($filterUser)   { $where[] = 'a.username LIKE ?'; $params[] = '%' . $filterUser . '%'; }
if ($filterAction) { $where[] = 'a.action   = ?';    $params[] = $filterAction; }
if ($filterDate)   { $where[] = 'DATE(a.created_at) = ?'; $params[] = $filterDate; }

$whereSQL = implode(' AND ', $where);

$total = (int)$db->prepare("SELECT COUNT(*) FROM audit_log a WHERE $whereSQL")->execute($params) ?
    $db->prepare("SELECT COUNT(*) FROM audit_log a WHERE $whereSQL")->execute($params) &&
    ($cnt = $db->prepare("SELECT COUNT(*) FROM audit_log a WHERE $whereSQL")) ?
        ($cnt->execute($params) ? (int)$cnt->fetchColumn() : 0) : 0 : 0;

$cntStmt = $db->prepare("SELECT COUNT(*) FROM audit_log a WHERE $whereSQL");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$params[] = $perPage;
$params[] = $offset;
$stmt = $db->prepare("
    SELECT a.*, u.display_name
    FROM audit_log a
    LEFT JOIN users u ON u.id = a.user_id
    WHERE $whereSQL
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Distinct actions for filter dropdown
$actions = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// Action labels
$actionLabels = [
    'login'               => '🔑 Inicio sesión',
    'logout'              => '↩ Cierre sesión',
    'change_password'     => '🔑 Cambió contraseña',
    'create_student'      => '➕ Creó estudiante',
    'edit_student'        => '✏️ Editó estudiante',
    'delete_student'      => '🗑 Eliminó estudiante',
    'update_membership'   => '💳 Actualizó membresía',
    'enroll'              => '📌 Inscripción',
    'unenroll'            => '❌ Retiro de grupo',
    'create_slot'         => '➕ Creó grupo',
    'edit_slot'           => '✏️ Editó grupo',
    'delete_slot'         => '🗑 Eliminó grupo',
    'update_slot_status'  => '🔄 Cambió estado grupo',
    'create_user'         => '👤 Creó usuario',
    'edit_user'           => '✏️ Editó usuario',
    'delete_user'         => '🗑 Eliminó usuario',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Morning Pass – Bitácora</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<script>(function(){var t=localStorage.getItem('morning-pass-theme');if(t)document.documentElement.setAttribute('data-theme',t);})();</script>
<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="page">
  <div class="page-title">🔍 Bitácora de Actividad</div>
  <div class="page-sub">Registro de todas las acciones realizadas en el sistema.</div>

  <!-- Filters -->
  <form method="GET" class="audit-filters">
    <input type="hidden" name="p" value="<?= $currentProgram ?>">
    <div class="form-group">
      <label class="form-label">Usuario</label>
      <input type="text" name="user" class="input-field" value="<?= htmlspecialchars($filterUser) ?>" placeholder="Nombre de usuario…">
    </div>
    <div class="form-group">
      <label class="form-label">Acción</label>
      <select name="action" class="select-field">
        <option value="">— Todas —</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= htmlspecialchars($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>>
            <?= htmlspecialchars($actionLabels[$a] ?? $a) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Fecha</label>
      <input type="date" name="date" class="input-field" value="<?= htmlspecialchars($filterDate) ?>">
    </div>
    <div class="form-group" style="align-self:flex-end">
      <button type="submit" class="btn-primary">Filtrar</button>
      <a href="audit.php?p=<?= $currentProgram ?>" class="btn-secondary" style="margin-left:6px">Limpiar</a>
    </div>
  </form>

  <div class="audit-summary">
    Mostrando <?= count($logs) ?> de <?= $total ?> registros
    <?php if ($total > $perPage): ?>
      — Página <?= $page ?> de <?= $pages ?>
    <?php endif; ?>
  </div>

  <?php if (!$logs): ?>
    <div class="empty-state">No hay registros que coincidan con los filtros.</div>
  <?php else: ?>
  <div class="scroll-x">
    <table class="data-table audit-table">
      <thead>
        <tr>
          <th>Fecha y hora</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Entidad</th>
          <th>Descripción</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="audit-date"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
          <td>
            <span class="audit-user"><?= htmlspecialchars($log['display_name'] ?: $log['username']) ?></span>
            <?php if ($log['display_name'] && $log['display_name'] !== $log['username']): ?>
              <small style="color:var(--text-muted)"><?= htmlspecialchars($log['username']) ?></small>
            <?php endif; ?>
          </td>
          <td>
            <span class="audit-action audit-action-<?= htmlspecialchars($log['action']) ?>">
              <?= htmlspecialchars($actionLabels[$log['action']] ?? $log['action']) ?>
            </span>
          </td>
          <td>
            <?php if ($log['entity_type']): ?>
              <span class="audit-entity"><?= htmlspecialchars($log['entity_type']) ?>
              <?php if ($log['entity_id']): ?> #<?= $log['entity_id'] ?><?php endif; ?>
              </span>
            <?php endif; ?>
          </td>
          <td class="audit-desc"><?= htmlspecialchars($log['description'] ?? '—') ?></td>
          <td class="audit-ip"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php
    $qBase = http_build_query(array_filter([
      'p'      => $currentProgram,
      'user'   => $filterUser,
      'action' => $filterAction,
      'date'   => $filterDate,
    ]));
    ?>
    <?php if ($page > 1): ?>
      <a href="?<?= $qBase ?>&pg=<?= $page - 1 ?>" class="btn-secondary">‹ Anterior</a>
    <?php endif; ?>
    <span>Página <?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?>
      <a href="?<?= $qBase ?>&pg=<?= $page + 1 ?>" class="btn-secondary">Siguiente ›</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
