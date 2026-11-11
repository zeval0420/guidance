<?php
/**
 * Guidance admin access.
 *
 * Admins are individual accounts listed in the `guidance_admins` table (not a
 * position). One "super admin" (is_super = 1) chooses who else is an admin from
 * admin/admins.php.
 *
 * Used by auth.php (login), helperFiles/mail.php (notification recipients)
 * and admin/admins.php (management page).
 */

/* The account that is made super admin the first time the table is set up. */
if (!defined('GUIDANCE_SUPER_ADMIN_EMAIL')) {
    define('GUIDANCE_SUPER_ADMIN_EMAIL', 'pangdasl@irc.pshs.edu.ph');
}

/** Last database error from an admin check (empty string if none). */
function guidance_admin_last_error($set = null)
{
    static $msg = '';
    if ($set !== null) $msg = $set;
    return $msg;
}

/** Create the table if needed, seed it on first use, and make sure a super admin exists. */
function guidance_ensure_admins_table($conn)
{
    static $done = false;
    if ($done) return;

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `guidance_admins` (
            `employeeID` VARCHAR(20) NOT NULL,
            `is_super`   TINYINT(1) NOT NULL DEFAULT 0,
            `added_by`   VARCHAR(20) NULL DEFAULT NULL,
            `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`employeeID`)
         ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci"
    );

    /* Make sure a super admin exists (re-tried until the account is found). */
    $has = $conn->query("SELECT 1 FROM `guidance_admins` WHERE `is_super` = 1 LIMIT 1")->num_rows > 0;
    if (!$has) {
        $email = GUIDANCE_SUPER_ADMIN_EMAIL;
        $stmt = $conn->prepare(
            "INSERT INTO `guidance_admins` (`employeeID`, `is_super`)
             SELECT employeeID, 1 FROM accounts WHERE email = ? ORDER BY (status = 'active') DESC LIMIT 1
             ON DUPLICATE KEY UPDATE `is_super` = 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }

    $done = true;
}

/** Is this employee a guidance admin? Fails closed on any database error. */
function guidance_is_admin($conn, $employeeID)
{
    try {
        guidance_ensure_admins_table($conn);
        $stmt = $conn->prepare("SELECT 1 FROM `guidance_admins` WHERE `employeeID` = ?");
        $stmt->bind_param('s', $employeeID);
        $stmt->execute();
        $ok = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        guidance_admin_last_error($e->getMessage());
        error_log('guidance_is_admin failed: ' . $e->getMessage());
        return false;
    }
}

/** Is this employee the super admin (the only one who can choose admins)? */
function guidance_is_super_admin($conn, $employeeID)
{
    try {
        guidance_ensure_admins_table($conn);
        $stmt = $conn->prepare("SELECT 1 FROM `guidance_admins` WHERE `employeeID` = ? AND `is_super` = 1");
        $stmt->bind_param('s', $employeeID);
        $stmt->execute();
        $ok = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        guidance_admin_last_error($e->getMessage());
        error_log('guidance_is_super_admin failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Make the admin set exactly $employeeIDs (the super admin is always kept).
 * Returns true on success.
 */
function guidance_set_admins($conn, array $employeeIDs, $byEmployeeID)
{
    try {
        guidance_ensure_admins_table($conn);

        $current = [];
        $super   = [];
        $res = $conn->query("SELECT `employeeID`, `is_super` FROM `guidance_admins`");
        while ($r = $res->fetch_assoc()) {
            $current[] = $r['employeeID'];
            if ((int)$r['is_super'] === 1) $super[] = $r['employeeID'];
        }

        $wanted = array_values(array_unique(array_merge($employeeIDs, $super)));
        $toAdd    = array_diff($wanted, $current);
        $toRemove = array_diff($current, $wanted);

        $conn->begin_transaction();
        $add = $conn->prepare("INSERT INTO `guidance_admins` (`employeeID`, `added_by`) VALUES (?, ?)");
        foreach ($toAdd as $id) {
            $add->bind_param('ss', $id, $byEmployeeID);
            $add->execute();
        }
        $add->close();
        $del = $conn->prepare("DELETE FROM `guidance_admins` WHERE `employeeID` = ? AND `is_super` = 0");
        foreach ($toRemove as $id) {
            $del->bind_param('s', $id);
            $del->execute();
        }
        $del->close();
        $conn->commit();
        return true;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        return false;
    }
}
