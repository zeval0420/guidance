<?php
require('helperFiles/db_connection.php');

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$timestamp = $_GET['ts'] ?? '';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <?php include('helperFiles/headData.php'); ?>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to right, #0e0054, #0036af, #0e0054);
            background-size: cover;
            font-family: "Raleway", sans-serif;
        }
        .reset-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 25px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            color: white;
            text-align: center;
        }
        .reset-box h2 { margin-bottom: 20px; font-weight: bold; }
        .reset-box label { font-weight: 600; }
        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            margin-bottom: 15px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: none;
        }
        .reset-box .btn-liquid { width: 100%; margin-top: 10px; }
        .password-wrapper { position: relative; }
        .toggle-button {
            display: inline-flex;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 12px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.8);
        }
        .eye-icon { width: 20px; height: 20px; }
    </style>
</head>

<body>
    <div class="reset-box">
        <h2>Reset Password</h2>
        <?php if (empty($email) || empty($token)): ?>
            <p class="text-danger">Invalid link. Please request a new password reset link.</p>
            <a href="index.php" class="btn btn-liquid-white">Go to Login</a>
        <?php else: ?>
            <form id="resetPasswordForm">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="ts" value="<?php echo htmlspecialchars($timestamp); ?>">

                <div class="form-group text-left">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" class="form-control liquid-input" required placeholder="Enter new password">
                        <div class="toggle-button"></div>
                    </div>
                </div>

                <div class="form-group text-left">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" class="form-control liquid-input" required placeholder="Confirm new password">
                        <div class="toggle-button"></div>
                    </div>
                </div>

                <button type="submit" class="btn-liquid">Reset Password</button>
            </form>
            <br>
            <div class="mt-3">
                <a href="index.php" style="color: rgba(255,255,255,0.8); font-size: 14px;">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script>
        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = 'toast-notification ' + type;
            toast.innerHTML =
                '<div style="display:flex; align-items:center;"><span>' + message + '</span></div>' +
                '<button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
            container.appendChild(toast);
            setTimeout(function () {
                toast.classList.add('hide');
                toast.addEventListener('animationend', function () { toast.remove(); });
            }, duration);
        }
    </script>

    <script>
        $(document).ready(function() {
            const eyeIcons = {
                open: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="eye-icon"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" /></svg>`,
                closed: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="eye-icon"><path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z" /><path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.53 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243z" /><path d="M6.75 12c0-.619.107-1.213.304-1.764l-3.1-3.1a11.25 11.25 0 00-2.63 4.31c-.12.362-.12.752 0 1.114 1.489 4.467 5.704 7.69 10.675 7.69 1.5 0 2.933-.294 4.242-.827l-2.477-2.477A5.25 5.25 0 016.75 12z" /></svg>`
            };

            $(".toggle-button").html(eyeIcons.open);

            $(".toggle-button").on("click", function () {
                const $button = $(this);
                const $input = $button.siblings("input");
                const isOpen = $button.hasClass("open");

                $button.toggleClass("open");
                $button.html(isOpen ? eyeIcons.open : eyeIcons.closed);
                $input.attr("type", isOpen ? "password" : "text");
            });

            $('#resetPasswordForm').submit(function(e) {
                e.preventDefault();
                const p1 = $('input[name="new_password"]').val();
                const p2 = $('input[name="confirm_password"]').val();

                if (p1 !== p2) {
                    showToast("Passwords do not match.", "warning");
                    return;
                }

                $.post('ajax_reset_password.php', $(this).serialize(), function(res) {
                    if (res.trim() === 'success') {
                        showToast("Password reset successfully! Redirecting...", "success");
                        setTimeout(() => window.location.href = 'index.php', 2000);
                    } else {
                        showToast(res, "error");
                    }
                }).fail(function() {
                    showToast("Server error. Please try again.", "error");
                });
            });
        });
    </script>
</body>

</html>