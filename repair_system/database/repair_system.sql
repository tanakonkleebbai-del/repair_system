-- Database: repair_system
-- Charset: utf8mb4

CREATE DATABASE IF NOT EXISTS `repair_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `repair_system`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `fullname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `role` ENUM('admin', 'technician', 'inventory', 'user') NOT NULL DEFAULT 'user',
    `department` VARCHAR(100) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Equipments Table
CREATE TABLE IF NOT EXISTS `equipments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `category_id` INT DEFAULT NULL,
    `serial_number` VARCHAR(100) DEFAULT NULL,
    `location` VARCHAR(150) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `purchase_date` DATE DEFAULT NULL,
    `purchase_price` DECIMAL(12, 2) DEFAULT 0.00,
    `warranty_expire` DATE DEFAULT NULL,
    `status` ENUM('available', 'repairing', 'damaged', 'disposed') NOT NULL DEFAULT 'available',
    `image` VARCHAR(255) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Repairs Table
CREATE TABLE IF NOT EXISTS `repairs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_no` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `equipment_id` INT DEFAULT NULL,
    `location` VARCHAR(150) DEFAULT NULL,
    `urgent_level` ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    `problem_title` VARCHAR(200) NOT NULL,
    `problem_description` TEXT NOT NULL,
    `damage_image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending', 'assigned', 'in_progress', 'waiting_parts', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `technician_id` INT DEFAULT NULL,
    `assigned_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `total_cost` DECIMAL(10, 2) DEFAULT 0.00,
    `repair_solution` TEXT DEFAULT NULL,
    `repair_image` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`equipment_id`) REFERENCES `equipments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`technician_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Repair Logs Table (ประวัติการบันทึกสถานะและการซ่อม)
CREATE TABLE IF NOT EXISTS `repair_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `repair_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `action_status` ENUM('pending', 'assigned', 'in_progress', 'waiting_parts', 'completed', 'cancelled') NOT NULL,
    `comment` TEXT DEFAULT NULL,
    `spare_parts` TEXT DEFAULT NULL,
    `cost` DECIMAL(10, 2) DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`repair_id`) REFERENCES `repairs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data
-- --------------------------------------------------------

-- Insert Initial Users (Password: admin123, staff123, tech123, user123)
INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `role`, `department`, `status`) VALUES
(1, 'admin', '$2y$10$iGbIZHeiKrMtp7EvIFai0e4upmYMWUcxe20L5zkdDvsvYZkYfMuGC', 'ผู้ดูแลระบบสูงสุด (Admin)', 'admin@repairsystem.local', '081-111-1111', 'admin', 'ศูนย์เทคโนโลยีสารสนเทศ', 'active'),
(2, 'inventory', '$2y$10$RlpAaoCSixwcyhlu/SL.a.o8xol6qF9LC7T/WWWm/GUSdMb32urgW', 'สมชาย จัดการพัสดุ (Inventory)', 'inventory@repairsystem.local', '082-222-2222', 'inventory', 'งานพัสดุและอาคารสถานที่', 'active'),
(3, 'technician', '$2y$10$5KYCP./Ng6ppikrwJrxUGOBzJbx5vptkYmxN03A2o/n68nzlY20ka', 'วิชัย ช่างชำนาญการ (Technician)', 'technician@repairsystem.local', '083-333-3333', 'technician', 'หน่วยซ่อมบำรุงและบริการ', 'active'),
(4, 'user', '$2y$10$zA1NMNy9AbvaS8HHlAo.iuefLFQ5yxMoyNudvXNILkpYOaaRRLDja', 'กานดา แจ่มใส (User/Staff)', 'user@repairsystem.local', '084-444-4444', 'user', 'ฝ่ายการเงินและบัญชี', 'active');

-- Insert Initial Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'คอมพิวเตอร์และอุปกรณ์ต่อพ่วง', 'คอมพิวเตอร์ตั้งโต๊ะ, แล็ปท็อป, จอภาพ, เครื่องพิมพ์, สแกนเนอร์'),
(2, 'ระบบเครือข่ายและการสื่อสาร', 'Router, Switch, Access Point, โทรศัพท์สำนักงาน'),
(3, 'เครื่องปรับอากาศและระบายอากาศ', 'แอร์สำนักงาน, พัดลมระบายอากาศ, แอร์ห้องเซิร์ฟเวอร์'),
(4, 'อุปกรณ์ไฟฟ้าและแสงสว่าง', 'หลอดไฟ, ปลั๊กไฟ, ตู้เบรกเกอร์, เครื่องสำรองไฟ (UPS)'),
(5, 'เฟอร์นิเจอร์และสุขภัณฑ์', 'โต๊ะทำงาน, เก้าอี้สำนักงาน, ประตู, หน้าต่าง, ก๊อกน้ำ');

-- Insert Initial Equipments
INSERT INTO `equipments` (`id`, `code`, `name`, `category_id`, `serial_number`, `location`, `department`, `purchase_date`, `purchase_price`, `warranty_expire`, `status`, `note`) VALUES
(1, 'EQ-2026-0001', 'คอมพิวเตอร์ All-in-One Dell OptiPlex', 1, 'SN-DELL-89211', 'ห้อง 302 ชั้น 3', 'ฝ่ายการเงินและบัญชี', '2024-01-15', 25900.00, '2027-01-15', 'available', 'ใช้สำหรับการประมวลผลระบบบัญชี'),
(2, 'EQ-2026-0002', 'เครื่องพิมพ์เลเซอร์ HP LaserJet Pro 400', 1, 'SN-HP-44120', 'ห้องธุรการ ชั้น 2', 'ฝ่ายบริหารทั่วไป', '2023-06-10', 14500.00, '2026-06-10', 'repairing', 'พิมพ์แล้วกระดาษติดบ่อยครั้ง'),
(3, 'EQ-2026-0003', 'เครื่องปรับอากาศ Daikin Inverter 24000 BTU', 3, 'SN-DK-99081', 'ห้องประชุมใหญ่ ชั้น 4', 'ฝ่ายบริหารทั่วไป', '2022-11-20', 38900.00, '2025-11-20', 'available', 'ล้างแอร์รอบล่าสุดเมื่อ 3 เดือนก่อน'),
(4, 'EQ-2026-0004', 'Cisco Catalyst Switch 24 Port', 2, 'SN-CS-77114', 'ห้อง Server ชั้น 1', 'ศูนย์เทคโนโลยีสารสนเทศ', '2023-03-05', 45000.00, '2028-03-05', 'available', 'สวิตช์หลักสำหรับเครือข่าย LAN อาคาร A'),
(5, 'EQ-2026-0005', 'เครื่องสำรองไฟ APC Smart-UPS 1500VA', 4, 'SN-APC-33019', 'ห้องการเงิน ชั้น 3', 'ฝ่ายการเงินและบัญชี', '2023-08-12', 18500.00, '2025-08-12', 'damaged', 'แบตเตอรี่เสื่อมสภาพ ไม่จ่ายไฟสำรอง');

-- Insert Initial Repair Requests
INSERT INTO `repairs` (`id`, `ticket_no`, `user_id`, `equipment_id`, `location`, `urgent_level`, `problem_title`, `problem_description`, `status`, `technician_id`, `assigned_at`, `completed_at`, `total_cost`, `repair_solution`, `created_at`) VALUES
(1, 'RP-202608-0001', 4, 2, 'ห้องธุรการ ชั้น 2', 'high', 'เครื่องพิมพ์ดึงกระดาษไม่ขึ้นและกระดาษติด', 'เวลาสั่งพิมพ์งานเอกสารหลายแผ่น ตัว Roller ไม่ดึงกระดาษ มีเสียงดังแก๊กๆ', 'in_progress', 3, '2026-08-15 09:30:00', NULL, 650.00, 'ตรวจสอบพบชุด Pick-up Roller สึกหรอ กำลังเปลี่ยนอะไหล่ชุดใหม่', '2026-08-15 08:45:00'),
(2, 'RP-202608-0002', 4, 5, 'ห้องการเงิน ชั้น 3', 'urgent', 'เครื่องสำรองไฟดับและส่งเสียงเตือนตลอดเวลา', 'เมื่อมีไฟตก เครื่อง UPS ดับทันที ไม่สามารถสำรองไฟให้คอมพิวเตอร์ได้', 'waiting_parts', 3, '2026-08-14 10:00:00', NULL, 1800.00, 'สั่งซื้อชุดแบตเตอรี่แห้ง 12V 9Ah จำนวน 2 ก้อน รอของเข้า', '2026-08-14 09:15:00'),
(3, 'RP-202608-0003', 4, 3, 'ห้องประชุมใหญ่ ชั้น 4', 'normal', 'แอร์มีน้ำหยดลงบนโต๊ะประชุม', 'เปิดแอร์ใช้งานแล้วมีน้ำหยดลงมาจากช่องลมด้านขวา', 'completed', 3, '2026-08-10 13:00:00', '2026-08-11 11:30:00', 500.00, 'ทำการเป่าล้างท่อน้ำทิ้งที่อุดตันและตรวจเช็คระดับน้ำยาแอร์เรียบร้อย', '2026-08-10 11:20:00');

-- Insert Initial Repair Logs
INSERT INTO `repair_logs` (`repair_id`, `user_id`, `action_status`, `comment`, `spare_parts`, `cost`, `created_at`) VALUES
(1, 4, 'pending', 'แจ้งซ่อมเครื่องพิมพ์เลเซอร์', NULL, 0.00, '2026-08-15 08:45:00'),
(1, 1, 'assigned', 'มอบหมายงานให้ช่างวิชัยดำเนินการตรวจสอบ', NULL, 0.00, '2026-08-15 09:30:00'),
(1, 3, 'in_progress', 'เข้าตรวจสอบหน้างาน พบ Roller สึก กำลังเบิกอะไหล่มาเปลี่ยน', 'Pick-up Roller Kit', 650.00, '2026-08-15 10:15:00'),
(2, 4, 'pending', 'แจ้งซ่อม UPS ห้องการเงิน', NULL, 0.00, '2026-08-14 09:15:00'),
(2, 1, 'assigned', 'มอบหมายงานให้ช่างวิชัยตรวจสอบด่วน', NULL, 0.00, '2026-08-14 10:00:00'),
(2, 3, 'waiting_parts', 'วัดแรงดันแบตเตอรี่แล้วเสื่อมสภาพ สั่งซื้อแบตเตอรี่ใหม่ 2 ก้อน', 'Battery 12V 9Ah x2', 1800.00, '2026-08-14 11:30:00'),
(3, 4, 'pending', 'แจ้งปัญหาน้ำแอร์หยดห้องประชุม', NULL, 0.00, '2026-08-10 11:20:00'),
(3, 1, 'assigned', 'มอบหมายช่างวิชัยดำเนินการ', NULL, 0.00, '2026-08-10 13:00:00'),
(3, 3, 'in_progress', 'เริ่มทำการล้างท่อน้ำทิ้งและแผงคอยล์เย็น', 'น้ำยาล้างคอยล์', 500.00, '2026-08-11 09:00:00'),
(3, 3, 'completed', 'ทดสอบเปิดแอร์ 1 ชั่วโมง ไม่มีน้ำหยด ทำงานปกติ ส่งมอบงาน', NULL, 0.00, '2026-08-11 11:30:00');
