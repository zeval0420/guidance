<?php
session_start();
$asset_base = '../';
require('../helperFiles/db_connection.php');
require_once('emailHelper.php');

/* PRG: confirmations arrive via ?saved=1 / ?saved=0 */
$saved = isset($_GET['saved']) ? (int)$_GET['saved'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_referral'])) {
    /* Sanitize basic fields */
    $campus        = trim($_POST['campus'] ?? '');
    $student       = trim($_POST['student'] ?? '');
    $gradeSection  = trim($_POST['grade'] ?? '');
    $dateReferred  = trim($_POST['date'] ?? '');
    if ($dateReferred === '') { $dateReferred = null; }
    $description   = trim($_POST['description'] ?? '');
    $intervention  = trim($_POST['intervention'] ?? '');
    $followup      = ($_POST['followup'] ?? '') === 'Yes' || ($_POST['followup'] ?? '') === 'No' ? $_POST['followup'] : null;
    $otherBehavior = trim($_POST['other'] ?? '');
    $followupDate  = trim($_POST['followup_date'] ?? '');
    if ($followupDate === '' || $followup !== 'Yes') { $followupDate = null; }

    /* Concern checkbox columns */
    $concernAcademic = isset($_POST['concern_academic']) ? 1 : 0;
    $concernBehavior = isset($_POST['concern_behavior']) ? 1 : 0;
    $concernPersonal = isset($_POST['concern_personal']) ? 1 : 0;

    /* Behavior checkbox columns */
    $behaviorColumns = [
        'behavior_depressed',
        'behavior_hopelessness',
        'behavior_crying',
        'behavior_suicide',
        'behavior_mood',
        'behavior_emotional',
        'behavior_withdrawal',
        'behavior_excessive_activity',
        'behavior_interaction',
        'behavior_disruptive',
        'behavior_appearance',
        'behavior_academic_decline',
    ];
    $behaviorValues = [];
    foreach ($behaviorColumns as $col) {
        $behaviorValues[] = isset($_POST[$col]) ? 1 : 0;
    }

    /* Validate required fields */
    $errors = [];
    if ($campus === '') $errors[] = 'Campus is required.';
    if ($student === '') $errors[] = 'Student name is required.';
    if ($gradeSection === '') $errors[] = 'Grade & Section is required.';
    if ($dateReferred === null || $dateReferred === '') $errors[] = 'Date is required.';
    if ($description === '') $errors[] = 'Description is required.';
    if ($intervention === '') $errors[] = 'Intervention is required.';
    if ($followup !== 'Yes' && $followup !== 'No') $errors[] = 'Follow-up selection is required.';
    if ($followup === 'Yes' && $followupDate === null) $errors[] = 'Follow-up date is required when follow-up is needed.';
    if ($followupDate !== null && $dateReferred !== null && $followupDate < $dateReferred) $errors[] = 'Follow-up date cannot be earlier than the date referred.';
    if ($concernAcademic == 0 && $concernBehavior == 0 && $concernPersonal == 0) $errors[] = 'Select at least one concern.';
    if (!in_array(1, $behaviorValues)) $errors[] = 'Select at least one behavior.';

    if (!empty($errors)) {
        // Store errors in session and redirect back
        $_SESSION['referral_errors'] = $errors;
        header('Location: index.php?error=1');
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO guidance_referral_form (
        campus, student, grade_section, date_referred,
        concern_academic, concern_behavior, concern_personal,
        description, intervention, requires_followup,
        behavior_depressed, behavior_hopelessness, behavior_crying,
        behavior_suicide, behavior_mood, behavior_emotional,
        behavior_withdrawal, behavior_excessive_activity, behavior_interaction,
        behavior_disruptive, behavior_appearance, behavior_academic_decline,
        other_behavior, followup_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        /* 4 strings, 3 concern ints, 3 strings, 12 behavior ints, 2 strings (other, followup date) */
        $types = 'ssss' . 'iii' . 'sss' . str_repeat('i', 12) . 'ss';
        $params = [
            $campus, $student, $gradeSection, $dateReferred,
            $concernAcademic, $concernBehavior, $concernPersonal,
            $description, $intervention, $followup,
            $behaviorValues[0], $behaviorValues[1], $behaviorValues[2],
            $behaviorValues[3], $behaviorValues[4], $behaviorValues[5],
            $behaviorValues[6], $behaviorValues[7], $behaviorValues[8],
            $behaviorValues[9], $behaviorValues[10], $behaviorValues[11],
            $otherBehavior, $followupDate,
        ];

        $stmt->bind_param($types, ...$params);
        $ok = $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();
    } else {
        $ok = false;
        $newId = 0;
    }

    if ($ok) {
        guidance_send_submission_notification($conn, [
            'id'              => $newId,
            'student'         => $student,
            'grade_section'   => $gradeSection,
            'campus'          => $campus,
            'date_referred'   => $dateReferred,
            'requires_followup' => $followup,
            'followup_date'   => $followupDate,
            'description'     => $description,
        ]);
        header('Location: index.php?saved=1');
    } else {
        header('Location: index.php?saved=0');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSHS Referral Form</title>
    <?php include('../helperFiles/headData.php'); ?>
    <style>
        /* ─── Referral form styling (scoped so it never touches the shared header/footer) ─── */
        body { background: var(--color-bg); }

        .referral-app {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1080px;
            margin: 0 auto;
            padding: 28px 20px 56px;
            color: var(--color-text-primary);
            font-family: var(--font-family);
        }

        .referral-app * { box-sizing: border-box; }

        /* Hero */
        .referral-app .hero {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            padding: 30px 32px;
            margin-bottom: 24px;
            color: #fff;
            background: var(--header-gradient);
            box-shadow: 0 18px 40px rgba(11, 27, 98, .22);
        }

        .referral-app .hero::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -80px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, .18), transparent 70%);
        }

        .referral-app .hero .eyebrow {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            opacity: .8;
        }

        .referral-app .hero h1 {
            margin: 6px 0 6px;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .referral-app .hero p {
            margin: 0;
            max-width: 560px;
            font-size: 14px;
            line-height: 1.55;
            opacity: .85;
        }

        /* Cards */
        .referral-app .panel {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-card);
            padding: 26px 28px;
        }

        .referral-app .panel + .panel { margin-top: 22px; }

        .referral-app .panel-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--color-border);
        }

        .referral-app .step {
            flex: 0 0 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-primary-mid), var(--color-primary));
            color: #fff;
            font-weight: 800;
            font-size: 14px;
        }

        .referral-app .panel-head h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .referral-app .panel-head small {
            display: block;
            color: var(--color-text-secondary);
            font-size: 12.5px;
            margin-top: 2px;
        }

        /* Fields */
        .referral-app .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 20px;
            margin-bottom: 18px;
        }

        .referral-app .field { margin-bottom: 18px; }
        .referral-app .grid-2 .field { margin-bottom: 0; }

        .referral-app .field > label,
        .referral-app .group-label {
            display: block;
            margin-bottom: 6px;
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: .02em;
            color: var(--color-text-primary);
        }

        .referral-app .req { color: var(--color-danger); }

        .referral-app input[type="text"],
        .referral-app input[type="date"],
        .referral-app textarea {
            width: 100%;
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-input);
            background: #f8fafc;
            color: #1b2438;
            font: inherit;
            font-size: 14px;
            padding: 11px 14px;
            outline: none;
            transition: var(--transition-fast);
        }

        .referral-app textarea {
            resize: vertical;
            min-height: 112px;
            line-height: 1.55;
        }

        .referral-app input:focus,
        .referral-app textarea:focus {
            background: #fff;
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 4px rgba(79, 115, 217, .16);
        }

        .referral-app .hint {
            margin-top: 5px;
            font-size: 12px;
            font-weight: 400;
            color: var(--color-text-secondary);
        }

        /* Selectable chips (checkbox / radio) */
        .referral-app .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .referral-app .chip {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            padding: 10px 16px;
            border: 1.5px solid var(--color-border);
            border-radius: 999px;
            background: #f8fafc;
            font-size: 14px;
            font-weight: 600;
            color: #3a4560;
            cursor: pointer;
            user-select: none;
            transition: var(--transition-fast);
        }

        .referral-app .chip:hover { border-color: var(--color-secondary); }

        .referral-app .chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .referral-app .chip::before {
            content: "";
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1.5px solid #aab4c8;
            background: #fff;
            transition: var(--transition-fast);
        }

        .referral-app .chip:has(input:checked) {
            background: rgba(43, 85, 196, .09);
            border-color: var(--color-primary-mid);
            color: var(--color-primary);
        }

        .referral-app .chip:has(input:checked)::before {
            border-color: var(--color-primary-mid);
            background: var(--color-primary-mid);
            box-shadow: inset 0 0 0 3px #fff;
        }

        .referral-app .chip:has(input:focus-visible) {
            box-shadow: 0 0 0 4px rgba(79, 115, 217, .25);
        }

        /* Behaviors list */
        .referral-app .instruction {
            margin: 0 0 18px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f1f5fd;
            color: #3a4560;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .referral-app .behavior-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .referral-app .behavior {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin: 0;
            padding: 13px 14px;
            border: 1.5px solid var(--color-border);
            border-radius: 12px;
            background: #fff;
            font-size: 13.5px;
            font-weight: 400;
            line-height: 1.4;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .referral-app .behavior:hover { border-color: var(--color-secondary); }

        .referral-app .behavior input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            flex: 0 0 20px;
            width: 20px;
            height: 20px;
            margin: 1px 0 0;
            border: 1.5px solid #aab4c8;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            display: grid;
            place-content: center;
            transition: var(--transition-fast);
        }

        .referral-app .behavior input[type="checkbox"]::after {
            content: "";
            width: 10px;
            height: 6px;
            border-left: 2.5px solid #fff;
            border-bottom: 2.5px solid #fff;
            transform: rotate(-45deg) scale(0);
            margin-top: -2px;
            transition: transform .12s ease;
        }

        .referral-app .behavior input[type="checkbox"]:checked {
            background: var(--color-primary-mid);
            border-color: var(--color-primary-mid);
        }

        .referral-app .behavior input[type="checkbox"]:checked::after { transform: rotate(-45deg) scale(1); }

        .referral-app .behavior:has(input[type="checkbox"]:checked) {
            background: rgba(43, 85, 196, .06);
            border-color: var(--color-primary-mid);
        }

        .referral-app .behavior.wide { grid-column: 1 / -1; }

        .referral-app .behavior .other-input {
            display: block;
            margin-top: 8px;
            padding: 8px 12px;
        }

        .referral-app .behavior .text { flex: 1; min-width: 0; }

        /* Alerts + actions */
        .referral-app .error-summary {
            background: var(--color-surface);
            border: 1px solid #fecaca;
            border-left: 5px solid var(--color-danger);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            color: #b42318;
            font-size: 14px;
        }

        .referral-app .error-summary ul { margin: 0; padding-left: 18px; }

        .referral-app .form-actions {
            position: sticky;
            bottom: 14px;
            z-index: 5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
            padding: 14px 18px;
            border: 1px solid var(--color-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 30px rgba(11, 27, 98, .12);
        }

        .referral-app .form-actions .note {
            font-size: 12.5px;
            color: var(--color-text-secondary);
        }

        .referral-app .form-actions .btns { display: flex; gap: 10px; flex-wrap: wrap; }

        .referral-app .btn-primary,
        .referral-app .btn-secondary {
            border-radius: 999px;
            padding: 11px 24px;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .referral-app .btn-primary {
            border: 0;
            color: #fff;
            background: linear-gradient(135deg, var(--color-primary-mid), var(--color-primary));
            box-shadow: 0 6px 16px rgba(43, 85, 196, .32);
        }

        .referral-app .btn-primary:hover { transform: translateY(-1px); box-shadow: var(--shadow-hover); }

        .referral-app .btn-secondary {
            border: 1.5px solid var(--color-border);
            background: #fff;
            color: var(--color-text-primary);
        }

        .referral-app .btn-secondary:hover { border-color: var(--color-secondary); }

        .referral-app .footer-code {
            margin-top: 18px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            color: var(--color-text-secondary);
        }

        .referral-app [hidden] { display: none !important; }

        @media (max-width: 760px) {
            .referral-app { padding: 16px 12px 40px; }
            .referral-app .hero { padding: 22px 20px; border-radius: 16px; }
            .referral-app .hero h1 { font-size: 24px; }
            .referral-app .panel { padding: 20px 16px; }
            .referral-app .grid-2,
            .referral-app .behavior-list { grid-template-columns: 1fr; }
            .referral-app .form-actions { position: static; }
            .referral-app .btn-primary, .referral-app .btn-secondary { flex: 1; }
        }

        @media print {
            @page { size: A4 portrait; margin: 10mm; }

            .header,
            .site-footer,
            .referral-app .hero,
            .referral-app .form-actions,
            .referral-app .instruction { display: none !important; }

            body { background: #fff; }
            .referral-app { max-width: none; padding: 0; }
            .referral-app .panel { box-shadow: none; border-color: #222; break-inside: avoid; }
            .referral-app .panel + .panel { break-before: page; margin-top: 0; }
        }
    </style>
</head>

<body>
    <?php include('../helperFiles/header.php'); ?>

    <div class="referral-app">
        <section class="hero">
            <span class="eyebrow">Philippine Science High School System</span>
            <h1>Student Referral Form</h1>
            <p>Refer a student to the Guidance Office. Submitted referrals are recorded and appear on the admin calendar so follow-ups are never missed.</p>
        </section>

        <form id="referralForm" method="post" action="" onsubmit="return validateForm()">
            <?php if (!empty($_SESSION['referral_errors'])): ?>
                <div class="error-summary">
                    <ul>
                        <?php foreach ($_SESSION['referral_errors'] as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['referral_errors']); ?>
            <?php endif; ?>

            <!-- STEP 1 -->
            <section class="panel">
                <div class="panel-head">
                    <span class="step">1</span>
                    <div>
                        <h2>Referral information</h2>
                        <small>Who is being referred, and why.</small>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="campus">Campus <span class="req">*</span></label>
                        <input type="text" id="campus" name="campus" placeholder="e.g. Ilocos Region Campus" required>
                    </div>
                    <div class="field">
                        <label for="student">Name of Student <span class="req">*</span></label>
                        <input type="text" id="student" name="student" placeholder="Last name, First name" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="grade">Grade &amp; Section <span class="req">*</span></label>
                        <input type="text" id="grade" name="grade" placeholder="e.g. 10 - Sigma" required>
                    </div>
                    <div class="field">
                        <label for="date">Date Referred <span class="req">*</span></label>
                        <input type="date" id="date" name="date" required>
                    </div>
                </div>

                <div class="field">
                    <span class="group-label">Concern <span class="req">*</span> <span class="hint">(select all that apply)</span></span>
                    <div class="chips">
                        <label class="chip"><input type="checkbox" name="concern_academic"> Academic</label>
                        <label class="chip"><input type="checkbox" name="concern_behavior"> Behavior</label>
                        <label class="chip"><input type="checkbox" name="concern_personal"> Personal / Social</label>
                    </div>
                </div>

                <div class="field">
                    <label for="description">Brief Description <span class="req">*</span></label>
                    <textarea id="description" name="description" rows="4" placeholder="Describe what you have observed." required></textarea>
                </div>

                <div class="field">
                    <label for="intervention">Intervention/s Done <span class="req">*</span></label>
                    <textarea id="intervention" name="intervention" rows="4" placeholder="What has already been done to help the student?" required></textarea>
                </div>

                <div class="grid-2" style="margin-bottom:0">
                    <div class="field">
                        <span class="group-label">Requires Follow-up? <span class="req">*</span></span>
                        <div class="chips">
                            <label class="chip"><input type="radio" name="followup" value="Yes" required> Yes</label>
                            <label class="chip"><input type="radio" name="followup" value="No" required> No</label>
                        </div>
                    </div>
                    <div class="field" id="followupDateField" hidden>
                        <label for="followup_date">Follow-up Date <span class="req">*</span></label>
                        <input type="date" id="followup_date" name="followup_date">
                        <div class="hint">This date is added to the admin calendar.</div>
                    </div>
                </div>
            </section>

            <!-- STEP 2 -->
            <section class="panel">
                <div class="panel-head">
                    <span class="step">2</span>
                    <div>
                        <h2>Behaviors spotted</h2>
                        <small>Select at least one.</small>
                    </div>
                </div>

                <p class="instruction">
                    Tick the box/es pertaining to the specific behaviors you have observed from the student you are referring.
                </p>

                <div class="behavior-list">
                    <label class="behavior"><input type="checkbox" name="behavior_depressed"> <span class="text">Depressed or apathetic mood</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_hopelessness"> <span class="text">Expression of helplessness, hopelessness, worthlessness</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_crying"> <span class="text">Evidence of crying</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_suicide"> <span class="text">Verbal expressions or gestures of suicide</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_mood"> <span class="text">Noticeable changes in mood and/or sudden outburst</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_emotional"> <span class="text">Inappropriate or exaggerated emotional reactions to situations, including a lack of emotional response to stressful events</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_withdrawal"> <span class="text">Excessive dependency on others or extreme withdrawal and isolation from others</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_excessive_activity"> <span class="text">Excessive activity or talkativeness</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_interaction"> <span class="text">Unusual or noticeable changed interaction patterns with friends or classmates</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_disruptive"> <span class="text">New or continuous behaviour which disrupts the class</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_appearance"> <span class="text">Noticeable changes in physical appearance (weight, dress, hygiene)</span></label>
                    <label class="behavior"><input type="checkbox" name="behavior_academic_decline"> <span class="text">Extremely poor academic performance, or a drastic decline in grades</span></label>
                    <label class="behavior wide">
                        <input type="checkbox" name="other_behavior_flag">
                        <span class="text">Others, please specify
                            <input class="other-input" type="text" name="other" placeholder="Describe the behavior">
                        </span>
                    </label>
                </div>
            </section>

            <div class="form-actions">
                <span class="note">Fields marked <span class="req">*</span> are required.</span>
                <div class="btns">
                    <button class="btn-secondary" type="button" onclick="clearForm()">Clear Form</button>
                    <button class="btn-primary" type="submit" name="submit_referral" value="1">Submit Referral</button>
                </div>
            </div>

            <div class="footer-code">PSHS-00-F-GCU-03-Ver02-Rev0-02/01/20</div>
        </form>
    </div>

    <?php include('../helperFiles/footer.php'); ?>

    <script>
        var followupDateField = document.getElementById('followupDateField');
        var followupDateInput = document.getElementById('followup_date');

        function syncFollowup() {
            var yes = document.querySelector('input[name="followup"][value="Yes"]').checked;
            followupDateField.hidden = !yes;
            followupDateInput.required = yes;
            if (!yes) followupDateInput.value = '';
        }

        document.querySelectorAll('input[name="followup"]').forEach(function (r) {
            r.addEventListener('change', syncFollowup);
        });

        function validateForm() {
            var concerns = document.querySelectorAll('input[name="concern_academic"], input[name="concern_behavior"], input[name="concern_personal"]');
            var concernSelected = Array.from(concerns).some(function(cb) { return cb.checked; });
            if (!concernSelected) {
                alert('Please select at least one concern.');
                return false;
            }
            var behaviors = document.querySelectorAll('input[name^="behavior_"]');
            var behaviorSelected = Array.from(behaviors).some(function(cb) { return cb.checked; });
            if (!behaviorSelected) {
                alert('Please select at least one behavior.');
                return false;
            }
            return true;
        }

        function clearForm() {
            if (!confirm("Clear all entered information?")) return;
            document.getElementById("referralForm").reset();
            syncFollowup();
        }
    </script>

    <?php if ($saved === 1): ?>
        <script>
            showToast('Referral record saved successfully.', 'success');
            document.getElementById("referralForm").reset();
            syncFollowup();
        </script>
    <?php elseif ($saved === 0): ?>
        <script>
            showToast('Failed to save the referral record.', 'error');
        </script>
    <?php endif; ?>
</body>

</html>
