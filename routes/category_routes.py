from flask import Blueprint, request, jsonify
from models.category import Category
from extensions import db

category_bp = Blueprint("category", __name__)

# CREATE
@category_bp.route("/", methods=["POST"])
def create_category():
    data = request.json
    category = Category(name=data["name"])
    db.session.add(category)
    db.session.commit()
    return jsonify({"message": "Category created"}), 201


# READ ALL
@category_bp.route("/", methods=["GET"])
def get_categories():
    categories = Category.query.all()
    return jsonify([
        {"id": c.id, "name": c.name} for c in categories
    ])


# READ BY ID
@category_bp.route("/<int:id>", methods=["GET"])
def get_category(id):
    c = Category.query.get_or_404(id)
    return jsonify({"id": c.id, "name": c.name})


# UPDATE
@category_bp.route("/<int:id>", methods=["PUT"])
def update_category(id):
    c = Category.query.get_or_404(id)
    data = request.json
    c.name = data["name"]
    db.session.commit()
    return jsonify({"message": "Category updated"})


# DELETE
@category_bp.route("/<int:id>", methods=["DELETE"])
def delete_category(id):
    c = Category.query.get_or_404(id)
    db.session.delete(c)
    db.session.commit()
    return jsonify({"message": "Category deleted"})
