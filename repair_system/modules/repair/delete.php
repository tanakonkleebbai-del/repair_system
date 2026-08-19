<?php
/**
 * Delete Repair Ticket Action (Admin Only)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Check if repair exists and retrieve associated images and equipment
    $stmt = $pdo->prepare("SELECT damage_image, repair_image, equipment_id FROM repairs WHERE id = ?");
    $stmt->execute([$id]);
    $repair = $stmt->fetch();

    if ($repair) {
        $delStmt = $pdo->prepare("DELETE FROM repairs WHERE id = ?");
        if ($delStmt->execute([$id])) {
            $uploadDir = __DIR__ . '/../../uploads/repairs/';

            // Delete associated images if they exist
            if (!empty($repair['damage_image']) && file_exists($uploadDir . $repair['damage_image'])) {
                @unlink($uploadDir . $repair['damage_image']);
            }
            if (!empty($repair['repair_image']) && file_exists($uploadDir . $repair['repair_image'])) {
                @unlink($uploadDir . $repair['repair_image']);
            }

            // If linked to equipment, check if equipment still has active repairs
            if (!empty($repair['equipment_id'])) {
                $checkActive = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE equipment_id = ? AND status IN ('pending', 'assigned', 'in_progress', 'waiting_parts')");
                $checkActive->execute([$repair['equipment_id']]);
                if ($checkActive->fetchColumn() == 0) {
                    $pdo->prepare("UPDATE equipments SET status = 'available' WHERE id = ? AND status = 'repairing'")->execute([$repair['equipment_id']]);
                }
            }

            set_flash('success', 'ลบรายการแจ้งซ่อมเรียบร้อยแล้ว');
        } else {
            set_flash('error', 'ไม่สามารถลบรายการแจ้งซ่อมได้');
        }
    } else {
        set_flash('error', 'ไม่พบรายการแจ้งซ่อมที่ต้องการลบ');
    }
}

redirect('modules/repair/index.php');
