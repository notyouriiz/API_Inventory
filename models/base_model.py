from extensions import db
from datetime import datetime

class TimestampMixin:
    created_at = db.Column(db.DateTime, default=datetime.now, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.now, nullable=False)
    deleted_at = db.Column(db.DateTime, nullable=True)
    
    def touch(self):
        """Manually update the updated_at when needed"""
        self.updated_at = datetime.now()