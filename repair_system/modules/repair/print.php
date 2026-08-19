<?php
/**
 * Printable Repair Ticket & Handover Slip
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_auth();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, e.code as eq_code, e.name as eq_name, e.serial_number as eq_sn, e.location as eq_location, 
                             u.fullname as requester_name, u.phone as requester_phone, u.department as requester_dept,
                             t.fullname as tech_name, t.phone as tech_phone
                      FROM repairs r 
                      LEFT JOIN equipments e ON r.equipment_id = e.id 
                      LEFT JOIN users u ON r.user_id = u.id 
                      LEFT JOIN users t ON r.technician_id = t.id 
                      WHERE r.id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

if (!$repair) {
    die('ไม่พบข้อมูลใบแจ้งซ่อม');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบแจ้งซ่อม - <?= htmlspecialchars($repair['ticket_no']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            color: #000;
            background: #f8fafc;
            padding: 20px;
        }
        .ticket-paper {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .header-title {
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .signature-box {
            border-top: 1px dashed #999;
            width: 200px;
            text-align: center;
            padding-top: 5px;
            margin-top: 50px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .ticket-paper { border: none; box-shadow: none; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-4">
    <button onclick="window.print()" class="btn btn-primary px-4"><i class="fas fa-print me-1"></i> พิมพ์เอกสาร</button>
    <button onclick="window.close()" class="btn btn-secondary px-3 ms-2">ปิดหน้าต่าง</button>
</div>

<div class="ticket-paper">
    <div class="header-title d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1"><?= SITE_NAME ?></h4>
            <div class="text-muted">ใบแจ้งซ่อมและส่งมอบงาน (Repair Service Ticket)</div>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($repair['ticket_no']) ?></h5>
            <small class="text-muted">วันที่พิมพ์: <?= date('d/m/Y H:i') ?></small>
        </div>
    </div>

    <!-- Ticket Meta -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted" width="35%">วันที่แจ้ง:</td><td><strong><?= format_thai_date($repair['created_at']) ?></strong></td></tr>
                <tr><td class="text-muted">ผู้แจ้งซ่อม:</td><td><?= htmlspecialchars($repair['requester_name']) ?></td></tr>
                <tr><td class="text-muted">ฝ่าย/หน่วยงาน:</td><td><?= htmlspecialchars($repair['requester_dept'] ?? '-') ?></td></tr>
                <tr><td class="text-muted">เบอร์โทรศัพท์:</td><td><?= htmlspecialchars($repair['requester_phone'] ?? '-') ?></td></tr>
            </table>
        </div>
        <div class="col-6">
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted" width="35%">ความเร่งด่วน:</td><td><?= strtoupper($repair['urgent_level']) ?></td></tr>
                <tr><td class="text-muted">สถานะงาน:</td><td><?= strtoupper($repair['status']) ?></td></tr>
                <tr><td class="text-muted">ช่างรับผิดชอบ:</td><td><?= htmlspecialchars($repair['tech_name'] ?? 'ยังไม่ระบุ') ?></td></tr>
                <tr><td class="text-muted">เบอร์โทรช่าง:</td><td><?= htmlspecialchars($repair['tech_phone'] ?? '-') ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Equipment & Issue Details -->
    <div class="border rounded p-3 mb-4">
        <h6 class="fw-bold border-bottom pb-2 mb-3">1. ข้อมูลครุภัณฑ์ / สถานที่แจ้งซ่อม</h6>
        <div class="row g-2 mb-2">
            <div class="col-6"><strong>รหัสครุภัณฑ์:</strong> <?= htmlspecialchars($repair['eq_code'] ?? 'ไม่ระบุ') ?></div>
            <div class="col-6"><strong>ชื่อครุภัณฑ์:</strong> <?= htmlspecialchars($repair['eq_name'] ?? 'แจ้งซ่อมสถานที่ทั่วไป') ?></div>
            <div class="col-6"><strong>Serial No:</strong> <?= htmlspecialchars($repair['eq_sn'] ?? '-') ?></div>
            <div class="col-6"><strong>สถานที่ตั้ง:</strong> <?= htmlspecialchars($repair['location'] ?? '-') ?></div>
        </div>
    </div>

    <div class="border rounded p-3 mb-4">
        <h6 class="fw-bold border-bottom pb-2 mb-3">2. รายละเอียดปัญหาและอาการชำรุด</h6>
        <div class="mb-2"><strong>หัวข้อปัญหา:</strong> <?= htmlspecialchars($repair['problem_title']) ?></div>
        <div class="text-muted"><strong>รายละเอียดอาการ:</strong></div>
        <div class="p-2 bg-light rounded mt-1"><?= nl2br(htmlspecialchars($repair['problem_description'])) ?></div>
    </div>

    <div class="border rounded p-3 mb-4">
        <h6 class="fw-bold border-bottom pb-2 mb-3">3. ผลการตรวจสอบและการดำเนินงานของช่าง</h6>
        <div class="mb-2"><strong>ผลการซ่อม:</strong> <?= nl2br(htmlspecialchars($repair['repair_solution'] ?? 'อยู่ระหว่างดำเนินการ')) ?></div>
        <div class="mt-2"><strong>ค่าใช้จ่าย/ค่าอะไหล่รวม:</strong> <span class="fw-bold"><?= format_currency($repair['total_cost']) ?></span></div>
    </div>

    <!-- Signatures -->
    <div class="d-flex justify-content-between mt-5 pt-4">
        <div class="text-center">
            <div class="signature-box">(........................................................)</div>
            <div class="small mt-1">ผู้แจ้งซ่อม / ผู้ตรวจรับมอบงาน</div>
            <div class="small text-muted">วันที่ ....../....../......</div>
        </div>
        <div class="text-center">
            <div class="signature-box">(........................................................)</div>
            <div class="small mt-1">ช่างผู้ดำเนินการซ่อม</div>
            <div class="small text-muted">วันที่ ....../....../......</div>
        </div>
    </div>
</div>

</body>
</html>
