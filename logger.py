import logging
import os
from logging.handlers import RotatingFileHandler
from config import Config

def setup_logger(name, log_file, level=logging.INFO):
    """Function setup logger chung cho hệ thống"""
    formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
    
    # Khởi tạo FileHandler
    file_path = os.path.join(Config.LOG_DIR, log_file)
    handler = RotatingFileHandler(file_path, maxBytes=5000000, backupCount=5, encoding='utf-8')
    handler.setFormatter(formatter)

    logger = logging.getLogger(name)
    logger.setLevel(level)
    logger.addHandler(handler)
    
    # Không propagate lên root logger để tránh ghi trùng lặp
    logger.propagate = False
    return logger

# 1. Log ứng dụng (Hoạt động user, login, view report, config)
app_logger = setup_logger('app_activity', 'app_activity.log')

# 2. Log giao dịch & Database
db_logger = setup_logger('expense_db', 'expense_db.log')

# 3. Log ngoại lệ & Hệ thống
error_logger = setup_logger('system_errors', 'system_errors.log', level=logging.ERROR)
