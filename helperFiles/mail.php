<?php
/**
 * Standalone mail helpers for the guidance module.
 * SMTP settings come from ../scilab/helperFiles/variableDeclarations.php
 * (loaded by the caller); PHPMailer is bundled in guidance/PHPMailer.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/admin_roles.php';

function scilab_resolve_lab_personnel_emails($conn) {
    $emails = [];
    $positions = GUIDANCE_ADMIN_POSITIONS;
    $placeholders = implode(',', array_fill(0, count($positions), '?'));
    $stmt = $conn->prepare("SELECT email FROM accounts WHERE status = 'active' AND position IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param(str_repeat('s', count($positions)), ...$positions);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['email'])) $emails[] = $row['email'];
        }
        $stmt->close();
    }
    return array_unique($emails);
}

function scilab_send_status_email($emails, $subject, $bodyHtml) {
    global $email_smtp_host, $email_smtp_user, $email_smtp_password, $email_smtp_secure, $email_smtp_port, $email_sender;

    if (empty($emails)) return;
    $emails = array_unique(array_filter($emails, function ($e) {
        return filter_var($e, FILTER_VALIDATE_EMAIL);
    }));
    if (empty($emails)) return;

    foreach ($emails as $email) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $email_smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $email_smtp_user;
            $mail->Password = $email_smtp_password;
            $mail->SMTPSecure = $email_smtp_secure;
            $mail->Port = $email_smtp_port;

            $mail->setFrom($email_sender, 'Guidance');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->send();
        } catch (Exception $e) {
            error_log("Status email failed to {$email}: " . ($mail->ErrorInfo ?? $e->getMessage()));
        }
    }
}
