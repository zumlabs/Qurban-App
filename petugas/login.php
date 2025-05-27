<?php
// Set session cookie security flags
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']),
        'samesite' => 'Strict'
    ]);
}
session_start();

// Brute force protection: limit login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 300) {
    $error = "Terlalu banyak percobaan login. Silakan coba lagi dalam beberapa menit.";
} else {

// Cek apakah user sudah login
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

include '../config/db.php';

if (isset($_POST['login'])) {
    // Sanitize username
    $username = trim($_POST['username']);
    $username = filter_var($username, FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    // Use prepared statement
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username=? AND role='admin' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['admin'] = $user['id'];
        $_SESSION['login_attempts'] = 0; // reset attempts
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['login_attempts'] += 1;
        $_SESSION['last_attempt_time'] = time();
        $error = "Username atau password salah!";
    }
    mysqli_stmt_close($stmt);
}
} // end brute force else
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - QurbanApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            --accent-color: #ffd700;
            --soft-white: #f8fafc;
            --card-shadow: 0 20px 40px rgba(13, 110, 253, 0.10), 0 2px 8px rgba(0,0,0,0.04);
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        body {
            height: 100vh;
            min-height: 0;
            background: var(--primary-gradient);
            font-family: 'Segoe UI', system-ui, sans-serif;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden; /* cegah scroll vertikal */
        }

        .center-wrapper {
            width: 100%;
            height: 100vh;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 600px) {
            .center-wrapper {
                height: 100vh;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding: 2rem;
            margin: 0 auto;
            box-sizing: border-box; /* Include padding in width calculation */
        }

        .login-box {
            background: var(--soft-white);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            padding: 2.7rem 2.5rem 2.2rem 2.5rem;
            transform: translateY(0);
            transition: none; /* Disable hover transition */
            border: 1.5px solid #e3e8f0;
        }

        .login-box:hover {
            transform: none; /* Remove hover effect */
            box-shadow: var(--card-shadow); /* Keep original shadow */
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 0.7rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 60%, #ffd700 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(13,110,253,0.13);
            margin-left: auto;
            margin-right: auto;
            animation: popIn 0.8s cubic-bezier(.4,2,.3,1);
        }
        @keyframes popIn {
            0% { transform: scale(0.7); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .login-title {
            font-weight: 800;
            color: #0d6efd;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
            letter-spacing: 1px;
        }

        .login-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .form-control {
            border-radius: 12px;
            padding: 1.1rem;
            transition: all 0.3s cubic-bezier(.4,2,.3,1);
            border: 2px solid #e0e0e0;
            background: #f6faff;
            font-size: 1.08rem;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.18);
            background: #fff;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
            font-size: 1.2rem;
        }

        .input-icon input {
            padding-left: 48px;
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            padding: 1.1rem;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.25s cubic-bezier(.4,2,.3,1);
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #fff;
            box-shadow: 0 2px 8px rgba(13,110,253,0.10);
            position: relative;
            overflow: hidden;
        }

        .btn-login:before {
            content: '';
            position: absolute;
            left: -60%;
            top: 0;
            width: 60%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.01) 100%);
            transform: skewX(-25deg);
            transition: left 0.4s cubic-bezier(.4,2,.3,1);
        }

        .btn-login:hover:before {
            left: 110%;
        }

        .btn-login:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 24px rgba(13, 110, 253, 0.18);
            background: linear-gradient(135deg, #0a58ca 0%, #0d6efd 100%);
        }

        .animated-bg {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, rgba(255,255,255,0.08) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.08) 75%, transparent 75%);
            background-size: 60px 60px;
            animation: animateBg 18s linear infinite;
            z-index: 1;
        }

        @keyframes animateBg {
            0% { transform: translate(0,0); }
            100% { transform: translate(-60px,-60px); }
        }

        .alert-danger {
            border-radius: 12px;
            padding: 1rem;
            border: 2px solid #dc3545;
            background: rgba(220, 53, 69, 0.08);
            font-size: 1.05rem;
        }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
            font-size: 0.98rem;
            opacity: 0.85;
            letter-spacing: 0.2px;
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    <div class="center-wrapper">
        <div class="login-container">
            <div class="login-box">
                <div class="login-header">
                    <div class="login-logo">
                        <i class="bi bi-shield-lock-fill" style="font-size:2.1rem;color:#fff;text-shadow:0 2px 8px #0a58ca77;"></i>
                    </div>
                    <h1 class="login-title">
                        QurbanApp
                    </h1>
                    <p class="text-muted mb-0" style="font-size:1.08rem;">Sistem Manajemen Qurban Digital</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-4 input-icon">
                        <i class="bi bi-person-fill"></i>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            class="form-control" 
                            placeholder="Username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-4 input-icon">
                        <i class="bi bi-key-fill"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control" 
                            placeholder="Password"
                            required
                        >
                    </div>

                    <button type="submit" name="login" class="btn btn-login btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Masuk ke Sistem
                    </button>
                </form>
                <div class="footer-note mt-4">
                    &copy; <?= date('Y') ?> QurbanApp. <span style="color:#0d6efd;font-weight:600;">Digital Qurban Management</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>