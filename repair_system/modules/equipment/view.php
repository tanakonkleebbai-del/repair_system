<?php
/**
 * Equipment View Details & Maintenance History
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin', 'inventory', 'technician', 'user']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT e.*, c.name as category_name 
                      FROM equipments e 
                      LEFT JOIN categories c ON e.category_id = c.id 
                      WHERE e.id = ?");
$stmt->execute([$id]);
$equipment = $stmt->fetch();

if (!$equipment) {
    set_flash('error', 'ไม่พบข้อมูลครุภัณฑ์ที่ระบุ');
    redirect('modules/equipment/index.php');
}

$page_title = 'รายละเอียดครุภัณฑ์: ' . $equipment['code'];

// Fetch Maintenance & Repair History for this specific equipment (Requirement 4)
$stmt_history = $pdo->prepare("SELECT r.*, u.fullname as requester_name, t.fullname as tech_name 
                               FROM repairs r 
                               LEFT JOIN users u ON r.user_id = u.id 
                               LEFT JOIN users t ON r.technician_id = t.id 
                               WHERE r.equipment_id = ? 
                               ORDER BY r.created_at DESC");
$stmt_history->execute([$id]);
$repair_history = $stmt_history->fetchAll();

// Total repair costs for this item
$total_repair_cost = 0;
foreach ($repair_history as $rh) {
    $total_repair_cost += (float)$rh['total_cost'];
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <a href="<?= base_url('modules/equipment/index.php') ?>" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="fas fa-arrow-left me-1"></i> กลับทะเบียนครุภัณฑ์
                </a>
                <h4 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($equipment['name']) ?></h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border fs-6"><?= htmlspecialchars($equipment['code']) ?></span>
                    <?= equipment_status_badge($equipment['status']) ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('modules/repair/create.php?equipment_id=' . $equipment['id']) ?>" class="btn btn-danger">
                    <i class="fas fa-wrench me-1"></i> แจ้งซ่อมครุภัณฑ์นี้
                </a>
                <?php if (in_array($_SESSION['user_role'], ['admin', 'inventory'])): ?>
                <a href="<?= base_url('modules/equipment/edit.php?id=' . $equipment['id']) ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> แก้ไขข้อมูล
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Equipment Specifications -->
            <div class="col-lg-8">
                <div class="card-modern h-100">
                    <div class="card-header-modern">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-info-circle text-primary me-2"></i>ข้อมูลรายละเอียดครุภัณฑ์</h5>
                    </div>
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">รหัสครุภัณฑ์:</span>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($equipment['code']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">หมวดหมู่:</span>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($equipment['category_name'] ?? 'ทั่วไป') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">หมายเลขซีเรียล (Serial Number):</span>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($equipment['serial_number'] ?? '-') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">สถานที่ติดตั้ง / พิกัดห้อง:</span>
                                <strong class="text-dark fs-6"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($equipment['location'] ?? '-') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">หน่วยงาน / ฝ่ายที่รับผิดชอบ:</span>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($equipment['department'] ?? '-') ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">ราคาจัดซื้อ:</span>
                                <strong class="text-success fs-6"><?= format_currency($equipment['purchase_price']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">วันที่ตรวจรับ / จัดซื้อ:</span>
                                <strong class="text-dark fs-6"><?= format_thai_date($equipment['purchase_date'], false) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block small">วันหมดอายุประกัน:</span>
                                <strong class="text-dark fs-6"><?= format_thai_date($equipment['warranty_expire'], false) ?></strong>
                            </div>
                            <div class="col-12 border-top pt-3">
                                <span class="text-muted d-block small">หมายเหตุ / ข้อมูลจำเพาะ:</span>
                                <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($equipment['note'] ?? '-')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Equipment Image & QR Code Card -->
            <div class="col-lg-4">
                <div class="card-modern p-4 text-center h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="fw-bold text-dark mb-3">รูปภาพครุภัณฑ์</h6>
                        <?php if (!empty($equipment['image'])): ?>
                            <div class="img-preview-container img-previewable" data-preview-image="<?= base_url('uploads/equipments/' . $equipment['image']) ?>" data-title="รูปภาพครุภัณฑ์ - <?= htmlspecialchars($equipment['name']) ?> (<?= htmlspecialchars($equipment['code']) ?>)">
                                <img src="<?= base_url('uploads/equipments/' . $equipment['image']) ?>" alt="รูปภาพครุภัณฑ์" class="img-fluid rounded border mb-3 shadow-sm" style="max-height: 180px; object-fit: cover;">
                                <div class="img-preview-overlay">
                                    <div class="text-center">
                                        <i class="fas fa-search-plus fa-2x mb-1"></i>
                                        <div class="small fw-bold">คลิกเพื่อดูรูปขยาย</div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-light rounded p-4 text-muted border mb-3">
                                <i class="fas fa-image fa-3x mb-2 text-secondary opacity-50"></i>
                                <div>ไม่มีรูปภาพ</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-3">
                        <small class="text-muted d-block mb-2">QR Code ตรวจสอบครุภัณฑ์</small>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=<?= urlencode(base_url('modules/equipment/view.php?id=' . $equipment['id'])) ?>" alt="QR Code" class="img-thumbnail">
                        <div class="mt-2"><small class="text-muted"><?= htmlspecialchars($equipment['code']) ?></small></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance & Repair History Card (Requirement 4) -->
        <div class="card-modern">
            <div class="card-header-modern">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-info me-2"></i>ประวัติการซ่อมบำรุงย้อนหลัง (Maintenance History)</h5>
                    <span class="badge bg-info text-white"><?= count($repair_history) ?> รายการ</span>
                </div>
                <div>
                    <span class="text-muted small me-2">ค่าใช้จ่ายซ่อมสะสม:</span>
                    <strong class="text-danger fs-6"><?= format_currency($total_repair_cost) ?></strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">รหัสใบแจ้งซ่อม</th>
                            <th>อาการชำรุด / ปัญหา</th>
                            <th>ผู้แจ้งซ่อม</th>
                            <th>ช่างผู้รับผิดชอบ</th>
                            <th>วันที่แจ้งซ่อม</th>
                            <th>ค่าใช้จ่าย</th>
                            <th>สถานะ</th>
                            <th class="text-end pe-4">ดูข้อมูล</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($repair_history)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-shield-alt fa-2x mb-2 text-success opacity-75"></i>
                                    <div>ครุภัณฑ์นี้ยังไม่มีประวัติการแจ้งซ่อม หรือยังไม่เคยชำรุด</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($repair_history as $rep): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= base_url('modules/repair/detail.php?id=' . $rep['id']) ?>" class="fw-bold text-primary">
                                        <?= htmlspecialchars($rep['ticket_no']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($rep['problem_title']) ?></div>
                                    <?php if (!empty($rep['repair_solution'])): ?>
                                        <small class="text-muted"><i class="fas fa-check text-success me-1"></i><?= htmlspecialchars(mb_substr($rep['repair_solution'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($rep['requester_name'] ?? 'ผู้ใช้งาน') ?></td>
                                <td><?= htmlspecialchars($rep['tech_name'] ?? 'ยังไม่ระบุ') ?></td>
                                <td><?= format_thai_date($rep['created_at']) ?></td>
                                <td class="fw-semibold text-danger"><?= format_currency($rep['total_cost']) ?></td>
                                <td><?= status_badge($rep['status']) ?></td>
                                <td class="text-end pe-4">
                                    <a href="<?= base_url('modules/repair/detail.php?id=' . $rep['id']) ?>" class="btn btn-sm btn-light border">
                                        <i class="fas fa-eye"></i> รายละเอียด
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
