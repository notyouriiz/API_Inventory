from flask import Blueprint, request, jsonify
from models.product import Product
from models.category import Category
from extensions import db
from flask_jwt_extended import jwt_required, get_jwt_identity
from datetime import datetime

product_bp = Blueprint("product", __name__)

# CREATE - Only for authenticated users
@product_bp.route("/", methods=["POST"])
@jwt_required()
def create_product():
    try:
        current_user_id = get_jwt_identity()
        data = request.get_json()
        
        # Validasi input
        if not data:
            return jsonify({"error": "No data provided"}), 400
        
        category_id = data.get("category_id")
        name = data.get("name", "").strip()
        stock = data.get("stock", 0)
        
        # Validasi field required
        if not category_id:
            return jsonify({"error": "Category ID is required"}), 400
        
        if not name:
            return jsonify({"error": "Product name is required"}), 400
        
        if len(name) > 150:
            return jsonify({"error": "Product name too long (max 150 characters)"}), 400
        
        # Validasi stock
        if not isinstance(stock, int) or stock < 0:
            return jsonify({"error": "Stock must be a non-negative integer"}), 400
        
        # Pastikan kategori valid
        category = Category.query.get(category_id)
        if not category:
            return jsonify({"error": "Category not found"}), 404
        
        # Cek apakah produk dengan nama yang sama sudah ada di kategori ini
        existing = Product.query.filter_by(
            name=name, 
            category_id=category_id
        ).first()
        
        if existing:
            return jsonify({"error": "Product with this name already exists in this category"}), 409
        
        # Buat produk baru
        product = Product(
            category_id=category_id,
            name=name,
            stock=stock
        )
        
        db.session.add(product)
        db.session.commit()
        
        return jsonify({
            "message": "Product created successfully",
            "product": {
                "id": product.id,
                "name": product.name,
                "stock": product.stock,
                "category": {
                    "id": product.category.id,
                    "name": product.category.name
                },
                "created_at": product.created_at.isoformat(),
                "updated_at": product.updated_at.isoformat()
            }
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Failed to create product: {str(e)}"}), 500


# READ ALL with filter and pagination
@product_bp.route("/", methods=["GET"])
@jwt_required()
def get_products():
    try:
        # Pagination
        page = request.args.get("page", 1, type=int)
        per_page = request.args.get("per_page", 10, type=int)
        
        # Limit per_page
        if per_page > 100:
            per_page = 100
        
        # Filter by category
        category_id = request.args.get("category_id", type=int)
        
        # Search by name
        search = request.args.get("search", "").strip()
        
        # Filter by stock status
        stock_status = request.args.get("stock_status", "").lower()  # "in_stock", "out_of_stock", "low_stock"
        low_stock_threshold = request.args.get("low_stock_threshold", 10, type=int)
        
        query = Product.query.filter(Product.deleted_at.is_(None))
        
        # Apply filters
        if category_id:
            query = query.filter_by(category_id=category_id)
        
        if search:
            query = query.filter(Product.name.ilike(f"%{search}%"))
        
        if stock_status == "in_stock":
            query = query.filter(Product.stock > 0)
        elif stock_status == "out_of_stock":
            query = query.filter(Product.stock == 0)
        elif stock_status == "low_stock":
            query = query.filter(Product.stock > 0, Product.stock <= low_stock_threshold)
        
        # Order by name
        query = query.order_by(Product.name.asc())
        
        # Pagination
        pagination = query.paginate(page=page, per_page=per_page, error_out=False)
        
        products = [{
            "id": p.id,
            "name": p.name,
            "stock": p.stock,
            "category": {
                "id": p.category.id,
                "name": p.category.name
            },
            "created_at": p.created_at.isoformat(),
            "updated_at": p.updated_at.isoformat()
        } for p in pagination.items]
        
        return jsonify({
            "products": products,
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
        return jsonify({"error": f"Failed to get products: {str(e)}"}), 500


# READ BY ID
@product_bp.route("/<int:id>", methods=["GET"])
@jwt_required()
def get_product(id):
    try:
        product = Product.query.filter(
            Product.id == id, 
            Product.deleted_at.is_(None)
            ).first()
        if not product:
            return jsonify({"error": "Product not found"}), 404
        
        return jsonify({
            "id": product.id,
            "name": product.name,
            "stock": product.stock,
            "category": {
                "id": product.category.id,
                "name": product.category.name
            },
            "created_at": product.created_at.isoformat(),
            "updated_at": product.updated_at.isoformat()
        }), 200
        
    except Exception as e:
        return jsonify({"error": "Product not found"}), 404


# UPDATE - Only for authenticated users
@product_bp.route("/<int:id>", methods=["PUT"])
@jwt_required()
def update_product(id):
    try:
        current_user_id = get_jwt_identity()
        product = Product.query.get_or_404(id)
        
        data = request.get_json()
        
        if not data:
            return jsonify({"error": "No data provided"}), 400
        
        # Update category_id jika ada
        if "category_id" in data:
            category_id = data["category_id"]
            category = Category.query.get(category_id)
            if not category:
                return jsonify({"error": "Category not found"}), 404
            product.category_id = category_id
        
        # Update name jika ada
        if "name" in data:
            name = data["name"].strip()
            if not name:
                return jsonify({"error": "Product name cannot be empty"}), 400
            
            if len(name) > 150:
                return jsonify({"error": "Product name too long (max 150 characters)"}), 400
            
            # Cek apakah nama sudah digunakan produk lain di kategori yang sama
            existing = Product.query.filter_by(
                name=name, 
                category_id=product.category_id
            ).first()
            
            if existing and existing.id != product.id:
                return jsonify({"error": "Product name already in use in this category"}), 409
            
            product.name = name
        
        # Update stock jika ada
        if "stock" in data:
            stock = data["stock"]
            if not isinstance(stock, int) or stock < 0:
                return jsonify({"error": "Stock must be a non-negative integer"}), 400
            product.stock = stock
        
        db.session.commit()
        
        return jsonify({
            "message": "Product updated successfully",
            "product": {
                "id": product.id,
                "name": product.name,
                "stock": product.stock,
                "category": {
                    "id": product.category.id,
                    "name": product.category.name
                },
                "updated_at": product.updated_at.isoformat()
            }
        }), 200
        
    except Exception as e:
        db.session.rollback()
        if "not found" in str(e).lower():
            return jsonify({"error": "Product not found"}), 404
        return jsonify({"error": f"Failed to update product: {str(e)}"}), 500

# HARD DELETE - Permanently remove from database
@product_bp.route("/<int:id>/force", methods=["DELETE"])
@jwt_required()
def force_delete_product(id):
    try:
        product = Product.query.get_or_404(id)

        # Only allow hard delete if it is already soft deleted
        if product.deleted_at is None:
            return jsonify({
                "error": "Product must be soft deleted before permanent deletion"
            }), 400

        db.session.delete(product)
        db.session.commit()

        return jsonify({
            "message": "Product permanently deleted"
        }), 200

    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Failed to permanently delete product: {str(e)}"}), 500

# SOFT DELETE - Only for authenticated users
@product_bp.route("/<int:id>", methods=["DELETE"])
@jwt_required()
def delete_product(id):
    try:
        current_user_id = get_jwt_identity()
        product = Product.query.get_or_404(id)
        
        if product.deleted_at is not None:
            return jsonify(
                {"message": "Product is already soft deleted"}
            ),200
        product.deleted_at = datetime.utcnow()
        # db.session.delete(product)
        db.session.commit()
        
        return jsonify({
            "message": "Product deleted successfully"
        }), 200
        
    except Exception as e:
        db.session.rollback()
        if "not found" in str(e).lower():
            return jsonify({"error": "Product not found"}), 404
        return jsonify({"error": f"Failed to delete product: {str(e)}"}), 500

# RESTORE - Only for authenticated users
@product_bp.route("/<int:id>/restore", methods=["PATCH"])
@jwt_required()
def restore_product(id):
    try:
        current_user_id = get_jwt_identity()

        product = Product.query.filter_by(id=id).first()

        if not product:
            return jsonify({"error": "Product not found"}), 404

        if product.deleted_at is None:
            return jsonify({"message": "Product is already active"}), 200

        product.deleted_at = None
        db.session.commit()

        return jsonify({
            "message": "Product restored successfully",
            "product": {
                "id": product.id,
                "name": product.name,
                "updated_at": product.updated_at.isoformat()
            }
        }), 200

    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Failed to restore product: {str(e)}"}), 500

# BULK UPDATE STOCK - Additional endpoints for updating stock of multiple products at once 
@product_bp.route("/bulk-update-stock", methods=["PUT"])
@jwt_required()
def bulk_update_stock():
    try:
        current_user_id = get_jwt_identity()
        data = request.get_json()
        
        if not data or "products" not in data:
            return jsonify({"error": "Products array is required"}), 400
        
        products_data = data["products"]
        
        if not isinstance(products_data, list):
            return jsonify({"error": "Products must be an array"}), 400
        
        updated_count = 0
        errors = []
        
        for item in products_data:
            product_id = item.get("id")
            stock = item.get("stock")
            
            if not product_id or stock is None:
                errors.append(f"Missing id or stock for item: {item}")
                continue
            
            if not isinstance(stock, int) or stock < 0:
                errors.append(f"Invalid stock value for product {product_id}")
                continue
            
            product = Product.query.get(product_id)
            if not product:
                errors.append(f"Product {product_id} not found")
                continue
            
            product.stock = stock
            updated_count += 1
        
        db.session.commit()
        
        return jsonify({
            "message": f"Successfully updated {updated_count} product(s)",
            "updated_count": updated_count,
            "errors": errors if errors else None
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Bulk update failed: {str(e)}"}), 500
