<?php
/**
 * Repair Ticket Details, Status Timeline & Management Actions
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_auth();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, e.code as eq_code, e.name as eq_name, e.serial_number as eq_sn, e.location as eq_location, 
                             u.fullname as requester_name, u.phone as requester_phone, u.email as requester_email, u.department as requester_dept,
                             t.fullname as tech_name, t.phone as tech_phone
                      FROM repairs r 
                      LEFT JOIN equipments e ON r.equipment_id = e.id 
                      LEFT JOIN users u ON r.user_id = u.id 
                      LEFT JOIN users t ON r.technician_id = t.id 
                      WHERE r.id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

if (!$repair) {
    set_flash('error', 'ไม่พบข้อมูลใบแจ้งซ่อมที่ระบุ');
    redirect('modules/repair/index.php');
}

$user_role = $_SESSION['user_role'] ?? 'user';
$user_id = $_SESSION['user_id'];

// Check permission: normal users can only view their own tickets
if ($user_role === 'user' && $repair['user_id'] != $user_id) {
    set_flash('error', 'คุณไม่มีสิทธิ์เข้าดูใบแจ้งซ่อมของผู้อื่น');
    redirect('modules/repair/index.php');
}

$page_title = 'ใบแจ้งซ่อม: ' . $repair['ticket_no'];

// Fetch Timeline Logs
$stmt_logs = $pdo->prepare("SELECT l.*, u.fullname as actor_name, u.role as actor_role 
                           FROM repair_logs l 
                           LEFT JOIN users u ON l.user_id = u.id 
                           WHERE l.repair_id = ? 
                           ORDER BY l.created_at ASC");
$stmt_logs->execute([$id]);
$logs = $stmt_logs->fetchAll();

// Handle Assign Technician (Admin, Inventory, or Technician Self-Assignment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_tech') {
    if (in_array($user_role, ['admin', 'inventory', 'technician'])) {
        $tech_id = (int)$_POST['technician_id'];
        $stmt_assign = $pdo->prepare("UPDATE repairs SET technician_id = ?, assigned_at = NOW(), status = 'assigned' WHERE id = ?");
        $stmt_assign->execute([$tech_id, $id]);

        $tName = $pdo->query("SELECT fullname FROM users WHERE id = {$tech_id}")->fetchColumn();
        $logStmt = $pdo->prepare("INSERT INTO repair_logs (repair_id, user_id, action_status, comment) VALUES (?, ?, 'assigned', ?)");
        $logStmt->execute([$id, $user_id, "มอบหมายงานให้ช่าง: " . $tName]);

        set_flash('success', 'มอบหมายงานให้ช่างเรียบร้อยแล้ว');
        redirect('modules/repair/detail.php?id=' . $id);
    }
}

// Fetch technicians list for assignment dropdown
$technicians = $pdo->query("SELECT id, fullname, phone FROM users WHERE role = 'technician' AND status = 'active'")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <a href="<?= base_url('modules/repair/index.php') ?>" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="fas fa-arrow-left me-1"></i> กลับหน้ารายการแจ้งซ่อม
                </a>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($repair['ticket_no']) ?></h4>
                    <?= status_badge($repair['status']) ?>
                    <?= urgent_badge($repair['urgent_level']) ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 w-100 w-sm-auto">
                <a href="<?= base_url('modules/repair/print.php?id=' . $repair['id']) ?>" target="_blank" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-print me-1"></i> พิมพ์ใบแจ้งซ่อม
                </a>
                <?php if ($user_role === 'admin'): ?>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete('<?= base_url('modules/repair/delete.php?id=' . $repair['id']) ?>', 'ยืนยันการลบใบแจ้งซ่อม?', 'ต้องการลบใบแจ้งซ่อม <?= htmlspecialchars($repair['ticket_no']) ?> หรือไม่?')">
                    <i class="fas fa-trash-alt me-1"></i> ลบใบแจ้งซ่อม
                </button>
                <?php endif; ?>
                <?php if (in_array($user_role, ['admin', 'technician']) && $repair['status'] !== 'completed' && $repair['status'] !== 'cancelled'): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                    <i class="fas fa-edit me-1"></i> อัปเดตสถานะงาน / บันทึกผล
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Details & Solution -->
            <div class="col-lg-8">
                <!-- Problem Details Card -->
                <div class="card-modern mb-4">
                    <div class="card-header-modern">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-file-alt text-primary me-2"></i>รายละเอียดการแจ้งซ่อม</h5>
                        <small class="text-muted">แจ้งเมื่อ: <?= format_thai_date($repair['created_at']) ?></small>
                    </div>
                    <div class="p-4">
                        <h5 class="fw-bold text-dark mb-3"><?= htmlspecialchars($repair['problem_title']) ?></h5>
                        <div class="bg-light p-3 rounded mb-4 text-dark" style="white-space: pre-wrap;"><?= htmlspecialchars($repair['problem_description']) ?></div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">สถานที่เกิดเหตุ / พิกัด:</span>
                                <strong class="text-dark"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($repair['location'] ?? '-') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">ระดับความเร่งด่วน:</span>
                                <div><?= urgent_badge($repair['urgent_level']) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($repair['damage_image'])): ?>
                            <div class="mt-4 pt-3 border-top">
                                <span class="text-muted d-block small mb-2"><i class="fas fa-image me-1"></i>รูปภาพสภาพความเสียหายที่แนบมา <small class="text-primary">(คลิกที่รูปเพื่อขยาย)</small>:</span>
                                <div class="img-preview-container img-previewable" data-preview-image="<?= base_url('uploads/repairs/' . $repair['damage_image']) ?>" data-title="ภาพสภาพความเสียหาย - <?= htmlspecialchars($repair['ticket_no'] ?? '') ?>">
                                    <img src="<?= base_url('uploads/repairs/' . $repair['damage_image']) ?>" alt="ภาพความเสียหาย" class="img-fluid rounded border shadow-sm" style="max-height: 220px;">
                                    <div class="img-preview-overlay">
                                        <div class="text-center">
                                            <i class="fas fa-search-plus fa-2x mb-1"></i>
                                            <div class="small fw-bold">คลิกเพื่อดูรูปขยาย</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Repair Solution / Outcome Card (If technician has worked on it) -->
                <?php if (!empty($repair['repair_solution']) || !empty($repair['repair_image']) || $repair['total_cost'] > 0): ?>
                <div class="card-modern mb-4 border-success">
                    <div class="card-header-modern bg-success bg-opacity-10">
                        <h5 class="fw-bold mb-0 text-success"><i class="fas fa-clipboard-check me-2"></i>ผลการดำเนินงานและสรุปค่าใช้จ่าย</h5>
                        <?php if (!empty($repair['completed_at'])): ?>
                            <small class="text-success">เสร็จสิ้นเมื่อ: <?= format_thai_date($repair['completed_at']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <?php if (!empty($repair['repair_solution'])): ?>
                            <h6 class="fw-bold text-dark">บันทึกการแก้ไข / ข้อเสนอแนะ:</h6>
                            <div class="bg-light p-3 rounded mb-3 text-dark" style="white-space: pre-wrap;"><?= htmlspecialchars($repair['repair_solution']) ?></div>
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">ค่าใช้จ่าย / อะไหล่รวม:</span>
                                <strong class="text-danger fs-5"><?= format_currency($repair['total_cost']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">ช่างผู้ดำเนินการ:</span>
                                <strong class="text-dark"><?= htmlspecialchars($repair['tech_name'] ?? 'ช่างซ่อมบำรุง') ?></strong>
                            </div>
                        </div>

                        <?php if (!empty($repair['repair_image'])): ?>
                            <div class="mt-4 pt-3 border-top">
                                <span class="text-muted d-block small mb-2"><i class="fas fa-camera me-1"></i>รูปภาพหลังการซ่อม / ส่งมอบงาน <small class="text-primary">(คลิกที่รูปเพื่อขยาย)</small>:</span>
                                <div class="img-preview-container img-previewable" data-preview-image="<?= base_url('uploads/repairs/' . $repair['repair_image']) ?>" data-title="ภาพหลังการซ่อม - <?= htmlspecialchars($repair['ticket_no'] ?? '') ?>">
                                    <img src="<?= base_url('uploads/repairs/' . $repair['repair_image']) ?>" alt="ภาพหลังการซ่อม" class="img-fluid rounded border shadow-sm" style="max-height: 220px;">
                                    <div class="img-preview-overlay">
                                        <div class="text-center">
                                            <i class="fas fa-search-plus fa-2x mb-1"></i>
                                            <div class="small fw-bold">คลิกเพื่อดูรูปขยาย</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status Timeline Log Card -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-stream text-primary me-2"></i>ไทม์ไลน์สถานะการดำเนินงาน (Timeline)</h5>
                    </div>
                    <div class="p-4">
                        <div class="timeline">
                            <?php foreach ($logs as $log): ?>
                            <div class="timeline-item <?= ($log['action_status'] === 'completed') ? 'completed' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div>
                                        <?= status_badge($log['action_status']) ?>
                                        <strong class="ms-2 text-dark"><?= htmlspecialchars($log['actor_name']) ?></strong>
                                        <small class="text-muted">(<?= htmlspecialchars($log['actor_role']) ?>)</small>
                                    </div>
                                    <small class="text-muted"><?= format_thai_date($log['created_at']) ?></small>
                                </div>
                                <?php if (!empty($log['comment'])): ?>
                                    <p class="mb-1 text-dark"><?= htmlspecialchars($log['comment']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($log['spare_parts'])): ?>
                                    <div class="small text-muted"><i class="fas fa-cog me-1"></i>อะไหล่: <?= htmlspecialchars($log['spare_parts']) ?> (<?= format_currency($log['cost']) ?>)</div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Equipment Info, People Involved & Assignment -->
            <div class="col-lg-4">
                <!-- Equipment Linked Card -->
                <div class="card-modern mb-4">
                    <div class="card-header-modern">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-boxes-stacked text-primary me-2"></i>ครุภัณฑ์ที่เกี่ยวข้อง</h6>
                    </div>
                    <div class="p-4">
                        <?php if ($repair['equipment_id']): ?>
                            <div class="mb-2">
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($repair['eq_code']) ?></span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($repair['eq_name']) ?></h6>
                            <?php if (!empty($repair['eq_sn'])): ?>
                                <div class="text-muted small mb-2">S/N: <?= htmlspecialchars($repair['eq_sn']) ?></div>
                            <?php endif; ?>
                            <div class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($repair['eq_location'] ?? '-') ?></div>
                            <a href="<?= base_url('modules/equipment/view.php?id=' . $repair['equipment_id']) ?>" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-history me-1"></i> ดูประวัติครุภัณฑ์ชิ้นนี้
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-0 small">ไม่ผูกกับรหัสครุภัณฑ์ (แจ้งซ่อมอาคารสถานที่หรือปัญหาทั่วไป)</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requester Info Card -->
                <div class="card-modern mb-4">
                    <div class="card-header-modern">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-user text-success me-2"></i>ข้อมูลผู้แจ้งซ่อม</h6>
                    </div>
                    <div class="p-4">
                        <div class="fw-bold text-dark fs-6 mb-1"><?= htmlspecialchars($repair['requester_name']) ?></div>
                        <div class="text-muted small mb-2"><i class="fas fa-building me-1"></i><?= htmlspecialchars($repair['requester_dept'] ?? '-') ?></div>
                        <div class="text-muted small mb-1"><i class="fas fa-phone me-1 text-success"></i><?= htmlspecialchars($repair['requester_phone'] ?? '-') ?></div>
                        <div class="text-muted small"><i class="fas fa-envelope me-1 text-primary"></i><?= htmlspecialchars($repair['requester_email'] ?? '-') ?></div>
                    </div>
                </div>

                <!-- Assigned Technician Card & Assignment Form -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-user-cog text-warning me-2"></i>ช่างผู้รับผิดชอบงาน</h6>
                    </div>
                    <div class="p-4">
                        <?php if (!empty($repair['tech_name'])): ?>
                            <div class="fw-bold text-dark fs-6 mb-1"><?= htmlspecialchars($repair['tech_name']) ?></div>
                            <div class="text-muted small mb-3"><i class="fas fa-phone me-1 text-success"></i><?= htmlspecialchars($repair['tech_phone'] ?? '-') ?></div>
                        <?php else: ?>
                            <p class="text-danger small mb-3"><i class="fas fa-exclamation-triangle me-1"></i>ยังไม่ได้มอบหมายช่างผู้รับผิดชอบ</p>
                        <?php endif; ?>

                        <?php if (in_array($user_role, ['admin', 'inventory', 'technician'])): ?>
                            <form action="" method="POST" class="border-top pt-3">
                                <input type="hidden" name="action" value="assign_tech">
                                <label class="form-label small fw-medium text-dark">มอบหมาย / เปลี่ยนช่าง:</label>
                                <div class="input-group">
                                    <select class="form-select form-select-sm" name="technician_id" required>
                                        <option value="">-- เลือกช่าง --</option>
                                        <?php foreach ($technicians as $tech): ?>
                                            <option value="<?= $tech['id'] ?>" <?= ($repair['technician_id'] == $tech['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tech['fullname']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">บันทึก</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<!-- Update Status & Solution Modal (Requirement 3) -->
<?php if (in_array($user_role, ['admin', 'technician'])): ?>
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="fas fa-tools text-primary me-2"></i>อัปเดตสถานะและบันทึกผลงานซ่อม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('modules/repair/update_status.php') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-dark">ปรับเปลี่ยนสถานะงานซ่อม <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="in_progress" <?= ($repair['status'] === 'in_progress') ? 'selected' : '' ?>>กำลังซ่อม (In Progress)</option>
                                <option value="waiting_parts" <?= ($repair['status'] === 'waiting_parts') ? 'selected' : '' ?>>รออะไหล่ / ส่งซ่อมภายนอก (Waiting Parts)</option>
                                <option value="completed" <?= ($repair['status'] === 'completed') ? 'selected' : '' ?>>ซ่อมเสร็จสิ้น / ส่งมอบงาน (Completed)</option>
                                <option value="cancelled" <?= ($repair['status'] === 'cancelled') ? 'selected' : '' ?>>ยกเลิก / ไม่สามารถซ่อมได้ (Cancelled)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium text-dark">ค่าใช้จ่ายรวม / ค่าอะไหล่ (บาท)</label>
                            <input type="number" step="0.01" class="form-control" name="total_cost" placeholder="0.00" value="<?= htmlspecialchars($repair['total_cost'] ?? '0.00') ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-medium text-dark">รายการอะไหล่ที่ใช้ / ชิ้นส่วนที่เปลี่ยน</label>
                            <input type="text" class="form-control" name="spare_parts" placeholder="เช่น RAM DDR4 8GB, สายพาน, สวิตช์ไฟ">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-medium text-dark">รายละเอียดการซ่อม / ผลการดำเนินงาน / ข้อเสนอแนะ</label>
                            <textarea class="form-control" name="repair_solution" rows="4" placeholder="ระบุสิ่งที่ได้ดำเนินการแก้ไข หรือข้อเสนอแนะในการดูแลรักษา..."><?= htmlspecialchars($repair['repair_solution'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-medium text-dark">แนบรูปภาพหลังการซ่อม / ส่งมอบงาน (ถ้ามี)</label>
                            <input type="file" class="form-control image-preview-input" name="repair_image" accept="image/*" data-preview-target="previewRepairedImg">
                            <div class="mt-2">
                                <img id="previewRepairedImg" src="#" alt="ตัวอย่างภาพหลังซ่อม" class="d-none img-thumbnail rounded" style="max-height: 150px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle me-1"></i> บันทึกการอัปเดต</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
