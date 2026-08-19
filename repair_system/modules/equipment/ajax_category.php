<?php
/**
 * AJAX Handler for Managing Equipment Categories (Admin & Inventory)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

$user_role = $_SESSION['user_role'] ?? '';

if (!in_array($user_role, ['admin', 'inventory'])) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ในการดำเนินการนี้']);
    exit;
}

$action = $_REQUEST['action'] ?? 'add';

// List categories
if ($action === 'list') {
    $stmt = $pdo->query("SELECT c.*, COUNT(e.id) as eq_count 
                         FROM categories c 
                         LEFT JOIN equipments e ON c.id = e.category_id 
                         GROUP BY c.id 
                         ORDER BY c.name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'categories' => $categories, 'is_admin' => ($user_role === 'admin')]);
    exit;
}

// Delete category (Admin Only)
if ($action === 'delete') {
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'สิทธิ์เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถลบหมวดหมู่ได้']);
        exit;
    }

    $cat_id = (int)($_POST['id'] ?? 0);
    if ($cat_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสหมวดหมู่ที่ต้องการลบ']);
        exit;
    }

    // Check if category exists
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$cat_id]);
    $cat = $stmt->fetch();

    if (!$cat) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลหมวดหมู่นี้ในระบบ']);
        exit;
    }

    // Delete category (equipments.category_id will be set to NULL automatically by DB foreign key)
    $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    if ($del->execute([$cat_id])) {
        echo json_encode([
            'success' => true,
            'id' => $cat_id,
            'name' => $cat['name'],
            'message' => 'ลบหมวดหมู่ "' . $cat['name'] . '" เรียบร้อยแล้ว'
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบหมวดหมู่ได้']);
        exit;
    }
}

// Add category (Admin & Inventory)
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อหมวดหมู่']);
        exit;
    }

    // Check duplicate category name
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    $existing = $stmt->fetch();
    if ($existing) {
        echo json_encode([
            'success' => true,
            'id' => (int)$existing['id'],
            'name' => $name,
            'message' => 'มีหมวดหมู่นี้อยู่แล้วในระบบและเลือกให้อัตโนมัติ',
            'already_exists' => true
        ]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    if ($stmt->execute([$name, $description])) {
        $cat_id = (int)$pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'id' => $cat_id,
            'name' => $name,
            'message' => 'เพิ่มหมวดหมู่ใหม่สำเร็จ'
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลหมวดหมู่ได้']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
