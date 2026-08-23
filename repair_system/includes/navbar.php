<?php
/**
 * Navbar Component (Mobile Responsive Topbar)
 */
$page_title = $page_title ?? 'แดชบอร์ด';
$user_name = $_SESSION['user_fullname'] ?? 'ผู้ใช้งาน';
$user_role = $_SESSION['user_role'] ?? 'user';
?>
<header class="top-navbar">
    <div class="d-flex align-items-center gap-2 gap-sm-3 min-w-0">
        <button type="button" class="navbar-toggle-btn d-lg-none" id="sidebarToggle" aria-label="เปิดเมนูหลัก">
            <i class="fas fa-bars"></i>
        </button>
        <h4 class="top-navbar-brand-title mb-0" title="<?= htmlspecialchars($page_title) ?>"><?= htmlspecialchars($page_title) ?></h4>
    </div>

    <div class="d-flex align-items-center gap-2 gap-sm-3 flex-shrink-0">
        <div class="d-none d-md-block text-end">
            <div class="fw-semibold text-dark small mb-0"><?= htmlspecialchars($user_name) ?></div>
            <div><?= role_badge($user_role) ?></div>
        </div>
        
        <div class="dropdown">
            <button class="btn p-0 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar-circle">
                    <?= mb_substr($user_name, 0, 1, 'UTF-8') ?>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                <li class="px-3 py-2 border-bottom bg-light bg-opacity-50">
                    <span class="d-block fw-bold text-dark"><?= htmlspecialchars($user_name) ?></span>
                    <small class="text-muted d-block"><?= htmlspecialchars($_SESSION['user_department'] ?? '') ?></small>
                    <div class="mt-1 d-md-none"><?= role_badge($user_role) ?></div>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="<?= base_url('modules/auth/profile.php') ?>">
                        <i class="fas fa-user-edit me-2 text-primary"></i> ข้อมูลส่วนตัว
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item py-2 text-danger" href="<?= base_url('modules/auth/logout.php') ?>">
                        <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
