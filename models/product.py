from extensions import db
from models.base_model import TimestampMixin

class Product(TimestampMixin, db.Model):
    __tablename__ = "products"

    id = db.Column(db.BigInteger, primary_key=True)
    category_id = db.Column(db.BigInteger, db.ForeignKey("categories.id"), nullable=False)
    name = db.Column(db.String(150), nullable=False)
    stock = db.Column(db.Integer, default=0)

    category = db.relationship("Category", backref="products")    