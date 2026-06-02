import os
import time
import traceback
from datetime import datetime
from flask import Flask, render_template, redirect, url_for, request, flash, jsonify, abort, g
from flask_login import LoginManager, login_user, logout_user, login_required, current_user
from werkzeug.security import generate_password_hash

from config import Config
from models import db, User, Category, Transaction
from logger import app_logger, db_logger, error_logger, access_logger

app = Flask(__name__)
app.config.from_object(Config)

# Init extensions
db.init_app(app)
login_manager = LoginManager(app)
login_manager.login_view = 'login'
login_manager.login_message = "Vui lòng đăng nhập để truy cập trang này."
login_manager.login_message_category = "warning"

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

# ==================== MIDDLEWARE LOG TRUY CẬP CHUYÊN NGHIỆP ====================
@app.before_request
def start_timer():
    g.start_time = time.time()

@app.after_request
def log_request(response):
    # Tính thời gian phản hồi (ms)
    now = time.time()
    duration = round((now - g.start_time) * 1000) if hasattr(g, 'start_time') else 0
    
    # Định dạng timestamp chuẩn Nginx/Apache: [02/Jun/2026:11:41:38 +0700]
    # Lấy thông tin thời gian hiện tại giờ Việt Nam (+0700)
    timestamp = datetime.now().strftime('%d/%b/%Y:%H:%M:%S +0700')
    
    # Lấy IP Client
    ip = request.headers.get('X-Forwarded-For', request.remote_addr)
    if ip and ',' in ip:
        ip = ip.split(',')[0].strip()
        
    # Lấy Tên đăng nhập (hoặc Guest)
    username = '-'
    try:
        if current_user and current_user.is_authenticated:
            username = current_user.username
        else:
            username = 'Guest'
    except Exception:
        username = 'Guest'
        
    # Lấy Method, Path, Protocol
    method = request.method
    path = request.full_path if request.query_string else request.path
    if path.endswith('?'):
        path = path[:-1]
    protocol = request.environ.get('SERVER_PROTOCOL', 'HTTP/1.1')
    
    # Lấy Status Code và Response Size
    status = response.status_code
    size = response.headers.get('Content-Length', response.content_length or 0)
    
    # Lấy Referer và User-Agent
    referer = request.headers.get('Referer', '-')
    user_agent = request.headers.get('User-Agent', '-')
    
    # Tạo dòng log
    # Định dạng: [IP] - [Username] [[Timestamp]] "[Method] [Path] [Protocol]" [Status] [ResponseSize] "[Referer]" "[User-Agent]" - [ResponseTime]ms
    log_line = f'{ip} - {username} [{timestamp}] "{method} {path} {protocol}" {status} {size} "{referer}" "{user_agent}" - {duration}ms'
    
    access_logger.info(log_line)
    return response

# ==================== LỖI HỆ THỐNG ====================
@app.errorhandler(500)
def internal_error(error):
    # Ghi lại log lỗi
    db.session.rollback()
    error_logger.error(f"Lỗi 500 xảy ra: {str(error)}\n{traceback.format_exc()}")
    return render_template('500.html'), 500

@app.errorhandler(404)
def not_found_error(error):
    return render_template('404.html'), 404

# ==================== ROUTES XÁC THỰC ====================
@app.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        
        user = User.query.filter_by(username=username).first()
        if user and user.check_password(password):
            login_user(user)
            app_logger.info(f"Người dùng '{username}' đăng nhập thành công.")
            return redirect(url_for('dashboard'))
        
        app_logger.warning(f"Đăng nhập thất bại cho username: '{username}'")
        flash('Tên đăng nhập hoặc mật khẩu không đúng.', 'error')
        
    return render_template('auth.html', is_login=True)

@app.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
        
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        name = request.form.get('name')
        
        if User.query.filter_by(username=username).first():
            flash('Tên đăng nhập đã tồn tại.', 'error')
            return redirect(url_for('register'))
            
        user = User(username=username, name=name)
        user.set_password(password)
        
        db.session.add(user)
        db.session.commit()
        db_logger.info(f"Đã tạo user mới: {username} (ID: {user.id})")
        
        # Tạo danh mục mặc định cho user
        default_categories = ['Ăn uống', 'Di chuyển', 'Mua sắm', 'Hóa đơn', 'Giải trí']
        for cat_name in default_categories:
            cat = Category(name=cat_name, user_id=user.id, is_default=True)
            db.session.add(cat)
        db.session.commit()
        db_logger.info(f"Đã tạo các danh mục mặc định cho user ID: {user.id}")
        
        flash('Đăng ký thành công! Vui lòng đăng nhập.', 'success')
        return redirect(url_for('login'))
        
    return render_template('auth.html', is_login=False)

@app.route('/logout')
@login_required
def logout():
    app_logger.info(f"Người dùng '{current_user.username}' đã đăng xuất.")
    logout_user()
    return redirect(url_for('login'))

# ==================== DASHBOARD & CHI TIÊU ====================
@app.route('/')
@app.route('/dashboard')
@login_required
def dashboard():
    app_logger.info(f"Người dùng '{current_user.username}' truy cập Dashboard.")
    
    # Lọc giao dịch theo ngày/danh mục nếu có
    month = request.args.get('month', datetime.now().strftime('%Y-%m'))
    
    try:
        query_date = datetime.strptime(month, '%Y-%m')
    except ValueError:
        error_logger.error(f"Người dùng '{current_user.username}' nhập sai định dạng tháng: {month}")
        query_date = datetime.now()
        flash('Định dạng tháng không hợp lệ.', 'error')

    # Thực hiện truy vấn phức tạp
    db_logger.info(f"Thực hiện truy vấn lọc giao dịch tháng {month} cho user_id {current_user.id}")
    
    transactions = Transaction.query.filter(
        Transaction.user_id == current_user.id,
        db.extract('year', Transaction.date) == query_date.year,
        db.extract('month', Transaction.date) == query_date.month
    ).order_by(Transaction.date.desc()).all()
    
    total_expense = sum(t.amount for t in transactions)
    categories = Category.query.filter_by(user_id=current_user.id).all()
    
    return render_template('dashboard.html', 
                           transactions=transactions, 
                           total_expense=total_expense,
                           categories=categories,
                           current_month=month)

@app.route('/transaction/add', methods=['POST'])
@login_required
def add_transaction():
    try:
        amount = float(request.form.get('amount'))
        date_str = request.form.get('date')
        category_id = int(request.form.get('category_id'))
        note = request.form.get('note')
        
        txn_date = datetime.strptime(date_str, '%Y-%m-%d').date() if date_str else datetime.utcnow().date()
        
        txn = Transaction(amount=amount, date=txn_date, note=note, 
                          user_id=current_user.id, category_id=category_id)
        db.session.add(txn)
        db.session.commit()
        
        db_logger.info(f"User {current_user.id} đã thêm khoản chi: {amount} vào ngày {txn_date} (Category ID: {category_id})")
        flash('Đã thêm khoản chi thành công!', 'success')
    except ValueError as e:
        error_logger.error(f"Lỗi định dạng khi thêm khoản chi (User {current_user.id}): {str(e)}")
        flash('Đã xảy ra lỗi về định dạng dữ liệu (ví dụ: nhập số tiền không đúng).', 'error')
    except Exception as e:
        error_logger.error(f"Lỗi hệ thống khi thêm khoản chi (User {current_user.id}): {str(e)}")
        flash('Đã xảy ra lỗi hệ thống.', 'error')
        db.session.rollback()

    return redirect(url_for('dashboard'))

@app.route('/transaction/delete/<int:txn_id>', methods=['POST'])
@login_required
def delete_transaction(txn_id):
    txn = Transaction.query.get_or_404(txn_id)
    if txn.user_id != current_user.id:
        abort(403)
        
    try:
        db.session.delete(txn)
        db.session.commit()
        db_logger.info(f"User {current_user.id} đã xóa giao dịch ID {txn_id} (Số tiền: {txn.amount})")
        flash('Đã xóa khoản chi thành công!', 'success')
    except Exception as e:
        error_logger.error(f"Lỗi khi xóa giao dịch ID {txn_id} (User {current_user.id}): {str(e)}")
        db.session.rollback()
        flash('Lỗi khi xóa giao dịch.', 'error')
        
    return redirect(url_for('dashboard'))

# ==================== PROFILE ====================
@app.route('/profile', methods=['GET', 'POST'])
@login_required
def profile():
    app_logger.info(f"Người dùng '{current_user.username}' truy cập trang Profile.")
    
    if request.method == 'POST':
        try:
            name = request.form.get('name')
            monthly_limit = request.form.get('monthly_limit')
            
            current_user.name = name
            if monthly_limit:
                current_user.monthly_limit = float(monthly_limit)
                
            db.session.commit()
            app_logger.info(f"Người dùng '{current_user.username}' đã thay đổi cấu hình tài khoản (Limit: {current_user.monthly_limit}).")
            flash('Cập nhật thông tin thành công.', 'success')
        except ValueError as e:
            error_logger.error(f"Lỗi nhập định dạng hạn mức (User {current_user.id}): {str(e)}")
            flash('Hạn mức phải là một con số.', 'error')
            
    return render_template('profile.html')

# ==================== API ====================
@app.route('/api/chart-data')
@login_required
def chart_data():
    month = request.args.get('month', datetime.now().strftime('%Y-%m'))
    try:
        query_date = datetime.strptime(month, '%Y-%m')
    except ValueError:
        query_date = datetime.now()
        
    transactions = Transaction.query.filter(
        Transaction.user_id == current_user.id,
        db.extract('year', Transaction.date) == query_date.year,
        db.extract('month', Transaction.date) == query_date.month
    ).all()
    
    data = {}
    for txn in transactions:
        cat_name = txn.category_rel.name if txn.category_rel else "N/A"
        data[cat_name] = data.get(cat_name, 0) + txn.amount
        
    return jsonify({
        'labels': list(data.keys()),
        'data': list(data.values())
    })

# ==================== TEST ERROR ====================
@app.route('/trigger-500')
def trigger_error():
    """Route giả lập lỗi 500 khi nhấn nút Giao dịch lỗi"""
    app_logger.warning("Đang giả lập lỗi 500 (Giao dịch lỗi)...")
    # Gây lỗi chia cho 0
    result = 1 / 0
    return "This will never be reached"

def setup_database():
    with app.app_context():
        db.create_all()

if __name__ == '__main__':
    setup_database()
    app.run(debug=True, port=5000)
