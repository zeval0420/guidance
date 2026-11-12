<?php
date_default_timezone_set('Asia/Manila');
$asset_base = '../';
require(__DIR__ . '/../../credentials/db_connection.php');
include('../helperFiles/session_handler.php');

/* Gate: admins only. */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

const LOGBOOK_UNITS = [
    'gcu'      => 'Guidance and Counseling Unit',
    'wellness' => 'Wellness Room',
];
$unit = $_REQUEST['unit'] ?? 'gcu';
if (!isset(LOGBOOK_UNITS[$unit])) $unit = 'gcu';

if (empty($_SESSION['logbook_csrf'])) {
    $_SESSION['logbook_csrf'] = bin2hex(random_bytes(32));
}

$conn->query(
    "CREATE TABLE IF NOT EXISTS `guidance_logbook` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `unit` ENUM('gcu','wellness') NOT NULL,
      `LRN` VARCHAR(50) NULL DEFAULT NULL,
      `student_name` VARCHAR(255) NOT NULL,
      `grade_section` VARCHAR(100) NOT NULL DEFAULT '',
      `log_date` DATE NOT NULL,
      `time_in` DATETIME NOT NULL,
      `time_out` DATETIME NULL DEFAULT NULL,
      `source` ENUM('qr','id','name') NOT NULL DEFAULT 'name',
      `logged_by` VARCHAR(255) NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_logbook_unit_date` (`unit`, `log_date`),
      KEY `idx_logbook_lrn` (`LRN`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

/* ───────────── helpers ───────────── */

function lb_json($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

/* Most recent school year in the student directory = the current students. */
function lb_current_sy($conn)
{
    $r = $conn->query("SELECT MAX(sy) AS sy FROM student_directory");
    $row = $r ? $r->fetch_assoc() : null;
    return $row['sy'] ?? null;
}

/* Look a student up by LRN (current school year first, any year as fallback). */
function lb_find_student($conn, $lrn)
{
    $stmt = $conn->prepare(
        "SELECT s.LRN, s.lastname, s.firstname, s.middlename, d.gradeLevel, d.section
           FROM student s
           LEFT JOIN student_directory d ON d.LRN = s.LRN
          WHERE s.LRN = ?
          ORDER BY d.sy DESC LIMIT 1"
    );
    $stmt->bind_param('s', $lrn);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return null;
    $mid = trim($row['middlename'] ?? '');
    return [
        'LRN'           => $row['LRN'],
        'name'          => trim($row['lastname'] . ', ' . $row['firstname'] . ($mid !== '' ? ' ' . $mid : '')),
        'grade_section' => ($row['gradeLevel'] !== null && $row['gradeLevel'] !== '') ? $row['gradeLevel'] . ' - ' . $row['section'] : '',
    ];
}

/* A scanned/typed ID may carry extra text; try it as-is, then any digit run. */
function lb_resolve_id($conn, $raw)
{
    $raw = trim($raw);
    if ($raw === '') return null;
    if ($s = lb_find_student($conn, $raw)) return $s;
    if (preg_match_all('/\d{6,}/', $raw, $m)) {
        foreach ($m[0] as $digits) {
            if ($s = lb_find_student($conn, $digits)) return $s;
        }
    }
    return null;
}

function lb_open_entry($conn, $unit, $today, $lrn, $name)
{
    if ($lrn !== null) {
        $stmt = $conn->prepare("SELECT id FROM guidance_logbook WHERE unit = ? AND log_date = ? AND time_out IS NULL AND LRN = ? LIMIT 1");
        $stmt->bind_param('sss', $unit, $today, $lrn);
    } else {
        $stmt = $conn->prepare("SELECT id FROM guidance_logbook WHERE unit = ? AND log_date = ? AND time_out IS NULL AND LRN IS NULL AND student_name = ? LIMIT 1");
        $stmt->bind_param('sss', $unit, $today, $name);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['id'] : null;
}

function lb_time_in($conn, $unit, $now, $lrn, $name, $gs, $source)
{
    $today = substr($now, 0, 10);
    $by = $_SESSION['username'] ?? '';
    $stmt = $conn->prepare(
        "INSERT INTO guidance_logbook (unit, LRN, student_name, grade_section, log_date, time_in, source, logged_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssssss', $unit, $lrn, $name, $gs, $today, $now, $source, $by);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function lb_time_out($conn, $id, $now)
{
    $stmt = $conn->prepare("UPDATE guidance_logbook SET time_out = ? WHERE id = ? AND time_out IS NULL");
    $stmt->bind_param('si', $now, $id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

/* ───────────── AJAX ───────────── */
if (isset($_REQUEST['ajax'])) {
    $action = $_REQUEST['ajax'];
    $now    = date('Y-m-d H:i:s');
    $today  = date('Y-m-d');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['logbook_csrf'], $_POST['csrf'] ?? '')) {
            lb_json(['ok' => false, 'message' => 'Session expired. Reload the page.'], 403);
        }
    }

    /* Name suggestions from the student directory. */
    if ($action === 'search') {
        $q = trim($_GET['q'] ?? '');
        $terms = array_values(array_filter(preg_split('/[\s,]+/', $q), 'strlen'));
        if (!$terms) lb_json([]);
        $sy = lb_current_sy($conn);

        $where = [];
        $types = '';
        $params = [];
        foreach (array_slice($terms, 0, 4) as $t) {
            /* student columns use mixed collations; normalise before matching */
            $where[] = "CONCAT_WS(' ', CONVERT(s.lastname USING utf8mb4), CONVERT(s.firstname USING utf8mb4), CONVERT(s.middlename USING utf8mb4), CONVERT(s.LRN USING utf8mb4)) COLLATE utf8mb4_general_ci LIKE ?";
            $types .= 's';
            $params[] = '%' . addcslashes($t, '%_\\') . '%';
        }
        $sql = "SELECT s.LRN, s.lastname, s.firstname, s.middlename, d.gradeLevel, d.section
                  FROM student s
                  JOIN student_directory d ON d.LRN = s.LRN AND d.sy = ?
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY s.lastname, s.firstname LIMIT 8";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s' . $types, $sy, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $mid = trim($r['middlename']);
            $out[] = [
                'LRN'           => $r['LRN'],
                'name'          => trim($r['lastname'] . ', ' . $r['firstname'] . ($mid !== '' ? ' ' . $mid : '')),
                'grade_section' => $r['gradeLevel'] . ' - ' . $r['section'],
            ];
        }
        $stmt->close();
        lb_json($out);
    }

    /* Today's entries for the unit. */
    if ($action === 'list') {
        $stmt = $conn->prepare(
            "SELECT id, LRN, student_name, grade_section, time_in, time_out, source
               FROM guidance_logbook WHERE unit = ? AND log_date = ? ORDER BY time_in DESC, id DESC"
        );
        $stmt->bind_param('ss', $unit, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id'            => (int)$r['id'],
                'LRN'           => $r['LRN'],
                'name'          => $r['student_name'],
                'grade_section' => $r['grade_section'],
                'time_in'       => date('h:i A', strtotime($r['time_in'])),
                'time_out'      => $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : null,
                'source'        => $r['source'],
            ];
        }
        $stmt->close();
        lb_json(['date' => date('l, F j, Y'), 'rows' => $rows]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') lb_json(['ok' => false, 'message' => 'Invalid request.'], 405);

    /* Scan / typed ID: already in today → time out, otherwise time in. */
    if ($action === 'scan') {
        $source = ($_POST['source'] ?? '') === 'qr' ? 'qr' : 'id';
        $student = lb_resolve_id($conn, $_POST['code'] ?? '');
        if (!$student) lb_json(['ok' => false, 'message' => 'ID not found in the student directory.']);

        $open = lb_open_entry($conn, $unit, $today, $student['LRN'], $student['name']);
        if ($open) {
            lb_time_out($conn, $open, $now);
            lb_json(['ok' => true, 'type' => 'out', 'name' => $student['name'], 'message' => $student['name'] . ' timed out.']);
        }
        lb_time_in($conn, $unit, $now, $student['LRN'], $student['name'], $student['grade_section'], $source);
        lb_json(['ok' => true, 'type' => 'in', 'name' => $student['name'], 'message' => $student['name'] . ' timed in.']);
    }

    /* Name entry (time in only; time out is the row button). */
    if ($action === 'add_name') {
        $lrn  = trim($_POST['lrn'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $gs   = '';
        if ($lrn !== '') {
            $student = lb_find_student($conn, $lrn);
            if (!$student) lb_json(['ok' => false, 'message' => 'Student not found.']);
            $lrn = $student['LRN']; $name = $student['name']; $gs = $student['grade_section'];
        } else {
            $lrn = null;
        }
        if ($name === '') lb_json(['ok' => false, 'message' => 'Enter a name.']);
        $name = mb_substr($name, 0, 255);

        if (lb_open_entry($conn, $unit, $today, $lrn, $name)) {
            lb_json(['ok' => false, 'message' => $name . ' is already timed in. Use Time Out in the list.']);
        }
        lb_time_in($conn, $unit, $now, $lrn, $name, $gs, 'name');
        lb_json(['ok' => true, 'type' => 'in', 'name' => $name, 'message' => $name . ' timed in.']);
    }

    if ($action === 'timeout') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT 1 FROM guidance_logbook WHERE id = ? AND unit = ?");
        $stmt->bind_param('is', $id, $unit);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$exists) lb_json(['ok' => false, 'message' => 'Entry not found.']);
        lb_json(lb_time_out($conn, $id, $now)
            ? ['ok' => true, 'type' => 'out', 'message' => 'Timed out.']
            : ['ok' => false, 'message' => 'Already timed out.']);
    }

    lb_json(['ok' => false, 'message' => 'Unknown action.'], 400);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook – <?php echo htmlspecialchars(LOGBOOK_UNITS[$unit]); ?></title>
    <?php include('../helperFiles/headData.php'); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <style>
        .lb-wrapper { flex: 1 0 auto; width: 100%; max-width: 1400px; margin: 0 auto; padding: 28px 24px 48px; }
        .lb-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
        .lb-head h1 { margin: 0; font-size: 26px; font-weight: 800; color: var(--color-primary); }
        .lb-head p { margin: 6px 0 0; color: var(--color-text-secondary); font-size: 14px; }
        .lb-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .lb-tabs a { padding: 9px 18px; border-radius: 999px; font-weight: 700; font-size: 14px; text-decoration: none;
            color: var(--color-primary-mid); background: rgba(43, 85, 196, 0.08); transition: var(--transition-base); }
        .lb-tabs a:hover { background: rgba(43, 85, 196, 0.16); text-decoration: none; }
        .lb-tabs a.active { background: var(--color-primary-mid); color: #fff; }

        .lb-grid { display: grid; grid-template-columns: minmax(280px, 380px) minmax(0, 1fr); gap: 20px; align-items: start; }
        @media (max-width: 900px) { .lb-grid { grid-template-columns: 1fr; } }

        .lb-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card);
            box-shadow: var(--shadow-card); padding: 18px; margin-bottom: 20px; }
        .lb-card h2 { margin: 0 0 12px; font-size: 16px; font-weight: 700; color: var(--color-primary); }
        #reader { width: 100%; min-height: 220px; background: #0f172a; border-radius: 10px; overflow: hidden; }
        #reader video { width: 100% !important; }
        .cam-msg { color: #cbd5e1; font-size: 13px; padding: 20px; text-align: center; }
        .cam-actions { display: flex; gap: 8px; margin-top: 10px; }

        .lb-card label { font-size: 12px; font-weight: 700; color: var(--color-text-secondary); margin: 10px 0 4px; display: block; }
        .lb-row { display: flex; gap: 8px; }
        .lb-row .form-control { flex: 1; }
        .ac-wrap { position: relative; }
        .ac-list { position: absolute; z-index: 20; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid var(--color-border);
            border-radius: 8px; box-shadow: var(--shadow-hover); max-height: 280px; overflow-y: auto; display: none; }
        .ac-list div { padding: 8px 12px; cursor: pointer; font-size: 13px; }
        .ac-list div small { display: block; color: var(--color-text-secondary); }
        .ac-list div:hover, .ac-list div.sel { background: rgba(43, 85, 196, 0.09); }
        .lb-picked { font-size: 12px; color: var(--color-success); margin-top: 4px; min-height: 16px; }

        #lbStatus { border-radius: 8px; padding: 10px 14px; font-size: 14px; font-weight: 600; margin-bottom: 14px; display: none; }
        #lbStatus.in { display: block; background: #e6f6ea; color: #1b6b32; }
        #lbStatus.out { display: block; background: #e8eefc; color: #1f3f9c; }
        #lbStatus.err { display: block; background: #fdeaea; color: #b3261e; }

        .lb-table-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
        .lb-table-head small { color: var(--color-text-secondary); }
        .lb-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .lb-table th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--color-text-secondary);
            padding: 8px 10px; border-bottom: 2px solid var(--color-border); }
        .lb-table td { padding: 9px 10px; border-bottom: 1px solid var(--color-border); vertical-align: middle; }
        .lb-table tr.inside td:first-child { border-left: 3px solid var(--color-success); }
        .lb-table .sub { display: block; font-size: 12px; color: var(--color-text-secondary); }
        .lb-empty { text-align: center; padding: 30px; color: var(--color-text-secondary); }
        .tag { font-size: 11px; padding: 2px 8px; border-radius: 999px; background: rgba(43, 85, 196, 0.1); color: var(--color-primary-mid); font-weight: 700; }
        .table-wrap { overflow-x: auto; }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="lb-wrapper">
        <div class="lb-head">
            <div>
                <h1>Logbook</h1>
                <p id="lbDate"><?php echo date('l, F j, Y'); ?></p>
            </div>
            <a href="../admin/hub.php" class="btn-liquid"><i class="bi bi-arrow-left"></i>&nbsp; Back to Hub</a>
        </div>

        <div class="lb-tabs">
            <?php foreach (LOGBOOK_UNITS as $key => $label): ?>
                <a href="?unit=<?php echo $key; ?>" class="<?php echo $key === $unit ? 'active' : ''; ?>"><?php echo htmlspecialchars($label); ?></a>
            <?php endforeach; ?>
        </div>

        <div class="lb-grid">
            <!-- Left: camera + manual entry -->
            <div>
                <div class="lb-card">
                    <h2><i class="bi bi-qr-code-scan"></i> Scan Student ID</h2>
                    <div id="reader"><div class="cam-msg">Starting camera…</div></div>
                    <div class="cam-actions">
                        <button type="button" id="camToggle" class="btn-liquid"><i class="bi bi-camera-video-off"></i> Stop camera</button>
                    </div>
                </div>

                <div class="lb-card">
                    <h2><i class="bi bi-pencil-square"></i> Manual Entry</h2>
                    <label for="idInput">Student ID (LRN)</label>
                    <div class="lb-row">
                        <input type="text" id="idInput" class="form-control" placeholder="Type or scan ID, press Enter" autocomplete="off">
                        <button type="button" id="idBtn" class="btn-liquid">Go</button>
                    </div>

                    <label for="nameInput">Name</label>
                    <div class="ac-wrap">
                        <input type="text" id="nameInput" class="form-control" placeholder="Start typing a name…" autocomplete="off">
                        <div class="ac-list" id="acList"></div>
                    </div>
                    <div class="lb-picked" id="picked"></div>
                    <button type="button" id="nameBtn" class="btn-liquid-success" style="margin-top:6px;width:100%;">
                        <i class="bi bi-box-arrow-in-right"></i> Time In
                    </button>
                </div>
            </div>

            <!-- Right: today's entries -->
            <div class="lb-card">
                <div class="lb-table-head">
                    <h2 style="margin:0;"><?php echo htmlspecialchars(LOGBOOK_UNITS[$unit]); ?> — Today</h2>
                    <small id="lbCount"></small>
                </div>
                <div id="lbStatus"></div>
                <div class="table-wrap">
                    <table class="lb-table">
                        <thead>
                            <tr><th>Student</th><th>Grade &amp; Section</th><th>Time In</th><th>Time Out</th><th>Via</th></tr>
                        </thead>
                        <tbody id="lbBody"><tr><td colspan="5" class="lb-empty">Loading…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include('../helperFiles/footer.php'); ?>

<script>
(function () {
    var UNIT = <?php echo json_encode($unit); ?>;
    var CSRF = <?php echo json_encode($_SESSION['logbook_csrf']); ?>;
    var $ = function (id) { return document.getElementById(id); };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function post(action, data) {
        var body = new URLSearchParams(Object.assign({ csrf: CSRF, unit: UNIT }, data || {}));
        return fetch('index.php?ajax=' + action, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .catch(function () { return { ok: false, message: 'Network or server error.' }; });
    }

    var statusTimer;
    function flash(res) {
        var el = $('lbStatus');
        el.className = res.ok ? res.type : 'err';
        el.textContent = res.message;
        clearTimeout(statusTimer);
        statusTimer = setTimeout(function () { el.className = ''; el.style.display = 'none'; }, 5000);
        el.style.display = 'block';
    }

    /* ── entries ── */
    function load() {
        fetch('index.php?ajax=list&unit=' + UNIT, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                $('lbDate').textContent = d.date;
                var inside = d.rows.filter(function (r) { return !r.time_out; }).length;
                $('lbCount').textContent = d.rows.length + ' entr' + (d.rows.length === 1 ? 'y' : 'ies') + ' · ' + inside + ' inside';
                if (!d.rows.length) {
                    $('lbBody').innerHTML = '<tr><td colspan="5" class="lb-empty">No entries yet today.</td></tr>';
                    return;
                }
                $('lbBody').innerHTML = d.rows.map(function (r) {
                    var out = r.time_out
                        ? esc(r.time_out)
                        : '<button type="button" class="btn-liquid-danger" data-out="' + r.id + '"><i class="bi bi-box-arrow-right"></i> Time Out</button>';
                    return '<tr class="' + (r.time_out ? '' : 'inside') + '">'
                        + '<td><strong>' + esc(r.name) + '</strong>' + (r.LRN ? '<span class="sub">' + esc(r.LRN) + '</span>' : '<span class="sub">Not in directory</span>') + '</td>'
                        + '<td>' + esc(r.grade_section) + '</td>'
                        + '<td>' + esc(r.time_in) + '</td>'
                        + '<td>' + out + '</td>'
                        + '<td><span class="tag">' + (r.source === 'qr' ? 'QR' : r.source === 'id' ? 'ID' : 'Name') + '</span></td></tr>';
                }).join('');
            });
    }
    $('lbBody').addEventListener('click', function (e) {
        var b = e.target.closest('[data-out]');
        if (!b) return;
        b.disabled = true;
        post('timeout', { id: b.getAttribute('data-out') }).then(function (res) { flash(res); load(); });
    });

    /* ── scan / ID entry ── */
    function submitCode(code, source) {
        return post('scan', { code: code, source: source }).then(function (res) { flash(res); load(); return res; });
    }

    var lastCode = '', lastAt = 0;
    function onScan(text) {
        var now = Date.now();
        if (text === lastCode && now - lastAt < 4000) return;   // same card still in view
        lastCode = text; lastAt = now;
        submitCode(text, 'qr');
    }

    var scanner = null, running = false;
    function startCam() {
        if (typeof Html5Qrcode === 'undefined') {
            $('reader').innerHTML = '<div class="cam-msg">QR library failed to load.</div>';
            return;
        }
        scanner = scanner || new Html5Qrcode('reader');
        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, onScan, function () {})
            .then(function () {
                running = true;
                $('camToggle').innerHTML = '<i class="bi bi-camera-video-off"></i> Stop camera';
            })
            .catch(function (err) {
                running = false;
                $('reader').innerHTML = '<div class="cam-msg">Camera unavailable (' + esc(err && err.message || err) + ').<br>'
                    + 'It needs HTTPS or localhost and camera permission. You can still use manual entry.</div>';
                $('camToggle').innerHTML = '<i class="bi bi-camera-video"></i> Start camera';
            });
    }
    $('camToggle').addEventListener('click', function () {
        if (running) {
            scanner.stop().then(function () {
                running = false;
                $('camToggle').innerHTML = '<i class="bi bi-camera-video"></i> Start camera';
            });
        } else {
            if (scanner) { try { scanner.clear(); } catch (e) {} scanner = null; }
            $('reader').innerHTML = '';
            startCam();
        }
    });

    $('idBtn').addEventListener('click', function () {
        var v = $('idInput').value.trim();
        if (!v) return;
        submitCode(v, 'id').then(function (res) { if (res.ok) $('idInput').value = ''; $('idInput').focus(); });
    });
    $('idInput').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('idBtn').click(); } });

    /* ── name autocomplete ── */
    var picked = null, results = [], selIdx = -1, acTimer, acSeq = 0;
    function hideAc() { $('acList').style.display = 'none'; selIdx = -1; }
    function pick(s) {
        picked = s;
        $('nameInput').value = s.name;
        $('picked').textContent = 'ID ' + s.LRN + ' · ' + s.grade_section;
        hideAc();
    }
    function renderAc() {
        var el = $('acList');
        if (!results.length) { hideAc(); return; }
        el.innerHTML = results.map(function (s, i) {
            return '<div data-i="' + i + '" class="' + (i === selIdx ? 'sel' : '') + '">' + esc(s.name) + '<small>' + esc(s.LRN) + ' · ' + esc(s.grade_section) + '</small></div>';
        }).join('');
        el.style.display = 'block';
    }
    $('nameInput').addEventListener('input', function () {
        picked = null; $('picked').textContent = '';
        var q = this.value.trim();
        clearTimeout(acTimer);
        if (q.length < 2) { results = []; hideAc(); return; }
        acTimer = setTimeout(function () {
            var seq = ++acSeq;
            fetch('index.php?ajax=search&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (seq === acSeq) { results = d; selIdx = -1; renderAc(); } });
        }, 180);
    });
    $('nameInput').addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' && results.length) { e.preventDefault(); selIdx = (selIdx + 1) % results.length; renderAc(); }
        else if (e.key === 'ArrowUp' && results.length) { e.preventDefault(); selIdx = (selIdx - 1 + results.length) % results.length; renderAc(); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            if (selIdx >= 0 && results[selIdx]) pick(results[selIdx]); else $('nameBtn').click();
        }
        else if (e.key === 'Escape') hideAc();
    });
    $('acList').addEventListener('mousedown', function (e) {
        var d = e.target.closest('[data-i]');
        if (d) { e.preventDefault(); pick(results[+d.getAttribute('data-i')]); }
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.ac-wrap')) hideAc(); });

    $('nameBtn').addEventListener('click', function () {
        var name = $('nameInput').value.trim();
        if (!name) { flash({ ok: false, message: 'Enter a name.' }); return; }
        post('add_name', { name: name, lrn: picked ? picked.LRN : '' }).then(function (res) {
            flash(res);
            if (res.ok) { $('nameInput').value = ''; picked = null; $('picked').textContent = ''; results = []; hideAc(); }
            load();
        });
    });

    load();
    setInterval(load, 30000);
    startCam();
})();
</script>
</body>

</html>
