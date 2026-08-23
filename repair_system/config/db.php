<?php
/**
 * Database Configuration & Helper Functions
 * Repair & Equipment Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection Constants
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'repair_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// System Settings
define('SITE_NAME', 'ระบบแจ้งซ่อมและจัดการครุภัณฑ์');
define('SITE_VERSION', '1.0.0');

// Base URL calculation
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = preg_replace('@/modules/.*|/config/.*|/includes/.*|/uploads/.*|/[^/]+\.php$@i', '', $scriptName);
define('BASE_URL', rtrim($protocol . $host . $baseDir, '/') . '/');

$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Attempt PDO Connection to MySQL server
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If database does not exist, try to connect to server and create database automatically
    try {
        $serverDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        $sqlFile = __DIR__ . '/../database/repair_system.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $serverPdo->exec($sqlContent);
            
            // Reconnect to newly created database
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } else {
            die("Database Error: Could not connect to database '" . DB_NAME . "'. " . $e->getMessage());
        }
    } catch (PDOException $ex) {
        die("<div style='font-family:sans-serif;padding:30px;background:#fff0f0;color:#c00;border:1px solid #f99;margin:40px auto;max-width:700px;border-radius:10px;'>"
            . "<h2>❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h2>"
            . "<p><strong>ข้อผิดพลาด:</strong> " . htmlspecialchars($ex->getMessage()) . "</p>"
            . "<p>กรุณาตรวจสอบว่าคุณได้เปิด MySQL ในโปรแกรม XAMPP หรือ Laragon เรียบร้อยแล้ว</p>"
            . "</div>");
    }
}

// ----------------------------------------------------
// Helper Functions
// ----------------------------------------------------

function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function redirect($path) {
    header("Location: " . base_url($path));
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function format_thai_date($datetime, $showTime = true) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $timestamp = strtotime($datetime);
    $year = date('Y', $timestamp) + 543;
    $month = $thai_months[(int)date('n', $timestamp)];
    $day = date('j', $timestamp);
    
    if ($showTime) {
        $time = date('H:i', $timestamp) . ' น.';
        return "{$day} {$month} {$year} ({$time})";
    }
    return "{$day} {$month} {$year}";
}

function format_currency($amount) {
    return number_format((float)$amount, 2) . ' ฿';
}

function status_badge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>รอดำเนินการ</span>';
        case 'assigned':
            return '<span class="badge bg-info text-white"><i class="fas fa-user-check me-1"></i>มอบหมายช่างแล้ว</span>';
        case 'in_progress':
            return '<span class="badge bg-primary"><i class="fas fa-tools me-1"></i>กำลังซ่อม</span>';
        case 'waiting_parts':
            return '<span class="badge bg-secondary"><i class="fas fa-hourglass-half me-1"></i>รออะไหล่</span>';
        case 'completed':
            return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>ซ่อมเสร็จสิ้น</span>';
        case 'cancelled':
            return '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>ยกเลิก</span>';
        default:
            return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
    }
}

function equipment_status_badge($status) {
    switch ($status) {
        case 'available':
            return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>พร้อมใช้งาน</span>';
        case 'repairing':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-wrench me-1"></i>กำลังซ่อม</span>';
        case 'damaged':
            return '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>ชำรุด</span>';
        case 'disposed':
            return '<span class="badge bg-dark"><i class="fas fa-trash-alt me-1"></i>แทงจำหน่าย</span>';
        default:
            return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
    }
}

function urgent_badge($level) {
    switch ($level) {
        case 'low':
            return '<span class="badge bg-info text-white"><i class="fas fa-arrow-down me-1"></i>ปกติ</span>';
        case 'normal':
            return '<span class="badge bg-primary"><i class="fas fa-minus me-1"></i>ปานกลาง</span>';
        case 'high':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-arrow-up me-1"></i>ด่วน</span>';
        case 'urgent':
            return '<span class="badge bg-danger"><i class="fas fa-fire me-1"></i>ด่วนที่สุด</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($level) . '</span>';
    }
}

function role_badge($role) {
    switch ($role) {
        case 'admin':
            return '<span class="badge bg-danger"><i class="fas fa-user-shield me-1"></i>ผู้ดูแลระบบ (Admin)</span>';
        case 'technician':
            return '<span class="badge bg-primary"><i class="fas fa-wrench me-1"></i>ช่างซ่อม (Technician)</span>';
        case 'inventory':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-boxes me-1"></i>เจ้าหน้าที่พัสดุ (Inventory)</span>';
        case 'user':
            return '<span class="badge bg-success"><i class="fas fa-user me-1"></i>ผู้แจ้งซ่อม (User)</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($role) . '</span>';
    }
}

function generate_ticket_no($pdo) {
    $prefix = 'RP-' . date('Ym') . '-';
    $stmt = $pdo->prepare("SELECT ticket_no FROM repairs WHERE ticket_no LIKE :prefix ORDER BY id DESC LIMIT 1");
    $stmt->execute(['prefix' => $prefix . '%']);
    $last = $stmt->fetchColumn();
    if ($last) {
        $seq = (int)substr($last, -4) + 1;
    } else {
        $seq = 1;
    }
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

function upload_file($file, $targetDir, $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf']) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $filename = $file['name'];
    $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedExts)) {
        return false;
    }
    $newFilename = uniqid('file_', true) . '.' . $fileExt;
    $targetPath = rtrim($targetDir, '/') . '/' . $newFilename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $newFilename;
    }
    return false;
}
