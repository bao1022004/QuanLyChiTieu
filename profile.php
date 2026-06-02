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

// Lấy thông tin user hiện tại
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $monthly_limit = filter_var($_POST['monthly_limit'], FILTER_VALIDATE_FLOAT);

    if ($monthly_limit === false && !empty($_POST['monthly_limit'])) {
        $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Hạn mức phải là một con số.'];
    } else {
        $monthly_limit = $monthly_limit !== false ? $monthly_limit : 0.0;
        $stmt = $pdo->prepare("UPDATE user SET name = ?, monthly_limit = ? WHERE id = ?");
        if ($stmt->execute([$name, $monthly_limit, $user_id])) {
            log_app_activity('INFO', "Người dùng '{$current_user['username']}' đã thay đổi cấu hình tài khoản (Limit: {$monthly_limit}).");
            $_SESSION['flash_messages'][] = ['type' => 'success', 'message' => 'Cập nhật thông tin thành công.'];
            
            // Cập nhật lại data để hiển thị
            $current_user['name'] = $name;
            $current_user['monthly_limit'] = $monthly_limit;
        } else {
            $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Có lỗi xảy ra khi cập nhật.'];
        }
    }
}

log_app_activity('INFO', "Người dùng '{$current_user['username']}' truy cập trang Profile.");

include 'includes/header.php';
?>

<div class="max-w-3xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8 border-b pb-4">Hồ sơ cá nhân</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="col-span-1 flex flex-col items-center">
            <div class="h-32 w-32 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-5xl font-bold mb-4 shadow-inner">
                <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
            </div>
            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($current_user['username']) ?></h2>
            <p class="text-gray-500 text-sm">Thành viên</p>
        </div>
        
        <div class="col-span-2">
            <form action="profile.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tên hiển thị</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($current_user['name'] ?? '') ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hạn mức chi tiêu hàng tháng (VNĐ)</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="number" step="0.01" name="monthly_limit" value="<?= htmlspecialchars($current_user['monthly_limit']) ?>" class="block w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">VNĐ</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">Đặt là 0 nếu không muốn giới hạn.</p>
                </div>
                
                <div class="pt-4 flex justify-end">
                    <button type="submit" class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Lưu Thay Đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
log_access($start_time);
?>
