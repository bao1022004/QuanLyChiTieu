<?php
$start_time = microtime(true);
require_once 'config/db.php';
require_once 'config/logger.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Lấy tham số tháng
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}
list($year, $month_num) = explode('-', $month);

// Lấy danh sách giao dịch
$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name 
    FROM transaction t 
    JOIN category c ON t.category_id = c.id 
    WHERE t.user_id = ? AND YEAR(t.date) = ? AND MONTH(t.date) = ?
    ORDER BY t.date DESC
");
$stmt->execute([$user_id, $year, $month_num]);
$transactions = $stmt->fetchAll();

// Tính tổng chi tiêu
$total_expense = array_sum(array_column($transactions, 'amount'));

// Lấy danh sách danh mục cho form thêm mới
$stmt = $pdo->prepare("SELECT * FROM category WHERE user_id = ?");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();

log_app_activity('INFO', "Người dùng '{$current_user['username']}' truy cập Dashboard (Tháng $month).");

include 'includes/header.php';
?>

<div class="mb-8 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
    
    <!-- Lọc theo tháng -->
    <form method="GET" action="dashboard.php" class="flex items-center space-x-2">
        <label for="month" class="text-sm font-medium text-gray-700">Tháng:</label>
        <input type="month" id="month" name="month" value="<?= htmlspecialchars($month) ?>" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-3 py-2 border">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">Lọc</button>
    </form>
</div>

<!-- Tổng quan -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500">
        <h3 class="text-gray-500 text-sm font-medium">Tổng chi tiêu tháng</h3>
        <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($total_expense, 0, ',', '.') ?> VNĐ</p>
    </div>
    <?php 
    $limit_class = ($current_user['monthly_limit'] > 0 && $total_expense > $current_user['monthly_limit']) ? 'border-red-500' : 'border-green-500'; 
    ?>
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 <?= $limit_class ?>">
        <h3 class="text-gray-500 text-sm font-medium">Hạn mức</h3>
        <p class="text-3xl font-bold text-gray-900 mt-2">
            <?php if ($current_user['monthly_limit'] > 0): ?>
                <?= number_format($current_user['monthly_limit'], 0, ',', '.') ?> VNĐ
            <?php else: ?>
                Chưa đặt
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Form thêm chi tiêu và danh sách -->
    <div>
        <div class="bg-white shadow-md rounded-xl p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Thêm Khoản Chi</h2>
            <form action="actions/transaction_add.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Số tiền (VNĐ)</label>
                    <input type="number" step="0.01" name="amount" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ngày</label>
                    <input type="date" name="date" required value="<?= date('Y-m-d') ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                    <select name="category_id" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
                    <input type="text" name="note" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Lưu Giao Dịch
                </button>
            </form>
        </div>
        
        <div class="bg-white shadow-md rounded-xl p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Giao Dịch Gần Đây</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Danh mục</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?= date('d/m/Y', strtotime($txn['date'])) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($txn['category_name']) ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-medium"><?= number_format($txn['amount'], 0, ',', '.') ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <form action="actions/transaction_delete.php" method="POST" class="inline">
                                        <input type="hidden" name="txn_id" value="<?= $txn['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Bạn có chắc chắn muốn xóa?');">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500 text-sm">Chưa có giao dịch nào trong tháng này.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="bg-white shadow-md rounded-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Phân Tích Chi Tiêu</h2>
        <div class="relative h-96">
            <canvas id="expenseChart"></canvas>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const monthParam = new URLSearchParams(window.location.search).get('month') || '<?= $month ?>';
        
        fetch(`api/chart_data.php?month=${monthParam}`)
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('expenseChart').getContext('2d');
                
                if (data.labels.length === 0) {
                    ctx.font = "14px Arial";
                    ctx.textAlign = "center";
                    ctx.fillText("Không có dữ liệu chi tiêu", 200, 150);
                    return;
                }

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.data,
                            backgroundColor: [
                                '#4F46E5', '#EF4444', '#10B981', '#F59E0B', '#6366F1', 
                                '#EC4899', '#8B5CF6', '#14B8A6', '#F97316'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            });
    });
</script>

<?php 
include 'includes/footer.php'; 
log_access($start_time);
?>
