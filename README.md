# Hệ thống Quản lý Chi tiêu Cá nhân (Phiên bản PHP Native)

Dự án này là một ứng dụng Web giúp người dùng quản lý tài chính cá nhân, bao gồm các chức năng cốt lõi như quản lý người dùng, theo dõi giao dịch và xem báo cáo trực quan qua biểu đồ. Ứng dụng đã được chuyển đổi hoàn toàn sang **PHP thuần (Native PHP)** và tối ưu hóa để chạy trên môi trường **XAMPP (Apache + MySQL)**.

## Phân tích Thiết kế Tính năng

### 1. Luồng dữ liệu (Data Flow)

1.  **Người dùng Mới -> Đăng ký (`register.php`):** Thông tin được gửi qua form POST. Server mã hóa mật khẩu bằng hàm `password_hash()` (chuẩn bcrypt) siêu an toàn của PHP, lưu vào MySQL (bảng `user`), đồng thời khởi tạo các danh mục mặc định cho user.
2.  **Đăng nhập & Phiên làm việc (`login.php`):** Người dùng nhập tài khoản, server kiểm tra bằng `password_verify()`. Nếu đúng, PHP sẽ khởi tạo một Session (`$_SESSION['user_id']`) để giữ trạng thái đăng nhập trên toàn hệ thống.
3.  **Quản lý Giao dịch (`dashboard.php`, `actions/`):** Tại Dashboard, Browser gửi form đến `transaction_add.php` hoặc `transaction_delete.php`. Server sử dụng PDO để thực thi câu lệnh SQL an toàn (chống SQL Injection) và ghi vào CSDL.
4.  **Báo cáo Biểu đồ (`api/chart_data.php`):** Script này trả về dữ liệu JSON cho Chart.js. CSDL nhóm dữ liệu theo danh mục và lọc theo tháng để render biểu đồ.
5.  **Cấu hình (`profile.php`):** Cho phép User đổi tên hiển thị và điều chỉnh `monthly_limit`. Mọi thay đổi đều commit về MySQL.

### 2. Sơ đồ Kiến trúc Mới

```text
[Trình duyệt Web]
       |
       | (HTTP GET/POST + Cookies)
       v
[Apache Web Server (XAMPP)] ---> Điều hướng: index.php, dashboard.php, login.php...
       |
       |----> Xử lý Session (`session_start()`)
       |
       +---> [Logic Xử lý Nghiệp vụ: actions/, api/]
       |          |
       |          +--> Ghi Log ---> [logs/access.log, logs/app_activity.log]
       |
       | (PDO - PHP Data Objects)
       v
[MySQL Database (quanlychitieu)]
    - Bảng: user
    - Bảng: category
    - Bảng: transaction
```

## Theo dõi Luồng hoạt động & Phân tích Log

Hệ thống được thiết kế cơ chế tự ghi log trong file `config/logger.php` để theo dõi các hoạt động một cách chuyên nghiệp:

1.  **`logs/access.log` (Web Server Log)**
    *   **Mục đích:** Ghi lại mọi truy cập (HTTP Request) theo định dạng chuẩn của Nginx/Apache bao gồm IP, Username, thời gian, method, đường dẫn, HTTP status, User-Agent và thời gian xử lý (ms). Rất hữu ích cho việc giám sát lưu lượng và phát hiện truy cập bất thường.
2.  **`logs/app_activity.log` (Log Ứng dụng & CSDL)**
    *   **Mục đích:** Ghi lại các sự kiện nghiệp vụ quan trọng (Đăng nhập, đăng xuất, đổi cấu hình) và vết dữ liệu (Thêm mới tài khoản, thêm/xóa giao dịch).
    *   **Ví dụ:** `[2026-06-02 10:29:40] INFO Đã tạo user mới: admin (ID: 1)`

## Hướng dẫn Vận hành

1.  **Yêu cầu hệ thống:** Đã cài đặt phần mềm **XAMPP** (bao gồm Apache và MySQL).
2.  **Cài đặt mã nguồn:**
    *   Khởi động **Apache** và **MySQL** trên XAMPP Control Panel.
    *   Copy toàn bộ thư mục chứa mã nguồn (ví dụ: `QuanLyChiTieu`) vào thư mục `C:\xampp\htdocs\`.
3.  **Khởi tạo Cơ sở dữ liệu:**
    *   Mở trình duyệt, truy cập: `http://localhost/QuanLyChiTieu/init_db.php` để tạo các bảng CSDL tự động.
4.  **Truy cập Ứng dụng:** 
    *   Mở trình duyệt tại địa chỉ: `http://localhost/QuanLyChiTieu/`
    *   Đăng ký một tài khoản và trải nghiệm hệ thống quản lý chi tiêu.

> **Lưu ý:** Mã nguồn gốc bằng Python (Flask) đã được sao lưu cẩn thận trong thư mục `python_backup/`.
