import os

BASE_DIR = os.path.abspath(os.path.dirname(__file__))

class Config:
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'a-very-secret-key-for-expense-manager'
    SQLALCHEMY_DATABASE_URI = os.environ.get('DATABASE_URL') or \
        'mysql+pymysql://root:@localhost/quanlychitieu?charset=utf8mb4'
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    
    # Thư mục chứa log
    LOG_DIR = os.path.join(BASE_DIR, 'logs')
    
    # Đảm bảo thư mục log tồn tại
    if not os.path.exists(LOG_DIR):
        os.makedirs(LOG_DIR)
