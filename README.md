# 📦 Inventory API Documentation (Flask + PostgreSQL/NeonDB)

This document contains the **complete and updated API documentation** for the Inventory API project.

---

## 🚀 Tech Stack

* Python + Flask
* PostgreSQL (NeonDB)
* SQLAlchemy ORM
* Flask-Migrate
* Flask-JWT-Extended (JWT Authentication)
* Postman / Thunder Client (API Testing)

---

## 📂 Project Structure

```
project/
│── app.py
│── config.py
│── .env.example
│── requirements.txt
│── README.md
│── models/
│   ├── user.py
│   ├── product.py
│   └── category.py
│── routes/
│   ├── auth.py
│   ├── product.py
│   └── category.py
│── extensions.py
```

---

## ⚙️ Environment Setup

### 1. Clone Repository

```bash
git clone https://github.com/notyouriiz/API_Inventory.git
cd API_Inventory
```

### 2. Install Dependencies

```bash
pip install -r requirements.txt
```

### 3. Create Environment File

```bash
cp .env.example .env
```

Update values (DB_URI, JWT_SECRET_KEY, etc.).

---

## ▶️ Run Server

```bash
python app.py
```

Server runs at:

```
http://127.0.0.1:5000/
```

---

# 🔐 AUTHENTICATION API

## **POST /auth/register** — Register User

### Request Body

```json
{
  "name": "User",
  "email": "user@mail.com",
  "password": "12345"
}
```

### Response

```json
{
  "message": "User registered successfully",
  "user_id": 1
}
```

---

## **POST /auth/login** — Login User

### Request Body

```json
{
  "email": "user@mail.com",
  "password": "12345"
}
```

### Response

```json
{
  "token": "<JWT_TOKEN>",
  "message": "Login successful"
}
```

---

## **GET /auth/profile** — Get User Profile (Protected)

### Headers

```
Authorization: Bearer <JWT_TOKEN>
```

### Response

```json
{
  "id": 1,
  "name": "User",
  "email": "user@mail.com",
  "created_at": "2025-11-21T10:30:00"
}
```

---

# 📦 CATEGORY API

## **POST /category** — Create Category

### Request

```json
{
  "name": "Electronics"
}
```

### Response

```json
{
  "message": "Category created ",
}
```

---

## **GET /category** — Get All Categories

### Response

```json
[
  {
    "id": 1,
    "name": "Electronics"
  }
]
```

---

## **GET /category/<id>** — Get Category by ID

### Response

```json
{
  "id": 1,
  "name": "Electronics"
}
```

---

## **PUT /category/<id>** — Update Category

### Request

```json
{
  "name": "Updated Name"
}
```

### Response

```json
{
  "message": "Category updated"
}
```

---

## **DELETE /category/<id>** — Delete Category

### Response

```json
{
  "message": "Category deleted"
}
```

---

# 🛒 PRODUCT API

## **POST /product** — Create Product

### Request

```json
{
  "name": "Laptop",
  "stock": 5,
  "category_id": 1
}
```

### Response

```json
{
  "message": "Product created",
  "product": {
    "id": 1,
    "name": "Laptop",
    "stock": 5,
    "category_id": 1
  }
}
```

---

## **GET /product** — Get All Products

```json
{
  "products": []
}
```

---

## **GET /product/<id>** — Get Product by ID

```json
{
  "id": 1,
  "name": "Laptop",
  "stock": 5,
  "category_id": 1
}
```

---

## **PUT /product/<id>** — Update Product

### Request

```json
{
  "name": "Gaming Laptop",
  "stock": 10
}
```

### Response

```json
{
  "message": "Product updated"
}
```

---

## **DELETE /product/<id>** — Delete Product

### Response

```json
{
  "message": "Product deleted"
}
```

---

# 🛠️ UPCOMING FEATURES

* Soft Delete
* Timestamp auto update
* UUID or Auto-ID Enhancements
* Response Logging

---

# 🔒 Security Best Practices

* Validate user input
* Use HTTPS in production
* Enable rate limiting
* Rotate JWT keys
* Hash passwords using bcrypt
* Keep dependencies updated

---

# 📧 Support

For questions or issues, open an issue in the repository or contact the development team.
