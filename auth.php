<?php
/* Guidance admin authentication endpoint (login + forgot password). */
require('helperFiles/db_connection.php');
include('helperFiles/session_handler.php');
include('../../helperFiles/variableDeclarations.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

/* Admin positions allowed into the guidance hub (same check reserve-a-lab uses). */
$accepted_admin_roles = ['Sci. Res. Assist.', 'Sci. Research Specialist I'];

if (isset($_POST['action']) && $_POST['action'] === 'loginUser') {
    $input_identity = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';

    if ($input_identity === '' || $password === '') {
        echo 'invalid_credentials';
        exit();
    }

    /* Accept email or username; append the campus domain for bare names. */
    $is_email = strpos($input_identity, '@') !== false;
    $email_query = $is_email ? $input_identity : $input_identity . '@irc.pshs.edu.ph';

    $stmt = $conn->prepare("SELECT * FROM accounts WHERE email = ? AND status = 'active'");
    $stmt->bind_param('s', $email_query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        echo 'invalid_email';
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (md5($password) !== $user['password']) {
        echo 'invalid_password';
        exit();
    }

    if (!in_array($user['position'], $accepted_admin_roles)) {
        echo 'access_denied';
        exit();
    }

    /* Admin authenticated — build the session. */
    $_SESSION['employeeID'] = $user['employeeID'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['firstname']  = $user['firstname'];
    $_SESSION['middlename'] = $user['middlename'];
    $_SESSION['lastname']   = $user['lastname'];
    $_SESSION['username']   = trim($user['firstname'] . ' ' . $user['lastname']);
    $_SESSION['role']       = 'admin';

    echo 'admin';
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'forgotPassword') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        echo 'Email not found.';
        exit();
    }

    /* Locate an active account in the accounts table. */
    $stmt = $conn->prepare("SELECT firstname, lastname, email, password FROM accounts WHERE email = ? AND status = 'active'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo 'Email not found.';
        exit();
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $email_smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_smtp_user;
        $mail->Password   = $email_smtp_password;
        $mail->SMTPSecure = $email_smtp_secure;
        $mail->Port       = $email_smtp_port;

        $mail->setFrom($email_sender, $email_sender_name);
        $mail->addAddress($user['email']);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';

        $timestamp = time();
        $token = md5($user['email'] . $user['password'] . 'SciLabSecretSalt2025' . $timestamp);
        $link = 'http://' . $_SERVER['HTTP_HOST'] . '/reserve-a-lab/fil/guidance/reset_password.php?email=' . urlencode($user['email']) . '&token=' . $token . '&ts=' . $timestamp;

        $mail->Body = 'Hello ' . $user['firstname'] . ',<br><br>You requested a password reset. Click the link below to proceed:<br><a href="' . $link . '">' . $link . '</a><br><br>If you did not request this, please ignore this email.';

        $mail->send();
        echo 'success';
    } catch (Exception $e) {
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
    exit();
}

echo 'invalid_request';