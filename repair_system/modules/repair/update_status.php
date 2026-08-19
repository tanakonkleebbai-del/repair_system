<?php
/**
 * Handle Repair Status & Work Log Update Action
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin', 'technician']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repair_id = (int)($_POST['repair_id'] ?? 0);
    $status = $_POST['status'] ?? 'in_progress';
    $total_cost = !empty($_POST['total_cost']) ? (float)$_POST['total_cost'] : 0.00;
    $spare_parts = trim($_POST['spare_parts'] ?? '');
    $repair_solution = trim($_POST['repair_solution'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Verify repair ticket exists
    $stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch();

    if ($repair) {
        // Upload repair image if provided
        $repair_image = $repair['repair_image'];
        if (isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = __DIR__ . '/../../uploads/repairs/';
            $uploaded = upload_file($_FILES['repair_image'], $targetDir);
            if ($uploaded) {
                $repair_image = $uploaded;
            }
        }

        // Check if completing
        $completed_at = $repair['completed_at'];
        if ($status === 'completed' && empty($completed_at)) {
            $completed_at = date('Y-m-d H:i:s');
        } elseif ($status !== 'completed') {
            $completed_at = null;
        }

        // Set technician if not set yet
        $technician_id = $repair['technician_id'] ?: $user_id;

        // Update repairs table
        $updateStmt = $pdo->prepare("UPDATE repairs SET status = ?, total_cost = ?, repair_solution = ?, repair_image = ?, completed_at = ?, technician_id = ? WHERE id = ?");
        $updateStmt->execute([$status, $total_cost, $repair_solution, $repair_image, $completed_at, $technician_id, $repair_id]);

        // Insert into repair_logs
        $logComment = "อัปเดตสถานะงานเป็น: " . $status . (!empty($repair_solution) ? " (" . mb_substr($repair_solution, 0, 80) . ")" : "");
        $logStmt = $pdo->prepare("INSERT INTO repair_logs (repair_id, user_id, action_status, comment, spare_parts, cost) VALUES (?, ?, ?, ?, ?, ?)");
        $logStmt->execute([$repair_id, $user_id, $status, $logComment, $spare_parts, $total_cost]);

        // Sync Equipment Status
        if ($repair['equipment_id']) {
            if ($status === 'completed') {
                $eqUp = $pdo->prepare("UPDATE equipments SET status = 'available' WHERE id = ?");
                $eqUp->execute([$repair['equipment_id']]);
            } elseif ($status === 'cancelled') {
                $eqUp = $pdo->prepare("UPDATE equipments SET status = 'damaged' WHERE id = ?");
                $eqUp->execute([$repair['equipment_id']]);
            } else {
                $eqUp = $pdo->prepare("UPDATE equipments SET status = 'repairing' WHERE id = ?");
                $eqUp->execute([$repair['equipment_id']]);
            }
        }

        set_flash('success', 'บันทึกการอัปเดตงานซ่อมเรียบร้อยแล้ว');
    } else {
        set_flash('error', 'ไม่พบรายการแจ้งซ่อม');
    }

    redirect('modules/repair/detail.php?id=' . $repair_id);
} else {
    redirect('modules/repair/index.php');
}
