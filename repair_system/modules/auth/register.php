<?php
/**
 * User Registration Page
 * Modern Flat Design 2.0 Split-Card UI (Ocean Blue Theme)
 */
require_once __DIR__ . '/../../config/db.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if (empty($username) || empty($password) || empty($fullname)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (ชื่อผู้ใช้, รหัสผ่าน, ชื่อ-นามสกุล)';
    } elseif ($password !== $password_confirm) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว กรุณาเลือกชื่อผู้ใช้อื่น';
        } else {
            // Hash Password and Insert
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, email, phone, department, role, status) VALUES (?, ?, ?, ?, ?, ?, 'user', 'active')");
            if ($stmt->execute([$username, $hashedPassword, $fullname, $email, $phone, $department])) {
                set_flash('success', 'ลงทะเบียนสำเร็จเรียบร้อย! กรุณาเข้าสู่ระบบด้วยบัญชีของคุณ');
                redirect('modules/auth/login.php');
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนผู้ใช้งาน - ระบบแจ้งซ่อมออนไลน์ อบต.หนองแวง</title>
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
            --brand-dark: #0a192f;
            --brand-primary: #1d4ed8;
            --brand-mid: #2563eb;
            --brand-light: #3b82f6;
            --brand-accent: #38bdf8;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-page: #f0f4f8;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-focus-border: #2563eb;
            --radius-lg: 24px;
            --radius-input: 12px;
            --shadow-card: 0 25px 60px -15px rgba(15, 23, 42, 0.18), 0 0 1px 1px rgba(0, 0, 0, 0.03);
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
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.07) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.25rem;
            color: var(--text-dark);
        }

        .register-container {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
        }

        .register-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            display: flex;
            flex-direction: row;
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
        }

        /* Left Side: Brand Panel (Blue Gradient) */
        .brand-panel {
            flex: 0.95;
            background: linear-gradient(155deg, var(--brand-dark) 0%, #1e3a8a 40%, var(--brand-mid) 80%, var(--brand-light) 100%);
            color: #ffffff;
            padding: 3.5rem 2.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
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
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
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
            background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, rgba(56, 189, 248, 0) 70%);
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 340px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-wrapper {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #ffffff;
            padding: 5px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25), 0 0 0 3px rgba(255, 255, 255, 0.25);
            margin-bottom: 1.5rem;
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
            font-size: 1.55rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            line-height: 1.3;
            color: #ffffff;
        }

        .brand-subtitle {
            font-size: 0.95rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 1.25rem;
            font-family: 'Plus Jakarta Sans', 'Prompt', sans-serif;
            letter-spacing: 0.3px;
        }

        .org-info {
            font-size: 0.84rem;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.6;
            margin-bottom: 1.75rem;
            font-weight: 300;
        }

        /* Features List */
        .features-list {
            text-align: left;
            width: 100%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            padding: 1.25rem 1.25rem;
            margin-bottom: 1.75rem;
            backdrop-filter: blur(8px);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 0.75rem;
        }

        .feature-item:last-child {
            margin-bottom: 0;
        }

        .feature-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(56, 189, 248, 0.25);
            color: #7dd3fc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            flex-shrink: 0;
        }

        .support-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 7px 18px;
            border-radius: 50px;
            backdrop-filter: blur(8px);
        }

        /* Right Side: Form Panel */
        .form-panel {
            flex: 1.35;
            background: #ffffff;
            padding: 3rem 2.8rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .form-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .register-heading {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.35rem;
        }

        .register-subheading {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }

        .heading-accent {
            width: 44px;
            height: 3.5px;
            background: var(--brand-mid);
            border-radius: 3px;
            margin: 0 auto;
        }

        .form-label-custom {
            font-size: 0.86rem;
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.4rem;
            display: block;
        }

        .input-group-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 0.95rem;
            color: #94a3b8;
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 4;
        }

        .input-custom {
            width: 100%;
            background-color: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: var(--radius-input);
            padding: 0.75rem 0.95rem 0.75rem 2.75rem;
            font-size: 0.92rem;
            color: var(--text-dark);
            font-family: inherit;
            transition: all 0.25s ease;
        }

        .input-custom::placeholder {
            color: #94a3b8;
            font-weight: 400;
            font-size: 0.88rem;
        }

        .input-custom:focus {
            outline: none;
            background-color: #ffffff;
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .input-custom:focus ~ .input-icon-left {
            color: var(--input-focus-border);
        }

        .toggle-password-btn {
            position: absolute;
            right: 0.8rem;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.92rem;
            border-radius: 6px;
            transition: color 0.2s;
            z-index: 4;
        }

        .toggle-password-btn:hover {
            color: var(--brand-mid);
        }

        .btn-register {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-input);
            padding: 0.85rem 1.5rem;
            font-size: 1.02rem;
            font-weight: 600;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
            transition: all 0.25s ease;
            margin-top: 1.25rem;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #172554 0%, #1d4ed8 50%, #2563eb 100%);
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.38);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .btn-register:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }

        .register-footer-links {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .register-footer-links a {
            color: var(--brand-mid);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .register-footer-links a:hover {
            color: #1e40af;
            text-decoration: underline;
        }

        .copyright-text {
            text-align: center;
            font-size: 0.76rem;
            color: #94a3b8;
            margin-top: 1.5rem;
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
        @media (max-width: 900px) {
            .register-card {
                flex-direction: column;
            }
            .brand-panel {
                padding: 2.5rem 1.5rem;
            }
            .features-list {
                display: none;
            }
            .logo-wrapper {
                width: 90px;
                height: 90px;
                margin-bottom: 1rem;
            }
            .brand-title {
                font-size: 1.35rem;
            }
            .form-panel {
                padding: 2.25rem 1.5rem;
            }
            .register-heading {
                font-size: 1.45rem;
            }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-card">
        <!-- Left Side: Organization & Branding Panel (Blue Ocean Theme) -->
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

                <!-- Highlight Features -->
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                        <span>แจ้งซ่อมสะดวกรวดเร็ว ทุกที่ทุกเวลา</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <span>ติดตามสถานะงานซ่อมแบบเรียลไทม์</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                        <span>ระบบปลอดภัย จัดเก็บข้อมูลเป็นระบบ</span>
                    </div>
                </div>

                <div class="support-badge">
                    <i class="fas fa-info-circle"></i>
                    <span>Internal Support System</span>
                </div>
            </div>
        </div>

        <!-- Right Side: Registration Form Panel -->
        <div class="form-panel">
            <div>
                <div class="form-header">
                    <h2 class="register-heading">ลงทะเบียนผู้ใช้งาน</h2>
                    <p class="register-subheading">กรอกข้อมูลเพื่อสร้างบัญชีสำหรับแจ้งซ่อมและติดตามงาน</p>
                    <div class="heading-accent"></div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="custom-alert custom-alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle fs-5 flex-shrink-0"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="registerForm" autocomplete="on">
                    <div class="row g-3">
                        <!-- Username -->
                        <div class="col-12">
                            <label class="form-label-custom">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                            <div class="input-group-wrapper">
                                <input 
                                    type="text" 
                                    class="input-custom" 
                                    name="username" 
                                    placeholder="เช่น somchai2026" 
                                    required 
                                    autofocus
                                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                >
                                <i class="fas fa-at input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Fullname -->
                        <div class="col-12">
                            <label class="form-label-custom">ชื่อ-นามสกุลจริง <span class="text-danger">*</span></label>
                            <div class="input-group-wrapper">
                                <input 
                                    type="text" 
                                    class="input-custom" 
                                    name="fullname" 
                                    placeholder="เช่น นายสมชาย ใจดี" 
                                    required 
                                    value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                                >
                                <i class="fas fa-user input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Department -->
                        <div class="col-12">
                            <label class="form-label-custom">ฝ่าย / ส่วนราชการ / แผนก</label>
                            <div class="input-group-wrapper">
                                <input 
                                    type="text" 
                                    class="input-custom" 
                                    name="department" 
                                    placeholder="เช่น สำนักปลัด / กองช่าง / กองคลัง" 
                                    value="<?= htmlspecialchars($_POST['department'] ?? '') ?>"
                                >
                                <i class="fas fa-building input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label-custom">เบอร์โทรศัพท์</label>
                            <div class="input-group-wrapper">
                                <input 
                                    type="tel" 
                                    class="input-custom" 
                                    name="phone" 
                                    placeholder="08X-XXX-XXXX" 
                                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                >
                                <i class="fas fa-phone input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label-custom">อีเมล</label>
                            <div class="input-group-wrapper">
                                <input 
                                    type="email" 
                                    class="input-custom" 
                                    name="email" 
                                    placeholder="example@mail.com" 
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                >
                                <i class="fas fa-envelope input-icon-left"></i>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label-custom">รหัสผ่าน <span class="text-danger">*</span></label>
                            <div class="input-group-wrapper">
                                <input 
                                    type="password" 
                                    class="input-custom" 
                                    id="regPassword" 
                                    name="password" 
                                    placeholder="อย่างน้อย 6 ตัวอักษร" 
                                    required
                                >
                                <i class="fas fa-lock input-icon-left"></i>
                                <button type="button" class="toggle-password-btn" onclick="togglePass('regPassword', 'toggleIcon1')" aria-label="แสดง/ซ่อนรหัสผ่าน">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password Confirm -->
                        <div class="col-md-6">
                            <label class="form-label-custom">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                            <div class="input-group-wrapper">
                                <input 
                                    type="password" 
                                    class="input-custom" 
                                    id="regPasswordConfirm" 
                                    name="password_confirm" 
                                    placeholder="กรอกรหัสผ่านอีกครั้ง" 
                                    required
                                >
                                <i class="fas fa-shield-check input-icon-left"></i>
                                <button type="button" class="toggle-password-btn" onclick="togglePass('regPasswordConfirm', 'toggleIcon2')" aria-label="แสดง/ซ่อนรหัสผ่าน">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus me-1"></i>
                        <span>ยืนยันการลงทะเบียน</span>
                    </button>

                    <div class="register-footer-links">
                        <span class="text-muted">มีบัญชีผู้ใช้งานอยู่แล้ว?</span> 
                        <a href="<?= base_url('modules/auth/login.php') ?>" class="ms-1">เข้าสู่ระบบ</a>
                    </div>
                </form>
            </div>

            <!-- Footer Copyright -->
            <div class="copyright-text">
                &copy; <?= date('Y') + 543 ?> ระบบแจ้งซ่อมออนไลน์ &mdash; สงวนลิขสิทธิ์
            </div>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input && icon) {
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        icon.classList.toggle('fa-eye', !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
    }
}
</script>

</body>
</html>
