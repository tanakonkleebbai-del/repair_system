<?php
/**
 * Authentication & Role Permission Middleware
 */

require_once __DIR__ . '/../config/db.php';

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        set_flash('warning', 'กรุณาเข้าสู่ระบบก่อนเข้าใช้งาน');
        redirect('modules/auth/login.php');
    }
}

function check_role($allowed_roles = []) {
    check_auth();
    $current_role = $_SESSION['user_role'] ?? '';
    
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if (!in_array($current_role, $allowed_roles)) {
        set_flash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        redirect('index.php');
    }
}

function get_current_user_data($pdo) {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
