from extensions import db
from datetime import datetime

class Product(db.Model):
    __tablename__ = "products"

    id = db.Column(db.BigInteger, primary_key=True)
    category_id = db.Column(db.BigInteger, db.ForeignKey("categories.id"), nullable=False)
    name = db.Column(db.String(150), nullable=False)
    stock = db.Column(db.Integer, default=0)
    deleted_at = db.Column(db.DateTime, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.now)
    updated_at = db.Column(db.DateTime, default=datetime.now)

    category = db.relationship("Category", backref="products")
    