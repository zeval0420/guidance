<?php
require('helperFiles/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $timestamp = $_POST['ts'] ?? 0;
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($email) || empty($token) || empty($newPassword) || empty($timestamp)) {
        echo 'Missing required fields.';
        exit;
    }

    if (time() - $timestamp > 3600) { // 1 hour expiration
        echo 'Link expired.';
        exit;
    }

    // Fetch user to verify token
    $stmt = $conn->prepare("SELECT password FROM accounts WHERE email = ? AND status = 'active'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Reconstruct token: md5(email + current_password_hash + secret_salt + timestamp)
        $expectedToken = md5($email . $row['password'] . 'SciLabSecretSalt2025' . $timestamp);

        if ($token === $expectedToken) {
            // Token valid, update password
            $newHash = md5($newPassword);
            $update = $conn->prepare("UPDATE accounts SET password = ? WHERE email = ?");
            $update->bind_param('ss', $newHash, $email);

            if ($update->execute()) {
                echo 'success';
            } else {
                echo 'Database error.';
            }
        } else {
            echo 'Invalid or expired link.';
        }
    } else {
        echo 'User not found.';
    }
}