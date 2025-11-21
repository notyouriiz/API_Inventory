from flask import Blueprint, request, jsonify
from models.product import Product
from models.category import Category
from extensions import db

product_bp = Blueprint("product", __name__)

# CREATE
@product_bp.route("/", methods=["POST"])
def create_product():
    data = request.json

    # pastikan kategori valid
    Category.query.get_or_404(data["category_id"])

    product = Product(
        category_id=data["category_id"],
        name=data["name"],
        stock=data.get("stock", 0),
    )
    db.session.add(product)
    db.session.commit()

    return jsonify({"message": "Product created"}), 201


# READ ALL
@product_bp.route("/", methods=["GET"])
def get_products():
    products = Product.query.all()
    return jsonify([
        {
            "id": p.id,
            "name": p.name,
            "category": p.category.name
        }
        for p in products
    ])


# READ BY ID
@product_bp.route("/<int:id>", methods=["GET"])
def get_product(id):
    p = Product.query.get_or_404(id)
    return jsonify({
        "id": p.id,
        "name": p.name,
        "stock": p.stock,
        "category": p.category.name
    })


# UPDATE
@product_bp.route("/<int:id>", methods=["PUT"])
def update_product(id):
    p = Product.query.get_or_404(id)
    data = request.json

    if "category_id" in data:
        Category.query.get_or_404(data["category_id"])
        p.category_id = data["category_id"]

    p.name = data.get("name", p.name)
    p.stock = data.get("stock", p.stock)

    db.session.commit()
    return jsonify({"message": "Product updated"})


# DELETE
@product_bp.route("/<int:id>", methods=["DELETE"])
def delete_product(id):
    p = Product.query.get_or_404(id)
    db.session.delete(p)
    db.session.commit()
    return jsonify({"message": "Product deleted"})
