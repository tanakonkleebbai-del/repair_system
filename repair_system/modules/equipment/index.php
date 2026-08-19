<?php
/**
 * Equipment Management - List & Directory
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin', 'inventory', 'technician']);

$page_title = 'ทะเบียนคุมครุภัณฑ์';

// Filter & Search
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category_id'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT e.*, c.name as category_name 
        FROM equipments e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (e.code LIKE :s1 OR e.name LIKE :s2 OR e.serial_number LIKE :s3 OR e.location LIKE :s4)";
    $params['s1'] = "%{$search}%";
    $params['s2'] = "%{$search}%";
    $params['s3'] = "%{$search}%";
    $params['s4'] = "%{$search}%";
}

if (!empty($category_filter)) {
    $sql .= " AND e.category_id = :cat_id";
    $params['cat_id'] = $category_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND e.status = :status";
    $params['status'] = $status_filter;
}

$sql .= " ORDER BY e.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipments = $stmt->fetchAll();

// Categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Status counts
$stat_total = $pdo->query("SELECT COUNT(*) FROM equipments")->fetchColumn();
$stat_avail = $pdo->query("SELECT COUNT(*) FROM equipments WHERE status = 'available'")->fetchColumn();
$stat_repair = $pdo->query("SELECT COUNT(*) FROM equipments WHERE status = 'repairing'")->fetchColumn();
$stat_damaged = $pdo->query("SELECT COUNT(*) FROM equipments WHERE status = 'damaged'")->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <!-- Page Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-boxes-stacked text-primary me-2"></i>ทะเบียนคุมครุภัณฑ์ (Equipment Management)</h4>
                <p class="text-muted mb-0">ระบบทะเบียนคุมครุภัณฑ์ออนไลน์ สถานะ และประวัติการซ่อมบำรุง</p>
            </div>
            <?php if (in_array($_SESSION['user_role'], ['admin', 'inventory'])): ?>
            <a href="<?= base_url('modules/equipment/create.php') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> เพิ่มครุภัณฑ์ใหม่
            </a>
            <?php endif; ?>
        </div>

        <!-- KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card stat-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">ครุภัณฑ์ทั้งหมด</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= number_format($stat_total) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-cube"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card stat-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">พร้อมใช้งาน</div>
                            <h3 class="fw-bold mb-0 text-success"><?= number_format($stat_avail) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card stat-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">กำลังส่งซ่อม</div>
                            <h3 class="fw-bold mb-0 text-warning"><?= number_format($stat_repair) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card stat-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">ชำรุด / เสียหาย</div>
                            <h3 class="fw-bold mb-0 text-danger"><?= number_format($stat_damaged) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card-modern mb-4 p-3">
            <form action="" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="ค้นหา รหัสครุภัณฑ์, ชื่อ, Serial No, สถานที่..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category_id">
                        <option value="">-- ทุกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">-- ทุกสถานะ --</option>
                        <option value="available" <?= ($status_filter === 'available') ? 'selected' : '' ?>>พร้อมใช้งาน</option>
                        <option value="repairing" <?= ($status_filter === 'repairing') ? 'selected' : '' ?>>กำลังซ่อม</option>
                        <option value="damaged" <?= ($status_filter === 'damaged') ? 'selected' : '' ?>>ชำรุด</option>
                        <option value="disposed" <?= ($status_filter === 'disposed') ? 'selected' : '' ?>>แทงจำหน่าย</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> กรอง</button>
                    <?php if (!empty($search) || !empty($category_filter) || !empty($status_filter)): ?>
                        <a href="<?= base_url('modules/equipment/index.php') ?>" class="btn btn-light border"><i class="fas fa-redo"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Equipments Table -->
        <div class="card-modern">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">รหัสครุภัณฑ์</th>
                            <th>ชื่อครุภัณฑ์ / หมวดหมู่</th>
                            <th>สถานที่ตั้ง / แผนก</th>
                            <th>ราคาจัดซื้อ</th>
                            <th>สถานะ</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($equipments)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <div>ไม่พบรายการครุภัณฑ์ตามเงื่อนไขที่ค้นหา</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($equipments as $item): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($item['code']) ?></span>
                                    <?php if (!empty($item['serial_number'])): ?>
                                        <div class="text-muted small mt-1">S/N: <?= htmlspecialchars($item['serial_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('modules/equipment/view.php?id=' . $item['id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                    <div class="text-muted small"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($item['category_name'] ?? 'ทั่วไป') ?></div>
                                </td>
                                <td>
                                    <div><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($item['location'] ?? '-') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($item['department'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= format_currency($item['purchase_price']) ?></div>
                                    <small class="text-muted"><?= format_thai_date($item['purchase_date'], false) ?></small>
                                </td>
                                <td><?= equipment_status_badge($item['status']) ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('modules/equipment/view.php?id=' . $item['id']) ?>" class="btn btn-outline-info" title="ดูรายละเอียดและประวัติการซ่อม">
                                            <i class="fas fa-history"></i> ประวัติ
                                        </a>
                                        <?php if (in_array($_SESSION['user_role'], ['admin', 'inventory'])): ?>
                                        <a href="<?= base_url('modules/equipment/edit.php?id=' . $item['id']) ?>" class="btn btn-outline-primary" title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= base_url('modules/equipment/delete.php?id=' . $item['id']) ?>')" title="ลบ">
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
