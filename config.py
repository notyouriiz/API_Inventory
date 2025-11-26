import os
from dotenv import load_dotenv
from datetime import timedelta

load_dotenv()

class Config:
    # Database
    SQLALCHEMY_DATABASE_URI = os.getenv("DATABASE_URL")
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    SQLACHEMY_ENGINE_OPTIONS = {
        "pool_pre_ping": True,
        "pool_recycle": 380,
    }

    # JWT Configuration
    JWT_SECRET_KEY = os.getenv("JWT_SECRET_KEY", "super-secret-key")
    JWT_ACCESS_TOKEN_EXPIRES = timedelta(hours=1)
    JWT_REFRESH_TOKEN_EXPIRES = timedelta(days=30)

    # Error handling
    JWT_ERROR_MESSAGE_KEY = "error"

    # CORS (if needed)
    # CORS_ORIGINS = os.getenv("CORS_ORIGINS", "*").split(",")

    # Pagination defaults
    DEFAULT_PAGE_SIZE = 10
    MAX_PAGE_SIZE = 100