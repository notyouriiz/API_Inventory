from flask import Flask
from config import Config
from extensions import db, bcrypt, jwt

def create_app():
    app = Flask(__name__)
    app.config.from_object(Config)

    db.init_app(app)
    bcrypt.init_app(app)
    jwt.init_app(app)

    # Import blueprints
    from routes.auth_routes import auth_bp
    from routes.category_routes import category_bp
    from routes.product_routes import product_bp

    app.register_blueprint(auth_bp, url_prefix="/auth")
    app.register_blueprint(category_bp, url_prefix="/categories")
    app.register_blueprint(product_bp, url_prefix="/products")

    @app.route("/")
    def home():
        return "Inventory API Running!"

    return app


if __name__ == "__main__":
    app = create_app()
    app.run(debug=True)
