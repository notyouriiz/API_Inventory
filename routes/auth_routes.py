from flask import Blueprint, request, jsonify
from models.user import User
from extensions import db
from flask_jwt_extended import create_access_token, create_refresh_token, jwt_required, get_jwt_identity

auth_bp = Blueprint("auth", __name__)

# REGISTER
@auth_bp.route("/register", methods=["POST"])
def register():
    try:
        data = request.get_json()
        
        # Validasi input
        if not data:
            return jsonify({"error": "No data provided"}), 400
        
        name = data.get("name", "").strip()
        email = data.get("email", "").strip().lower()
        password = data.get("password", "")
        
        # Validasi field required
        if not name or not email or not password:
            return jsonify({"error": "Name, email, and password are required"}), 400
        
        # Validasi panjang password
        if len(password) < 6:
            return jsonify({"error": "Password must be at least 6 characters"}), 400
        
        # Validasi format email sederhana
        if "@" not in email or "." not in email:
            return jsonify({"error": "Invalid email format"}), 400
        
        # Cek apakah email sudah terdaftar
        if User.query.filter_by(email=email).first():
            return jsonify({"error": "Email already registered"}), 409
        
        # Buat user baru
        user = User(name=name, email=email)
        user.set_password(password)
        
        db.session.add(user)
        db.session.commit()
        
        return jsonify({
            "message": "User registered successfully",
            "user": {
                "id": user.id,
                "name": user.name,
                "email": user.email
            }
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Registration failed: {str(e)}"}), 500


# LOGIN
@auth_bp.route("/login", methods=["POST"])
def login():
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({"error": "No data provided"}), 400
        
        email = data.get("email", "").strip().lower()
        password = data.get("password", "")
        
        if not email or not password:
            return jsonify({"error": "Email and password are required"}), 400
        
        # Cari user berdasarkan email
        user = User.query.filter_by(email=email).first()
        
        if not user or not user.check_password(password):
            return jsonify({"error": "Invalid email or password"}), 401
        
        # Buat access token dan refresh token
        access_token = create_access_token(identity=str(user.id))
        refresh_token = create_refresh_token(identity=str(user.id))
        
        return jsonify({
            "message": "Login successful",
            "access_token": access_token,
            "refresh_token": refresh_token,
            "user": {
                "id": user.id,
                "name": user.name,
                "email": user.email
            }
        }), 200
        
    except Exception as e:
        return jsonify({"error": f"Login failed: {str(e)}"}), 500

# REFRESH TOKEN
@auth_bp.route("/refresh", methods=["POST"])
@jwt_required(refresh=True)
def refresh():
    try:
        current_user_id = int(get_jwt_identity())
        
        # Buat access token baru
        access_token = create_access_token(identity=str(current_user_id))
        
        return jsonify({
            "access_token": access_token
        }), 200
        
    except Exception as e:
        return jsonify({"error": f"Token refresh failed: {str(e)}"}), 500

# GET CURRENT USER PROFILE
@auth_bp.route("/me", methods=["GET"])
@jwt_required()
def get_current_user():
    try:
        current_user_id = int(get_jwt_identity())
        user = User.query.get_or_404(current_user_id)
        
        return jsonify({
            "id": user.id,
            "name": user.name,
            "email": user.email,
            "created_at": user.created_at.isoformat(),
            "updated_at": user.updated_at.isoformat()
        }), 200
        
    except Exception as e:
        return jsonify({"error": f"Failed to get user: {str(e)}"}), 500

# UPDATE CURRENT USER PROFILE
@auth_bp.route("/me", methods=["PUT"])
@jwt_required()
def update_current_user():
    try:
        current_user_id = int(get_jwt_identity())
        user = User.query.get_or_404(current_user_id)
        
        data = request.get_json()
        
        if not data:
            return jsonify({"error": "No data provided"}), 400
        
        # Update name if exists
        if "name" in data:
            name = data["name"].strip()
            if not name:
                return jsonify({"error": "Name cannot be empty"}), 400
            user.name = name
        
        # Update email if exists
        if "email" in data:
            email = data["email"].strip().lower()
            if not email or "@" not in email:
                return jsonify({"error": "Invalid email format"}), 400
            
            # Check whether the email has been used by another user
            existing_user = User.query.filter_by(email=email).first()
            if existing_user and existing_user.id != user.id:
                return jsonify({"error": "Email already in use"}), 409
            
            user.email = email
        
        # Update password if exists
        if "password" in data:
            password = data["password"]
            if len(password) < 6:
                return jsonify({"error": "Password must be at least 6 characters"}), 400
            user.set_password(password)

        db.session.commit()
        
        return jsonify({
            "message": "Profile updated successfully",
            "user": {
                "id": user.id,
                "name": user.name,
                "email": user.email
            }
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({"error": f"Update failed: {str(e)}"}), 500
