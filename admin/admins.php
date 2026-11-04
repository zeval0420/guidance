<?php
$asset_base = '../';
require('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');
require_once('../helperFiles/admin_roles.php');

/* Gate: admins only. */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

/* ── Constants ─────────────────────────────────────────────── */
$ADMIN_POSITIONS = guidance_admin_positions($conn);
$BASE_TYPES      = ['staff', 'faculty'];
$STATUSES        = ['active', 'inactive'];
$MIN_PASSWORD    = 8;

/* CSRF token (per session). */
if (empty($_SESSION['admin_mgmt_csrf'])) {
    $_SESSION['admin_mgmt_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['admin_mgmt_csrf'];

/* Flash message (survives the post/redirect/get round trip). */
$flash = $_SESSION['admin_mgmt_flash'] ?? null;
unset($_SESSION['admin_mgmt_flash']);

function flash_and_redirect($type, $text)
{
    $_SESSION['admin_mgmt_flash'] = ['type' => $type, 'text' => $text];
    header('Location: admins.php');
    exit();
}

/* True if another account already uses this username/email. */
function account_field_taken($conn, $field, $value, $exceptEmployeeID = null)
{
    $col = $field === 'email' ? 'email' : 'username';
    $sql = "SELECT 1 FROM accounts WHERE $col = ?" . ($exceptEmployeeID !== null ? " AND employeeID <> ?" : "") . " LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($exceptEmployeeID !== null) {
        $stmt->bind_param('ss', $value, $exceptEmployeeID);
    } else {
        $stmt->bind_param('s', $value);
    }
    $stmt->execute();
    $taken = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $taken;
}

/* ── POST handlers ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        flash_and_redirect('danger', 'Your session token was invalid. Please try again.');
    }

    $action = $_POST['action'] ?? '';

    /* ── Choose which positions count as admins ── */
    if ($action === 'save_positions') {
        $selected = array_map('trim', (array)($_POST['positions'] ?? []));
        $custom   = trim($_POST['new_position'] ?? '');

        /* Only accept positions that exist on an account, are already admin positions, or were typed in. */
        $known = $ADMIN_POSITIONS;
        $res = $conn->query("SELECT DISTINCT position FROM accounts WHERE position <> ''");
        while ($row = $res->fetch_assoc()) $known[] = $row['position'];
        if ($custom !== '') {
            if (strlen($custom) > 50) flash_and_redirect('danger', 'Custom position is too long (max 50 characters).');
            $known[]    = $custom;
            $selected[] = $custom;
        }
        $selected = array_values(array_unique(array_intersect($selected, $known)));

        if (!$selected) {
            flash_and_redirect('danger', 'At least one position must be an admin position.');
        }

        /* Don't let an admin remove their own access. */
        $stmt = $conn->prepare("SELECT position FROM accounts WHERE employeeID = ?");
        $stmt->bind_param('s', $_SESSION['employeeID']);
        $stmt->execute();
        $me = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($me && !in_array($me['position'], $selected, true)) {
            flash_and_redirect('danger', 'You cannot remove your own position (' . $me['position'] . ') from the admin list.');
        }

        if (!guidance_save_admin_positions($conn, $selected)) {
            flash_and_redirect('danger', 'Database error: admin positions could not be saved.');
        }
        flash_and_redirect('success', 'Admin positions updated.');
    }

    $employeeID = trim($_POST['employeeID'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $firstname  = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $lastname   = trim($_POST['lastname'] ?? '');
    $position   = trim($_POST['position'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $type       = trim($_POST['type'] ?? '');
    $status     = trim($_POST['status'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['password_confirm'] ?? '';

    $errors = [];

    if (!in_array($action, ['create', 'update'], true)) {
        flash_and_redirect('danger', 'Unknown action.');
    }

    /* Existing row (for update): must exist and be an admin account. */
    $existing = null;
    if ($action === 'update') {
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE employeeID = ?");
        $stmt->bind_param('s', $employeeID);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$existing || !in_array($existing['position'], $ADMIN_POSITIONS, true)) {
            flash_and_redirect('danger', 'Admin account not found.');
        }
    }

    /* Field validation. */
    if ($employeeID === '' || strlen($employeeID) > 20) $errors[] = 'Employee ID is required (max 20 characters).';
    if ($username === '' || strlen($username) > 100)    $errors[] = 'Username is required (max 100 characters).';
    if ($firstname === '' || strlen($firstname) > 100)  $errors[] = 'First name is required (max 100 characters).';
    if (strlen($middlename) > 100)                      $errors[] = 'Middle name is too long (max 100 characters).';
    if ($lastname === '' || strlen($lastname) > 100)    $errors[] = 'Last name is required (max 100 characters).';
    if (!in_array($position, $ADMIN_POSITIONS, true))   $errors[] = 'Please choose a valid position.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) $errors[] = 'A valid email is required (max 50 characters).';

    $allowedTypes = $BASE_TYPES;
    if ($existing && !in_array($existing['type'], $allowedTypes, true)) $allowedTypes[] = $existing['type'];
    if (!in_array($type, $allowedTypes, true))          $errors[] = 'Please choose a valid account type.';
    if (!in_array($status, $STATUSES, true))            $errors[] = 'Please choose a valid status.';

    /* Password: required on create, optional on update. */
    if ($action === 'create' || $password !== '') {
        if (strlen($password) < $MIN_PASSWORD)          $errors[] = "Password must be at least $MIN_PASSWORD characters.";
        if ($password !== $confirm)                     $errors[] = 'Passwords do not match.';
    }

    /* Don't let an admin lock themselves out. */
    if ($action === 'update' && $employeeID === ($_SESSION['employeeID'] ?? null) && $status !== 'active') {
        $errors[] = 'You cannot deactivate your own account.';
    }

    if (!$errors) {
        try {
            if ($action === 'create') {
                $stmt = $conn->prepare("SELECT 1 FROM accounts WHERE employeeID = ?");
                $stmt->bind_param('s', $employeeID);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) $errors[] = 'That Employee ID already exists.';
                $stmt->close();
                if (account_field_taken($conn, 'username', $username)) $errors[] = 'That username is already taken.';
                if (account_field_taken($conn, 'email', $email))       $errors[] = 'That email is already used by another account.';
            } else {
                if (account_field_taken($conn, 'username', $username, $employeeID)) $errors[] = 'That username is already taken.';
                if (account_field_taken($conn, 'email', $email, $employeeID))       $errors[] = 'That email is already used by another account.';
            }

            if (!$errors) {
                if ($action === 'create') {
                    /* The whole system (auth.php, reset flow) uses MD5 for accounts.password. */
                    $hash = md5($password);
                    $stmt = $conn->prepare(
                        "INSERT INTO accounts (employeeID, username, firstname, middlename, lastname, password, position, email, type, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param('ssssssssss', $employeeID, $username, $firstname, $middlename, $lastname, $hash, $position, $email, $type, $status);
                    $stmt->execute();
                    $stmt->close();
                    flash_and_redirect('success', "Account for $firstname $lastname created.");
                } else {
                    if ($password !== '') {
                        $hash = md5($password);
                        $stmt = $conn->prepare(
                            "UPDATE accounts SET username=?, firstname=?, middlename=?, lastname=?, password=?, position=?, email=?, type=?, status=?
                             WHERE employeeID=?"
                        );
                        $stmt->bind_param('ssssssssss', $username, $firstname, $middlename, $lastname, $hash, $position, $email, $type, $status, $employeeID);
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE accounts SET username=?, firstname=?, middlename=?, lastname=?, position=?, email=?, type=?, status=?
                             WHERE employeeID=?"
                        );
                        $stmt->bind_param('sssssssss', $username, $firstname, $middlename, $lastname, $position, $email, $type, $status, $employeeID);
                    }
                    $stmt->execute();
                    $stmt->close();

                    /* Keep the current session in sync when editing yourself. */
                    if ($employeeID === ($_SESSION['employeeID'] ?? null)) {
                        $_SESSION['email']      = $email;
                        $_SESSION['firstname']  = $firstname;
                        $_SESSION['middlename'] = $middlename;
                        $_SESSION['lastname']   = $lastname;
                        $_SESSION['username']   = trim($firstname . ' ' . $lastname);
                    }
                    flash_and_redirect('success', "Account for $firstname $lastname updated.");
                }
            }
        } catch (mysqli_sql_exception $e) {
            $errors[] = 'Database error: the account could not be saved.';
        }
    }

    flash_and_redirect('danger', implode(' ', $errors));
}

/* ── Load current admins ───────────────────────────────────── */
$placeholders = implode(',', array_fill(0, count($ADMIN_POSITIONS), '?'));
$stmt = $conn->prepare(
    "SELECT employeeID, username, firstname, middlename, lastname, password, position, email, type, status
       FROM accounts WHERE position IN ($placeholders) ORDER BY lastname, firstname"
);
$stmt->bind_param(str_repeat('s', count($ADMIN_POSITIONS)), ...$ADMIN_POSITIONS);
$stmt->execute();
$admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Every position in use, with how many accounts hold it (for the position picker). */
$positionCounts = [];
$res = $conn->query("SELECT position, COUNT(*) AS c FROM accounts WHERE position <> '' GROUP BY position ORDER BY position");
while ($row = $res->fetch_assoc()) $positionCounts[$row['position']] = (int)$row['c'];
foreach ($ADMIN_POSITIONS as $p) {
    if (!isset($positionCounts[$p])) $positionCounts[$p] = 0;
}
ksort($positionCounts);

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <?php include('../helperFiles/headData.php'); ?>
    <style>
        .mgmt-wrapper {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 36px 24px 56px;
        }

        .mgmt-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .mgmt-head h1 { margin: 0; font-size: 26px; font-weight: 800; color: var(--color-primary); }
        .mgmt-head p  { margin: 6px 0 0; color: var(--color-text-secondary); font-size: 14px; }
        .mgmt-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        .mgmt-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            overflow-x: auto;
        }

        .mgmt-table { width: 100%; margin: 0; font-size: 13px; }
        .mgmt-table th {
            background: rgba(43, 85, 196, 0.07);
            color: var(--color-primary);
            white-space: nowrap;
            font-weight: 700;
        }
        .mgmt-table td { vertical-align: middle !important; }
        .mgmt-table .pw-mask { letter-spacing: 2px; color: var(--color-text-secondary); }

        .badge-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-status.active   { background: rgba(40, 167, 69, 0.12); color: var(--color-success); }
        .badge-status.inactive { background: rgba(108, 117, 125, 0.15); color: var(--color-secondary-grey); }

        .mgmt-alert { margin-bottom: 18px; }

        .pos-list { max-height: 320px; overflow-y: auto; border: 1px solid var(--color-border); border-radius: 8px; padding: 6px 14px; }
        .pos-list .checkbox { margin: 8px 0; font-size: 13px; }
        .modal-title { color: var(--color-primary); font-weight: 700; }
        .modal-body .form-group label { font-weight: 600; font-size: 13px; }
        .modal-body .hint { font-size: 12px; color: var(--color-text-secondary); margin-top: 4px; }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="mgmt-wrapper">
        <div class="mgmt-head">
            <div>
                <h1>Admin Management</h1>
                <p>View and edit guidance administrator accounts, or create a new one.</p>
            </div>
            <div class="mgmt-actions">
                <button type="button" class="btn-liquid-success" id="btnCreate">
                    <i class="bi bi-person-plus"></i>&nbsp; Create Account
                </button>
                <button type="button" class="btn-liquid" data-toggle="modal" data-target="#positionsModal">
                    <i class="bi bi-shield-check"></i>&nbsp; Admin Positions
                </button>
                <a href="hub.php" class="btn-liquid"><i class="bi bi-arrow-left"></i>&nbsp; Back to Hub</a>
                <a href="../logout.php" class="btn-liquid-danger"><i class="bi bi-box-arrow-right"></i>&nbsp; Logout</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo h($flash['type']); ?> mgmt-alert" role="alert">
                <?php echo h($flash['text']); ?>
            </div>
        <?php endif; ?>

        <div class="mgmt-card">
            <table class="table table-hover mgmt-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Username</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Password</th>
                        <th>Position</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$admins): ?>
                        <tr><td colspan="11" class="text-center text-muted">No admin accounts found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($admins as $a): ?>
                        <tr>
                            <td><?php echo h($a['employeeID']); ?></td>
                            <td><?php echo h($a['username']); ?></td>
                            <td><?php echo h($a['firstname']); ?></td>
                            <td><?php echo h($a['middlename']); ?></td>
                            <td><?php echo h($a['lastname']); ?></td>
                            <td><span class="pw-mask" title="Passwords are stored hashed and cannot be viewed">••••••••</span></td>
                            <td><?php echo h($a['position']); ?></td>
                            <td><?php echo h($a['email']); ?></td>
                            <td><?php echo h($a['type']); ?></td>
                            <td><span class="badge-status <?php echo h($a['status']); ?>"><?php echo h($a['status']); ?></span></td>
                            <td>
                                <button type="button" class="btn-liquid btn-edit"
                                    data-employeeid="<?php echo h($a['employeeID']); ?>"
                                    data-username="<?php echo h($a['username']); ?>"
                                    data-firstname="<?php echo h($a['firstname']); ?>"
                                    data-middlename="<?php echo h($a['middlename']); ?>"
                                    data-lastname="<?php echo h($a['lastname']); ?>"
                                    data-position="<?php echo h($a['position']); ?>"
                                    data-email="<?php echo h($a['email']); ?>"
                                    data-type="<?php echo h($a['type']); ?>"
                                    data-status="<?php echo h($a['status']); ?>">
                                    <i class="bi bi-pencil"></i>&nbsp; Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Edit modal (one form, switched by JS) -->
    <div class="modal fade" id="accountModal" tabindex="-1" role="dialog" aria-labelledby="accountModalTitle">
        <div class="modal-dialog" role="document">
            <form method="post" action="admins.php" class="modal-content" autocomplete="off">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" id="fAction" value="create">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="accountModalTitle">Create Account</h4>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label for="fEmployeeID">Employee ID</label>
                            <input type="text" class="form-control" id="fEmployeeID" name="employeeID" maxlength="20" required>
                            <div class="hint" id="fEmployeeIDHint" style="display:none;">Employee ID cannot be changed.</div>
                        </div>
                        <div class="col-sm-6 form-group">
                            <label for="fUsername">Username</label>
                            <input type="text" class="form-control" id="fUsername" name="username" maxlength="100" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12 form-group">
                            <label for="fFirstname">First Name</label>
                            <input type="text" class="form-control" id="fFirstname" name="firstname" maxlength="100" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label for="fMiddlename">Middle Name</label>
                            <input type="text" class="form-control" id="fMiddlename" name="middlename" maxlength="100">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label for="fLastname">Last Name</label>
                            <input type="text" class="form-control" id="fLastname" name="lastname" maxlength="100" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fEmail">Email</label>
                        <input type="email" class="form-control" id="fEmail" name="email" maxlength="50" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label for="fPosition">Position</label>
                            <select class="form-control" id="fPosition" name="position" required>
                                <?php foreach ($ADMIN_POSITIONS as $p): ?>
                                    <option value="<?php echo h($p); ?>"><?php echo h($p); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3 form-group">
                            <label for="fType">Type</label>
                            <select class="form-control" id="fType" name="type" required>
                                <?php foreach ($BASE_TYPES as $t): ?>
                                    <option value="<?php echo h($t); ?>"><?php echo h($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-3 form-group">
                            <label for="fStatus">Status</label>
                            <select class="form-control" id="fStatus" name="status" required>
                                <?php foreach ($STATUSES as $s): ?>
                                    <option value="<?php echo h($s); ?>"><?php echo h($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-sm-6 form-group">
                            <label for="fPassword" id="fPasswordLabel">Password</label>
                            <input type="password" class="form-control" id="fPassword" name="password" minlength="<?php echo $MIN_PASSWORD; ?>" autocomplete="new-password">
                        </div>
                        <div class="col-sm-6 form-group">
                            <label for="fPasswordConfirm">Confirm Password</label>
                            <input type="password" class="form-control" id="fPasswordConfirm" name="password_confirm" minlength="<?php echo $MIN_PASSWORD; ?>" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="hint" id="fPasswordHint">At least <?php echo $MIN_PASSWORD; ?> characters. It is stored hashed.</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-liquid-success" id="fSubmit"><i class="bi bi-check2"></i>&nbsp; Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin positions modal -->
    <div class="modal fade" id="positionsModal" tabindex="-1" role="dialog" aria-labelledby="positionsModalTitle">
        <div class="modal-dialog" role="document">
            <form method="post" action="admins.php" class="modal-content" autocomplete="off">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" value="save_positions">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="positionsModalTitle">Admin Positions</h4>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning" style="font-size:13px;">
                        Every <strong>active</strong> account holding a checked position can log in to the Guidance Hub
                        and receives referral notification emails. Unchecking a position removes its accounts from the
                        admin list and from login access.
                    </div>

                    <div class="pos-list">
                        <?php foreach ($positionCounts as $pos => $count): ?>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="positions[]" value="<?php echo h($pos); ?>"
                                        <?php echo in_array($pos, $ADMIN_POSITIONS, true) ? 'checked' : ''; ?>>
                                    <?php echo h($pos); ?>
                                    <span class="text-muted">(<?php echo $count; ?> account<?php echo $count === 1 ? '' : 's'; ?>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label for="fNewPosition">Add another position</label>
                        <input type="text" class="form-control" id="fNewPosition" name="new_position" maxlength="50"
                               placeholder="Type a position not listed above (optional)">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-liquid-success"><i class="bi bi-check2"></i>&nbsp; Save Positions</button>
                </div>
            </form>
        </div>
    </div>

    <?php include('../helperFiles/footer.php'); ?>

    <script>
        (function () {
            var $modal = $('#accountModal');
            var minPw = <?php echo (int)$MIN_PASSWORD; ?>;

            function setRequiredPassword(required) {
                $('#fPassword, #fPasswordConfirm').prop('required', required);
            }

            /* Make sure a non-standard existing type stays selectable. */
            function ensureOption($select, value) {
                if (value && $select.find('option').filter(function () { return this.value === value; }).length === 0) {
                    $select.append($('<option>').val(value).text(value));
                }
            }

            $('#btnCreate').on('click', function () {
                $modal.find('form')[0].reset();
                $('#fAction').val('create');
                $('#accountModalTitle').text('Create Account');
                $('#fEmployeeID').prop('readonly', false);
                $('#fEmployeeIDHint').hide();
                $('#fPasswordLabel').text('Password');
                $('#fPasswordHint').text('At least ' + minPw + ' characters. It is stored hashed.');
                setRequiredPassword(true);
                $modal.modal('show');
            });

            $('.btn-edit').on('click', function () {
                var d = $(this).data();
                $modal.find('form')[0].reset();
                $('#fAction').val('update');
                $('#accountModalTitle').text('Edit Account');
                $('#fEmployeeID').val(d.employeeid).prop('readonly', true);
                $('#fEmployeeIDHint').show();
                $('#fUsername').val(d.username);
                $('#fFirstname').val(d.firstname);
                $('#fMiddlename').val(d.middlename);
                $('#fLastname').val(d.lastname);
                $('#fEmail').val(d.email);
                $('#fPosition').val(d.position);
                ensureOption($('#fType'), String(d.type));
                $('#fType').val(String(d.type));
                $('#fStatus').val(d.status);
                $('#fPasswordLabel').text('New Password');
                $('#fPasswordHint').text('Leave blank to keep the current password.');
                setRequiredPassword(false);
                $modal.modal('show');
            });
        })();
    </script>
</body>

</html>
