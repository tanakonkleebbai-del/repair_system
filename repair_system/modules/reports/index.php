<?php
/**
 * Reports & Analytics Dashboard (Executive View)
 * Requirement 5: สรุปสถิติภาพรวม ครุภัณฑ์ที่เสียบ่อยที่สุด ระยะเวลาเฉลี่ย งบประมาณที่ใช้
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin', 'inventory', 'technician']);

$page_title = 'รายงานและแดชบอร์ดวิเคราะห์ข้อมูล';

// 1. KPI Summary
$total_repairs = (int)$pdo->query("SELECT COUNT(*) FROM repairs")->fetchColumn();
$pending_repairs = (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status = 'pending'")->fetchColumn();
$in_progress_repairs = (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status IN ('assigned', 'in_progress', 'waiting_parts')")->fetchColumn();
$completed_repairs = (int)$pdo->query("SELECT COUNT(*) FROM repairs WHERE status = 'completed'")->fetchColumn();
$total_cost = (float)$pdo->query("SELECT SUM(total_cost) FROM repairs")->fetchColumn();

// 2. Average Resolution Time (in Hours/Days for completed repairs)
$avg_hours_stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours FROM repairs WHERE status = 'completed' AND completed_at IS NOT NULL");
$avg_hours = round((float)$avg_hours_stmt->fetchColumn(), 1);
$avg_days = round($avg_hours / 24, 1);

// 3. Top Most Frequent Damaged Equipments / Categories (ครุภัณฑ์ที่เสียบ่อยที่สุด)
$top_categories_stmt = $pdo->query("
    SELECT c.name as category_name, COUNT(r.id) as repair_count, SUM(r.total_cost) as total_cost 
    FROM repairs r 
    INNER JOIN equipments e ON r.equipment_id = e.id 
    INNER JOIN categories c ON e.category_id = c.id 
    GROUP BY c.id, c.name 
    ORDER BY repair_count DESC 
    LIMIT 6
");
$top_categories = $top_categories_stmt->fetchAll();

// Top Equipments that failed most
$top_eq_stmt = $pdo->query("
    SELECT e.code, e.name, COUNT(r.id) as repair_count, SUM(r.total_cost) as total_cost, e.location 
    FROM repairs r 
    INNER JOIN equipments e ON r.equipment_id = e.id 
    GROUP BY e.id, e.code, e.name, e.location 
    ORDER BY repair_count DESC 
    LIMIT 5
");
$top_equipments = $top_eq_stmt->fetchAll();

// 4. Monthly Repair Counts (Last 6 months)
$monthly_stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month_label, COUNT(id) as total_count, SUM(total_cost) as month_cost 
    FROM repairs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY month_label ASC
");
$monthly_data = $monthly_stmt->fetchAll();

$month_labels = [];
$month_counts = [];
$month_costs = [];
foreach ($monthly_data as $m) {
    $month_labels[] = $m['month_label'];
    $month_counts[] = (int)$m['total_count'];
    $month_costs[] = (float)$m['month_cost'];
}

// 5. Technician Performance
$tech_perf_stmt = $pdo->query("
    SELECT u.fullname, 
           COUNT(r.id) as total_assigned, 
           SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
           SUM(r.total_cost) as total_cost 
    FROM users u 
    LEFT JOIN repairs r ON u.id = r.technician_id 
    WHERE u.role = 'technician' 
    GROUP BY u.id, u.fullname
");
$tech_performance = $tech_perf_stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="fas fa-chart-line text-primary me-2"></i>รายงานและแดชบอร์ดวิเคราะห์ข้อมูล (Reports & Analytics)</h4>
                <p class="text-muted mb-0">ข้อมูลสถิติภาพรวม ครุภัณฑ์ที่เสียบ่อย ระยะเวลาเฉลี่ย และงบประมาณในการซ่อมบำรุง</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('modules/reports/export_csv.php') ?>" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i> ส่งออกข้อมูล (CSV / Excel)
                </a>
            </div>
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card stat-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">งานแจ้งซ่อมทั้งหมด</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= number_format($total_repairs) ?></h3>
                            <small class="text-primary"><i class="fas fa-clipboard-list me-1"></i>รายการทั้งหมด</small>
                        </div>
                        <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-card stat-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">ซ่อมเสร็จสิ้น</div>
                            <h3 class="fw-bold mb-0 text-success"><?= number_format($completed_repairs) ?></h3>
                            <small class="text-success"><?= $total_repairs > 0 ? round(($completed_repairs / $total_repairs) * 100, 1) : 0 ?>% สำเร็จ</small>
                        </div>
                        <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-card stat-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">งบประมาณซ่อมรวม</div>
                            <h3 class="fw-bold mb-0 text-danger"><?= format_currency($total_cost) ?></h3>
                            <small class="text-muted">ค่าอะไหล่และบริการ</small>
                        </div>
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-card stat-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">ระยะเวลาเฉลี่ยในการซ่อม</div>
                            <h3 class="fw-bold mb-0 text-info"><?= $avg_days > 0 ? $avg_days . ' วัน' : ($avg_hours . ' ชม.') ?></h3>
                            <small class="text-muted"><?= $avg_hours ?> ชั่วโมง / รายการ</small>
                        </div>
                        <div class="stat-icon"><i class="fas fa-business-time"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Monthly Trends Bar Chart -->
            <div class="col-lg-7">
                <div class="card-modern h-100">
                    <div class="card-header-modern">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-bar text-primary me-2"></i>แนวโน้มการแจ้งซ่อมและค่าใช้จ่ายรายเดือน</h5>
                    </div>
                    <div class="p-4">
                        <canvas id="monthlyChart" height="230"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Problem Categories Pie/Doughnut Chart -->
            <div class="col-lg-5">
                <div class="card-modern h-100">
                    <div class="card-header-modern">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-pie text-info me-2"></i>สัดส่วนปัญหาตามหมวดหมู่</h5>
                    </div>
                    <div class="p-4">
                        <canvas id="categoryChart" height="230"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Failure Equipments Table & Technician Performance -->
        <div class="row g-4">
            <!-- Top Frequent Failures Table -->
            <div class="col-lg-7">
                <div class="card-modern h-100">
                    <div class="card-header-modern">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-fire text-danger me-2"></i>อันดับครุภัณฑ์ที่ชำรุดบ่อยที่สุด (Top Problem Equipments)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">รหัส / ครุภัณฑ์</th>
                                    <th>สถานที่ตั้ง</th>
                                    <th class="text-center">จำนวนครั้งที่ซ่อม</th>
                                    <th class="text-end pe-4">งบประมาณสะสม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_equipments)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">ยังไม่มีข้อมูลสถิติเพียงพอ</td></tr>
                                <?php else: ?>
                                    <?php foreach ($top_equipments as $item): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['code']) ?></span>
                                        </td>
                                        <td><small class="text-muted"><i class="fas fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($item['location'] ?? '-') ?></small></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1 fs-6">
                                                <?= $item['repair_count'] ?> ครั้ง
                                            </span>
                                        </td>
                                        <td class="text-end pe-4 fw-bold text-dark"><?= format_currency($item['total_cost']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Technician Performance -->
            <div class="col-lg-5">
                <div class="card-modern h-100">
                    <div class="card-header-modern">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-user-check text-success me-2"></i>ผลงานและภาระงานช่างซ่อมบำรุง</h5>
                    </div>
                    <div class="p-4">
                        <?php if (empty($tech_performance)): ?>
                            <p class="text-muted text-center py-3">ยังไม่มีข้อมูลช่างในระบบ</p>
                        <?php else: ?>
                            <?php foreach ($tech_performance as $tp): 
                                $rate = ($tp['total_assigned'] > 0) ? round(($tp['completed_count'] / $tp['total_assigned']) * 100) : 0;
                            ?>
                            <div class="mb-3 border-bottom pb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark"><?= htmlspecialchars($tp['fullname']) ?></strong>
                                    <small class="text-muted">สำเร็จ <?= (int)$tp['completed_count'] ?> / <?= (int)$tp['total_assigned'] ?> งาน</small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $rate ?>%;" aria-valuenow="<?= $rate ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-success fw-medium"><?= $rate ?>% สำเร็จ</small>
                                    <small class="text-muted">งบประมาณที่เบิก: <?= format_currency($tp['total_cost']) ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Monthly Trends Chart
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(!empty($month_labels) ? $month_labels : ['ปัจจุบัน']) ?>,
                datasets: [
                    {
                        label: 'จำนวนงานแจ้งซ่อม (รายการ)',
                        data: <?= json_encode(!empty($month_counts) ? $month_counts : [count($repairs ?? [1])]) ?>,
                        backgroundColor: 'rgba(67, 97, 238, 0.8)',
                        borderRadius: 6
                    },
                    {
                        label: 'ค่าใช้จ่ายซ่อม (ร้อยบาท)',
                        data: <?= json_encode(array_map(function($v){ return $v / 100; }, !empty($month_costs) ? $month_costs : [5])) ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // 2. Category Share Chart
    const catCtx = document.getElementById('categoryChart');
    if (catCtx) {
        const catLabels = <?= json_encode(array_column($top_categories, 'category_name')) ?>;
        const catData = <?= json_encode(array_map('intval', array_column($top_categories, 'repair_count'))) ?>;

        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels.length ? catLabels : ['คอมพิวเตอร์', 'แอร์', 'ระบบไฟฟ้า', 'เน็ตเวิร์ก'],
                datasets: [{
                    data: catData.length ? catData : [12, 8, 5, 3],
                    backgroundColor: [
                        '#4361ee', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
