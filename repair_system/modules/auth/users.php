<?php
/**
 * User Management Page (Admin Only)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin']);

$page_title = 'ระบบจัดการผู้ใช้งานและสิทธิ์';
$error = '';
$success = '';

// Handle Create / Update / Toggle Status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $role = $_POST['role'] ?? 'user';

        if (empty($username) || empty($password) || empty($fullname)) {
            $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'ชื่อผู้ใช้นี้มีในระบบแล้ว';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, email, phone, department, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$username, $hash, $fullname, $email, $phone, $department, $role]);
                set_flash('success', 'เพิ่มผู้ใช้งานสำเร็จ');
                redirect('modules/auth/users.php');
            }
        }
    } elseif ($action === 'edit_user') {
        $user_id = (int)$_POST['user_id'];
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
        $new_password = $_POST['new_password'] ?? '';

        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, department = ?, role = ?, status = ?, password = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $phone, $department, $role, $status, $hash, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, department = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $phone, $department, $role, $status, $user_id]);
        }
        set_flash('success', 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อย');
        redirect('modules/auth/users.php');
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">จัดการผู้ใช้งานและสิทธิ์ (User Management)</h4>
                <p class="text-muted mb-0">ควบคุมสิทธิ์ผู้แจ้งซ่อม, เจ้าหน้าที่พัสดุ, ช่างซ่อมบำรุง และผู้ดูแลระบบ</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-1"></i> เพิ่มผู้ใช้งานใหม่
            </button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card-modern">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>ชื่อผู้ใช้ / นามสกุล</th>
                            <th>บทบาท / สิทธิ์</th>
                            <th>ฝ่าย / แผนก</th>
                            <th>เบอร์โทร / อีเมล</th>
                            <th>สถานะ</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-muted">#<?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar-circle" style="width: 38px; height: 38px; font-size: 0.95rem;">
                                        <?= mb_substr($u['fullname'], 0, 1, 'UTF-8') ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($u['fullname']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= role_badge($u['role']) ?></td>
                            <td><?= htmlspecialchars($u['department'] ?? '-') ?></td>
                            <td>
                                <div><small><i class="fas fa-phone text-muted me-1"></i><?= htmlspecialchars($u['phone'] ?? '-') ?></small></div>
                                <div><small><i class="fas fa-envelope text-muted me-1"></i><?= htmlspecialchars($u['email'] ?? '-') ?></small></div>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">เปิดใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">ระงับการใช้งาน</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title fw-bold"><i class="fas fa-user-edit text-primary me-2"></i>แก้ไขผู้ใช้งาน: <?= htmlspecialchars($u['username']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="" method="POST">
                                        <input type="hidden" name="action" value="edit_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-medium">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="fullname" value="<?= htmlspecialchars($u['fullname']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-medium">บทบาท / สิทธิ์การใช้งาน <span class="text-danger">*</span></label>
                                                <select class="form-select" name="role" required>
                                                    <option value="user" <?= ($u['role'] === 'user') ? 'selected' : '' ?>>ผู้แจ้งซ่อม (User / Staff / ประชาชน)</option>
                                                    <option value="technician" <?= ($u['role'] === 'technician') ? 'selected' : '' ?>>ช่างซ่อมบำรุง (Technician)</option>
                                                    <option value="inventory" <?= ($u['role'] === 'inventory') ? 'selected' : '' ?>>เจ้าหน้าที่พัสดุ (Inventory)</option>
                                                    <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>ผู้ดูแลระบบสูงสุด (Admin)</option>
                                                </select>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-medium">ฝ่าย / แผนก</label>
                                                    <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($u['department'] ?? '') ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-medium">สถานะบัญชี</label>
                                                    <select class="form-select" name="status">
                                                        <option value="active" <?= ($u['status'] === 'active') ? 'selected' : '' ?>>เปิดใช้งาน (Active)</option>
                                                        <option value="inactive" <?= ($u['status'] === 'inactive') ? 'selected' : '' ?>>ระงับใช้งาน (Inactive)</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-medium">เบอร์โทรศัพท์</label>
                                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-medium">อีเมล</label>
                                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label fw-medium">รีเซ็ตรหัสผ่านใหม่ (เว้นว่างหากไม่เปลี่ยน)</label>
                                                <input type="password" class="form-control" name="new_password" placeholder="รหัสผ่านใหม่อย่างน้อย 6 ตัวอักษร">
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                            <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-primary me-2"></i>เพิ่มผู้ใช้งานใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create_user">
                <div class="modal-body p-4">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">รหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="fullname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">บทบาท / สิทธิ์การใช้งาน <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="user">ผู้แจ้งซ่อม (User / Staff / ประชาชน)</option>
                            <option value="technician">ช่างซ่อมบำรุง (Technician)</option>
                            <option value="inventory">เจ้าหน้าที่พัสดุ (Inventory)</option>
                            <option value="admin">ผู้ดูแลระบบสูงสุด (Admin)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">ฝ่าย / แผนก</label>
                        <input type="text" class="form-control" name="department" placeholder="เช่น ฝ่ายบริหารงานทั่วไป">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-medium">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">อีเมล</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> บันทึกผู้ใช้</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
