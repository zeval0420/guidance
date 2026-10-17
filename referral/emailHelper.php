<?php
/**
 * Referral email notifications (guidance-only).
 *
 * Reuses the main reserve-a-lab email stack:
 *   - ../../helperFiles/variableDeclarations.php  → SMTP creds + $enable_email_notifications
 *   - ../../helperFiles/scilab_email.php          → scilab_send_status_email() + scilab_resolve_lab_personnel_emails()
 *
 * Both files resolve their own paths, so they work from anywhere under /reserve-a-lab.
 */

/* Deployed at reserve-a-lab/fil/guidance; a standalone htdocs/guidance checkout uses the sibling reserve-a-lab. */
$guidanceMainHelpers = is_file(__DIR__ . '/../../../helperFiles/scilab_email.php')
    ? __DIR__ . '/../../../helperFiles'
    : __DIR__ . '/../../reserve-a-lab/helperFiles';
require_once $guidanceMainHelpers . '/variableDeclarations.php';
require_once $guidanceMainHelpers . '/scilab_email.php';

/**
 * Recipients: every active account with guidance-admin clearance
 * (same positions the hub login accepts, and the same query reserve-a-lab
 * uses for its lab personnel).
 */
function guidance_admin_emails($conn)
{
    return scilab_resolve_lab_personnel_emails($conn);
}

/**
 * Notify all guidance admins that a new referral form has been submitted.
 * $data: student, grade_section, campus, date_referred, requires_followup, description, id
 */
function guidance_send_submission_notification($conn, $data)
{
    global $enable_email_notifications;

    if (empty($enable_email_notifications)) return;

    $id = intval($data['id'] ?? 0);
    $subject = 'PSHS Referral #' . $id . ' - New Submission';

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/reserve-a-lab/fil/guidance';
    $dashLink = $baseURL . '/referral/dashboard.php?status=Pending';

    $body = '<p>A new guidance referral form has been submitted and is awaiting review.</p>'
        . '<p><strong>Referral No.:</strong> ' . $id . '<br>'
        . '<strong>Student:</strong> ' . htmlspecialchars($data['student'] ?? '') . '<br>'
        . '<strong>Grade &amp; Section:</strong> ' . htmlspecialchars($data['grade_section'] ?? '') . '<br>'
        . '<strong>Campus:</strong> ' . htmlspecialchars($data['campus'] ?? '') . '<br>'
        . '<strong>Date Referred:</strong> ' . htmlspecialchars($data['date_referred'] ?? '-') . '<br>'
        . '<strong>Requires Follow-up:</strong> ' . htmlspecialchars($data['requires_followup'] ?? '-') . '</p>'
        . '<p><strong>Brief Description:</strong><br>' . nl2br(htmlspecialchars($data['description'] ?? '')) . '</p>'
        . '<p>Review this submission here: <a href="' . htmlspecialchars($dashLink) . '">Referral Dashboard</a></p>';

    $emails = guidance_admin_emails($conn);
    scilab_send_status_email($emails, $subject, $body);
}

/**
 * Notify the referring teacher when a submission is approved or rejected.
 * $data: id, student, status, reviewed_by, reviewed_at, referrer_email
 */
function guidance_send_review_notification($conn, $data)
{
    global $enable_email_notifications;

    if (empty($enable_email_notifications)) return;

    $referrerEmail = trim($data['referrer_email'] ?? '');
    if ($referrerEmail === '' || !filter_var($referrerEmail, FILTER_VALIDATE_EMAIL)) return;

    $id = intval($data['id'] ?? 0);
    $status = ($data['status'] ?? '') === 'Approved' ? 'approved' : 'rejected';
    $subject = 'PSHS Referral #' . $id . ' - ' . ucfirst($status);
    $student = $data['student'] ?? '';
    $reviewer = trim($data['reviewed_by'] ?? '');
    $reviewedAt = $data['reviewed_at'] ?? '';

    $body = '<p>Your guidance referral form for <strong>' . htmlspecialchars($student) . '</strong> (Referral No. ' . $id . ') has been <strong>' . $status . '</strong> by the Guidance Office.</p>';
    if ($reviewer !== '') {
        $body .= '<p><strong>Reviewed By:</strong> ' . htmlspecialchars($reviewer) . '<br>'
            . '<strong>Review Date:</strong> ' . htmlspecialchars($reviewedAt) . '</p>';
    }
    $body .= '<p>Please coordinate with the Guidance Office at ' . htmlspecialchars($_SERVER['HTTP_HOST']) . ' if you have any questions.</p>';

    scilab_send_status_email([$referrerEmail], $subject, $body);
}