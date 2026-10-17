<?php
require('helperFiles/db_connection.php');
include('helperFiles/session_handler.php');

/* Already logged in as admin? Straight to the hub. */
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/hub.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Guidance Admin · Login</title>
    <link rel="icon" type="image/x-icon" href="img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet" />
    <style>
        /* ─── DESIGN TOKENS ─────────────────────────────────── */
        :root {
            --primary: #0B1B62;
            --primary-light: #152985;
            --secondary: #4F73D9;
            --secondary-glow: rgba(79, 115, 217, 0.25);
            --bg-page: #EBF0FA;
            --bg-card: rgba(255, 255, 255, 0.9);
            --bg-modal: #FFFFFF;
            --text-primary: #0B1B62;
            --text-secondary: #5E6E88;
            --input-bg: #F8FAFC;
            --border: #E2E8F0;
            --error: #DC2626;
            --error-bg: #FEF2F2;
            --error-border: #FECACA;
            --success: #16A34A;
            --success-bg: #F0FDF4;
            --success-border: #BBF7D0;
            --radius-card: 16px;
            --radius-input: 10px;
            --shadow-card: 0 20px 40px rgba(11, 27, 98, 0.08), 0 1px 3px rgba(11, 27, 98, 0.04);
            --shadow-panel: 0 30px 60px rgba(11, 27, 98, 0.15);
            --shadow-modal: 0 30px 60px rgba(11, 27, 98, 0.2);
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-page);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
        }

        /* ─── LAYOUT ─────────────────────────────────────────── */
        .page-wrapper {
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, #F0F4FC 0%, #EBF0FA 100%);
            overflow: hidden;
        }

        .page-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(11, 27, 98, 0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(11, 27, 98, 0.02) 1px, transparent 1px);
            background-size: 24px 24px;
            pointer-events: none;
            z-index: 0;
        }

        .split-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .card-pane {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            overflow-y: auto;
        }

        .visual-pane {
            display: none;
        }

        @media (min-width: 992px) {
            .split-container {
                width: 92vw;
                max-width: 1200px;
                height: 85vh;
                max-height: 780px;
                gap: 2.5rem;
            }

            .card-pane {
                flex: 1;
                width: auto;
                height: 100%;
                padding: 0;
                justify-content: flex-end;
                overflow-y: visible;
            }

            .visual-pane {
                display: block;
                flex: 1.1;
                height: 100%;
                position: relative;
                border-radius: var(--radius-card);
                overflow: hidden;
                box-shadow: var(--shadow-panel);
                animation: panel-entrance 0.7s var(--transition) both;
            }

            @keyframes panel-entrance {
                from { opacity: 0; transform: translateX(30px) scale(0.98); }
                to { opacity: 1; transform: translateX(0) scale(1); }
            }

            .visual-panel-img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                background-color: var(--primary);
                background-image:
                    radial-gradient(circle at 80% 20%, rgba(79, 115, 217, 0.4) 0%, transparent 50%),
                    radial-gradient(circle at 20% 80%, rgba(11, 27, 98, 0.6) 0%, transparent 70%),
                    url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><g stroke="%23ffffff" stroke-width="0.5" stroke-opacity="0.1"><circle cx="50" cy="50" r="40" fill="none"/><circle cx="50" cy="50" r="25" fill="none"/><line x1="10" y1="50" x2="90" y2="50"/><line x1="50" y1="10" x2="50" y2="90"/></g></svg>');
                background-size: cover, cover, 180px 180px;
                border-radius: var(--radius-card);
                transition: transform 4s ease;
            }

            .visual-pane:hover .visual-panel-img { transform: scale(1.03); }

            .visual-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(to top, rgba(11, 27, 98, 0.85) 0%, rgba(11, 27, 98, 0.2) 60%, transparent 100%);
                padding: 3rem;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                color: #FFFFFF;
                pointer-events: none;
            }

            .visual-overlay h2 {
                font-family: 'Playfair Display', Georgia, serif;
                font-size: 2.2rem;
                font-weight: 700;
                margin-bottom: 0.75rem;
                line-height: 1.2;
                text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            }

            .visual-overlay p {
                font-size: 0.95rem;
                color: rgba(255, 255, 255, 0.85);
                max-width: 440px;
                line-height: 1.5;
                text-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
            }
        }

        /* ─── CARD ───────────────────────────────────────────── */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            animation: card-entrance 0.6s var(--transition) both;
        }

        @keyframes card-entrance {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-header-band {
            background: var(--primary);
            padding: 1.75rem 2rem;
            text-align: center;
            position: relative;
        }

        .card-header-band::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
        }

        .card-header-band .system-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.3rem;
        }

        .card-header-band h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: #FFFFFF;
            line-height: 1.25;
        }

        .card-body {
            padding: 2.25rem 2.25rem 1.75rem;
        }

        /* ─── INSTITUTION PROFILE ────────────────────────────── */
        .institution {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .logo-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #FFFFFF;
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(11, 27, 98, 0.04);
            overflow: hidden;
        }

        .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .institution-text { line-height: 1.4; }

        .inst-line1 {
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .inst-line2 {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--secondary);
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .inst-line3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary);
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0 0 1.75rem;
        }

        /* ─── ALERTS ─────────────────────────────────────────── */
        #alert-area { margin-bottom: 1.25rem; }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: var(--radius-input);
            font-size: 0.82rem;
            font-weight: 500;
            animation: alert-fade-in 0.25s ease both;
        }

        @keyframes alert-fade-in {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error);
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success);
        }

        .alert-icon { flex-shrink: 0; margin-top: 0.05rem; }

        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
            font-size: 1rem;
            padding-left: 0.5rem;
            line-height: 1;
        }

        .alert-close:hover { opacity: 1; }

        /* ─── FIELDS & INPUTS ────────────────────────────────── */
        .field {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }

        .input-wrap { position: relative; }

        .input-wrap .field-icon {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .field input {
            width: 100%;
            height: 46px;
            padding: 0 1rem 0 2.6rem;
            background: var(--input-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-input);
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s var(--transition), box-shadow 0.2s var(--transition), background-color 0.2s ease;
        }

        .field input::placeholder { color: #A0AEC0; }

        .field input:focus {
            background-color: #FFFFFF;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px var(--secondary-glow);
        }

        .field input:focus+.field-icon { color: var(--secondary); }

        .field input.is-invalid {
            border-color: var(--error) !important;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.15) !important;
        }

        .inline-feedback {
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.35rem;
            display: none;
        }

        .field input.is-invalid~.inline-feedback {
            color: var(--error);
            display: block;
        }

        .field .input-wrap-password input { padding-right: 2.8rem; }

        .pw-toggle {
            position: absolute;
            right: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: color 0.2s ease;
        }

        .pw-toggle:hover { color: var(--primary); }

        .forgot-row {
            text-align: right;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        /* ─── BUTTONS ────────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            height: 46px;
            border: none;
            border-radius: var(--radius-input);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s var(--transition), background-color 0.2s var(--transition), box-shadow 0.2s var(--transition);
            letter-spacing: 0.01em;
        }

        .btn:active { transform: scale(0.98); }

        .btn-primary {
            background: var(--primary);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(11, 27, 98, 0.15);
        }

        .btn-primary:hover {
            background: var(--primary-light);
            box-shadow: 0 6px 20px rgba(11, 27, 98, 0.25);
        }

        .btn-secondary {
            background: transparent;
            color: var(--secondary);
            border: 1.5px solid var(--secondary);
        }

        .btn-secondary:hover {
            background: rgba(79, 115, 217, 0.06);
            box-shadow: 0 4px 12px rgba(79, 115, 217, 0.1);
        }

        .btn-ghost {
            background: none;
            border: none;
            color: var(--secondary);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
            transition: color 0.2s ease;
            text-decoration: none;
        }

        .btn-ghost:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        /* ─── MODAL ──────────────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 27, 98, 0.4);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
            overflow-y: auto;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal {
            background: var(--bg-modal);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-modal);
            width: 100%;
            max-width: 480px;
            padding: 2rem;
            transform: scale(0.94) translateY(10px);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            margin: auto;
        }

        .modal-overlay.open .modal { transform: scale(1) translateY(0); }

        .modal-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.35rem;
        }

        .modal-desc {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.75rem;
        }

        .modal-actions .btn { height: 44px; }

        @media (max-width: 480px) {
            .card-body { padding: 1.75rem 1.5rem 1.5rem; }
            .modal { padding: 1.5rem; }
        }
    </style>
</head>

<body>

    <div class="page-wrapper">
        <div class="split-container">

            <div class="card-pane">
                <div class="card" role="main">

                    <div class="card-header-band">
                        <p class="system-label">Guidance Office</p>
                        <h1>Admin Access</h1>
                    </div>

                    <div class="card-body">

                        <div class="institution" aria-label="Institution Profile">
                            <div class="logo-circle" aria-hidden="true">
                                <img src="img/logo.png" alt="Philippine Science High School Logo">
                            </div>
                            <div class="institution-text">
                                <p class="inst-line1">Department of Science and Technology</p>
                                <p class="inst-line2">Ilocos Region Campus</p>
                                <p class="inst-line3">Philippine Science High School</p>
                            </div>
                        </div>

                        <hr class="divider" />

                        <div id="alert-area" role="alert" aria-live="polite"></div>

                        <form id="login-form" novalidate>

                            <div class="field">
                                <label for="username">Email or Username</label>
                                <div class="input-wrap">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    <input type="text" id="username" name="username" placeholder="you@pshs.edu.ph"
                                        autocomplete="username" />
                                    <div class="inline-feedback"></div>
                                </div>
                            </div>

                            <div class="field">
                                <label for="password">Password</label>
                                <div class="input-wrap input-wrap-password">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                    <input type="password" id="password" name="password" placeholder="••••••••"
                                        autocomplete="current-password" />
                                    <button type="button" class="pw-toggle" id="pw-toggle"
                                        aria-label="Toggle password visibility">
                                        <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                    <div class="inline-feedback"></div>
                                </div>
                            </div>

                            <div class="forgot-row">
                                <button type="button" class="btn-ghost" id="open-forgot">Forgot password?</button>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                    <polyline points="10 17 15 12 10 7" />
                                    <line x1="15" y1="12" x2="3" y2="12" />
                                </svg>
                                Login
                            </button>

                        </form>
                    </div>
                </div>
            </div>

            <div class="visual-pane">
                <div class="visual-panel-img"></div>
                <div class="visual-overlay">
                    <h2>Guidance Records Hub</h2>
                    <p>Sign in to manage referral submissions and access guidance office functions across the
                        Ilocos Region Campus.</p>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-overlay" id="modal-forgot" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
        <div class="modal">
            <p class="modal-title" id="forgot-title">Reset Password</p>
            <p class="modal-desc">Enter your registered email address and we'll send you password reset instructions.
            </p>
            <div class="field">
                <label for="reset-email">Email Address</label>
                <div class="input-wrap">
                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                    <input type="email" id="reset-email" placeholder="you@pshs.edu.ph" autocomplete="email" />
                    <div class="inline-feedback"></div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="close-forgot">Cancel</button>
                <button type="button" class="btn btn-primary" id="submit-reset">Send Instructions</button>
            </div>
        </div>
    </div>

    <script>
        const $ = id => document.getElementById(id);

        /* ── Modal Automation ── */
        function openModal(id) {
            $(id).classList.add('open');
            const firstInput = $(id).querySelector('input');
            if (firstInput) setTimeout(() => firstInput.focus(), 260);
        }

        function closeModal(id) {
            $(id).classList.remove('open');
            const form = $(id).querySelector('form');
            if (form) form.reset();
        }

        ['modal-forgot'].forEach(id => {
            if ($(id)) {
                $(id).addEventListener('click', e => {
                    if (e.target === $(id)) closeModal(id);
                });
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal('modal-forgot');
        });

        if ($('open-forgot')) $('open-forgot').addEventListener('click', () => openModal('modal-forgot'));
        if ($('close-forgot')) $('close-forgot').addEventListener('click', () => closeModal('modal-forgot'));

        /* ── Visual state ── */
        function setValidationState(element, state, message = '') {
            const feedback = element.parentElement.querySelector('.inline-feedback');
            element.classList.remove('is-invalid');
            if (state === 'invalid') {
                element.classList.add('is-invalid');
                if (feedback) feedback.textContent = message;
            } else {
                if (feedback) feedback.textContent = '';
            }
        }

        function clearAlerts() {
            if ($('alert-area')) $('alert-area').innerHTML = '';
        }

        function showAlert(message, type = 'error') {
            clearAlerts();
            if (!$('alert-area')) return;
            const vectors = {
                error: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                success: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`
            };
            const block = document.createElement('div');
            block.className = `alert alert-${type}`;
            block.innerHTML =
                `<span class="alert-icon">${vectors[type] || ''}</span>` +
                `<span>${message}</span>` +
                `<button class="alert-close" type="button" aria-label="Dismiss Alert" onclick="this.parentElement.remove()">✕</button>`;
            $('alert-area').appendChild(block);
        }

        /* ── Password visibility toggle ── */
        if ($('pw-toggle')) {
            $('pw-toggle').addEventListener('click', function () {
                const targetInput = $('password');
                const isHidden = targetInput.type === 'password';
                targetInput.type = isHidden ? 'text' : 'password';
                const valLength = targetInput.value.length;
                targetInput.setSelectionRange(valLength, valLength);
                targetInput.focus();
                this.innerHTML = isHidden
                    ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                    : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
            });
        }

        /* ── Forgot password ── */
        if ($('submit-reset')) {
            $('submit-reset').addEventListener('click', () => {
                const email = $('reset-email');
                const emailVal = email.value.trim();
                if (!emailVal || !/\S+@\S+\.\S+/.test(emailVal)) {
                    setValidationState(email, 'invalid', 'Please provide a valid email address.');
                    email.focus();
                    return;
                }
                setValidationState(email, 'neutral');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'auth.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        if (xhr.responseText.trim() === 'success') {
                            closeModal('modal-forgot');
                            showAlert('Reset link sent to your email.', 'success');
                        } else {
                            showAlert(xhr.responseText.trim(), 'error');
                        }
                    } else {
                        showAlert('Server error. Please try again.', 'error');
                    }
                };
                xhr.send('action=forgotPassword&email=' + encodeURIComponent(emailVal));
            });
        }

        /* ── Login ── */
        if ($('login-form')) {
            $('login-form').addEventListener('submit', function (e) {
                e.preventDefault();
                clearAlerts();

                const usernameField = $('username');
                const passwordField = $('password');
                let formValid = true;

                const email = usernameField.value.trim();
                const password = passwordField.value;

                if (!email) {
                    setValidationState(usernameField, 'invalid', 'Please enter your email or username.');
                    formValid = false;
                } else {
                    setValidationState(usernameField, 'neutral');
                }

                if (!password) {
                    setValidationState(passwordField, 'invalid', 'Please enter your password.');
                    formValid = false;
                } else {
                    setValidationState(passwordField, 'neutral');
                }

                if (!formValid) {
                    showAlert('Please fill out all fields before submitting.', 'error');
                    return;
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'auth.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const response = xhr.responseText.trim();
                        if (response === 'admin') {
                            window.location.href = 'admin/hub.php';
                        } else if (response === 'invalid_email') {
                            setValidationState(usernameField, 'invalid', 'Email or Username not found.');
                            showAlert('Email or Username not found.', 'error');
                        } else if (response === 'invalid_password') {
                            setValidationState(passwordField, 'invalid', 'Incorrect password.');
                            showAlert('Incorrect password.', 'error');
                        } else if (response === 'access_denied') {
                            showAlert('Access denied. Admin clearance is required.', 'error');
                        } else {
                            showAlert('Unexpected response: ' + response, 'error');
                        }
                    } else {
                        showAlert('An error occurred. Please try again.', 'error');
                    }
                };
                xhr.send('action=loginUser&email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password));
            });
        }
    </script>
</body>

</html>