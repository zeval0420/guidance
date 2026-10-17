<?php
$asset_base = '../';
require('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

/* Gate: admins only. */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

/* ── JSON feed: every referral touching the requested range ──
   A referral appears on its date referred and, when follow-up is
   required, again on its follow-up date. */
if (isset($_GET['feed'])) {
    header('Content-Type: application/json; charset=utf-8');

    $start = $_GET['start'] ?? '';
    $end   = $_GET['end'] ?? '';
    $valid = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($valid, $start) || !preg_match($valid, $end)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid range.']);
        exit();
    }

    $stmt = $conn->prepare(
        "SELECT id, campus, student, grade_section, date_referred, followup_date,
                requires_followup, description, status,
                concern_academic, concern_behavior, concern_personal
           FROM guidance_referral_form
          WHERE (date_referred BETWEEN ? AND ?)
             OR (followup_date BETWEEN ? AND ?)
          ORDER BY date_referred, id"
    );
    $stmt->bind_param('ssss', $start, $end, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();

    $events = [];
    while ($r = $res->fetch_assoc()) {
        $concerns = [];
        if ($r['concern_academic']) $concerns[] = 'Academic';
        if ($r['concern_behavior']) $concerns[] = 'Behavior';
        if ($r['concern_personal']) $concerns[] = 'Personal/Social';

        $base = [
            'id'       => (int)$r['id'],
            'student'  => $r['student'],
            'grade'    => $r['grade_section'],
            'campus'   => $r['campus'],
            'status'   => $r['status'] ?? 'Pending',
            'concerns' => implode(', ', $concerns),
            'summary'  => mb_strimwidth($r['description'], 0, 220, '…'),
        ];

        if ($r['date_referred'] >= $start && $r['date_referred'] <= $end) {
            $events[] = $base + ['date' => $r['date_referred'], 'type' => 'referral'];
        }
        if ($r['followup_date'] !== null && $r['followup_date'] >= $start && $r['followup_date'] <= $end) {
            $events[] = $base + ['date' => $r['followup_date'], 'type' => 'followup'];
        }
    }
    $stmt->close();

    echo json_encode($events);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Calendar</title>
    <?php include('../helperFiles/headData.php'); ?>
    <style>
        .cal-wrapper {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }

        .cal-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .cal-head h1 {
            margin: 0;
            font-size: 25px;
            font-weight: 800;
            color: var(--color-primary);
        }

        .cal-head p {
            margin: 6px 0 0;
            color: var(--color-text-secondary);
            font-size: 14px;
        }

        .cal-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        .cal-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 20px;
            align-items: start;
        }

        .cal-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .cal-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--color-border);
        }

        .cal-toolbar h2 { margin: 0; font-size: 18px; font-weight: 800; color: var(--color-primary); }

        .cal-nav { display: flex; gap: 6px; }

        .cal-nav button {
            border: 1px solid var(--color-border);
            background: #fff;
            border-radius: 10px;
            padding: 6px 12px;
            font-weight: 700;
            color: var(--color-text-primary);
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .cal-nav button:hover { border-color: var(--color-secondary); color: var(--color-primary-mid); }

        .cal-dow, .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .cal-dow div {
            padding: 9px 4px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--color-text-secondary);
            background: #f4f6fb;
        }

        .cal-cell {
            min-height: 104px;
            padding: 6px;
            border-right: 1px solid var(--color-border);
            border-bottom: 1px solid var(--color-border);
            cursor: pointer;
            transition: background .15s ease;
            overflow: hidden;
        }

        .cal-cell:nth-child(7n) { border-right: 0; }
        .cal-cell:hover { background: #f6f8fe; }
        .cal-cell.other { background: #fafbfd; }
        .cal-cell.other .num { color: #b3bccd; }
        .cal-cell.selected { background: rgba(43, 85, 196, .08); box-shadow: inset 0 0 0 2px var(--color-primary-mid); }

        .cal-cell .num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            border-radius: 12px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--color-text-primary);
        }

        .cal-cell.today .num { background: var(--color-primary-mid); color: #fff; }

        .ev {
            display: block;
            margin-top: 3px;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-left: 3px solid;
        }

        .ev.referral.Pending  { background: #fff4d6; border-color: #e0a100; color: #7a5600; }
        .ev.referral.Approved { background: #dff5e6; border-color: #28a745; color: #1b6b30; }
        .ev.referral.Rejected { background: #fde3e5; border-color: #dc3545; color: #8f1d29; }
        .ev.followup { background: #e3ebff; border-color: var(--color-primary-mid); color: var(--color-primary); }

        .cal-more { margin-top: 3px; font-size: 11px; font-weight: 700; color: var(--color-text-secondary); }

        .side-card { padding: 18px; }
        .side-card h3 { margin: 0 0 4px; font-size: 16px; font-weight: 800; color: var(--color-primary); }
        .side-card .sub { margin: 0 0 14px; font-size: 12.5px; color: var(--color-text-secondary); }

        .detail {
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            font-size: 13px;
        }

        .detail .who { font-weight: 800; color: var(--color-text-primary); }
        .detail .meta { color: var(--color-text-secondary); font-size: 12px; margin: 2px 0 6px; }
        .detail p { margin: 6px 0 0; line-height: 1.45; }

        .tag {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .tag.referral { background: #eef1f6; color: #3a4560; }
        .tag.followup { background: #e3ebff; color: var(--color-primary); }
        .tag.Pending  { background: #fff4d6; color: #7a5600; }
        .tag.Approved { background: #dff5e6; color: #1b6b30; }
        .tag.Rejected { background: #fde3e5; color: #8f1d29; }

        .detail a { font-weight: 700; font-size: 12.5px; }

        .legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 14px; font-size: 12px; color: var(--color-text-secondary); }
        .legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; vertical-align: -1px; }

        .empty { color: var(--color-text-secondary); font-size: 13px; padding: 8px 0; }

        @media (max-width: 960px) {
            .cal-layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 600px) {
            .cal-wrapper { padding: 18px 10px 40px; }
            .cal-cell { min-height: 64px; padding: 3px; }
            .ev { font-size: 0; padding: 0; height: 6px; border-left-width: 0; border-radius: 3px; }
        }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="cal-wrapper">
        <div class="cal-head">
            <div>
                <h1>Referral Calendar</h1>
                <p>All submitted referrals and scheduled follow-ups in one place.</p>
            </div>
            <div class="cal-actions">
                <a href="hub.php" class="btn-liquid"><i class="bi bi-grid"></i> Hub</a>
                <a href="../referral/dashboard.php" class="btn-liquid"><i class="bi bi-table"></i> Dashboard</a>
            </div>
        </div>

        <div class="cal-layout">
            <div class="cal-card">
                <div class="cal-toolbar">
                    <h2 id="monthLabel"></h2>
                    <div class="cal-nav">
                        <button type="button" id="prevBtn" aria-label="Previous month">&lsaquo;</button>
                        <button type="button" id="todayBtn">Today</button>
                        <button type="button" id="nextBtn" aria-label="Next month">&rsaquo;</button>
                    </div>
                </div>
                <div class="cal-dow">
                    <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                </div>
                <div class="cal-grid" id="calGrid"></div>
            </div>

            <aside class="cal-card side-card">
                <h3 id="dayTitle">Select a day</h3>
                <p class="sub" id="daySub">Click any date to see its referrals.</p>
                <div id="dayList"></div>
                <div class="legend">
                    <span><i style="background:#e0a100"></i>Referral (pending)</span>
                    <span><i style="background:#28a745"></i>Approved</span>
                    <span><i style="background:#dc3545"></i>Rejected</span>
                    <span><i style="background:#2b55c4"></i>Follow-up</span>
                </div>
            </aside>
        </div>
    </div>

    <?php include('../helperFiles/footer.php'); ?>

    <script>
        var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var view = new Date();
        view.setDate(1);
        var selected = null;
        var byDate = {};

        function pad(n) { return n < 10 ? '0' + n : '' + n; }
        function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function esc(s) {
            var d = document.createElement('div');
            d.textContent = s == null ? '' : s;
            return d.innerHTML;
        }

        function gridRange() {
            var first = new Date(view.getFullYear(), view.getMonth(), 1);
            var start = new Date(first);
            start.setDate(1 - first.getDay());
            var end = new Date(start);
            end.setDate(start.getDate() + 41);
            return { start: start, end: end };
        }

        function load() {
            var r = gridRange();
            document.getElementById('monthLabel').textContent = MONTHS[view.getMonth()] + ' ' + view.getFullYear();
            fetch('calendar.php?feed=1&start=' + iso(r.start) + '&end=' + iso(r.end), { credentials: 'same-origin' })
                .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
                .then(function (events) {
                    byDate = {};
                    events.forEach(function (e) { (byDate[e.date] = byDate[e.date] || []).push(e); });
                    render();
                })
                .catch(function () {
                    byDate = {};
                    render();
                    showToast('Could not load calendar data.', 'error');
                });
        }

        function render() {
            var r = gridRange();
            var todayIso = iso(new Date());
            var html = '';
            for (var i = 0; i < 42; i++) {
                var d = new Date(r.start);
                d.setDate(r.start.getDate() + i);
                var key = iso(d);
                var evs = byDate[key] || [];
                var cls = 'cal-cell' + (d.getMonth() !== view.getMonth() ? ' other' : '')
                    + (key === todayIso ? ' today' : '') + (key === selected ? ' selected' : '');
                html += '<div class="' + cls + '" data-date="' + key + '"><span class="num">' + d.getDate() + '</span>';
                evs.slice(0, 3).forEach(function (e) {
                    var label = (e.type === 'followup' ? 'Follow-up: ' : '') + e.student;
                    html += '<span class="ev ' + e.type + ' ' + esc(e.status) + '" title="' + esc(label) + '">' + esc(label) + '</span>';
                });
                if (evs.length > 3) html += '<div class="cal-more">+' + (evs.length - 3) + ' more</div>';
                html += '</div>';
            }
            document.getElementById('calGrid').innerHTML = html;
            showDay();
        }

        function showDay() {
            var title = document.getElementById('dayTitle');
            var sub = document.getElementById('daySub');
            var list = document.getElementById('dayList');
            if (!selected) { title.textContent = 'Select a day'; sub.textContent = 'Click any date to see its referrals.'; list.innerHTML = ''; return; }

            var p = selected.split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            title.textContent = d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            var evs = byDate[selected] || [];
            sub.textContent = evs.length + (evs.length === 1 ? ' entry' : ' entries');

            if (!evs.length) { list.innerHTML = '<div class="empty">Nothing scheduled.</div>'; return; }

            list.innerHTML = evs.map(function (e) {
                return '<div class="detail">'
                    + '<div class="who">' + esc(e.student) + '</div>'
                    + '<div class="meta">' + esc(e.grade) + ' &middot; ' + esc(e.campus) + '</div>'
                    + '<span class="tag ' + e.type + '">' + (e.type === 'followup' ? 'Follow-up' : 'Referred') + '</span> '
                    + '<span class="tag ' + esc(e.status) + '">' + esc(e.status) + '</span>'
                    + (e.concerns ? '<p><strong>Concern:</strong> ' + esc(e.concerns) + '</p>' : '')
                    + '<p>' + esc(e.summary) + '</p>'
                    + '<p><a href="../referral/dashboard.php?status=All#ref-' + e.id + '">View in dashboard &rarr;</a></p>'
                    + '</div>';
            }).join('');
        }

        document.getElementById('calGrid').addEventListener('click', function (ev) {
            var cell = ev.target.closest('.cal-cell');
            if (!cell) return;
            selected = cell.getAttribute('data-date');
            render();
        });
        document.getElementById('prevBtn').onclick = function () { view.setMonth(view.getMonth() - 1); load(); };
        document.getElementById('nextBtn').onclick = function () { view.setMonth(view.getMonth() + 1); load(); };
        document.getElementById('todayBtn').onclick = function () {
            view = new Date(); view.setDate(1); selected = iso(new Date()); load();
        };

        selected = iso(new Date());
        load();
    </script>
</body>

</html>
