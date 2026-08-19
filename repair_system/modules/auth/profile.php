<?php
/**
 * User Profile & Password Update Page
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_auth();

$page_title = 'โปรไฟล์ของฉัน';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (empty($fullname)) {
            $error = 'กรุณาระบุชื่อ-นามสกุล';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, department = ? WHERE id = ?");
            if ($stmt->execute([$fullname, $email, $phone, $department, $user_id])) {
                $_SESSION['user_fullname'] = $fullname;
                $_SESSION['user_department'] = $department;
                set_flash('success', 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว');
                redirect('modules/auth/profile.php');
            } else {
                $error = 'ไม่สามารถบันทึกข้อมูลได้';
            }
        }
    } elseif ($action === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $curr_hash = $stmt->fetchColumn();

        if (!password_verify($old_password, $curr_hash)) {
            $error = 'รหัสผ่านเดิมไม่ถูกต้อง';
        } elseif (strlen($new_password) < 6) {
            $error = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        } elseif ($new_password !== $confirm_password) {
            $error = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$new_hash, $user_id])) {
                set_flash('success', 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อย');
                redirect('modules/auth/profile.php');
            } else {
                $error = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
            }
        }
    }
}

$user = get_current_user_data($pdo);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="row g-4">
            <!-- Profile Info Column -->
            <div class="col-lg-4">
                <div class="card-modern p-4 text-center">
                    <div class="user-avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= mb_substr($user['fullname'], 0, 1, 'UTF-8') ?>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($user['fullname']) ?></h5>
                    <p class="text-muted mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                    <div class="mb-3">
                        <?= role_badge($user['role']) ?>
                    </div>
                    <hr>
                    <div class="text-start fs-6">
                        <div class="mb-2"><i class="fas fa-building text-muted me-2"></i> <strong>แผนก:</strong> <?= htmlspecialchars($user['department'] ?? '-') ?></div>
                        <div class="mb-2"><i class="fas fa-phone text-muted me-2"></i> <strong>เบอร์โทร:</strong> <?= htmlspecialchars($user['phone'] ?? '-') ?></div>
                        <div class="mb-2"><i class="fas fa-envelope text-muted me-2"></i> <strong>อีเมล:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></div>
                        <div class="mb-0"><i class="fas fa-calendar-alt text-muted me-2"></i> <strong>สมาชิกเมื่อ:</strong> <?= format_thai_date($user['created_at'], false) ?></div>
                    </div>
                </div>
            </div>

            <!-- Edit Forms Column -->
            <div class="col-lg-8">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Update Info Card -->
                <div class="card-modern mb-4">
                    <div class="card-header-modern">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-user-edit text-primary me-2"></i>แก้ไขข้อมูลส่วนตัว</h5>
                    </div>
                    <div class="p-4">
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">ฝ่าย / แผนก / หน่วยงาน</label>
                                    <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">เบอร์โทรศัพท์</label>
                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">อีเมล</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-1"></i> บันทึกข้อมูลส่วนตัว
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Card -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-key text-warning me-2"></i>เปลี่ยนรหัสผ่าน</h5>
                    </div>
                    <div class="p-4">
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-medium">รหัสผ่านเดิม <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="old_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="new_password" placeholder="อย่างน้อย 6 ตัวอักษร" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="confirm_password" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required>
                                </div>
                            </div>
                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-warning px-4 text-dark fw-medium">
                                    <i class="fas fa-lock me-1"></i> เปลี่ยนรหัสผ่าน
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
