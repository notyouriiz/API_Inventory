from flask import Blueprint, request, jsonify
from models.category import Category
from extensions import db
from flask_jwt_extended import jwt_required, get_jwt_identity
from datetime import datetime

category_bp = Blueprint("category", __name__)

# CREATE - Only for authenticated users
@category_bp.route("/", methods=["POST"])
@jwt_required()
def create_category():
    try:
        current_user_id = get_jwt_identity()
        data = request.get_json()
        
        # Validasi input
        if not data:
            return jsonify({"error": "No data provided"}), 400
        
        name = data.get("name", "").strip()
        
        if not name:
            return jsonify({"error": "Category name is required"}), 400
        
        if len(name) > 100:
            return jsonify({"error": "Category name too long (max 100 characters)"}), 400
        
        # Cek apakah kategori sudah ada
        existing = Category.query.filter_by(name=name).first()
        if existing:
            return jsonify({"error": "Category already exists"}), 409
        
        # Buat kategori baru
        category = Category(name=name)
        db.session.add(category)
        db.session.commit()
        
        return jsonify({
            "message": "Category created successfully",
            "category": {
                "id": category.id,
                "name": category.name,
                "created_at": category.created_at.isoformat(),
                "updated_at": category.updated_at.isoformat()
            }
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Failed to create category: {str(e)}"}), 500


# READ ALL - Public or Authenticated
@category_bp.route("/", methods=["GET"])
@jwt_required()
def get_categories():
    try:
        # Pagination
        page = request.args.get("page", 1, type=int)
        per_page = request.args.get("per_page", 10, type=int)
        
        # Limit per_page untuk mencegah load berlebihan
        if per_page > 100:
            per_page = 100
        
        # Search/filter by name
        search = request.args.get("search", "").strip()
        
        query = Category.query.filter(Category.deleted_at.is_(None))
        
        if search:
            query = query.filter(Category.name.ilike(f"%{search}%"))
        
        # Order by name
        query = query.order_by(Category.name.asc())
        
        # Pagination
        pagination = query.paginate(page=page, per_page=per_page, error_out=False)
        
        categories = [{
            "id": c.id,
            "name": c.name,
            "created_at": c.created_at.isoformat(),
            "updated_at": c.updated_at.isoformat()
        } for c in pagination.items]
        
        return jsonify({
            "categories": categories,
            "pagination": {
                "page": page,
                "per_page": per_page,
                "total": pagination.total,
                "pages": pagination.pages,
                "has_next": pagination.has_next,
                "has_prev": pagination.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({"error": f"Failed to get categories: {str(e)}"}), 500


# READ BY ID
@category_bp.route("/<int:id>", methods=["GET"])
@jwt_required()
def get_category(id):
    try:
        category = Category.query.filter(
            Category.id == id, 
            Category.deleted_at.is_(None)
            ).first()
        if not category:
            return jsonify({"error": "Category not found"}), 404
        
        return jsonify({
            "id": category.id,
            "name": category.name,
            "created_at": category.created_at.isoformat(),
            "updated_at": category.updated_at.isoformat()
        }), 200
        
    except Exception as e:
        return jsonify({"error": str(e)}), 500

# UPDATE - Only for authenticated users
@category_bp.route("/<int:id>", methods=["PUT"])
@jwt_required()
def update_category(id):
    try:
        current_user_id = get_jwt_identity()
        category = Category.query.filter(
            Category.id == id,
            Category.deleted_at.is_(None)
        ).first()
        if not category:
            return jsonify({"error": "Category not found"}), 404
        
        data = request.get_json()
        
        if not data:
            return jsonify({"error": "No data provided"}), 400
        
        name = data.get("name", "").strip()
        
        if not name:
            return jsonify({"error": "Category name is required"}), 400
        
        if len(name) > 100:
            return jsonify({"error": "Category name too long (max 100 characters)"}), 400
        
        # Cek apakah nama baru sudah digunakan kategori lain
        existing = Category.query.filter_by(name=name).first()
        if existing and existing.id != category.id:
            return jsonify({"error": "Category name already in use"}), 409
        
        category.name = name
        db.session.commit()
        
        return jsonify({
            "message": "Category updated successfully",
            "category": {
                "id": category.id,
                "name": category.name,
                "updated_at": category.updated_at.isoformat()
            }
        }), 200
        
    except Exception as e:
        db.session.rollback()
        if "not found" in str(e).lower():
            return jsonify({"error": "Category not found"}), 404
        return jsonify({"error": f"Failed to update category: {str(e)}"}), 500

# HARD DELETE - Permanently remove from database
@category_bp.route("/<int:id>/force", methods=["DELETE"])
@jwt_required()
def force_delete_category(id):
    try:
        category = Category.query.get_or_404(id)

        # Only allow hard delete if it is already soft deleted
        if category.deleted_at is None:
            return jsonify({
                "error": "Category must be soft deleted before permanent deletion"
            }), 400

        db.session.delete(category)
        db.session.commit()

        return jsonify({
            "message": "Category permanently deleted"
        }), 200

    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Failed to permanently delete category: {str(e)}"}), 500


# SOFT DELETE - Only for authenticated users
@category_bp.route("/<int:id>", methods=["DELETE"])
@jwt_required()
def delete_category(id):
    try:
        current_user_id = get_jwt_identity()
        category = Category.query.get_or_404(id)
        
        # Cek apakah ada produk yang menggunakan kategori ini
        from models.product import Product
        product_count = Product.query.filter_by(category_id=id).count()
        
        if product_count > 0:
            return jsonify({
                "error": f"Cannot delete category. {product_count} product(s) are using this category"
            }), 409
        
        # db.session.delete(category)
        category.deleted_at = datetime.utcnow() #soft delete timestamp feature
        db.session.commit()
        
        return jsonify({
            "message": "Category deleted successfully"
        }), 200
        
    except Exception as e:
        db.session.rollback()
        if "not found" in str(e).lower():
            return jsonify({"error": "Category not found"}), 404
        return jsonify({"error": f"Failed to delete category: {str(e)}"}), 500

# RESTORE - Only for authenticated users
@category_bp.route("/<int:id>/restore", methods=["PATCH"])
@jwt_required()
def restore_category(id):
    try:
        current_user_id = get_jwt_identity()

        category = Category.query.filter_by(id=id).first()

        if not category:
            return jsonify({"error": "Category not found"}), 404

        if category.deleted_at is None:
            return jsonify({"message": "Category is already active"}), 200

        category.deleted_at = None
        db.session.commit()

        return jsonify({
            "message": "Category restored successfully",
            "category": {
                "id": category.id,
                "name": category.name,
                "updated_at": category.updated_at.isoformat()
            }
        }), 200

    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Failed to restore category: {str(e)}"}), 500
