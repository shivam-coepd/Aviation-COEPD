<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_admin_login();

$allowedStatuses = ['new','contacted','interested','not_interested','enrolled'];
$statusFilter = $_GET['status'] ?? '';
$search = trim((string)($_GET['q'] ?? ''));

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];

if ($statusFilter !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $statusFilter;
}

if ($search !== '') {
    $where[] = '(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search OR preferred_market LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$sql = 'SELECT * FROM leads';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$totalStmt = db()->query('SELECT COUNT(*) AS total FROM leads');
$totalLeads = (int)$totalStmt->fetch()['total'];

$newStmt = db()->query("SELECT COUNT(*) AS total FROM leads WHERE status = 'new'");
$newLeads = (int)$newStmt->fetch()['total'];

$todayStmt = db()->query('SELECT COUNT(*) AS total FROM leads WHERE DATE(created_at) = CURRENT_DATE');
$todayLeads = (int)$todayStmt->fetch()['total'];

$updateMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $leadId = (int)($_POST['lead_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $notes = trim((string)($_POST['admin_notes'] ?? ''));

    if ($leadId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $update = db()->prepare('UPDATE leads SET status = :status, admin_notes = :notes WHERE id = :id');
        $update->execute([
            ':status' => $newStatus,
            ':notes' => $notes ?: null,
            ':id' => $leadId,
        ]);

        $updateMessage = 'Lead updated successfully.';
        header('Location: dashboard.php?' . http_build_query(['status' => $statusFilter, 'q' => $search]));
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Leads Dashboard | <?= htmlspecialchars(SITE_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Source+Serif+4:wght@600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#f3f5f7;color:#20272e;font-size:15px}
a{color:#2f6da4;text-decoration:none}
a:hover{text-decoration:underline}
.topbar{background:#102538;color:#d8e2ea;padding:14px 0}
.topbar-inner{max-width:1280px;margin:auto;padding:0 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.topbar strong{color:#fff;font-family:'Source Serif 4',serif;font-size:18px}
.logout{color:#fff;background:#b4622b;padding:9px 16px;border-radius:6px;font-weight:600;font-size:14px}
.logout:hover{background:#93491c;text-decoration:none}
.container{max-width:1280px;margin:auto;padding:28px 24px 60px}
.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;margin-bottom:24px}
h1{font-family:'Source Serif 4',serif;color:#17324d;font-size:30px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:26px}
.stat{background:#fff;border:1px solid #dfe4e8;border-radius:10px;padding:22px}
.stat b{display:block;font-family:'Source Serif 4',serif;font-size:30px;color:#17324d}
.stat span{color:#5f6b76;font-size:14px}
.toolbar{background:#fff;border:1px solid #dfe4e8;border-radius:10px;padding:18px;margin-bottom:20px;display:flex;gap:14px;flex-wrap:wrap;align-items:center}
.toolbar form{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
input[type=text],select{padding:10px;border:1px solid #c8d0d7;border-radius:6px;font:inherit}
button,.btn{padding:10px 16px;border-radius:6px;border:0;background:#17324d;color:#fff;font-weight:600;cursor:pointer;font-size:14px;text-decoration:none;display:inline-block}
.btn-secondary{background:#fff;color:#17324d;border:1px solid #c8d0d7}
.export{background:#2f6da4}
.table-card{background:#fff;border:1px solid #dfe4e8;border-radius:10px;overflow:hidden}
.table-scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:1150px}
th{background:#17324d;color:#fff;text-align:left;padding:14px;font-size:12.5px;text-transform:uppercase;letter-spacing:.06em}
td{padding:14px;border-bottom:1px solid #e6eaee;vertical-align:top}
tr:last-child td{border-bottom:0}
.lead-name{font-weight:600;color:#17324d}
.muted{color:#5f6b76;font-size:13px}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:600}
.badge-new{background:#eaf2f8;color:#2f6da4}
.badge-contacted{background:#fff4e2;color:#96601d}
.badge-interested{background:#e8f6ec;color:#276b3a}
.badge-not_interested{background:#f4f0ee;color:#7a6a61}
.badge-enrolled{background:#e5eff8;color:#1d4f7c}
.update-form{display:grid;gap:8px;min-width:240px}
.update-form select,.update-form textarea{width:100%;padding:8px;border:1px solid #c8d0d7;border-radius:6px;font:inherit;font-size:13.5px}
.update-form textarea{min-height:62px;resize:vertical}
.update-form button{padding:8px;font-size:13px}
.success{background:#e8f6ec;border:1px solid #b7d3b8;color:#275c31;padding:12px;border-radius:6px;margin-bottom:20px}
.empty{padding:48px;text-align:center;color:#5f6b76}
@media(max-width:760px){.stats{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-inner">
    <div><strong><?= htmlspecialchars(SITE_NAME) ?></strong> — Leads Dashboard</div>
    <div>
      Signed in as <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong>
      &nbsp;·&nbsp; <a class="logout" href="logout.php">Logout</a>
    </div>
  </div>
</div>

<div class="container">
  <div class="page-head">
    <div>
      <h1>Program Enquiries</h1>
      <p class="muted">View, search, update, and export all enquiries submitted through the website.</p>
    </div>
    <a class="btn export" href="export-leads.php">Export CSV</a>
  </div>

  <?php if ($updateMessage): ?><div class="success"><?= htmlspecialchars($updateMessage) ?></div><?php endif; ?>

  <div class="stats">
    <div class="stat"><b><?= $totalLeads ?></b><span>Total enquiries received</span></div>
    <div class="stat"><b><?= $newLeads ?></b><span>New enquiries awaiting contact</span></div>
    <div class="stat"><b><?= $todayLeads ?></b><span>Enquiries received today</span></div>
  </div>

  <div class="toolbar">
    <form method="get" action="dashboard.php">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, phone, or market">
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach ($allowedStatuses as $status): ?>
          <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Apply Filters</button>
      <a class="btn btn-secondary" href="dashboard.php">Reset</a>
    </form>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>Lead Details</th>
            <th>Contact</th>
            <th>Profile</th>
            <th>Submitted</th>
            <th>Lead Management</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$leads): ?>
            <tr><td colspan="5" class="empty">No enquiries found for the selected filters.</td></tr>
          <?php endif; ?>

          <?php foreach ($leads as $lead): ?>
            <tr>
              <td>
                <div class="lead-name"><?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) ?></div>
                <div class="muted">Lead #<?= (int)$lead['id'] ?></div>
                <?php if (!empty($lead['career_goal'])): ?>
                  <div class="muted" style="margin-top:8px;max-width:300px"><strong>Goal:</strong> <?= nl2br(htmlspecialchars($lead['career_goal'])) ?></div>
                <?php endif; ?>
              </td>

              <td>
                <div><a href="mailto:<?= htmlspecialchars($lead['email']) ?>"><?= htmlspecialchars($lead['email']) ?></a></div>
                <div class="muted"><?= htmlspecialchars($lead['phone']) ?></div>
                <div class="muted"><?= htmlspecialchars($lead['preferred_market'] ?: 'Market not specified') ?></div>
              </td>

              <td>
                <div><strong>Experience:</strong> <?= htmlspecialchars($lead['experience']) ?></div>
                <div><strong>Background:</strong> <?= htmlspecialchars($lead['background']) ?></div>
              </td>

              <td>
                <?= htmlspecialchars(date('d M Y', strtotime($lead['created_at']))) ?>
                <div class="muted"><?= htmlspecialchars(date('h:i A', strtotime($lead['created_at']))) ?></div>
                <span class="badge badge-<?= htmlspecialchars($lead['status']) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $lead['status']))) ?></span>
              </td>

              <td>
                <form class="update-form" method="post" action="dashboard.php">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
                  <select name="status">
                    <?php foreach ($allowedStatuses as $status): ?>
                      <option value="<?= htmlspecialchars($status) ?>" <?= $lead['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <textarea name="admin_notes" placeholder="Add follow-up notes"><?= htmlspecialchars($lead['admin_notes'] ?? '') ?></textarea>
                  <button type="submit">Update Lead</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>
