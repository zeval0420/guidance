<?php
/**
 * Single source of truth for which account positions count as guidance admins.
 * Used by the login check (auth.php), the notification recipients (mail.php)
 * and the admin management module (admin/admins.php).
 */
if (!defined('GUIDANCE_ADMIN_POSITIONS')) {
    define('GUIDANCE_ADMIN_POSITIONS', [
        'Sci. Res. Assist.',
        'Sci. Research Specialist I',
        'G.S.A. I',
        'Guidance Designate',
    ]);
}
