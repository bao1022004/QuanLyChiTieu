<?php
// init_db.php
require_once 'config/db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("DROP TABLE IF EXISTS transaction;");
    $pdo->exec("DROP TABLE IF EXISTS category;");
    $pdo->exec("DROP TABLE IF EXISTS user;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $sql = "
    CREATE TABLE user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(64) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(128),
        monthly_limit FLOAT DEFAULT 0.0
    );

    CREATE TABLE category (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(64) NOT NULL,
        user_id INT,
        is_default TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    );

    CREATE TABLE transaction (
        id INT AUTO_INCREMENT PRIMARY KEY,
        amount FLOAT NOT NULL,
        date DATE NOT NULL,
        note VARCHAR(256),
        user_id INT NOT NULL,
        category_id INT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
    );
    ";

    $pdo->exec($sql);
    echo "Khởi tạo cơ sở dữ liệu thành công!";
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
