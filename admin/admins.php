<?php
$asset_base = '../';
require('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');
require_once('../helperFiles/admin_roles.php');

/* Gate: admins only (re-checked against the table so revoked admins lose access here immediately). */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !guidance_is_admin($conn, $_SESSION['employeeID'] ?? '')) {
    header('Location: ../index.php');
    exit();
}

/* ── Constants ─────────────────────────────────────────────── */
$BASE_TYPES   = ['staff', 'faculty'];
$STATUSES     = ['active', 'inactive'];
$MIN_PASSWORD = 8;
$myID         = $_SESSION['employeeID'];
$isSuper      = guidance_is_super_admin($conn, $myID);

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

    /* ── Choose who is an admin (super admin only) ── */
    if ($action === 'save_admins') {
        if (!$isSuper) {
            flash_and_redirect('danger', 'Only the super admin can choose who is an admin.');
        }
        $selected = array_map('strval', (array)($_POST['admins'] ?? []));

        /* Only accept IDs that exist in accounts. */
        $valid = [];
        $res = $conn->query("SELECT employeeID FROM accounts");
        while ($row = $res->fetch_assoc()) $valid[$row['employeeID']] = true;
        $selected = array_values(array_filter($selected, function ($id) use ($valid) { return isset($valid[$id]); }));

        if (!guidance_set_admins($conn, $selected, $myID)) {
            flash_and_redirect('danger', 'Database error: admins could not be updated.');
        }
        flash_and_redirect('success', 'Admin access updated.');
    }

    if (!in_array($action, ['create', 'update'], true)) {
        flash_and_redirect('danger', 'Unknown action.');
    }
    if ($action === 'create' && !$isSuper) {
        flash_and_redirect('danger', 'Only the super admin can create admin accounts.');
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

    /* Existing row (for update): must exist and be an admin account. */
    $existing = null;
    if ($action === 'update') {
        $stmt = $conn->prepare(
            "SELECT a.*, g.is_super FROM accounts a
               JOIN guidance_admins g ON g.employeeID = a.employeeID
              WHERE a.employeeID = ?"
        );
        $stmt->bind_param('s', $employeeID);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$existing) {
            flash_and_redirect('danger', 'Admin account not found.');
        }
        if ((int)$existing['is_super'] === 1 && !$isSuper) {
            flash_and_redirect('danger', 'Only the super admin can edit the super admin account.');
        }
    }

    /* Field validation. */
    if ($employeeID === '' || strlen($employeeID) > 20) $errors[] = 'Employee ID is required (max 20 characters).';
    if ($username === '' || strlen($username) > 100)    $errors[] = 'Username is required (max 100 characters).';
    if ($firstname === '' || strlen($firstname) > 100)  $errors[] = 'First name is required (max 100 characters).';
    if (strlen($middlename) > 100)                      $errors[] = 'Middle name is too long (max 100 characters).';
    if ($lastname === '' || strlen($lastname) > 100)    $errors[] = 'Last name is required (max 100 characters).';
    if ($position === '' || strlen($position) > 50)     $errors[] = 'Position is required (max 50 characters).';
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
    if ($action === 'update' && $employeeID === $myID && $status !== 'active') {
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
                    $conn->begin_transaction();
                    $stmt = $conn->prepare(
                        "INSERT INTO accounts (employeeID, username, firstname, middlename, lastname, password, position, email, type, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param('ssssssssss', $employeeID, $username, $firstname, $middlename, $lastname, $hash, $position, $email, $type, $status);
                    $stmt->execute();
                    $stmt->close();
                    /* An account created here is an admin account. */
                    $stmt = $conn->prepare("INSERT INTO guidance_admins (employeeID, added_by) VALUES (?, ?)");
                    $stmt->bind_param('ss', $employeeID, $myID);
                    $stmt->execute();
                    $stmt->close();
                    $conn->commit();
                    flash_and_redirect('success', "Admin account for $firstname $lastname created.");
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
                    if ($employeeID === $myID) {
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
            try { $conn->rollback(); } catch (Throwable $e2) {}
            $errors[] = 'Database error: the account could not be saved.';
        }
    }

    flash_and_redirect('danger', implode(' ', $errors));
}

/* ── Load current admins ───────────────────────────────────── */
$admins = $conn->query(
    "SELECT a.employeeID, a.username, a.firstname, a.middlename, a.lastname, a.password,
            a.position, a.email, a.type, a.status, g.is_super
       FROM guidance_admins g
       JOIN accounts a ON a.employeeID = g.employeeID
      ORDER BY a.lastname, a.firstname"
)->fetch_all(MYSQLI_ASSOC);

/* Super admin only: every account, for the "who is an admin" picker. */
$allAccounts = [];
$adminIDs = array_column($admins, 'employeeID');
if ($isSuper) {
    $allAccounts = $conn->query(
        "SELECT employeeID, firstname, lastname, position, email, status FROM accounts ORDER BY lastname, firstname"
    )->fetch_all(MYSQLI_ASSOC);
}

/* Existing positions, offered as suggestions in the position field. */
$positionSuggestions = [];
$res = $conn->query("SELECT DISTINCT position FROM accounts WHERE position <> '' ORDER BY position");
while ($row = $res->fetch_assoc()) $positionSuggestions[] = $row['position'];

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
        .badge-super { background: rgba(43, 85, 196, 0.14); color: var(--color-primary-mid); margin-left: 6px; }

        .mgmt-alert { margin-bottom: 18px; }

        .pick-list { max-height: 360px; overflow-y: auto; border: 1px solid var(--color-border); border-radius: 8px; padding: 6px 14px; }
        .pick-list .checkbox { margin: 8px 0; font-size: 13px; }
        .pick-list .checkbox .sub { display: block; margin-left: 0; color: var(--color-text-secondary); font-size: 12px; }

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
                <p>
                    View and edit guidance administrator accounts.
                    <?php if ($isSuper): ?>As super admin you can also choose who is an admin and create admin accounts.<?php endif; ?>
                </p>
            </div>
            <div class="mgmt-actions">
                <?php if ($isSuper): ?>
                    <button type="button" class="btn-liquid-success" id="btnCreate">
                        <i class="bi bi-person-plus"></i>&nbsp; Create Account
                    </button>
                    <button type="button" class="btn-liquid" data-toggle="modal" data-target="#adminsModal">
                        <i class="bi bi-shield-check"></i>&nbsp; Choose Admins
                    </button>
                <?php endif; ?>
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
                        <?php $rowIsSuper = (int)$a['is_super'] === 1; ?>
                        <tr>
                            <td><?php echo h($a['employeeID']); ?></td>
                            <td>
                                <?php echo h($a['username']); ?>
                                <?php if ($rowIsSuper): ?><span class="badge-status badge-super">super admin</span><?php endif; ?>
                            </td>
                            <td><?php echo h($a['firstname']); ?></td>
                            <td><?php echo h($a['middlename']); ?></td>
                            <td><?php echo h($a['lastname']); ?></td>
                            <td><span class="pw-mask" title="Passwords are stored hashed and cannot be viewed">••••••••</span></td>
                            <td><?php echo h($a['position']); ?></td>
                            <td><?php echo h($a['email']); ?></td>
                            <td><?php echo h($a['type']); ?></td>
                            <td><span class="badge-status <?php echo h($a['status']); ?>"><?php echo h($a['status']); ?></span></td>
                            <td>
                                <?php if (!$rowIsSuper || $isSuper): ?>
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
                                <?php endif; ?>
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
                            <input type="text" class="form-control" id="fPosition" name="position" maxlength="50" list="positionList" required>
                            <datalist id="positionList">
                                <?php foreach ($positionSuggestions as $p): ?>
                                    <option value="<?php echo h($p); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
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

    <?php if ($isSuper): ?>
    <!-- Choose admins modal (super admin only) -->
    <div class="modal fade" id="adminsModal" tabindex="-1" role="dialog" aria-labelledby="adminsModalTitle">
        <div class="modal-dialog" role="document">
            <form method="post" action="admins.php" class="modal-content" autocomplete="off">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="action" value="save_admins">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="adminsModalTitle">Choose Admins</h4>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning" style="font-size:13px;">
                        Checked accounts can log in to the Guidance Hub (when active) and receive referral notification
                        emails. Unchecking an account removes its admin access. The super admin cannot be removed.
                    </div>

                    <input type="text" class="form-control" id="pickFilter" placeholder="Search name, position, or email" style="margin-bottom:10px;">

                    <div class="pick-list">
                        <?php foreach ($allAccounts as $acc): ?>
                            <?php
                                $isAdmin = in_array($acc['employeeID'], $adminIDs, true);
                                $locked  = false;
                                foreach ($admins as $ad) {
                                    if ($ad['employeeID'] === $acc['employeeID'] && (int)$ad['is_super'] === 1) $locked = true;
                                }
                                $search = strtolower($acc['firstname'] . ' ' . $acc['lastname'] . ' ' . $acc['position'] . ' ' . $acc['email'] . ' ' . $acc['employeeID']);
                            ?>
                            <div class="checkbox pick-item" data-search="<?php echo h($search); ?>">
                                <label>
                                    <input type="checkbox" name="admins[]" value="<?php echo h($acc['employeeID']); ?>"
                                        <?php echo $isAdmin ? 'checked' : ''; ?> <?php echo $locked ? 'disabled' : ''; ?>>
                                    <strong><?php echo h($acc['lastname'] . ', ' . $acc['firstname']); ?></strong>
                                    <?php if ($locked): ?><span class="badge-status badge-super">super admin</span><?php endif; ?>
                                    <?php if ($acc['status'] !== 'active'): ?><span class="badge-status inactive">inactive</span><?php endif; ?>
                                    <span class="sub"><?php echo h($acc['position']); ?> &middot; <?php echo h($acc['email']); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-liquid-success"><i class="bi bi-check2"></i>&nbsp; Save Admins</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

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
                $('#accountModalTitle').text('Create Admin Account');
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

            /* Search box in the "Choose Admins" picker. */
            $('#pickFilter').on('input', function () {
                var q = this.value.toLowerCase().trim();
                $('.pick-item').each(function () {
                    $(this).toggle(q === '' || $(this).data('search').indexOf(q) !== -1);
                });
            });
        })();
    </script>
</body>

</html>
