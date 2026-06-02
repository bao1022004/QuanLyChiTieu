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
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $date = $_POST['date'];
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
    $note = trim($_POST['note'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($amount === false || $category_id === false || empty($date)) {
        $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Đã xảy ra lỗi về định dạng dữ liệu (ví dụ: nhập số tiền không đúng).'];
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO transaction (amount, date, note, user_id, category_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$amount, $date, $note, $user_id, $category_id]);
            
            log_db_activity('INFO', "User {$user_id} đã thêm khoản chi: {$amount} vào ngày {$date} (Category ID: {$category_id})");
            $_SESSION['flash_messages'][] = ['type' => 'success', 'message' => 'Đã thêm khoản chi thành công!'];
        } catch (PDOException $e) {
            $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Đã xảy ra lỗi hệ thống.'];
        }
    }
}

log_access($start_time);
header('Location: ../dashboard.php');
exit;
?>
