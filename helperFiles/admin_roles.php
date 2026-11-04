<?php
/**
 * Which account positions count as guidance admins.
 *
 * The list is stored in the `guidance_admin_positions` table and managed from
 * admin/admins.php. If the table is missing it is created and seeded with the
 * defaults below; if the database call fails the defaults are used, so the
 * login can never end up with an empty list.
 *
 * Used by auth.php (login), helperFiles/mail.php (notification recipients)
 * and admin/admins.php (management page).
 */
if (!defined('GUIDANCE_ADMIN_POSITIONS')) {
    define('GUIDANCE_ADMIN_POSITIONS', [
        'Sci. Res. Assist.',
        'Sci. Research Specialist I',
        'G.S.A. I',
        'Guidance Designate',
    ]);
}

/** Create the table (and seed the defaults) if it does not exist yet. */
function guidance_ensure_admin_positions_table($conn)
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS `guidance_admin_positions` (
            `position` VARCHAR(50) NOT NULL,
            PRIMARY KEY (`position`)
         ) ENGINE=InnoDB DEFAULT CHARSET=latin1"
    );
    $count = $conn->query("SELECT COUNT(*) AS c FROM `guidance_admin_positions`")->fetch_assoc();
    if ((int)$count['c'] === 0) {
        $stmt = $conn->prepare("INSERT IGNORE INTO `guidance_admin_positions` (`position`) VALUES (?)");
        foreach (GUIDANCE_ADMIN_POSITIONS as $p) {
            $stmt->bind_param('s', $p);
            $stmt->execute();
        }
        $stmt->close();
    }
}

/** Positions currently treated as admins (array of strings, never empty). */
function guidance_admin_positions($conn)
{
    static $cache = null;
    if ($cache !== null) return $cache;

    try {
        guidance_ensure_admin_positions_table($conn);
        $positions = [];
        $res = $conn->query("SELECT `position` FROM `guidance_admin_positions` ORDER BY `position`");
        while ($row = $res->fetch_assoc()) $positions[] = $row['position'];
        $cache = $positions ?: GUIDANCE_ADMIN_POSITIONS;
    } catch (Throwable $e) {
        $cache = GUIDANCE_ADMIN_POSITIONS;
    }
    return $cache;
}

/** Replace the stored admin positions. Returns true on success. */
function guidance_save_admin_positions($conn, array $positions)
{
    try {
        guidance_ensure_admin_positions_table($conn);
        $conn->begin_transaction();
        $conn->query("DELETE FROM `guidance_admin_positions`");
        $stmt = $conn->prepare("INSERT INTO `guidance_admin_positions` (`position`) VALUES (?)");
        foreach ($positions as $p) {
            $stmt->bind_param('s', $p);
            $stmt->execute();
        }
        $stmt->close();
        $conn->commit();
        return true;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        return false;
    }
}
