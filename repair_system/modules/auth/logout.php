<?php
/**
 * User Logout Action
 */
require_once __DIR__ . '/../../config/db.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_flash('info', 'คุณได้ออกจากระบบเรียบร้อยแล้ว');
redirect('modules/auth/login.php');
