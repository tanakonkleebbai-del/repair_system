<?php
/**
 * Export Repair Reports to CSV/Excel Format
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin', 'inventory']);

$filename = "repair_report_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Add UTF-8 BOM for Thai language Excel support
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// CSV Header
fputcsv($output, [
    'รหัสใบแจ้งซ่อม',
    'วันที่แจ้งซ่อม',
    'ผู้แจ้งซ่อม',
    'แผนก/หน่วยงาน',
    'เบอร์โทรศัพท์',
    'รหัสครุภัณฑ์',
    'ชื่อครุภัณฑ์',
    'สถานที่ตั้ง/ห้อง',
    'ระดับความเร่งด่วน',
    'หัวข้อปัญหา',
    'รายละเอียดอาการเสีย',
    'สถานะงาน',
    'ช่างผู้รับผิดชอบ',
    'วันที่ซ่อมเสร็จ',
    'ค่าใช้จ่ายรวม (บาท)',
    'วิธีแก้ไข/บันทึกการซ่อม'
]);

// Query all repair records
$sql = "SELECT r.*, e.code as eq_code, e.name as eq_name, 
               u.fullname as requester_name, u.department as requester_dept, u.phone as requester_phone,
               t.fullname as tech_name 
        FROM repairs r 
        LEFT JOIN equipments e ON r.equipment_id = e.id 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN users t ON r.technician_id = t.id 
        ORDER BY r.id DESC";

$stmt = $pdo->query($sql);

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['ticket_no'],
        $row['created_at'],
        $row['requester_name'],
        $row['requester_dept'],
        $row['requester_phone'],
        $row['eq_code'] ?? 'ไม่ระบุ',
        $row['eq_name'] ?? 'แจ้งซ่อมทั่วไป',
        $row['location'],
        $row['urgent_level'],
        $row['problem_title'],
        $row['problem_description'],
        $row['status'],
        $row['tech_name'] ?? 'ยังไม่ระบุ',
        $row['completed_at'] ?? '-',
        number_format((float)$row['total_cost'], 2),
        $row['repair_solution'] ?? '-'
    ]);
}

fclose($output);
exit;
