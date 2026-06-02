<?php
$start_time = microtime(true);
require_once '../config/db.php';
require_once '../config/logger.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
list($year, $month_num) = explode('-', $month);

// Lấy danh sách giao dịch gom nhóm theo danh mục
$stmt = $pdo->prepare("
    SELECT c.name as category_name, SUM(t.amount) as total_amount
    FROM transaction t 
    JOIN category c ON t.category_id = c.id 
    WHERE t.user_id = ? AND YEAR(t.date) = ? AND MONTH(t.date) = ?
    GROUP BY c.id, c.name
");
$stmt->execute([$user_id, $year, $month_num]);
$results = $stmt->fetchAll();

$labels = [];
$data = [];

foreach ($results as $row) {
    $labels[] = $row['category_name'];
    $data[] = (float) $row['total_amount'];
}

echo json_encode([
    'labels' => $labels,
    'data' => $data
]);

log_access($start_time);
?>
