
<?php
/*
 * MediLink - Login Page
 * POST: Processes login requests.
 * GET: Displays the sign-in page.
 */

require_once '../../includes/auth.php';

$ROOT = '../../';

/* =========================================================
   1. LOGIN API - PRESERVED AUTHENTICATION FUNCTIONALITY
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $in = body();

    $email = trim($in['email'] ?? '');
    $role = $in['role'] ?? '';
    $password = $in['password'] ?? '';

    if (
        !$email ||
        !$password ||
        !isset(HOME[$role])
    ) {
        json_out([
            'status' => 'error',
            'message' => 'Email, password and role are required'
        ], 400);
    }

    $stmt = db()->prepare(
        'SELECT user_id, email, role, password_hash
         FROM users
         WHERE email = ? AND role = ? AND is_active = 1'
    );

    $stmt->execute([$email, $role]);

    $user = $stmt->fetch();

    if (
        !$user ||
        !password_verify($password, $user['password_hash'])
    ) {
        json_out([
            'status' => 'error',
            'message' => 'Invalid email, password or role'
        ], 401);
    }

    // Find the matching user profile.
    $profile = [
        'name' => ucfirst($role)
    ];

    $tables = [
        'patient' => 'patients',
        'doctor' => 'doctors',
        'staff' => 'staff'
    ];

    $table = $tables[$role] ?? null;

    if ($table) {

        $stmt = db()->prepare(
            "SELECT * FROM `$table` WHERE user_id = ?"
        );

        $stmt->execute([$user['user_id']]);

        if ($row = $stmt->fetch()) {

            $firstName = $row['first_name'] ?? '';
            $lastName = $row['last_name'] ?? '';

            $name = trim($firstName . ' ' . $lastName);

            if ($name !== '') {
                $profile['name'] = $name;
            }

            $profile['patient_id'] = $row['patient_id'] ?? null;
            $profile['doctor_id'] = $row['doctor_id'] ?? null;
        }
    }

    // Create a new secure session.
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'role' => $role
    ] + $profile;

    json_out([
        'status' => 'success',
        'redirect' => 'dashboards/' . HOME[$role]
    ]);

    exit;
}

/* =========================================================
   2. PAGE INITIALIZATION
========================================================= */

header('Content-Type: text/html; charset=utf-8');

// Redirect users who are already signed in.
$current = $_SESSION['user']['role'] ?? null;

if ($current && isset(HOME[$current])) {

    header(
        'Location: ' . $ROOT . 'dashboards/' . HOME[$current]
    );

    exit;
}

// Available role labels.
$labels = [
    'patient' => 'Patient',
    'doctor' => 'Doctor',
    'staff' => 'Staff',
    'admin' => 'Administrator',
    'financial' => 'Finance',
    'datascientist' => 'Data Scientist',
    'insurance' => 'Insurance'
];

// Get roles directly from the authentication configuration.
$roles = array_keys(HOME);

// Keep patient first without changing the other roles' order.
$patientIndex = array_search('patient', $roles, true);

if ($patientIndex !== false) {
    array_splice($roles, $patientIndex, 1);
    array_unshift($roles, 'patient');
}

$e = fn($value) => htmlspecialchars(
    (string)$value,
    ENT_QUOTES,
    'UTF-8'
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sign In | MediLink</title>

    <meta name="theme-color" content="#0b2340">
    <meta name="description" content="Secure sign-in to MediLink healthcare management platform.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        /* =====================================================
           3. DESIGN SYSTEM
        ===================================================== */

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --teal: #14b8a6;

            --navy: #0b2340;
            --navy-light: #143759;

            --background: #f3f7fc;
            --white: #ffffff;

            --text: #172b45;
            --muted: #718198;

            --border: #dce6f0;
            --danger: #dc4545;

            --radius: 16px;

            --font: 'Inter', system-ui, sans-serif;

            --shadow:
                0 20px 60px rgba(15, 35, 60, 0.09);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            font-family: var(--font);
            color: var(--text);
            background: var(--background);
            -webkit-font-smoothing: antialiased;
        }

        button,
        input,
        select {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =====================================================
           4. MAIN LAYOUT
        ===================================================== */

        .shell {
            min-height: 100vh;

            display: grid;

            grid-template-columns:
                minmax(0, 1.08fr)
                minmax(0, 0.92fr);
        }

        /* =====================================================
           5. LEFT HERO SECTION
        ===================================================== */

        .visual-panel {
            position: relative;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            min-height: 100vh;

            padding:
                42px clamp(28px, 5vw, 76px);

            overflow: hidden;

            color: white;

            background:
                radial-gradient(
                    circle at 15% 15%,
                    rgba(37, 99, 235, 0.30),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 85% 85%,
                    rgba(20, 184, 166, 0.20),
                    transparent 35%
                ),
                linear-gradient(
                    145deg,
                    #071a31,
                    #0b2949 55%,
                    #103b59
                );
        }

        .visual-panel::before {
            content: "";

            position: absolute;

            inset: 0;

            background-image:
                linear-gradient(
                    rgba(255,255,255,0.035) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(255,255,255,0.035) 1px,
                    transparent 1px
                );

            background-size: 42px 42px;

            pointer-events: none;
        }

        .visual-panel::after {
            content: "";

            position: absolute;

            width: 420px;
            height: 420px;

            right: -190px;
            top: 20%;

            border-radius: 50%;

            border: 1px solid rgba(255,255,255,0.08);

            box-shadow:
                0 0 0 45px rgba(255,255,255,0.02),
                0 0 0 90px rgba(255,255,255,0.015);

            pointer-events: none;
        }

        .visual-inner {
            position: relative;
            z-index: 1;

            display: flex;
            flex-direction: column;

            height: 100%;
        }

        /* Brand */

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;

            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-symbol {
            width: 44px;
            height: 44px;

            display: grid;
            place-items: center;

            border-radius: 13px;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #14b8a6,
                    #0d9488
                );

            box-shadow:
                0 8px 25px rgba(20,184,166,0.25);
        }

        .brand-symbol svg {
            width: 25px;
            height: 25px;
        }

        .brand span:last-child {
            color: white;
        }

        .brand small {
            display: block;

            color: #91a9c1;

            font-size: 0.65rem;
            font-weight: 500;

            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* Hero content */

        .hero-content {
            max-width: 610px;

            margin: auto 0;

            padding: 50px 0;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;

            padding: 8px 13px;

            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 30px;

            color: #a9c7e0;

            background: rgba(255,255,255,0.055);

            font-size: 0.76rem;
            font-weight: 600;

            letter-spacing: 1px;
            text-transform: uppercase;

            margin-bottom: 27px;
        }

        .eyebrow-dot {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: var(--teal);

            box-shadow:
                0 0 12px rgba(20,184,166,0.9);
        }

        .hero-title {
            max-width: 610px;

            color: #ffffff;

            font-size: clamp(2.4rem, 4.3vw, 4.1rem);

            font-weight: 800;

            line-height: 1.12;

            letter-spacing: -2.3px;
        }

        .hero-title span {
            color: #40d5c2;
        }

        .hero-description {
            max-width: 470px;

            margin-top: 23px;

            color: #a5bad0;

            font-size: 1.02rem;

            line-height: 1.8;
        }

        /* =====================================================
           6. ECG ANIMATION CARD
        ===================================================== */

        .wave-card {
            position: relative;

            margin-top: 38px;

            padding: 19px 20px 12px;

            border: 1px solid rgba(255,255,255,0.11);

            border-radius: 17px;

            background:
                linear-gradient(
                    120deg,
                    rgba(255,255,255,0.09),
                    rgba(255,255,255,0.025)
                );

            backdrop-filter: blur(12px);

            box-shadow:
                0 18px 45px rgba(0,0,0,0.15);

            animation: floatCard 4.5s ease-in-out infinite;
        }

        @keyframes floatCard {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        .wave-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            color: #d6e5f3;

            font-size: 0.77rem;
            font-weight: 600;
        }

        .wave-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wave-label span {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: var(--teal);
        }

        .wave-status {
            color: #6fe0d1;
        }

        #ecg {
            display: block;

            width: 100%;
            height: 105px;

            margin-top: 10px;
        }

        .feature-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);

            gap: 12px;

            margin-top: 25px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 9px;

            color: #b8cbe0;

            font-size: 0.76rem;
            font-weight: 500;
        }

        .feature svg {
            width: 17px;
            height: 17px;

            flex-shrink: 0;

            color: #42d4c1;
        }

        .visual-footer {
            position: relative;
            z-index: 1;

            color: #809bb6;

            font-size: 0.75rem;
        }

        /* =====================================================
           7. RIGHT LOGIN PANEL
        ===================================================== */

        .auth-panel {
            min-height: 100vh;

            display: grid;
            place-items: center;

            padding: 45px 30px;

            background: var(--background);
        }

        .auth-container {
            width: 100%;
            max-width: 440px;

            animation: appear 0.65s ease both;
        }

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-brand {
            display: none;
        }

        .welcome {
            margin-bottom: 31px;
        }

        .welcome-tag {
            margin-bottom: 12px;

            color: var(--primary);

            font-size: 0.76rem;
            font-weight: 700;

            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .welcome h2 {
            color: #142b45;

            font-size: clamp(2rem, 3vw, 2.5rem);

            font-weight: 800;

            letter-spacing: -1.4px;
        }

        .welcome p {
            margin-top: 9px;

            color: var(--muted);

            font-size: 0.92rem;

            line-height: 1.7;
        }

        /* =====================================================
           8. FORM FIELDS
        ===================================================== */

        .field {
            margin-bottom: 21px;
        }

        .field label {
            display: block;

            margin-bottom: 9px;

            color: #253e59;

            font-size: 0.85rem;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;

            left: 15px;
            top: 50%;

            transform: translateY(-50%);

            width: 19px;
            height: 19px;

            color: #92a3b8;

            pointer-events: none;
        }

        .input-wrap input,
        .input-wrap select {
            width: 100%;
            height: 55px;

            padding: 0 16px 0 46px;

            border: 1px solid var(--border);
            border-radius: 12px;

            color: var(--text);

            background: white;

            font-size: 0.9rem;

            outline: none;

            transition:
                border-color 0.2s,
                box-shadow 0.2s,
                background 0.2s;
        }

        .input-wrap input::placeholder {
            color: #a6b3c2;
        }

        .input-wrap select {
            appearance: none;

            padding-right: 45px;

            cursor: pointer;

            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23718498' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");

            background-repeat: no-repeat;

            background-position: right 17px center;
        }

        .input-wrap input:focus,
        .input-wrap select:focus {
            border-color: var(--primary);

            background: #ffffff;

            box-shadow:
                0 0 0 4px rgba(37,99,235,0.10);
        }

        .input-wrap [aria-invalid="true"] {
            border-color: var(--danger) !important;

            box-shadow:
                0 0 0 4px rgba(220,69,69,0.09);
        }

        /* Password button */

        .password-input {
            padding-right: 82px !important;
        }

        .toggle {
            position: absolute;

            right: 8px;
            top: 8px;

            height: 39px;

            padding: 0 12px;

            border: 0;
            border-radius: 8px;

            color: var(--primary);

            background: transparent;

            font-size: 0.8rem;
            font-weight: 700;
        }

        .toggle:hover {
            background: #eff5ff;
        }

        .toggle:focus-visible {
            outline: 2px solid var(--primary);
        }

        /* =====================================================
           9. ERROR MESSAGE
        ===================================================== */

        .msg {
            display: none;

            margin-bottom: 18px;

            padding: 13px 15px;

            border: 1px solid #f2caca;
            border-radius: 11px;

            color: #b52b2b;

            background: #fff0f0;

            font-size: 0.86rem;
            line-height: 1.5;
        }

        .msg.show {
            display: block;

            animation: messageIn 0.2s ease;
        }

        @keyframes messageIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =====================================================
           10. SIGN IN BUTTON
        ===================================================== */

        .submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            width: 100%;
            height: 56px;

            border: none;
            border-radius: 12px;

            color: white;

            background:
                linear-gradient(
                    110deg,
                    #2563eb,
                    #1d4ed8
                );

            box-shadow:
                0 9px 22px rgba(37,99,235,0.22);

            font-size: 0.95rem;
            font-weight: 700;

            transition:
                transform 0.2s,
                box-shadow 0.2s,
                background 0.2s;
        }

        .submit:hover {
            transform: translateY(-2px);

            box-shadow:
                0 13px 27px rgba(37,99,235,0.30);
        }

        .submit:active {
            transform: translateY(0);
        }

        .submit:focus-visible {
            outline: 3px solid #93b4ff;
            outline-offset: 3px;
        }

        .submit:disabled {
            opacity: 0.7;
            cursor: progress;
            transform: none;
        }

        .submit svg {
            width: 19px;
            height: 19px;
        }

        /* =====================================================
           11. REGISTER LINK AND FOOTER
        ===================================================== */

        .alt {
            margin-top: 25px;

            text-align: center;

            color: var(--muted);

            font-size: 0.88rem;
        }

        .alt a {
            color: var(--primary);

            font-weight: 700;

            transition: color 0.2s;
        }

        .alt a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            margin-top: 35px;

            color: #8a9bb0;

            font-size: 0.76rem;
        }

        .security-note svg {
            width: 15px;
            height: 15px;

            color: #14a58f;
        }

        /* =====================================================
           12. RESPONSIVE DESIGN
        ===================================================== */

        @media (max-width: 1000px) {

            .shell {
                grid-template-columns:
                    minmax(0, 1fr)
                    minmax(0, 1fr);
            }

            .visual-panel {
                padding: 35px 30px;
            }

            .hero-title {
                font-size: 2.8rem;
            }

            .feature-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }

        }

        @media (max-width: 800px) {

            .shell {
                grid-template-columns: 1fr;
            }

            .visual-panel {
                min-height: auto;

                padding: 25px 25px 30px;
            }

            .hero-content {
                padding: 42px 0 10px;
            }

            .hero-title {
                max-width: 650px;

                font-size: 2.5rem;
            }

            .hero-description {
                margin-top: 14px;
            }

            .wave-card {
                margin-top: 25px;
            }

            .feature-row {
                grid-template-columns: repeat(3, 1fr);
            }

            .visual-footer {
                display: none;
            }

            .auth-panel {
                min-height: auto;

                padding: 48px 24px;
            }

        }

        @media (max-width: 480px) {

            .visual-panel {
                padding: 22px 20px;
            }

            .brand {
                font-size: 1.08rem;
            }

            .hero-content {
                padding: 32px 0 5px;
            }

            .hero-title {
                font-size: 2.1rem;
                letter-spacing: -1px;
            }

            .hero-description {
                font-size: 0.88rem;
            }

            .wave-card {
                padding: 14px;
            }

            #ecg {
                height: 80px;
            }

            .feature-row {
                grid-template-columns: 1fr;
            }

            .auth-panel {
                padding: 38px 20px;
            }

            .welcome h2 {
                font-size: 2rem;
            }

        }

        /* =====================================================
           13. REDUCED MOTION ACCESSIBILITY
        ===================================================== */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

        }

    </style>

</head>

<body>

<div class="shell">

    <!-- ================================================
         LEFT HERO PANEL
    ================================================= -->

    <aside class="visual-panel">

        <div class="visual-inner">

            <!-- MediLink brand -->

            <a href="<?= $ROOT ?>index.html" class="brand">

                <span class="brand-symbol">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                    >
                        <path d="M12 5v14M5 12h14"/>
                    </svg>

                </span>

                <span>
                    MediLink
                    <small>Healthcare Management</small>
                </span>

            </a>


            <!-- Main content -->

            <div class="hero-content">

                <div class="eyebrow">

                    <span class="eyebrow-dot"></span>

                    Connected Healthcare Platform

                </div>


                <h1 class="hero-title">

                    Better care starts with

                    <span>better connection.</span>

                </h1>


                <p class="hero-description">

                    One secure platform connecting patients,
                    doctors and healthcare professionals.
                    Manage care, records and daily operations
                    through your dedicated workspace.

                </p>


                <!-- ECG animation -->

                <div class="wave-card">

                    <div class="wave-header">

                        <div class="wave-label">

                            <span></span>

                            Healthcare Technology

                        </div>

                        <div class="wave-status">
                            Connected
                        </div>

                    </div>

                    <canvas
                        id="ecg"
                        aria-hidden="true"
                    ></canvas>

                </div>


                <!-- Features -->

                <div class="feature-row">

                    <div class="feature">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>

                        Secure Access

                    </div>


                    <div class="feature">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M5 21a7 7 0 0 1 14 0"/>
                        </svg>

                        Role-Based Workspaces

                    </div>


                    <div class="feature">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M3 12h4l3-8 4 16 3-8h4"/>
                        </svg>

                        Connected Care

                    </div>

                </div>

            </div>


            <div class="visual-footer">

                © <?= date('Y') ?> MediLink. All rights reserved.

            </div>

        </div>

    </aside>


    <!-- ================================================
         RIGHT LOGIN PANEL
    ================================================= -->

    <main class="auth-panel">

        <div class="auth-container">

            <div class="welcome">

                <div class="welcome-tag">
                    Welcome Back
                </div>

                <h2>Sign in to MediLink</h2>

                <p>
                    Access your workspace by selecting your role
                    and entering your account details.
                </p>

            </div>


            <!-- Login form -->

            <form id="form" novalidate>

                <!-- ROLE -->

                <div class="field">

                    <label for="role">
                        Select your role
                    </label>

                    <div class="input-wrap">

                        <svg
                            class="input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M5 21a7 7 0 0 1 14 0"/>
                        </svg>


                        <select
                            id="role"
                            name="role"
                            required
                        >

                            <?php foreach ($roles as $r): ?>

                                <option value="<?= $e($r) ?>">
                                    <?= $e($labels[$r] ?? ucfirst($r)) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="field">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrap">

                        <svg
                            class="input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="m3 7 9 6 9-6"/>
                        </svg>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="username"
                            placeholder="name@hospital.com"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="field">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrap">

                        <svg
                            class="input-icon"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>

                        <input
                            id="password"
                            name="password"
                            class="password-input"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle"
                            id="toggle"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <!-- ERROR MESSAGE -->

                <div
                    class="msg"
                    id="msg"
                    role="alert"
                    aria-live="assertive"
                ></div>


                <!-- SUBMIT -->

                <button
                    class="submit"
                    id="submit"
                    type="submit"
                >

                    <span>Sign in to your account</span>

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M5 12h14"/>
                        <path d="m12 5 7 7-7 7"/>
                    </svg>

                </button>

            </form>


            <!-- REGISTER LINK -->

            <p class="alt">

                New to MediLink?

                <a href="register.php">
                    Create an account
                </a>

            </p>


            <!-- SECURITY -->

            <div class="security-note">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>

                Your account is protected by secure authentication.

            </div>

        </div>

    </main>

</div>


<!-- ================================================
     14. JAVASCRIPT
================================================= -->

<script>

    const ROOT = <?= json_encode(
        $ROOT,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;

    const $ = id => document.getElementById(id);

    const form = $('form');
    const msg = $('msg');

    const email = $('email');
    const pass = $('password');

    const btn = $('submit');


    // -------------------------------------------------
    // Display errors
    // -------------------------------------------------

    function fail(message, field = null) {

        msg.textContent = message;

        msg.classList.add('show');

        document.querySelectorAll('[aria-invalid="true"]')
            .forEach(element => {
                element.removeAttribute('aria-invalid');
            });

        if (field) {

            field.setAttribute('aria-invalid', 'true');

            field.focus();
        }

    }


    // -------------------------------------------------
    // Clear errors
    // -------------------------------------------------

    function clear() {

        msg.textContent = '';

        msg.classList.remove('show');

        document.querySelectorAll('[aria-invalid="true"]')
            .forEach(element => {
                element.removeAttribute('aria-invalid');
            });

    }


    [email, pass, $('role')].forEach(element => {

        element.addEventListener('input', clear);

        element.addEventListener('change', clear);

    });


    // -------------------------------------------------
    // Password visibility
    // -------------------------------------------------

    $('toggle').addEventListener('click', function () {

        const show = pass.type === 'password';

        pass.type = show ? 'text' : 'password';

        this.textContent = show ? 'Hide' : 'Show';

        this.setAttribute(
            'aria-pressed',
            String(show)
        );

        this.setAttribute(
            'aria-label',
            show ? 'Hide password' : 'Show password'
        );

    });


    // -------------------------------------------------
    // LOGIN SUBMISSION
    // Backend request structure is preserved.
    // -------------------------------------------------

    form.addEventListener('submit', async event => {

        event.preventDefault();

        clear();

        const payload = {

            email: email.value.trim(),

            password: pass.value,

            role: $('role').value

        };


        if (!payload.email) {

            fail('Please enter your email address.', email);

            return;

        }


        if (!email.validity.valid) {

            fail('Please enter a valid email address.', email);

            return;

        }


        if (!payload.password) {

            fail('Please enter your password.', pass);

            return;

        }


        btn.disabled = true;

        btn.querySelector('span').textContent = 'Signing in...';

        let redirecting = false;


        try {

            const response = await fetch(
                location.pathname + location.search,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },

                    credentials: 'same-origin',

                    body: JSON.stringify(payload)
                }
            );


            const data = await response.json().catch(() => ({}));


            if (
                response.ok &&
                data.status === 'success' &&
                data.redirect
            ) {

                btn.querySelector('span').textContent = 'Signed in successfully';

                redirecting = true;

                const redirect = String(data.redirect).replace(/^\/+/, '');

                window.location.assign(ROOT + redirect);

                return;

            }


            fail(
                data.message ||
                'Sign-in failed. Check your email, password and selected role.'
            );

        } catch (error) {

            fail(
                'Cannot reach the server. Please check your connection and try again.'
            );

            console.error('MediLink login error:', error);

        } finally {

            if (!redirecting) {

                btn.disabled = false;

                btn.querySelector('span').textContent =
                    'Sign in to your account';

            }

        }

    });


    // =================================================
    // ECG ANIMATION
    // Small animated visual, not an external video.
    // =================================================

    (function () {

        const canvas = $('ecg');

        if (!canvas) return;

        const context = canvas.getContext('2d');

        if (!context) return;

        const reducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;


        let width = 0;

        let height = 0;

        let offset = 40;

        let previousTime = 0;

        let animationId = null;


        // ECG waveform.
        function gaussian(value, mean, spread) {

            return Math.exp(
                -Math.pow((value - mean) / spread, 2)
            );

        }


        function heartbeat(position) {

            return (

                0.14 * gaussian(position, 0.16, 0.035)

                - 0.14 * gaussian(position, 0.30, 0.010)

                + gaussian(position, 0.33, 0.011)

                - 0.28 * gaussian(position, 0.365, 0.012)

                + 0.26 * gaussian(position, 0.60, 0.060)

            );

        }


        // Responsive canvas sizing.
        function resizeCanvas() {

            const ratio = Math.min(
                window.devicePixelRatio || 1,
                2
            );

            width = canvas.clientWidth;

            height = canvas.clientHeight;

            canvas.width = Math.round(width * ratio);

            canvas.height = Math.round(height * ratio);

            context.setTransform(
                ratio,
                0,
                0,
                ratio,
                0,
                0
            );

            draw();

        }


        // Draw waveform.
        function draw() {

            if (!width || !height) return;

            context.clearRect(
                0,
                0,
                width,
                height
            );


            const beatWidth = Math.max(
                220,
                width * 0.42
            );

            const middle = height * 0.62;

            const amplitude = height * 0.50;


            const gradient = context.createLinearGradient(
                0,
                0,
                width,
                0
            );

            gradient.addColorStop(
                0,
                'rgba(20,184,166,0)'
            );

            gradient.addColorStop(
                0.55,
                'rgba(20,184,166,0.95)'
            );

            gradient.addColorStop(
                1,
                '#70eee0'
            );


            context.strokeStyle = gradient;

            context.lineWidth = 2.5;

            context.lineJoin = 'round';

            context.beginPath();


            let y = middle;


            for (let x = 0; x <= width; x += 2) {

                const position =
                    (((x + offset) % beatWidth) + beatWidth) % beatWidth
                    / beatWidth;

                y = middle - heartbeat(position) * amplitude;

                if (x === 0) {

                    context.moveTo(x, y);

                } else {

                    context.lineTo(x, y);

                }

            }


            context.stroke();


            // Moving endpoint.
            context.fillStyle = '#b7fff4';

            context.beginPath();

            context.arc(
                width - 3,
                y,
                4,
                0,
                Math.PI * 2
            );

            context.fill();

        }


        // Animation loop.
        function animate(time) {

            if (document.hidden) {

                animationId = null;

                return;

            }

            const delta = previousTime
                ? time - previousTime
                : 16;

            previousTime = time;

            offset += delta * 0.09;

            draw();

            animationId = requestAnimationFrame(animate);

        }


        function startAnimation() {

            if (reducedMotion || animationId !== null) return;

            animationId = requestAnimationFrame(animate);

        }


        function stopAnimation() {

            if (animationId !== null) {

                cancelAnimationFrame(animationId);

                animationId = null;

            }

            previousTime = 0;

        }


        window.addEventListener(
            'resize',
            resizeCanvas
        );


        document.addEventListener(
            'visibilitychange',
            () => {

                if (document.hidden) {

                    stopAnimation();

                } else {

                    startAnimation();

                }

            }
        );


        resizeCanvas();

        if (!reducedMotion) {

            startAnimation();

        }

    })();

</script>

</body>
</html>
