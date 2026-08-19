<?php
/**
 * Create New Repair Ticket
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_auth();

$page_title = 'แจ้งซ่อมใหม่';
$error = '';
$pre_eq_id = (int)($_GET['equipment_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = !empty($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
    $location = trim($_POST['location'] ?? '');
    $urgent_level = $_POST['urgent_level'] ?? 'normal';
    $problem_title = trim($_POST['problem_title'] ?? '');
    $problem_description = trim($_POST['problem_description'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (empty($problem_title) || empty($problem_description)) {
        $error = 'กรุณาระบุหัวข้อปัญหาและรายละเอียดอาการเสีย';
    } else {
        // Generate Ticket Number
        $ticket_no = generate_ticket_no($pdo);

        // Upload Damage Image
        $damage_image = null;
        if (isset($_FILES['damage_image']) && $_FILES['damage_image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = __DIR__ . '/../../uploads/repairs/';
            $damage_image = upload_file($_FILES['damage_image'], $targetDir);
        }

        // Auto fetch location from equipment if not explicitly entered
        if ($equipment_id && empty($location)) {
            $eq_stmt = $pdo->prepare("SELECT location FROM equipments WHERE id = ?");
            $eq_stmt->execute([$equipment_id]);
            $location = $eq_stmt->fetchColumn() ?: '';
        }

        // Insert Repair Record
        $stmt = $pdo->prepare("INSERT INTO repairs (ticket_no, user_id, equipment_id, location, urgent_level, problem_title, problem_description, damage_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$ticket_no, $user_id, $equipment_id, $location, $urgent_level, $problem_title, $problem_description, $damage_image])) {
            $repair_id = $pdo->lastInsertId();

            // If an equipment is linked, update equipment status to 'repairing'
            if ($equipment_id) {
                $upEq = $pdo->prepare("UPDATE equipments SET status = 'repairing' WHERE id = ?");
                $upEq->execute([$equipment_id]);
            }

            // Log Initial Action
            $logStmt = $pdo->prepare("INSERT INTO repair_logs (repair_id, user_id, action_status, comment) VALUES (?, ?, 'pending', 'ผู้ใช้สร้างรายการแจ้งซ่อม')");
            $logStmt->execute([$repair_id, $user_id]);

            set_flash('success', 'ส่งรายการแจ้งซ่อมเรียบร้อยแล้ว รหัสใบแจ้ง: ' . $ticket_no);
            redirect('modules/repair/detail.php?id=' . $repair_id);
        } else {
            $error = 'เกิดข้อผิดพลาดในการส่งข้อมูลแจ้งซ่อม กรุณาลองใหม่อีกครั้ง';
        }
    }
}

// Fetch Equipments List for Dropdown
$equipments = $pdo->query("SELECT id, code, name, location, department FROM equipments WHERE status != 'disposed' ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="mb-4">
            <a href="<?= base_url('modules/repair/index.php') ?>" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left me-1"></i> กลับหน้ารายการแจ้งซ่อม
            </a>
            <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-plus-circle text-primary me-2"></i>แบบฟอร์มแจ้งซ่อม (Repair Request)</h4>
            <p class="text-muted mb-0">กรอกข้อมูลเพื่อส่งเรื่องให้ทีมช่างและเจ้าหน้าที่ดำเนินการ</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card-modern p-4">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-md-7">
                        <label class="form-label fw-medium text-dark">เลือกครุภัณฑ์ที่ต้องการแจ้งซ่อม</label>
                        <select class="form-select select-equipment" name="equipment_id" id="equipmentSelect">
                            <option value="">-- ไม่ระบุครุภัณฑ์ / แจ้งซ่อมอาคารสถานที่/ระบบทั่วไป --</option>
                            <?php foreach ($equipments as $eq): ?>
                                <option value="<?= $eq['id'] ?>" data-location="<?= htmlspecialchars($eq['location'] ?? '') ?>" <?= ($pre_eq_id == $eq['id']) ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($eq['code']) ?>] <?= htmlspecialchars($eq['name']) ?> (<?= htmlspecialchars($eq['department'] ?? 'ทั่วไป') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">หากเป็นปัญหาเกี่ยวกับอุปกรณ์ที่มีรหัสครุภัณฑ์ กรุณาเลือกรายการจากระบบ</small>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-medium text-dark">ระดับความเร่งด่วน <span class="text-danger">*</span></label>
                        <select class="form-select" name="urgent_level" required>
                            <option value="low">ปกติ (Low) - ไม่กระทบงาน</option>
                            <option value="normal" selected>ปานกลาง (Normal) - ตามคิวมาตรฐาน</option>
                            <option value="high">ด่วน (High) - กระทบขั้นตอนการทำงาน</option>
                            <option value="urgent">ด่วนที่สุด (Urgent) - ระบบหยุดชะงัก / ความปลอดภัย</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-medium text-dark">สถานที่เกิดเหตุ / พิกัดห้องติดตั้ง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="location" id="locationInput" placeholder="เช่น ชั้น 3 อาคารสารสนเทศ ห้องประชุม 302" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-medium text-dark">หัวข้อปัญหา / สรุปอาการเสีย <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="problem_title" placeholder="เช่น คอมพิวเตอร์เปิดไม่ติด มีเสียงพัดลมดังผิดปกติ" value="<?= htmlspecialchars($_POST['problem_title'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-medium text-dark">รายละเอียดอาการเสียเพิ่มเติม <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="problem_description" rows="4" placeholder="ระบุสิ่งที่เกิดขึ้น ข้อความแจ้งเตือน หรือสาเหตุที่พบ..." required><?= htmlspecialchars($_POST['problem_description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-medium text-dark">แนบรูปภาพความเสียหาย (ถ้ามี)</label>
                        <input type="file" class="form-control image-preview-input" name="damage_image" accept="image/*" data-preview-target="previewDamageImg">
                        <div class="mt-2">
                            <img id="previewDamageImg" src="#" alt="ตัวอย่างภาพความเสียหาย" class="d-none img-thumbnail rounded" style="max-height: 180px;">
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-end">
                    <a href="<?= base_url('modules/repair/index.php') ?>" class="btn btn-secondary px-4 me-2">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-1"></i> ส่งเรื่องแจ้งซ่อม</button>
                </div>
            </form>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const eqSelect = document.getElementById('equipmentSelect');
    const locInput = document.getElementById('locationInput');
    if (eqSelect && locInput) {
        eqSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            const loc = selectedOpt.getAttribute('data-location');
            if (loc && !locInput.value) {
                locInput.value = loc;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
