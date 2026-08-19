<?php
/**
 * Main Application Landing / Dashboard
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Check Authentication
if (!isset($_SESSION['user_id'])) {
    redirect('modules/auth/login.php');
}

$page_title = 'ภาพรวมระบบ (Dashboard)';
$user_role = $_SESSION['user_role'] ?? 'user';
$user_id = $_SESSION['user_id'];
$user_fullname = $_SESSION['user_fullname'] ?? 'ผู้ใช้งาน';

// KPI Statistics
$stat_total_repairs = (int)$pdo->query("SELECT COUNT(*) FROM repairs")->fetchColumn();
$stat_pending = (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status = 'pending'")->fetchColumn();
$stat_in_progress = (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status IN ('assigned', 'in_progress', 'waiting_parts')")->fetchColumn();
$stat_completed = (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status = 'completed'")->fetchColumn();

// Role-specific stats
$my_repairs_count = 0;
$my_active_count = 0;
$my_assigned_jobs = 0;

if ($user_role === 'user') {
    $my_total = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE user_id = ?");
    $my_total->execute([$user_id]);
    $my_repairs_count = (int)$my_total->fetchColumn();

    $my_pending = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE user_id = ? AND status != 'completed' AND status != 'cancelled'");
    $my_pending->execute([$user_id]);
    $my_active_count = (int)$my_pending->fetchColumn();
} elseif ($user_role === 'technician') {
    $tech_assigned = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE technician_id = ? AND status IN ('assigned', 'in_progress', 'waiting_parts')");
    $tech_assigned->execute([$user_id]);
    $my_assigned_jobs = (int)$tech_assigned->fetchColumn();
}

// Recent Repairs List
$recent_sql = "SELECT r.*, e.code as eq_code, e.name as eq_name, u.fullname as requester_name, t.fullname as tech_name 
               FROM repairs r 
               LEFT JOIN equipments e ON r.equipment_id = e.id 
               LEFT JOIN users u ON r.user_id = u.id 
               LEFT JOIN users t ON r.technician_id = t.id 
               WHERE 1=1";

if ($user_role === 'user') {
    $recent_sql .= " AND r.user_id = {$user_id}";
} elseif ($user_role === 'technician') {
    $recent_sql .= " AND (r.technician_id = {$user_id} OR r.status = 'pending')";
}

$recent_sql .= " ORDER BY r.id DESC LIMIT 8";
$recent_repairs = $pdo->query($recent_sql)->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-body">
        <!-- Welcome Banner -->
        <div class="card-modern p-4 mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fw-bold mb-1">สวัสดีคุณ <?= htmlspecialchars($user_fullname) ?> 👋</h3>
                    <p class="text-white-50 mb-0">ยินดีต้อนรับสู่ <?= SITE_NAME ?> (สิทธิ์: <?= role_badge($user_role) ?>)</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('modules/repair/create.php') ?>" class="btn btn-primary px-4 py-2 fw-medium shadow">
                        <i class="fas fa-plus-circle me-1"></i> แจ้งซ่อมครุภัณฑ์ / ปัญหา
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 KPI Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= ($user_role === 'user') ? 'งานแจ้งซ่อมของฉัน' : 'งานแจ้งซ่อมทั้งหมด' ?></div>
                            <h3 class="fw-bold mb-0 text-dark"><?= ($user_role === 'user') ? $my_repairs_count : number_format($stat_total_repairs) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-card stat-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">รอดำเนินการ / รับเรื่อง</div>
                            <h3 class="fw-bold mb-0 text-warning"><?= number_format($stat_pending) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-card stat-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= ($user_role === 'technician') ? 'งานที่ฉันรับผิดชอบ' : 'กำลังดำเนินการซ่อม' ?></div>
                            <h3 class="fw-bold mb-0 text-info"><?= ($user_role === 'technician') ? $my_assigned_jobs : number_format($stat_in_progress) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-card stat-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">ซ่อมเสร็จสิ้นแล้ว</div>
                            <h3 class="fw-bold mb-0 text-success"><?= number_format($stat_completed) ?></h3>
                        </div>
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Latest Activity -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-primary me-2"></i>รายการแจ้งซ่อมล่าสุด</h5>
                            <span class="badge bg-light text-dark border"><?= count($recent_repairs) ?> รายการ</span>
                        </div>
                        <a href="<?= base_url('modules/repair/index.php') ?>" class="btn btn-sm btn-outline-primary">
                            ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">รหัสแจ้งซ่อม</th>
                                    <th>หัวข้อปัญหา / ครุภัณฑ์</th>
                                    <th>สถานที่ตั้ง</th>
                                    <th>ผู้แจ้ง</th>
                                    <th>ความเร่งด่วน</th>
                                    <th>ช่างผู้รับผิดชอบ</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_repairs)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-clipboard-check fa-2x mb-2 opacity-50"></i>
                                            <div>ยังไม่มีรายการแจ้งซ่อมในระบบ</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_repairs as $rep): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <a href="<?= base_url('modules/repair/detail.php?id=' . $rep['id']) ?>" class="fw-bold text-primary">
                                                <?= htmlspecialchars($rep['ticket_no']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($rep['problem_title']) ?></div>
                                            <?php if (!empty($rep['eq_name'])): ?>
                                                <small class="text-muted"><i class="fas fa-cube me-1"></i><?= htmlspecialchars($rep['eq_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($rep['location'] ?? '-') ?></small></td>
                                        <td><small class="fw-medium text-dark"><?= htmlspecialchars($rep['requester_name']) ?></small></td>
                                        <td><?= urgent_badge($rep['urgent_level']) ?></td>
                                        <td>
                                            <?php if (!empty($rep['tech_name'])): ?>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($rep['tech_name']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">ยังไม่ระบุ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= status_badge($rep['status']) ?></td>
                                        <td class="text-end pe-4">
                                            <a href="<?= base_url('modules/repair/detail.php?id=' . $rep['id']) ?>" class="btn btn-sm btn-light border">
                                                <i class="fas fa-eye"></i> ดู
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
