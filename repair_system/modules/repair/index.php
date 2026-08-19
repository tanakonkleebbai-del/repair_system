<?php
/**
 * Repair Management - List & Tracking
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_auth();

$page_title = 'รายการแจ้งซ่อม';
$user_role = $_SESSION['user_role'] ?? 'user';
$user_id = $_SESSION['user_id'];

// Filter & Search
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$urgent_filter = $_GET['urgent'] ?? '';

$sql = "SELECT r.*, e.code as eq_code, e.name as eq_name, u.fullname as requester_name, u.phone as requester_phone, t.fullname as tech_name 
        FROM repairs r 
        LEFT JOIN equipments e ON r.equipment_id = e.id 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN users t ON r.technician_id = t.id 
        WHERE 1=1";
$params = [];

// Filter by Role Permission
if ($user_role === 'user') {
    $sql .= " AND r.user_id = :uid";
    $params['uid'] = $user_id;
} elseif ($user_role === 'technician') {
    // Technician can see jobs assigned to them OR pending unassigned jobs
    $sql .= " AND (r.technician_id = :tid OR r.status = 'pending')";
    $params['tid'] = $user_id;
}

if (!empty($search)) {
    $sql .= " AND (r.ticket_no LIKE :s1 OR r.problem_title LIKE :s2 OR r.location LIKE :s3 OR e.name LIKE :s4 OR u.fullname LIKE :s5)";
    $params['s1'] = "%{$search}%";
    $params['s2'] = "%{$search}%";
    $params['s3'] = "%{$search}%";
    $params['s4'] = "%{$search}%";
    $params['s5'] = "%{$search}%";
}

if (!empty($status_filter)) {
    $sql .= " AND r.status = :status";
    $params['status'] = $status_filter;
}

if (!empty($urgent_filter)) {
    $sql .= " AND r.urgent_level = :urgent";
    $params['urgent'] = $urgent_filter;
}

$sql .= " ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$repairs = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-clipboard-list text-primary me-2"></i><?= ($user_role === 'user') ? 'รายการแจ้งซ่อมของฉัน' : 'จัดการรายการแจ้งซ่อม (Repair Management)' ?></h4>
                <p class="text-muted mb-0">ติดตามสถานะงานซ่อม มอบหมายงานช่าง และประวัติการดำเนินงาน</p>
            </div>
            <a href="<?= base_url('modules/repair/create.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> แจ้งซ่อมใหม่
            </a>
        </div>

        <!-- Filter Card -->
        <div class="card-modern mb-4 p-3">
            <form action="" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" placeholder="ค้นหา รหัสแจ้งซ่อม, หัวข้อปัญหา, ครุภัณฑ์, ผู้แจ้ง..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">-- ทุกสถานะงาน --</option>
                        <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="assigned" <?= ($status_filter === 'assigned') ? 'selected' : '' ?>>มอบหมายช่างแล้ว</option>
                        <option value="in_progress" <?= ($status_filter === 'in_progress') ? 'selected' : '' ?>>กำลังซ่อม</option>
                        <option value="waiting_parts" <?= ($status_filter === 'waiting_parts') ? 'selected' : '' ?>>รออะไหล่</option>
                        <option value="completed" <?= ($status_filter === 'completed') ? 'selected' : '' ?>>ซ่อมเสร็จสิ้น</option>
                        <option value="cancelled" <?= ($status_filter === 'cancelled') ? 'selected' : '' ?>>ยกเลิก</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="urgent">
                        <option value="">-- ระดับความเร่งด่วน --</option>
                        <option value="low" <?= ($urgent_filter === 'low') ? 'selected' : '' ?>>ปกติ</option>
                        <option value="normal" <?= ($urgent_filter === 'normal') ? 'selected' : '' ?>>ปานกลาง</option>
                        <option value="high" <?= ($urgent_filter === 'high') ? 'selected' : '' ?>>ด่วน</option>
                        <option value="urgent" <?= ($urgent_filter === 'urgent') ? 'selected' : '' ?>>ด่วนที่สุด</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> กรอง</button>
                    <?php if (!empty($search) || !empty($status_filter) || !empty($urgent_filter)): ?>
                        <a href="<?= base_url('modules/repair/index.php') ?>" class="btn btn-light border"><i class="fas fa-redo"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Repairs Table -->
        <div class="card-modern">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">รหัสแจ้งซ่อม</th>
                            <th>ปัญหา / ครุภัณฑ์ / สถานที่</th>
                            <th>ผู้แจ้งซ่อม</th>
                            <th>ความเร่งด่วน</th>
                            <th>ช่างผู้รับผิดชอบ</th>
                            <th>สถานะ</th>
                            <th>วันที่แจ้ง</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($repairs)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                                    <div>ไม่พบรายการแจ้งซ่อมตามเงื่อนไขที่เลือก</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($repairs as $rep): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= base_url('modules/repair/detail.php?id=' . $rep['id']) ?>" class="fw-bold text-primary">
                                        <?= htmlspecialchars($rep['ticket_no']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($rep['problem_title']) ?></div>
                                    <?php if (!empty($rep['eq_name'])): ?>
                                        <div class="text-muted small"><i class="fas fa-cube text-primary me-1"></i><?= htmlspecialchars($rep['eq_name']) ?> (<?= htmlspecialchars($rep['eq_code']) ?>)</div>
                                    <?php endif; ?>
                                    <div class="text-muted small"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($rep['location'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($rep['requester_name'] ?? '-') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($rep['requester_phone'] ?? '') ?></small>
                                </td>
                                <td><?= urgent_badge($rep['urgent_level']) ?></td>
                                <td>
                                    <?php if (!empty($rep['tech_name'])): ?>
                                        <span class="badge bg-light text-dark border"><i class="fas fa-user-gear text-primary me-1"></i><?= htmlspecialchars($rep['tech_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="fas fa-clock me-1"></i>ยังไม่มอบหมาย</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= status_badge($rep['status']) ?></td>
                                <td><small class="text-muted"><?= format_thai_date($rep['created_at']) ?></small></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('modules/repair/detail.php?id=' . $rep['id']) ?>" class="btn btn-outline-primary" title="ดูรายละเอียด">
                                            <i class="fas fa-eye me-1"></i> รายละเอียด
                                        </a>
                                        <?php if ($user_role === 'admin'): ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= base_url('modules/repair/delete.php?id=' . $rep['id']) ?>', 'ยืนยันการลบใบแจ้งซ่อม?', 'ต้องการลบใบแจ้งซ่อม <?= htmlspecialchars($rep['ticket_no']) ?> หรือไม่?')" title="ลบรายการแจ้งซ่อม">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
