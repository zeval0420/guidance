<?php
    session_start();
    $timeout_duration = 28800; // 8 hours

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        $_SESSION['session_expired'] = true;
        $base = $asset_base ?? '';
        $redirect_url = $base . 'index.php';
        if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
            $redirect_url .= '?redirect=' . urlencode(basename($_SERVER['REQUEST_URI']));
        }
        header("Location: " . $redirect_url);
        exit();
    }
    $_SESSION['last_activity'] = time();
?>