-- ============================================================
-- SQL Schema for Clever Cloud MySQL Database
-- (ตัดคำสั่ง CREATE DATABASE และ USE ออกเพื่อให้ Import บน Cloud ได้ทันที)
-- ============================================================

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

-- 5. Repair Logs Table
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

-- 6. Initial Seed Data (Users: admin, inventory, technician, user)
INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `role`, `department`, `status`) VALUES
(1, 'admin', '$2y$10$iGbIZHeiKrMtp7EvIFai0e4upmYMWUcxe20L5zkdDvsvYZkYfMuGC', 'ผู้ดูแลระบบสูงสุด (Admin)', 'admin@repairsystem.local', '081-111-1111', 'admin', 'ศูนย์เทคโนโลยีสารสนเทศ', 'active'),
(2, 'inventory', '$2y$10$RlpAaoCSixwcyhlu/SL.a.o8xol6qF9LC7T/WWWm/GUSdMb32urgW', 'สมชาย จัดการพัสดุ (Inventory)', 'inventory@repairsystem.local', '082-222-2222', 'inventory', 'งานพัสดุและอาคารสถานที่', 'active'),
(3, 'technician', '$2y$10$5KYCP./Ng6ppikrwJrxUGOBzJbx5vptkYmxN03A2o/n68nzlY20ka', 'วิชัย ช่างชำนาญการ (Technician)', 'technician@repairsystem.local', '083-333-3333', 'technician', 'หน่วยซ่อมบำรุงและบริการ', 'active'),
(4, 'user', '$2y$10$zA1NMNy9AbvaS8HHlAo.iuefLFQ5yxMoyNudvXNILkpYOaaRRLDja', 'กานดา แจ่มใส (User/Staff)', 'user@repairsystem.local', '084-444-4444', 'user', 'ฝ่ายการเงินและบัญชี', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;
