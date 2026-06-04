<?php
$start_time = microtime(true);
require_once 'config/db.php';
require_once 'config/logger.php';

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if (empty($username) || empty($password)) {
        $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Tên đăng nhập và mật khẩu không được để trống.'];
    } else {
        // Kiểm tra xem username đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Tên đăng nhập đã tồn tại.'];
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Thêm User mới
            $stmt = $pdo->prepare("INSERT INTO user (username, password_hash, name) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $password_hash, $name])) {
                $user_id = $pdo->lastInsertId();
                log_app_activity('INFO', "Đã tạo user mới: {$username} (ID: {$user_id})");

                // Thêm danh mục mặc định
                $default_categories = ['Ăn uống', 'Di chuyển', 'Mua sắm', 'Hóa đơn', 'Giải trí'];
                $stmt_cat = $pdo->prepare("INSERT INTO category (name, user_id, is_default) VALUES (?, ?, 1)");
                foreach ($default_categories as $cat) {
                    $stmt_cat->execute([$cat, $user_id]);
                }
                log_app_activity('INFO', "Đã tạo các danh mục mặc định cho user ID: {$user_id}");

                $_SESSION['flash_messages'][] = ['type' => 'success', 'message' => 'Đăng ký thành công! Vui lòng đăng nhập.'];
                header('Location: login.php');
                exit;
            } else {
                $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => 'Đã xảy ra lỗi hệ thống.'];
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden mt-10">
    <div class="px-6 py-8">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">Đăng ký tài khoản</h2>
        <form action="register.php" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Tên đăng nhập</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" name="username" type="text" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Tên hiển thị (Tùy chọn)</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" name="name" type="text">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Mật khẩu</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" type="password" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                    Đăng ký
                </button>
            </div>
        </form>
        <p class="text-center text-gray-600 text-sm mt-4">
            Đã có tài khoản? <a href="login.php" class="text-indigo-600 hover:underline">Đăng nhập</a>
        </p>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
log_access($start_time);
?>
