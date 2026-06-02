<?php
// config/logger.php

define('LOG_DIR', __DIR__ . '/../logs');

if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
}

function write_log($file, $message) {
    $filepath = LOG_DIR . '/' . $file;
    // Tự động thêm dòng mới
    file_put_contents($filepath, $message . PHP_EOL, FILE_APPEND);
}

function log_app_activity($level, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "{$timestamp} {$level} {$message}";
    write_log('app_activity.log', $log_line);
}

function log_access($start_time) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    
    $username = $_SESSION['username'] ?? 'Guest';
    $timestamp = date('d/M/Y:H:i:s O');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    $status = http_response_code() ?: 200;
    
    // PHP không có hàm mặc định lấy response size dễ dàng sau khi output, ta ước lượng hoặc để "-"
    $size = '-'; 
    $referer = $_SERVER['HTTP_REFERER'] ?? '-';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '-';
    
    $duration = round((microtime(true) - $start_time) * 1000);
    
    $log_line = "{$ip} - {$username} [{$timestamp}] \"{$method} {$path} {$protocol}\" {$status} {$size} \"{$referer}\" \"{$user_agent}\" - {$duration}ms";
    write_log('access.log', $log_line);
}
?>
