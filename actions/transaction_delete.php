<?php
$start_time = microtime(true);
require_once '../config/db.php';
require_once '../config/logger.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txn_id = filter_var($_POST['txn_id'], FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if ($txn_id !== false) {
        // Kiểm tra quyền sở hữu
        $stmt = $pdo->prepare("SELECT id, amount FROM transaction WHERE id = ? AND user_id = ?");
        $stmt->execute([$txn_id, $user_id]);
        $txn = $stmt->fetch();

        if ($txn) {
            $stmt = $pdo->prepare("DELETE FROM transaction WHERE id = ?");
            if ($stmt->execute([$txn_id])) {
                log_app_activity('INFO', "User {$user_id} đã xóa giao dịch ID {$txn_id} (Số tiền: {$txn['amount']})");
                $_SESSION['flash_messages'][] = ['type' => 'success', 'message' => 'Đã xóa khoản chi thành công!'];
            } else {
                $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Lỗi khi xóa giao dịch.'];
            }
        } else {
            $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Không tìm thấy giao dịch hoặc không có quyền.'];
        }
    }
}

log_access($start_time);
header('Location: ../dashboard.php');
exit;
?>
