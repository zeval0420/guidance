<?php
$asset_base = '../';
require(__DIR__ . '/../../credentials/db_connection.php');
include('../helperFiles/session_handler.php');
require_once('../helperFiles/admin_roles.php');

/* Gate: super admin only (re-checked against the table on every request). */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'
    || !guidance_is_super_admin($conn, $_SESSION['employeeID'] ?? '')) {
    header('Location: hub.php');
    exit();
}

if (empty($_SESSION['sql_runner_csrf'])) {
    $_SESSION['sql_runner_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['sql_runner_csrf'];

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Only .sql files that sit directly in the Guidance root folder can be run. */
$sqlDir = realpath(__DIR__ . '/..');
function list_sql_files($dir)
{
    $files = [];
    foreach (glob($dir . '/*.sql') ?: [] as $path) {
        if (!is_file($path)) continue;
        $sql = file_get_contents($path);
        $files[basename($path)] = [
            'size'      => filesize($path),
            'modified'  => filemtime($path),
            'dangerous' => (bool)preg_match('/\b(DROP\s+(TABLE|DATABASE)|TRUNCATE|DELETE\s+FROM)\b/i', $sql),
        ];
    }
    ksort($files);
    return $files;
}
$files = list_sql_files($sqlDir);

$selected = $_GET['file'] ?? '';
if (!isset($files[$selected])) $selected = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['file'] ?? '';
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $result = ['ok' => false, 'msg' => 'Your session token was invalid. Please try again.'];
    } elseif (!isset($files[$selected])) {
        $result = ['ok' => false, 'msg' => 'Unknown SQL file.'];
        $selected = '';
    } elseif ($files[$selected]['dangerous'] && trim($_POST['confirm_name'] ?? '') !== $selected) {
        $result = ['ok' => false, 'msg' => 'This file deletes data. Type its exact file name to confirm.'];
    } else {
        $sql = file_get_contents($sqlDir . '/' . $selected);
        $log = [];
        $errors = 0;
        $t0 = microtime(true);
        mysqli_report(MYSQLI_REPORT_OFF);
        if ($conn->multi_query($sql)) {
            do {
                if ($res = $conn->store_result()) {
                    $rows = [];
                    $cols = array_map(function ($f) { return $f->name; }, $res->fetch_fields());
                    while ($r = $res->fetch_row()) $rows[] = $r;
                    $log[] = ['type' => 'rows', 'cols' => $cols, 'rows' => $rows];
                    $res->free();
                } else {
                    $log[] = ['type' => 'affected', 'n' => $conn->affected_rows];
                }
                if (!$conn->more_results()) break;
            } while ($conn->next_result());
        }
        if ($conn->errno) {
            $errors++;
            $log[] = ['type' => 'error', 'msg' => $conn->error];
        }
        $result = [
            'ok'  => $errors === 0,
            'msg' => $errors === 0 ? 'Finished without errors.' : 'Stopped at an error; statements before it were already applied.',
            'log' => $log,
            'sec' => round(microtime(true) - $t0, 2),
        ];
        error_log('guidance sql_runner: ' . ($_SESSION['employeeID'] ?? '?') . ' ran ' . $selected . ($errors ? ' (error)' : ' (ok)'));
    }
}
$preview = $selected !== '' ? file_get_contents($sqlDir . '/' . $selected, false, null, 0, 20000) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Runner</title>
    <?php include('../helperFiles/headData.php'); ?>
    <style>
        .sql-wrapper { flex: 1 0 auto; width: 100%; max-width: 1100px; margin: 0 auto; padding: 36px 24px 56px; }
        .sql-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .sql-head h1 { margin: 0; font-size: 26px; font-weight: 800; color: var(--color-primary); }
        .sql-head p { margin: 6px 0 0; color: var(--color-text-secondary); font-size: 14px; }
        .sql-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .sql-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card); box-shadow: var(--shadow-card); padding: 20px; margin-bottom: 20px; }
        .sql-card h2 { margin: 0 0 12px; font-size: 16px; font-weight: 700; }
        .sql-list a { display: flex; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 8px; text-decoration: none; color: var(--color-text-primary); }
        .sql-list a:hover, .sql-list a.active { background: rgba(43, 85, 196, 0.09); }
        .sql-list small { color: var(--color-text-secondary); }
        .tag-danger { background: #fdeaea; color: #c62828; border-radius: 6px; padding: 1px 7px; font-size: 11px; font-weight: 700; margin-left: 6px; }
        pre.sql-preview { max-height: 320px; overflow: auto; background: #0f172a; color: #e2e8f0; padding: 14px; border-radius: 8px; font-size: 12px; white-space: pre-wrap; word-break: break-word; }
        .sql-warn { background: #fff4e0; color: #8a5200; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; font-size: 13px; }
        .sql-out table { border-collapse: collapse; margin: 8px 0; font-size: 12px; }
        .sql-out th, .sql-out td { border: 1px solid var(--color-border); padding: 4px 8px; }
        .sql-out .err { color: #c62828; font-weight: 600; }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="sql-wrapper">
        <div class="sql-head">
            <div>
                <h1>SQL Runner</h1>
                <p>Run the <code>.sql</code> files in the Guidance folder against the database. Super admin only.</p>
            </div>
            <div class="sql-actions">
                <a href="hub.php" class="btn-liquid"><i class="bi bi-arrow-left"></i>&nbsp; Back to Hub</a>
                <a href="../logout.php" class="btn-liquid-danger"><i class="bi bi-box-arrow-right"></i>&nbsp; Logout</a>
            </div>
        </div>

        <?php if ($result): ?>
            <div class="alert alert-<?php echo $result['ok'] ? 'success' : 'danger'; ?>">
                <?php echo h($result['msg']); ?><?php if (isset($result['sec'])) echo ' (' . h($result['sec']) . 's)'; ?>
            </div>
            <?php if (!empty($result['log'])): ?>
                <div class="sql-card sql-out">
                    <h2>Output</h2>
                    <?php foreach ($result['log'] as $i => $e): ?>
                        <?php if ($e['type'] === 'error'): ?>
                            <div class="err"><?php echo h($e['msg']); ?></div>
                        <?php elseif ($e['type'] === 'rows'): ?>
                            <div class="table-responsive"><table>
                                <tr><?php foreach ($e['cols'] as $c) echo '<th>' . h($c) . '</th>'; ?></tr>
                                <?php foreach (array_slice($e['rows'], 0, 200) as $r): ?>
                                    <tr><?php foreach ($r as $v) echo '<td>' . h($v ?? 'NULL') . '</td>'; ?></tr>
                                <?php endforeach; ?>
                            </table></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php $n = count(array_filter($result['log'], function ($e) { return $e['type'] === 'affected'; })); ?>
                    <?php if ($n): ?><div><?php echo $n; ?> statement(s) executed.</div><?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="sql-card">
            <h2>Available files</h2>
            <div class="sql-list">
                <?php if (!$files): ?><p>No .sql files found.</p><?php endif; ?>
                <?php foreach ($files as $name => $f): ?>
                    <a href="?file=<?php echo urlencode($name); ?>" class="<?php echo $name === $selected ? 'active' : ''; ?>">
                        <span><i class="bi bi-filetype-sql"></i> <?php echo h($name); ?>
                            <?php if ($f['dangerous']): ?><span class="tag-danger">deletes data</span><?php endif; ?></span>
                        <small><?php echo number_format($f['size'] / 1024, 1); ?> KB &middot; <?php echo date('M j, Y g:i A', $f['modified']); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($selected !== ''): ?>
            <div class="sql-card">
                <h2><?php echo h($selected); ?></h2>
                <pre class="sql-preview"><?php echo h($preview); ?><?php if ($files[$selected]['size'] > 20000) echo "\n… (preview truncated; the whole file will run)"; ?></pre>
                <form method="post" onsubmit="return confirm('Run <?php echo h($selected); ?> on the database now?');">
                    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                    <input type="hidden" name="file" value="<?php echo h($selected); ?>">
                    <?php if ($files[$selected]['dangerous']): ?>
                        <div class="sql-warn">
                            <strong>Warning:</strong> this file contains DROP / TRUNCATE / DELETE statements and can erase data
                            (including accounts). Type the file name to confirm.
                            <input type="text" name="confirm_name" class="form-control" style="margin-top:8px" autocomplete="off" placeholder="<?php echo h($selected); ?>">
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn-liquid-success"><i class="bi bi-play-fill"></i>&nbsp; Run this file</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include('../helperFiles/footer.php'); ?>
</body>

</html>
