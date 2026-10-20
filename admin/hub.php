<?php
$asset_base = '../';
require('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

/* Gate: admins only. */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guidance Hub</title>
    <?php include('../helperFiles/headData.php'); ?>
    <style>
        .hub-wrapper {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 36px 24px 56px;
        }

        .hub-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .hub-head h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            color: var(--color-primary);
        }

        .hub-head p {
            margin: 6px 0 0;
            color: var(--color-text-secondary);
            font-size: 14px;
        }

        .hub-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hub-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .hub-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding: 22px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            text-decoration: none;
            color: var(--color-text-primary);
            transition: var(--transition-base);
            min-height: 150px;
        }

        .hub-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
            text-decoration: none;
            color: var(--color-primary);
            border-color: rgba(43, 85, 196, 0.35);
        }

        .hub-card .card-ico {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(43, 85, 196, 0.08), rgba(43, 85, 196, 0.18));
            color: var(--color-primary-mid);
            font-size: 22px;
        }

        .hub-card h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .hub-card p {
            margin: 0;
            font-size: 13px;
            color: var(--color-text-secondary);
            line-height: 1.45;
        }

        .hub-card .card-links {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
            margin-top: auto;
        }

        .hub-card .card-links a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-primary-mid);
            background: rgba(43, 85, 196, 0.07);
            text-decoration: none;
            transition: var(--transition-base);
        }

        .hub-card .card-links a:hover {
            background: rgba(43, 85, 196, 0.16);
            color: var(--color-primary);
        }

        .hub-card.disabled {
            cursor: default;
            opacity: 0.6;
        }

        .hub-card.disabled:hover {
            transform: none;
            box-shadow: var(--shadow-card);
            border-color: var(--color-border);
        }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="hub-wrapper">
        <div class="hub-head">
            <div>
                <h1>Guidance Hub</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?> — select a function below.</p>
            </div>
            <div class="hub-actions">
                <a href="../logout.php" class="btn-liquid-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <div class="hub-grid">
            <div class="hub-card">
                <span class="card-ico"><i class="bi bi-journal-text"></i></span>
                <h3>Referral System</h3>
                <p>Submit referrals, review and approve them, and track follow-ups on the calendar.</p>
                <div class="card-links">
                    <a href="../referral/index.php"><i class="bi bi-file-earmark-text"></i> Referral Form</a>
                    <a href="../referral/dashboard.php"><i class="bi bi-table"></i> Dashboard</a>
                    <a href="../referral/calendar.php"><i class="bi bi-calendar3"></i> Calendar</a>
                </div>
            </div>

            <div class="hub-card disabled">
                <span class="card-ico"><i class="bi bi-journal-plus"></i></span>
                <h3>More Modules</h3>
                <p>Additional guidance office functions will be added here in the future.</p>
            </div>
        </div>
    </div>

    <?php include('../helperFiles/footer.php'); ?>
</body>

</html>