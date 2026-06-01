# Hệ thống Quản lý Chi tiêu Cá nhân

Dự án này là một ứng dụng Web Full-Stack giúp người dùng quản lý tài chính cá nhân, bao gồm các chức năng cốt lõi như quản lý người dùng, theo dõi giao dịch và xem báo cáo trực quan qua biểu đồ. Ứng dụng đáp ứng các yêu cầu cao về cấu trúc mã nguồn (sạch sẽ), cơ chế hoạt động và theo dõi log chi tiết.

## Phân tích Thiết kế Tính năng

### 1. Luồng dữ liệu (Data Flow)

1.  **Người dùng Mới -> Đăng ký:** Thông tin (username, password) gửi từ Browser đến `/register`. Server mã hóa password bằng `werkzeug.security` (SHA256), lưu vào DB (bảng `User`), đồng thời khởi tạo các `Category` mặc định cho user.
2.  **Đăng nhập & Phiên làm việc (Session):** Người dùng nhập tài khoản, gửi đến `/login`. Server kiểm tra password (bằng hash), nếu đúng, `flask_login` sẽ tạo một session an toàn (lưu cookie trên trình duyệt) để giữ trạng thái đăng nhập.
3.  **Quản lý Giao dịch:** Tại `/dashboard`, Browser gọi API và nộp form (Add Transaction). Server tạo mới record trong bảng `Transaction` liên kết với `User` (thông qua `current_user.id`) và `Category`.
4.  **Báo cáo (Dashboard):** Giao diện gọi endpoint `/api/chart-data` để lấy dữ liệu. DB thực hiện truy vấn nhóm theo danh mục và lọc theo tháng/năm. Trả về JSON để `Chart.js` trên Client render biểu đồ.
5.  **Cấu hình:** `Profile` cho phép User điều chỉnh `monthly_limit`. Mọi thay đổi đều commit về SQLite.

### 2. Sơ đồ Luồng (Flow Diagram - Mô tả văn bản)

```
[Trình duyệt Web]
       |
       | (HTTP GET/POST + Cookies)
       v
[Flask App (main.py)] --- Đăng nhập/Phân quyền ---> [Flask-Login]
       |
       |----> Routing: /, /profile, /transaction
       |
       +---> [Logic Xử lý Nghiệp vụ & Validation]
       |          |
       |          +--> Lỗi / Exceptions ---> [Bắt lỗi @errorhandler(500)]
       |
       | (SQLAlchemy ORM)
       v
[SQLite Database (expense_db.sqlite)]
    - Bảng: User
    - Bảng: Category
    - Bảng: Transaction
```

## Theo dõi Luồng hoạt động & Phân tích Log

Hệ thống được thiết kế sử dụng thư viện `logging` của Python thông qua `logger.py` để phân tách log thành 3 tập tin riêng biệt. Điều này rất hữu ích cho quản trị viên (Admin) để dễ dàng theo dõi hệ thống.

### Hướng dẫn Đọc Log

Các file log sẽ tự động được tạo tại thư mục `logs/` trong thư mục dự án khi ứng dụng chạy và phát sinh các hoạt động.

1.  **`logs/app_activity.log` (Log Ứng dụng)**
    *   **Mục đích:** Theo dõi hành vi sử dụng ở tầng Application (Đăng nhập, truy cập trang, đổi cấu hình).
    *   **Cách phân tích:** Bạn có thể grep các từ khóa như `INFO`, `WARNING`. Ví dụ:
        *   `Người dùng 'admin' đăng nhập thành công.`
        *   `Đăng nhập thất bại cho username: 'hacker'`
        *   `Người dùng 'test1' đã thay đổi cấu hình tài khoản...`

2.  **`logs/expense_db.log` (Log CSDL)**
    *   **Mục đích:** Ghi vết (Trace) các hành động liên quan đến thay đổi hoặc truy xuất dữ liệu mạnh (Thêm/Xóa/Truy vấn phức tạp).
    *   **Cách phân tích:** Xem xét xem người dùng nào đang thêm hay xóa dữ liệu. Rất hữu ích khi dữ liệu bị mất và bạn cần tra cứu lại. Ví dụ:
        *   `Đã tạo user mới: admin (ID: 1)`
        *   `User 1 đã thêm khoản chi: 50000.0 vào ngày ...`
        *   `User 1 đã xóa giao dịch ID 5`

3.  **`logs/system_errors.log` (Log Ngoại lệ & Hệ thống)**
    *   **Mục đích:** Ghi lại mọi lỗi (Exception) để Developer debug.
    *   **Cách phân tích:** Khi có thông báo lỗi 500 trên màn hình, hãy mở file này và xem chi tiết lỗi kĩ thuật (Traceback).
    *   **Test:** Trên Dashboard có nút "Test Lỗi 500 (Giao dịch lỗi)", khi nhấn vào sẽ sinh lỗi cố ý (chia cho 0) và lỗi này sẽ được ghi chi tiết kèm Traceback vào file `system_errors.log`.

## Hướng dẫn Vận hành

1.  **Yêu cầu hệ thống:** Python 3.8+
2.  **Cài đặt thư viện:**
    ```bash
    pip install -r requirements.txt
    ```
3.  **Chạy ứng dụng:**
    ```bash
    python main.py
    ```
4.  **Truy cập:** Mở trình duyệt tại địa chỉ `http://127.0.0.1:5000`
5.  **Tài khoản:** Bấm vào nút "Đăng ký" để tạo tài khoản mới ngay trên ứng dụng và bắt đầu trải nghiệm.
