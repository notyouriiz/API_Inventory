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

Update values (DATABASE_URL, JWT_SECRET_KEY).

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


## 🗄️ Database Structure

### Users
```sql
- id (BigInteger, PK)
- name (String 100)
- email (String 150, Unique)
- password_hash (Text)
- created_at (DateTime)
- updated_at (DateTime)
```

### Categories
```sql
- id (BigInteger, PK)
- name (String 100, Unique)
- created_at (DateTime)
- updated_at (DateTime)
```

### Products
```sql
- id (BigInteger, PK)
- category_id (BigInteger, FK)
- name (String 150)
- stock (Integer)
- created_at (DateTime)
- updated_at (DateTime)
```

---

## 📡 Endpoints

### General Endpoints

#### 1. Root Endpoint
```
GET /
```

**Success Response (200)**
```json
{
  "message": "Inventory API Running!",
  "version": "1.0.0",
  "endpoints": {
    "auth": "/api/auth",
    "categories": "/api/categories",
    "products": "/api/products"
  }
}
```

#### 2. Health Check
```
GET /health
```

**Success Response (200)**
```json
{
  "status": "healthy",
  "database": "connected"
}
```

**Failed Response (503)**
```json
{
  "status": "unhealthy",
  "database": "disconnected",
  "error": "Connection refused"
}
```

---

### Authentication Endpoints

#### 1. Register User
```
POST /api/auth/register
```

**Request Body**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Success Response (201)**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Failed Response - Email Already Registered (409)**
```json
{
  "error": "Email already registered"
}
```

**Failed Response - Incomplete Data (400)**
```json
{
  "error": "Name, email, and password are required"
}
```

**Failed Response - Invalid Email (400)**
```json
{
  "error": "Invalid email format"
}
```

**Failed Response - Password Too Short (400)**
```json
{
  "error": "Password must be at least 6 characters"
}
```

#### 2. Login
```
POST /api/auth/login
```

**Request Body**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Success Response (200)**
```json
{
  "message": "Login successful",
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Failed Response - Wrong Email/Password (401)**
```json
{
  "error": "Invalid email or password"
}
```

**Failed Response - Incomplete Data (400)**
```json
{
  "error": "Email and password are required"
}
```

#### 3. Refresh Token
```
POST /api/auth/refresh
Headers: Authorization: Bearer <refresh_token>
```

**Success Reponse (200)**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Failed Response - Invalid Token (401)**
```json
{
  "error": "Invalid token",
  "message": "The token is invalid or has been tampered with"
}
```

**Failed Response - Expired Token (401)**
```json
{
  "error": "Token has expired",
  "message": "Please refresh your token or login again"
}
```
---

### 4. Get Current User Profile
```
GET /api/auth/me
Headers: Authorization: Bearer <access_token>
```

**Success Response (200)**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2025-01-15T10:30:00",
  "updated_at": "2025-01-15T10:30:00"
}
```

**Failed Response - Missing Token (401)**
```json
{
  "error": "Missing or invalid authorization token",
  "message": "Please provide a valid access token"
}
```

**Failed Response - Invalid Token (401)**
```json
{
  "error": "Invalid token",
  "message": "The token is invalid or has been tampered with"
}
```

**Failed Response - Expired Token (401)**
```json
{
  "error": "Token has expired",
  "message": "Please refresh your token or login again"
}
```

**Failed Response - User Not Found (404)**
```json
{
  "error": "Failed to get user: 404 Not Found: The requested URL was not found on the server."
}
```

---

### 5. Update Current User Profile
```
PUT /api/auth/me
Headers: Authorization: Bearer <access_token>
```

**Request Body (All fields are optional)**
```json
{
  "name": "John Updated",
  "email": "johnupdated@example.com",
  "password": "NewSecurePass123"
}
```

**Example - Update Name Only**
```json
{
  "name": "John Smith"
}
```

**Example - Update Email Only**
```json
{
  "email": "johnsmith@example.com"
}
```

**Example - Update Password Only**
```json
{
  "password": "NewPassword456"
}
```

**Success Response (200)**
```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "name": "John Updated",
    "email": "johnupdated@example.com"
  }
}
```

**Failed Response - No Data Provided (400)**
```json
{
  "error": "No data provided"
}
```

**Failed Response - Empty Name (400)**
```json
{
  "error": "Name cannot be empty"
}
```

**Failed Response - Invalid Email Format (400)**
```json
{
  "error": "Invalid email format"
}
```

**Failed Response - Email Already Used (409)**
```json
{
  "error": "Email already in use"
}
```

**Failed Response - Password Too Short (400)**
```json
{
  "error": "Password must be at least 6 characters"
}
```

**Failed Response - Missing Token (401)**
```json
{
  "error": "Missing or invalid authorization token",
  "message": "Please provide a valid access token"
}
```

**Failed Response - Invalid Token (401)**
```json
{
  "error": "Invalid token",
  "message": "The token is invalid or has been tampered with"
}
```

**Failed Response - Expired Token (401)**
```json
{
  "error": "Token has expired",
  "message": "Please refresh your token or login again"
}
```
---

### Category Endpoints

**Note:** All category endpoints require a JWT token in the header
```
Authorization: Bearer <access_token>
```

#### 1. Create Category
```
POST /api/categories/
```

**Request Body**
```json
{
  "name": "Electronics"
}
```

**Success Response (201)**
```json
{
  "message": "Category created successfully",
  "category": {
    "id": 1,
    "name": "Electronics",
    "created_at": "2025-11-22T10:30:00",
    "updated_at": "2025-11-22T10:30:00"
  }
}
```

**Failed Response - Blank Name (400)**
```json
{
  "error": "Category name is required"
}
```

**Failed Response - Name Too Long (400)**
```json
{
  "error": "Category name too long (max 100 characters)"
}
```

**Failed Response - Category Already Exists (409)**
```json
{
  "error": "Category already exists"
}
```

**Failed Response - No Data Provided (400)**
```json
{
  "error": "No data provided"
}
```

**Failed Response - Unauthorized (401)**
```json
{
  "error": "Missing or invalid authorization token",
  "message": "Please provide a valid access token"
}
```

#### 2. Get All Categories
```
GET /api/categories/?page=1&per_page=10&search=electronics
```

**Query Parameters:**
- `page` (optional): Page (default: 1)
- `per_page` (optional): Items per page (default: 10, max: 100)
- `search` (optional): Search by name

**Success Response (200)**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Electronics",
      "created_at": "2025-11-22T10:30:00",
      "updated_at": "2025-11-22T10:30:00"
    },
    {
      "id": 2,
      "name": "Furniture",
      "created_at": "2025-11-22T11:00:00",
      "updated_at": "2025-11-22T11:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 2,
    "pages": 1,
    "has_next": false,
    "has_prev": false
  }
}
```

**Failed Response - Unauthorized (401)**
```json
{
  "error": "Missing or invalid authorization token",
  "message": "Please provide a valid access token"
}
```

**Failed Response - Server Error (500)**
```json
{
  "error": "Failed to get categories: Database connection error"
}
```

**Failed Response - category not Found (404)**
```json
{
  "error": "Category not found"
}
```

#### 3. Get Category by ID
```
GET /api/categories/1
```

**Success Response (200)**
```json
{
  "id": 1,
  "name": "Electronics",
  "created_at": "2025-11-22T10:30:00",
  "updated_at": "2025-11-22T10:30:00"
}
```

**Failed Response - Not Found (404)**
```json
{
  "error": "Category not found"
}
```

#### 4. Update Category
```
PUT /api/categories/1
```

**Request Body**
```json
{
  "name": "Updated Electronics"
}
```

**Success Response (200)**
```json
{
  "message": "Category updated successfully",
  "category": {
    "id": 1,
    "name": "Updated Electronics",
    "updated_at": "2025-11-22T12:00:00"
  }
}
```

**Failed Response - Name Already Used (409)**
```json
{
  "error": "Category name already in use"
}
```

**Failed Response - Blank Name (400)**
```json
{
  "error": "Category name is required"
}
```

**Failed Response - Not Found (404)**
```json
{
  "error": "Category not found"
}
```

#### 5. Soft Delete Category
```
DELETE /api/categories/<categories_id>
```

**Success Response (200)**
```json
{
  "message": "Category deleted successfully"
}
```

**Failed Response - Category Used Product (409)**
```json
{
  "error": "Cannot delete category. {product_count} product(s) are using this category"
}
```

**Failed Response - Not Found (404)**
```json
{
  "error": "Category not found"
}
```

#### 5. Hard Delete Category
```
DELETE /api/categories/<categories_id>/force
```
**Success Response (200)**
```json
{
  "message": "Category permanently deleted"
}
```

**Failed Response - Category Used Product (500)**
```json
{
  "error": "Failed to permanently delete category:"
}
```

**Failed Response - Not Found (404)**
```json
{
  "error": "Category not found"
}
```

**Failed Response - Category is Not Soft Deleted Yet (400)**
```json
{
  "error": "Category must be soft deleted before permanent deletion"
}
```

#### 6. Restore Delete Categories
```
DELETE /api/product/<categories_id>/restore
```

**Success Response (200)**
```json
{
            "message": "Category restored successfully",
            "category":
                "id": {category.id},
                "name": {category.name},
                "updated_at": {timestamp}
}
```

**Success Response - Category is Restored (200)**
```json
{
    "message": "Category is already active"
}
```

**Failed Response - Category Not Found (404)**
```json
{
    "error": "Category not found"
}
```

**Failed Response (500)**
```json
{
    "error": "Failed to restore category"
}
```

---

### Product Endpoints

**Note:** All product endpoints require a JWT token in the header

#### 1. Create Product
```
POST /api/products/
```

**Request Body**
```json
{
  "category_id": 1,
  "name": "Laptop Dell XPS 15",
  "stock": 50
}
```

**Success Response (201)**
```json
{
  "message": "Product created successfully",
  "product": {
    "id": 1,
    "name": "Laptop Dell XPS 15",
    "stock": 50,
    "category": {
      "id": 1,
      "name": "Electronics"
    },
    "created_at": "2025-11-22T10:30:00",
    "updated_at": "2025-11-22T10:30:00"
  }
}
```

**Failed Response - Category ID Required (400)**
```json
{
  "error": "Category ID is required"
}
```

**Failed Response - Product Name Required (400)**
```json
{
  "error": "Product name is required"
}
```

**Failed Response - Name Too Long (400)**
```json
{
  "error": "Product name too long (max 150 characters)"
}
```

**Failed Response - Invalid Stock (400)**
```json
{
  "error": "Stock must be a non-negative integer"
}
```

**Failed Response - Category not found (404)**
```json
{
  "error": "Category not found"
}
```

**Failed Response - Product Already Exists (409)**
```json
{
  "error": "Product with this name already exists in this category"
}
```

**Failed Response - No Data (400)**
```json
{
  "error": "No data provided"
}
```

#### 2. Get All Products
```
GET /api/products/?page=1&per_page=10&category_id=1&search=laptop&stock_status=low_stock&low_stock_threshold=10
```

**Query Parameters:**
- `page` (optional): Page (default: 1)
- `per_page` (optional): Items per page (default: 10, max: 100)
- `category_id` (optional): Filter by category
- `search` (optional): Search by name
- `stock_status` (optional): "in_stock", "out_of_stock", "low_stock"
- `low_stock_threshold` (optional): Batas stok rendah (default: 10)

**Success Response (200)**
```json
{
  "products": [
    {
      "id": 1,
      "name": "Laptop Dell XPS 15",
      "stock": 50,
      "category": {
        "id": 1,
        "name": "Electronics"
      },
      "created_at": "2025-11-22T10:30:00",
      "updated_at": "2025-11-22T10:30:00"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 10,
    "total": 1,
    "pages": 1,
    "has_next": false,
    "has_prev": false
  }
}
```

**Failed Response - Server Error (500)**
```json
{
  "error": "Failed to get products: Database connection error"
}
```

**Failed Response - Product not Found (404)**
```json
{
  "error": "Product not found"
}
```

#### 3. Get Product by ID
```
GET /api/products/1
```

**Success Response (200)**
```json
{
  "id": 1,
  "name": "Laptop Dell XPS 15",
  "stock": 50,
  "category": {
    "id": 1,
    "name": "Electronics"
  },
  "created_at": "2025-11-22T10:30:00",
  "updated_at": "2025-11-22T10:30:00"
}
```

**Failed Response - Not Found (404)**
```json
{
  "error": "Product not found"
}
```

#### 4. Update Product
```
PUT /api/products/1
```

**Request Body**
```json
{
  "category_id": 1,
  "name": "Laptop Dell XPS 15 Updated",
  "stock": 45
}
```

**Success Response (200)**
```json
{
  "message": "Product updated successfully",
  "product": {
    "id": 1,
    "name": "Laptop Dell XPS 15 Updated",
    "stock": 45,
    "category": {
      "id": 1,
      "name": "Electronics"
    },
    "updated_at": "2025-11-22T12:00:00"
  }
}
```

**Failed Response - No Data (400)**
```json
{
  "error": "No data provided"
}
```

**Failed Response - Category Not Found (404)**
```json
{
  "error": "Category not found"
}
```

**Failed Response - Blank Name (400)**
```json
{
  "error": "Product name cannot be empty"
}
```

**Failed Response - Name Too Long (400)**
```json
{
  "error": "Product name too long (max 150 characters)"
}
```

**Failed Response - Name Already Used (409)**
```json
{
  "error": "Product name already in use in this category"
}
```

**Failed Response - Invalid Stock (400)**
```json
{
  "error": "Stock must be a non-negative integer"
}
```

**Failed Response - Product not found (404)**
```json
{
  "error": "Product not found"
}
```
---

#### 5. Soft Delete Product

```
DELETE /api/products/<product_id>
```

**Success Response (200)**

```json
{
  "message": "Product deleted successfully"
}
```

**Success Response - Already Soft Deleted (200)**

```json
{
  "message": "Product is already soft deleted"
}
```

**Failed Response - Not Found (404)**

```json
{
  "error": "Product not found"
}
```

**Failed Response (500)**

```json
{
  "error": "Failed to delete product"
}
```

---

#### 6. Hard Delete Product

```
DELETE /api/products/<product_id>/force
```

**Success Response (200)**

```json
{
  "message": "Product permanently deleted"
}
```

**Failed Response - Not Soft Deleted Yet (400)**

```json
{
  "error": "Product must be soft deleted before permanent deletion"
}
```

**Failed Response - Not Found (404)**

```json
{
  "error": "Product not found"
}
```

**Failed Response (500)**

```json
{
  "error": "Failed to permanently delete product"
}
```

---

#### 7. Restore Product

```
PATCH /api/products/<product_id>/restore
```

**Success Response (200)**

```json
{
  "message": "Product restored successfully",
  "product": {
      "id": {product.id},
      "name": {product.name},
      "updated_at": {timestamp}
  }
}
```

**Success Response - Already Active (200)**

```json
{
  "message": "Product is already active"
}
```

**Failed Response - Not Found (404)**

```json
{
  "error": "Product not found"
}
```

**Failed Response (500)**

```json
{
  "error": "Failed to restore product"
}
```
---


#### 7. Bulk Update Stock
```
PUT /api/products/bulk-update-stock
```

**Request Body**
```json
{
  "products": [
    {
      "id": 1,
      "stock": 100
    },
    {
      "id": 2,
      "stock": 50
    },
    {
      "id": 3,
      "stock": 25
    }
  ]
}
```

**Success Response (200)**
```json
{
  "message": "Successfully updated 3 product(s)",
  "updated_count": 3,
  "errors": null
}
```

**Successful Response with Partial Error (200)**
```json
{
  "message": "Successfully updated 2 product(s)",
  "updated_count": 2,
  "errors": [
    "Product 999 not found",
    "Invalid stock value for product 3"
  ]
}
```

**Failed Response - Products Array Required (400)**
```json
{
  "error": "Products array is required"
}
```

**Failed Response - Invalid Format (400)**
```json
{
  "error": "Products must be an array"
}
```

**Failed Response - Server Error (500)**
```json
{
  "error": "Bulk update failed: Database connection error"
}
```

---

## 🔒 Error Responses (Global)

### 404 - Not Found
```json
{
  "error": "Resource Not Found"
}
```

### 405 - Method Not Allowed
```json
{
  "error": "Method Not Allowed"
}
```

### 500 - Internal Server Error
```json
{
  "error": "Internal Server Error"
}
```

### 401 - Unauthorized (Missing Token)
```json
{
  "error": "Missing or invalid authorization token",
  "message": "Please provide a valid access token"
}
```

### 401 - Invalid Token
```json
{
  "error": "Invalid token",
  "message": "The token is invalid or has been tampered with"
}
```

### 401 - Expired Token
```json
{
  "error": "Token has expired",
  "message": "Please refresh your token or login again"
}
```

### 401 - Revoked Token
```json
{
  "error": "Token has been revoked",
  "message": "Please login again"
}
```

### 401 - Fresh Token Required
```json
{
  "error": "Fresh token required",
  "message": "Please login again to access this resource"
}
```

---

## 📝 Key Notes

1. **Authentication**: Almost all endpoints (except root, health, register, and login) require a JWT token.
2. **Token Header Format**: `Authorization: Bearer <your_access_token>`
3. **Token Expiry**: 
   - Access token: 1 hour
   - Refresh token: 30 days
4. **Pagination**: Maximum 100 items per page
5. **Validation**:
   - Email must be in valid format
   - Password must be at least 6 characters
   - Category name maximum 100 characters
   - Maximum product name 150 characters
   - Stock must be a non-negative integer

---

# 🛠️ UPCOMING FEATURES

* Soft Delete
* Timestamp auto update
* UUID or Auto-ID Enhancements
* Response Logging

---

# 📧 Support

For questions or issues, open an issue in the repository or contact the development team.
