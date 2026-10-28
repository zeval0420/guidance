<?php
$asset_base = '../';
require('../helperFiles/db_connection.php');
require_once('emailHelper.php');
include('../helperFiles/session_handler.php');

/* Gate: admins only. */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$msg = $_GET['msg'] ?? null;

/* ── Approve / Reject (one-way: only Pending rows can be moved) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $statusFilter = (isset($_POST['status_filter']) && in_array($_POST['status_filter'], ['All', 'Pending', 'Approved', 'Rejected'], true))
        ? $_POST['status_filter'] : 'All';

    $redirect = 'dashboard.php?status=' . urlencode($statusFilter);

    if (in_array($action, ['approve', 'reject'], true) && $id > 0) {
        $newStatus = ($action === 'approve') ? 'Approved' : 'Rejected';
        $reviewer  = $_SESSION['username'] ?? '';
        $stmt = $conn->prepare(
            "UPDATE guidance_referral_form
                SET status = ?, reviewed_by = ?, reviewed_at = NOW()
              WHERE id = ? AND status = 'Pending'"
        );
        $stmt->bind_param('ssi', $newStatus, $reviewer, $id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $sel = $conn->prepare("SELECT student, referrer_email, reviewed_at FROM guidance_referral_form WHERE id = ?");
            $sel->bind_param('i', $id);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            $sel->close();
            if ($row) {
                guidance_send_review_notification($conn, [
                    'id'            => $id,
                    'student'       => $row['student'],
                    'referrer_email'=> $row['referrer_email'],
                    'status'        => $newStatus,
                    'reviewed_by'   => $reviewer,
                    'reviewed_at'   => $row['reviewed_at'],
                ]);
            }
        }
        $redirect .= $ok ? '&msg=saved' : '&msg=error';
    }
    header('Location: ' . $redirect);
    exit();
}

$statusFilter = (isset($_GET['status']) && in_array($_GET['status'], ['All', 'Pending', 'Approved', 'Rejected'], true))
    ? $_GET['status'] : 'All';

/* Rows load in pages so the table stays fast no matter how many referrals exist. */
const REFERRALS_PAGE_SIZE = 25;

/* Returns [rows, hasMore] for one page of referrals, newest first. */
function fetchReferralPage($conn, $statusFilter, $offset, $limit) {
    $sql = "SELECT id, campus, student, grade_section, date_referred,
                   description, intervention, requires_followup, other_behavior, referrer_email,
                   behavior_depressed, behavior_hopelessness, behavior_crying,
                   behavior_suicide, behavior_mood, behavior_emotional,
                   behavior_withdrawal, behavior_excessive_activity, behavior_interaction,
                   behavior_disruptive, behavior_appearance, behavior_academic_decline,
                   concern_academic, concern_behavior, concern_personal,
                   status, reviewed_by, reviewed_at, created_at
              FROM guidance_referral_form";
    if ($statusFilter !== 'All') {
        $sql .= " WHERE status = ?";
    }
    $sql .= " ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $fetch = $limit + 1; /* one extra row tells us whether another page exists */
    if ($statusFilter !== 'All') {
        $stmt->bind_param('sii', $statusFilter, $fetch, $offset);
    } else {
        $stmt->bind_param('ii', $fetch, $offset);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $hasMore = count($rows) > $limit;
    return [array_slice($rows, 0, $limit), $hasMore];
}

$behaviorLabels = [
    'behavior_depressed'            => 'Depressed or apathetic mood',
    'behavior_hopelessness'         => 'Expression of helplessness, hopelessness, worthlessness',
    'behavior_crying'               => 'Evidence of crying',
    'behavior_suicide'              => 'Verbal expressions or gestures of suicide',
    'behavior_mood'                 => 'Noticeable changes in mood / sudden outburst',
    'behavior_emotional'            => 'Inappropriate or exaggerated emotional reactions',
    'behavior_withdrawal'           => 'Excessive dependency / extreme withdrawal and isolation',
    'behavior_excessive_activity'   => 'Excessive activity or talkativeness',
    'behavior_interaction'          => 'Changed interaction patterns with friends or classmates',
    'behavior_disruptive'           => 'Behaviour which disrupts the class',
    'behavior_appearance'           => 'Changes in physical appearance',
    'behavior_academic_decline'     => 'Poor academic performance / decline in grades',
];

function statusBadgeClass($status) {
    return match ($status) {
        'Approved' => 'badge-approve',
        'Rejected' => 'badge-reject',
        default    => 'badge-pending',
    };
}

/* One <tr> for the referrals table; used for the first page and for scroll-loaded pages. */
function renderReferralRow($row, $behaviorLabels, $statusFilter) {
    $detail = [
        'id'              => $row['id'],
        'campus'          => $row['campus'],
        'student'         => $row['student'],
        'grade_section'   => $row['grade_section'],
        'date_referred'   => $row['date_referred'],
        'concern'         => array_values(array_filter([
            $row['concern_academic'] ? 'Academic' : '',
            $row['concern_behavior'] ? 'Behavior' : '',
            $row['concern_personal'] ? 'Personal/Social' : '',
        ])),
        'description'     => $row['description'],
        'intervention'    => $row['intervention'],
        'requires_followup' => $row['requires_followup'],
        'referrer_email'  => $row['referrer_email'],
        'other_behavior'  => $row['other_behavior'],
        'behaviors'       => array_values(array_filter(array_map(
            fn($col) => isset($row[$col]) && $row[$col] ? $behaviorLabels[$col] : null,
            array_keys($behaviorLabels)
        ))),
        'status'          => $row['status'],
        'reviewed_by'     => $row['reviewed_by'],
        'reviewed_at'     => $row['reviewed_at'],
        'created_at'      => $row['created_at'],
    ];
    ob_start();
    ?>
    <tr id="ref-<?php echo (int)$row['id']; ?>">
        <td><?php echo $row['id']; ?></td>
        <td class="muted"><?php echo htmlspecialchars($row['date_referred'] ?: '-'); ?></td>
        <td class="student"><?php echo htmlspecialchars($row['student']); ?></td>
        <td class="muted"><?php echo htmlspecialchars($row['grade_section']); ?></td>
        <td class="muted"><?php echo htmlspecialchars($row['requires_followup'] ?: '-'); ?></td>
        <td><span class="status-badge <?php echo statusBadgeClass($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
        <td class="muted"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($row['created_at']))); ?></td>
        <td>
            <div class="row-actions">
                <button type="button" class="btn-view" data-detail="<?php echo htmlspecialchars(json_encode($detail), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-eye"></i> View
                </button>
                <?php if ($row['status'] === 'Pending'): ?>
                    <form method="post" action="dashboard.php" class="d-inline"
                          onsubmit="return confirm('Approve referral #<?php echo $row['id']; ?>?' );">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="status_filter" value="<?php echo $statusFilter; ?>">
                        <button type="submit" class="btn-liquid">Approve</button>
                    </form>
                    <form method="post" action="dashboard.php" class="d-inline"
                          onsubmit="return confirm('Reject referral #<?php echo $row['id']; ?>?' );">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="status_filter" value="<?php echo $statusFilter; ?>">
                        <button type="submit" class="btn-liquid-danger">Reject</button>
                    </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

/* ── JSON feed: next page of rows for the scrolling table ── */
if (isset($_GET['feed'])) {
    header('Content-Type: application/json; charset=utf-8');
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    [$pageRows, $hasMore] = fetchReferralPage($conn, $statusFilter, $offset, REFERRALS_PAGE_SIZE);
    $html = '';
    foreach ($pageRows as $r) {
        $html .= renderReferralRow($r, $behaviorLabels, $statusFilter);
    }
    echo json_encode(['html' => $html, 'count' => count($pageRows), 'hasMore' => $hasMore]);
    exit();
}

[$records, $hasMore] = fetchReferralPage($conn, $statusFilter, 0, REFERRALS_PAGE_SIZE);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Dashboard</title>
    <?php include('../helperFiles/headData.php'); ?>
    <style>
        .dash-wrapper {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }

        .dash-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .dash-head h1 {
            margin: 0;
            font-size: 25px;
            font-weight: 800;
            color: var(--color-primary);
        }

        .dash-head p {
            margin: 6px 0 0;
            color: var(--color-text-secondary);
            font-size: 14px;
        }

        .dash-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .filter-pill {
            padding: 7px 14px;
            border-radius: 999px;
            border: 1px solid var(--color-border);
            background: var(--color-surface);
            color: var(--color-text-secondary);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-base);
        }

        .filter-pill:hover {
            color: var(--color-primary);
            border-color: rgba(43, 85, 196, 0.35);
            text-decoration: none;
        }

        .filter-pill.active {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
        }

        .dash-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .table-responsive { margin: 0; }

        /* Fixed-height scroll area; more rows load as the bottom is reached. */
        .table-scroll {
            max-height: 70vh;
            overflow: auto;
        }

        .scroll-status {
            padding: 12px;
            text-align: center;
            font-size: 12.5px;
            color: var(--color-text-secondary);
        }

        .table-referrals {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
        }

        .table-referrals thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f4f6fb;
            color: var(--color-text-secondary);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 12px 14px;
            border-bottom: 1px solid var(--color-border);
            white-space: nowrap;
        }

        .table-referrals tbody td {
            padding: 12px 14px;
            font-size: 13px;
            border-bottom: 1px solid #eef1f6;
            vertical-align: middle;
        }

        .table-referrals tbody tr:hover { background: #fafbfe; }

        .table-referrals .student {
            font-weight: 700;
            color: var(--color-text-primary);
        }

        .table-referrals .muted { color: var(--color-text-secondary); }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .badge-pending { background: #fff4e0; color: #b45309; }
        .badge-approve { background: #e6f7ec; color: #12804a; }
        .badge-reject  { background: #fdeaea; color: #c62828; }

        .row-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .row-actions .btn-liquid,
        .row-actions .btn-liquid-danger {
            padding: 7px 13px;
            font-size: 12px;
        }

        .btn-view {
            padding: 7px 13px;
            font-size: 12px;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            background: var(--color-surface);
            color: var(--color-text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-base);
        }

        .btn-view:hover {
            color: var(--color-primary);
            border-color: rgba(43, 85, 196, 0.35);
        }

        .empty-state {
            text-align: center;
            padding: 46px 20px;
            color: var(--color-text-secondary);
        }

        .empty-state i { font-size: 34px; color: var(--color-border-strong, #cdd5e1); }

        .empty-state p { margin: 12px 0 0; font-size: 14px; }

        .detail-sheet { font-size: 14px; }

        .detail-sheet .dl-row {
            padding: 9px 2px;
            border-bottom: 1px dashed #e4e8f0;
        }

        .detail-sheet .dl-row:last-child { border-bottom: 0; }

        .detail-sheet .dl-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--color-text-secondary);
            margin-bottom: 3px;
        }

        .detail-sheet .dl-value { color: var(--color-text-primary); white-space: pre-line; }

        .detail-sheet .checks-list {
            margin: 0;
            padding-left: 18px;
        }

        .detail-sheet .checks-list li { margin-bottom: 3px; }

        @media (max-width: 700px) {
            .table-referrals thead { display: none; }
            .table-referrals, .table-referrals tbody, .table-referrals tr, .table-referrals td { display: block; width: 100%; }
            .table-referrals tr { padding: 10px 14px; border-bottom: 1px solid var(--color-border); }
            .table-referrals td { border: 0; padding: 3px 0; }
            .table-referrals td::before {
                content: attr(data-label);
                display: inline-block;
                width: 130px;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                color: var(--color-text-secondary);
            }
            .table-referrals .row-actions { justify-content: flex-start; }
        }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="dash-wrapper">
        <div class="dash-head">
            <div>
                <h1>Referral Dashboard</h1>
                <p>Review submitted referral forms and approve or reject pending submissions.</p>
            </div>
            <div class="dash-actions">
                <a href="calendar.php" class="btn-liquid"><i class="bi bi-calendar3"></i> Calendar</a>
                <a href="index.php" class="btn-liquid"><i class="bi bi-file-earmark-text"></i> New Referral Form</a>
                <a href="../logout.php" class="btn-liquid-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>

        <div class="filter-pills">
            <?php foreach (['All', 'Pending', 'Approved', 'Rejected'] as $opt): ?>
                <a href="dashboard.php?status=<?php echo $opt; ?>"
                   class="filter-pill <?php echo $statusFilter === $opt ? 'active' : ''; ?>"><?php echo $opt; ?></a>
            <?php endforeach; ?>
        </div>

        <div class="dash-card">
            <?php if (count($records) === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>No referral submissions<?php echo $statusFilter !== 'All' ? ' under "' . htmlspecialchars($statusFilter) . '"' : ''; ?>.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive table-scroll" id="tableScroll">
                    <table class="table-referrals">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date Referred</th>
                                <th>Student</th>
                                <th>Grade &amp; Section</th>
                                <th>Follow-up</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="referralRows">
                            <?php foreach ($records as $row) echo renderReferralRow($row, $behaviorLabels, $statusFilter); ?>
                        </tbody>
                    </table>
                    <div class="scroll-status" id="scrollStatus" hidden>Loading more&hellip;</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detail-title">Referral Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detail-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('../helperFiles/footer.php'); ?>

    <script>
        function esc(v) {
            return $('<div>').text(v == null || v === '' ? '-' : v).html();
        }

        function listItems(arr) {
            if (!arr || !arr.length) return '<em class="text-muted">None</em>';
            return '<ul class="checks-list">' + arr.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>';
        }

        function badges(arr) {
            if (!arr || !arr.length) return '<em class="text-muted">None</em>';
            return arr.map(function (x) { return '<span class="label label-primary" style="margin-right:6px;">' + esc(x) + '</span>'; }).join('');
        }

        /* ── Infinite scroll: fetch the next page when the bottom of the table nears ── */
        (function () {
            var scroller = document.getElementById('tableScroll');
            if (!scroller) return;
            var tbody = document.getElementById('referralRows');
            var status = document.getElementById('scrollStatus');
            var offset = tbody.children.length;
            var hasMore = <?php echo $hasMore ? 'true' : 'false'; ?>;
            var loading = false;
            var statusFilter = <?php echo json_encode($statusFilter); ?>;

            function loadMore() {
                if (loading || !hasMore) return Promise.resolve();
                loading = true;
                status.hidden = false;
                return fetch('dashboard.php?feed=1&status=' + encodeURIComponent(statusFilter) + '&offset=' + offset, { credentials: 'same-origin' })
                    .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
                    .then(function (data) {
                        tbody.insertAdjacentHTML('beforeend', data.html);
                        offset += data.count;
                        hasMore = data.hasMore;
                    })
                    .catch(function () {
                        hasMore = false;
                        showToast('Could not load more referrals.', 'error');
                    })
                    .then(function () {
                        loading = false;
                        status.hidden = true;
                    });
            }

            function nearBottom() {
                return scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 200;
            }

            function fillIfShort() {
                if (hasMore && nearBottom()) loadMore().then(fillIfShort);
            }

            scroller.addEventListener('scroll', fillIfShort);

            /* Deep link from the calendar (#ref-ID): keep loading until that row exists. */
            var target = location.hash;
            if (/^#ref-\d+$/.test(target)) {
                (function seek() {
                    var row = document.querySelector(target);
                    if (row) { row.scrollIntoView({ block: 'center' }); return; }
                    if (hasMore) loadMore().then(seek);
                })();
            } else {
                fillIfShort();
            }
        })();

        $('#referralRows').on('click', '.btn-view', function () {
            var d = $(this).data('detail');
            $('#detail-title').text('Referral #' + d.id);
            var html = '<div class="detail-sheet">';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Submitted</div><div class="dl-value">' + esc(d.created_at) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Student</div><div class="dl-value">' + esc(d.student) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Grade &amp; Section</div><div class="dl-value">' + esc(d.grade_section) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Campus</div><div class="dl-value">' + esc(d.campus) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Date Referred</div><div class="dl-value">' + esc(d.date_referred) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Concern</div><div class="dl-value">' + badges(d.concern) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Brief Description</div><div class="dl-value">' + esc(d.description) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Intervention/s Done</div><div class="dl-value">' + esc(d.intervention) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Behaviors Spotted</div><div class="dl-value">' + listItems(d.behaviors) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Other Behaviors</div><div class="dl-value">' + esc(d.other_behavior) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Requires Follow-up</div><div class="dl-value">' + esc(d.requires_followup) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Referrer Email</div><div class="dl-value">' + esc(d.referrer_email) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Status</div><div class="dl-value">' + esc(d.status) + '</div>';
            html += '</div>';

            html += '<div class="dl-row">';
            html += '<div class="dl-label">Reviewed By</div><div class="dl-value">' + esc(d.reviewed_by) + ' &mdash; ' + esc(d.reviewed_at) + '</div>';
            html += '</div>';

            html += '</div>';
            $('#detail-body').html(html);
            $('#detailModal').modal('show');
        });
    </script>

    <?php if ($msg === 'saved'): ?>
        <script>showToast('Referral status updated.', 'success');</script>
    <?php elseif ($msg === 'error'): ?>
        <script>showToast('Could not update the referral status.', 'error');</script>
    <?php endif; ?>
</body>

</html>