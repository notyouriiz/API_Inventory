from extensions import db
from models.base_model import TimestampMixin

class Category(TimestampMixin, db.Model):
    __tablename__ = "categories"

    id = db.Column(db.BigInteger, primary_key=True)
    name = db.Column(db.String(100), nullable=False, unique=True)