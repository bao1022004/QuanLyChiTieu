<?php
$start_time = microtime(true);
require_once 'config/logger.php';

session_start();
if (isset($_SESSION['username'])) {
    log_app_activity('INFO', "Người dùng '{$_SESSION['username']}' đã đăng xuất.");
}

session_unset();
session_destroy();
session_start();
$_SESSION['flash_messages'][] = ['type' => 'success', 'message' => 'Đã đăng xuất thành công.'];

log_access($start_time);
header('Location: login.php');
exit;
?>
