<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Xử lý thông báo flash
$flash_messages = $_SESSION['flash_messages'] ?? [];
unset($_SESSION['flash_messages']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Chi tiêu Cá nhân</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-indigo-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold tracking-tight">Expense Manager</a>
                </div>
                <div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-500">Dashboard</a>
                        <a href="profile.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-500">Hồ sơ</a>
                        <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium bg-red-500 hover:bg-red-400 ml-4">Đăng xuất</a>
                    <?php else: ?>
                        <a href="login.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-500">Đăng nhập</a>
                        <a href="register.php" class="px-3 py-2 rounded-md text-sm font-medium bg-green-500 hover:bg-green-400">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        <?php foreach ($flash_messages as $msg): ?>
            <?php 
                $bg_class = 'bg-yellow-100 text-yellow-800';
                if ($msg['type'] === 'success') $bg_class = 'bg-green-100 text-green-800';
                if ($msg['type'] === 'error') $bg_class = 'bg-red-100 text-red-800';
            ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?= $bg_class ?>" role="alert">
                <?= htmlspecialchars($msg['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
