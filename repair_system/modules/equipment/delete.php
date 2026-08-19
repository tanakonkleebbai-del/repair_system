<?php
/**
 * Delete Equipment Action
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin', 'inventory']);

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    // Check if equipment exists
    $stmt = $pdo->prepare("SELECT image FROM equipments WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetchColumn();

    $delStmt = $pdo->prepare("DELETE FROM equipments WHERE id = ?");
    if ($delStmt->execute([$id])) {
        if (!empty($image) && file_exists(__DIR__ . '/../../uploads/equipments/' . $image)) {
            @unlink(__DIR__ . '/../../uploads/equipments/' . $image);
        }
        set_flash('success', 'ลบข้อมูลครุภัณฑ์เรียบร้อยแล้ว');
    } else {
        set_flash('error', 'ไม่สามารถลบข้อมูลครุภัณฑ์ได้');
    }
}

redirect('modules/equipment/index.php');
