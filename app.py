from flask import Flask, jsonify
from config import Config
from extensions import db, bcrypt, jwt
from flask_cors import CORS
from sqlalchemy import text

def create_app():
    app = Flask(__name__)
    app.config.from_object(Config)

    # Initialize extensions
    db.init_app(app)
    bcrypt.init_app(app)
    jwt.init_app(app)

    # Enable CORS if needed
    # CORS(app, resources={r"/*": {"origins": app.config["CORS_ORIGINS"]}})

    # Import blueprints
    from routes.auth_routes import auth_bp
    from routes.category_routes import category_bp
    from routes.product_routes import product_bp

    app.register_blueprint(auth_bp, url_prefix="/api/auth")
    app.register_blueprint(category_bp, url_prefix="/api/categories")
    app.register_blueprint(product_bp, url_prefix="/api/products")

    # Root endpoint
    @app.route("/")
    def home():
       return jsonify({
           "message": "Inventory API Running!",
           "version": "1.0.0",
           "endpoints": {
               "auth": "/api/auth",
               "categories": "/api/categories",
               "products": "/api/products"
           }
       })
    
    # Health check endpoint
    @app.route("/health")
    def health():
        try:
            # Test database connection
            db.session.execute(text("SELECT 1"))
            return jsonify({
                "status": "healthy",
                "database": "connected"
            }), 200
        except Exception as e:
            return jsonify({
                "status": "unhealthy",
                "database": "disconnected",
                "error": str(e)
            }), 503

    # Automatic session cleanup
    @app.after_request
    def cleanup_session(response):
        try:
            db.session.remove()
        except Exception:
            pass
        return response

    # Global error handler
    @app.errorhandler(404)
    def not_found(error):
        return jsonify({"error": "Resource Not Found"}), 404
    
    @app.errorhandler(500)
    def internal_error(error):
        db.session.rollback()
        return jsonify({"error": "Internal Server Error"}), 500
    
    @app.errorhandler(405)
    def method_not_allowed(error):
        return jsonify({"error": "Method Not Allowed"}), 405
    
    # JWT error handlers
    @jwt.unauthorized_loader
    def unauthorized_callback(callback):
        return jsonify({
            "error": "Missing or invalid authorization token",
            "message": "Please provide a valid access token"
        }), 401
    
    @jwt.invalid_token_loader
    def invalid_token_callback(callback):
        return jsonify({
            "error": "Invalid token",
            "message": "The token is invalid or has been tampered with"
        }), 401
    
    @jwt.expired_token_loader
    def expired_token_callback(jwt_header, jwt_payload):
        return jsonify({
            "error": "Token has expired",
            "message": "Please refresh your token or login again"
        }), 401
    
    @jwt.revoked_token_loader
    def revoked_token_callback(jwt_header, jwt_payload):
        return jsonify({
            "error": "Token has been revoked",
            "message": "Please login again"
        }), 401
    
    @jwt.needs_fresh_token_loader
    def needs_fresh_token_callback(jwt_header, jwt_payload):
        return jsonify({
            "error": "Fresh token required",
            "message": "Please login again to access this resource"
        }), 401
    
    return app


if __name__ == "__main__":
    app = create_app()

    # Create tables if they don't exist
    with app.app_context():
        db.create_all()
        print("Database tables created successfully!")
        
    app.run(debug=True)