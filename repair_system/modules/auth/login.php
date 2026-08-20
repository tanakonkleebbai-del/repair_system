<?php
/**
 * User Login Page
 * Modern Flat Design 2.0 Split-Card UI
 */
require_once __DIR__ . '/../../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
            } else {
                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['username'];
                $_SESSION['user_fullname'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_department'] = $user['department'];

                set_flash('success', 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ' . $user['fullname']);
                redirect('index.php');
            }
        } else {
            $error = 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบแจ้งซ่อมออนไลน์ อบต.หนองแวง</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo.png') ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Prompt & Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Prompt:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-dark: #062b25;
            --brand-primary: #0d5446;
            --brand-light: #10725c;
            --brand-accent: #14b8a6;
            --brand-button: #0b5c4e;
            --brand-button-hover: #08493e;
            --text-dark: #0f2d27;
            --text-muted: #64748b;
            --bg-page: #f1f5f9;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-focus-border: #0d9488;
            --radius-lg: 24px;
            --radius-input: 12px;
            --shadow-card: 0 25px 60px -15px rgba(15, 45, 39, 0.2), 0 0 1px 1px rgba(0, 0, 0, 0.03);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Prompt', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            background-image: 
                radial-gradient(at 0% 0%, rgba(13, 84, 70, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 114, 92, 0.06) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            color: var(--text-dark);
        }

        .login-container {
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
        }

        .login-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            display: flex;
            flex-direction: row;
            min-height: 560px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
        }

        /* Left Side: Brand Panel */
        .brand-panel {
            flex: 1.1;
            background: linear-gradient(155deg, var(--brand-dark) 0%, var(--brand-primary) 50%, var(--brand-light) 100%);
            color: #ffffff;
            padding: 3.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
            pointer-events: none;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.15) 0%, rgba(20, 184, 166, 0) 70%);
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 360px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-wrapper {
            width: 124px;
            height: 124px;
            border-radius: 50%;
            background: #ffffff;
            padding: 6px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25), 0 0 0 3px rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .logo-wrapper:hover {
            transform: scale(1.04);
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .brand-title {
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 0.35rem;
            line-height: 1.3;
            color: #ffffff;
        }

        .brand-subtitle {
            font-size: 1.05rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
            font-family: 'Plus Jakarta Sans', 'Prompt', sans-serif;
            letter-spacing: 0.2px;
        }

        .org-info {
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 2.2rem;
            font-weight: 300;
        }

        .support-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 8px 20px;
            border-radius: 50px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* Right Side: Form Panel */
        .form-panel {
            flex: 1.2;
            background: #ffffff;
            padding: 3.5rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-heading {
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.4rem;
        }

        .login-subheading {
            font-size: 0.92rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .heading-accent {
            width: 44px;
            height: 3.5px;
            background: #0d8b74;
            border-radius: 3px;
            margin: 0 auto;
        }

        .form-group-custom {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .input-group-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 1rem;
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 4;
        }

        .input-custom {
            width: 100%;
            background-color: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: var(--radius-input);
            padding: 0.85rem 1rem 0.85rem 2.85rem;
            font-size: 0.95rem;
            color: var(--text-dark);
            font-family: inherit;
            transition: all 0.25s ease;
        }

        .input-custom::placeholder {
            color: #94a3b8;
            font-weight: 400;
            font-size: 0.92rem;
        }

        .input-custom:focus {
            outline: none;
            background-color: #ffffff;
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
        }

        .input-custom:focus ~ .input-icon-left {
            color: var(--input-focus-border);
        }

        .toggle-password-btn {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            border-radius: 6px;
            transition: color 0.2s;
            z-index: 4;
        }

        .toggle-password-btn:hover {
            color: var(--text-dark);
        }

        .btn-login {
            background: linear-gradient(135deg, #0a4d41 0%, #0d705c 100%);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-input);
            padding: 0.9rem 1.5rem;
            font-size: 1.05rem;
            font-weight: 600;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(10, 77, 65, 0.25);
            transition: all 0.25s ease;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #073f35 0%, #0a5e4d 100%);
            box-shadow: 0 10px 22px rgba(10, 77, 65, 0.35);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(10, 77, 65, 0.25);
        }

        .login-footer-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .login-footer-links a {
            color: #0d8b74;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-footer-links a:hover {
            color: #085a4b;
            text-decoration: underline;
        }

        /* Demo Quick Accounts Accordion/Drawer */
        .demo-section {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px dashed #e2e8f0;
        }

        .demo-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .demo-badges-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }

        .demo-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .demo-chip:hover {
            background: #e6fffa;
            border-color: #99f6e4;
            color: #0d705c;
            transform: translateY(-1px);
        }

        .demo-chip.admin:hover { background: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .demo-chip.tech:hover { background: #e0f2fe; border-color: #7dd3fc; color: #0369a1; }
        .demo-chip.inv:hover { background: #fef3c7; border-color: #fde68a; color: #b45309; }

        .copyright-text {
            text-align: center;
            font-size: 0.76rem;
            color: #94a3b8;
            margin-top: 1.75rem;
        }

        /* Alert Styling */
        .custom-alert {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInDown 0.3s ease;
        }

        .custom-alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        .custom-alert-success {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #15803d;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Layout */
        @media (max-width: 860px) {
            .login-card {
                flex-direction: column;
                min-height: auto;
            }
            .brand-panel {
                padding: 2.5rem 1.5rem;
            }
            .logo-wrapper {
                width: 100px;
                height: 100px;
                margin-bottom: 1.25rem;
            }
            .brand-title {
                font-size: 1.4rem;
            }
            .form-panel {
                padding: 2.25rem 1.75rem;
            }
            .login-heading {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <!-- Left Side: Organization & Branding Panel -->
        <div class="brand-panel">
            <div class="brand-content">
                <div class="logo-wrapper">
                    <img src="<?= base_url('assets/images/logo.png') ?>" alt="ตราสัญลักษณ์ อบต.หนองแวง" class="logo-img">
                </div>

                <h1 class="brand-title">ระบบแจ้งซ่อมออนไลน์</h1>
                <div class="brand-subtitle">Maintenance Request</div>

                <div class="org-info">
                    องค์การบริหารส่วนตำบลหนองแวง<br>
                    อำเภอบ้านผือ จังหวัดอุดรธานี
                </div>

                <div class="support-badge">
                    <i class="fas fa-info-circle"></i>
                    <span>Internal Support System</span>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form Panel -->
        <div class="form-panel">
            <div>
                <div class="form-header">
                    <h2 class="login-heading">เข้าสู่ระบบ</h2>
                    <p class="login-subheading">กรุณากรอกข้อมูลเพื่อเข้าใช้งาน</p>
                    <div class="heading-accent"></div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="custom-alert custom-alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle fs-5 flex-shrink-0"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($flash && $flash['type'] === 'success'): ?>
                    <div class="custom-alert custom-alert-success" role="alert">
                        <i class="fas fa-check-circle fs-5 flex-shrink-0"></i>
                        <div><?= htmlspecialchars($flash['message']) ?></div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="loginForm" autocomplete="on">
                    <div class="form-group-custom">
                        <div class="input-group-wrapper">
                            <input 
                                type="text" 
                                class="input-custom" 
                                id="username" 
                                name="username" 
                                placeholder="ชื่อผู้ใช้ (Username)" 
                                required 
                                autofocus
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            >
                            <i class="fas fa-user input-icon-left"></i>
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <div class="input-group-wrapper">
                            <input 
                                type="password" 
                                class="input-custom" 
                                id="password" 
                                name="password" 
                                placeholder="รหัสผ่าน (Password)" 
                                required
                            >
                            <i class="fas fa-lock input-icon-left"></i>
                            <button type="button" class="toggle-password-btn" id="togglePasswordBtn" title="แสดง/ซ่อนรหัสผ่าน" aria-label="แสดง/ซ่อนรหัสผ่าน">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>เข้าสู่ระบบ</span>
                        <i class="fas fa-arrow-right-to-bracket ms-1"></i>
                    </button>

                    <div class="login-footer-links">
                        <span class="text-muted">ยังไม่มีบัญชีผู้ใช้?</span> 
                        <a href="<?= base_url('modules/auth/register.php') ?>" class="ms-1">ลงทะเบียนที่นี่</a>
                    </div>
                </form>

                <!-- Demo Quick Login Helper -->
                <!-- <div class="demo-section">
                    <div class="demo-title">
                        <span><i class="fas fa-key me-1 text-teal"></i> บัญชีทดสอบระบบ</span>
                        <span class="text-muted" style="font-size:0.72rem; font-weight:400;">คลิกเพื่อกรอกอัตโนมัติ</span>
                    </div>
                    <div class="demo-badges-wrapper">
                        <button type="button" class="demo-chip admin" onclick="setLogin('admin', 'admin123')">
                            <i class="fas fa-crown text-danger"></i> Admin
                        </button>
                        <button type="button" class="demo-chip tech" onclick="setLogin('technician', 'tech123')">
                            <i class="fas fa-wrench text-primary"></i> Tech
                        </button>
                        <button type="button" class="demo-chip inv" onclick="setLogin('inventory', 'staff123')">
                            <i class="fas fa-box text-warning"></i> Inventory
                        </button>
                        <button type="button" class="demo-chip" onclick="setLogin('user', 'user123')">
                            <i class="fas fa-user text-success"></i> User
                        </button>
                    </div>
                </div>
            </div> -->

            <!-- Footer Copyright -->
            <div class="copyright-text">
                &copy; <?= date('Y') + 543 ?> ระบบแจ้งซ่อมออนไลน์ &mdash; สงวนลิขสิทธิ์
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
const togglePasswordBtn = document.getElementById('togglePasswordBtn');
const passwordInput = document.getElementById('password');
const togglePasswordIcon = document.getElementById('togglePasswordIcon');

if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener('click', function() {
        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        togglePasswordIcon.classList.toggle('fa-eye', !isPassword);
        togglePasswordIcon.classList.toggle('fa-eye-slash', isPassword);
    });
}

// Quick fill credentials for demo
function setLogin(username, password) {
    const userField = document.getElementById('username');
    const passField = document.getElementById('password');
    if (userField && passField) {
        userField.value = username;
        passField.value = password;
        
        // Highlight animation
        userField.style.borderColor = '#0d9488';
        passField.style.borderColor = '#0d9488';
        setTimeout(() => {
            userField.style.borderColor = '';
            passField.style.borderColor = '';
        }, 800);
    }
}
</script>

</body>
</html>
