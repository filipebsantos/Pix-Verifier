# logging_config.py
import logging
from pathlib import Path
from logging.handlers import RotatingFileHandler

def setup_logging():
    base_path = Path(__file__).resolve().parent
    log_file = base_path / 'logs' / 'pix-service.log'

    logger = logging.getLogger()
    logger.setLevel(logging.DEBUG)

    consoleHandler = logging.StreamHandler()
    consoleHandler.setLevel(logging.INFO)

    fileHandler = RotatingFileHandler(log_file, maxBytes=10*1024*1024, backupCount=5, encoding='utf-8')
    fileHandler.setLevel(logging.ERROR)

    formatter = logging.Formatter('%(asctime)s [%(name)s][%(levelname)s]: %(message)s', datefmt='%d/%m/%Y %H:%M:%S')
    fileHandler.setFormatter(formatter)
    consoleHandler.setFormatter(formatter)

    logger.addHandler(fileHandler)
    logger.addHandler(consoleHandler)
