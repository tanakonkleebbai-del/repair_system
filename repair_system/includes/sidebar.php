<?php
/**
 * Sidebar Navigation Component
 */
$user_role = $_SESSION['user_role'] ?? 'user';
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= base_url('index.php') ?>" class="sidebar-logo">
            <i class="fas fa-tools"></i>
            <span>Repair System</span>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-category">เมนูหลัก</li>
        <li class="nav-item">
            <a href="<?= base_url('index.php') ?>" class="nav-link-custom <?= ($current_page == 'index.php' && $current_dir == 'repair_system') ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>แดชบอร์ด</span>
            </a>
        </li>

        <li class="menu-category">การแจ้งซ่อม</li>
        <li class="nav-item">
            <a href="<?= base_url('modules/repair/create.php') ?>" class="nav-link-custom <?= ($current_page == 'create.php' && $current_dir == 'repair') ? 'active' : '' ?>">
                <i class="fas fa-plus-circle"></i>
                <span>แจ้งซ่อมใหม่</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('modules/repair/index.php') ?>" class="nav-link-custom <?= ($current_page == 'index.php' && $current_dir == 'repair') ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span><?= ($user_role === 'user') ? 'รายการแจ้งซ่อมของฉัน' : 'จัดการรายการแจ้งซ่อม' ?></span>
            </a>
        </li>

        <?php if (in_array($user_role, ['admin', 'inventory', 'technician'])): ?>
        <li class="menu-category">ระบบครุภัณฑ์</li>
        <li class="nav-item">
            <a href="<?= base_url('modules/equipment/index.php') ?>" class="nav-link-custom <?= ($current_dir == 'equipment') ? 'active' : '' ?>">
                <i class="fas fa-boxes-stacked"></i>
                <span>ทะเบียนคุมครุภัณฑ์</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (in_array($user_role, ['admin', 'inventory'])): ?>
        <li class="menu-category">สถิติและรายงาน</li>
        <li class="nav-item">
            <a href="<?= base_url('modules/reports/index.php') ?>" class="nav-link-custom <?= ($current_dir == 'reports') ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>รายงานวิเคราะห์ & สถิติ</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
        <li class="menu-category">ผู้ดูแลระบบ</li>
        <li class="nav-item">
            <a href="<?= base_url('modules/auth/users.php') ?>" class="nav-link-custom <?= ($current_page == 'users.php') ? 'active' : '' ?>">
                <i class="fas fa-users-gear"></i>
                <span>จัดการผู้ใช้งาน & สิทธิ์</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="menu-category">บัญชีของฉัน</li>
        <li class="nav-item">
            <a href="<?= base_url('modules/auth/profile.php') ?>" class="nav-link-custom <?= ($current_page == 'profile.php') ? 'active' : '' ?>">
                <i class="fas fa-id-badge"></i>
                <span>โปรไฟล์ & รหัสผ่าน</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('modules/auth/logout.php') ?>" class="nav-link-custom text-danger">
                <i class="fas fa-sign-out-alt text-danger"></i>
                <span>ออกจากระบบ</span>
            </a>
        </li>
    </ul>
</aside>
