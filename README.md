# 📦 Inventory API (Flask + PostgreSQL/NeonDB)

All development work should be done on the **development** branch. Only stable, tested code should be merged to the **main** branch.

---

## 🚀 Tech Stack

- Python + Flask
- PostgreSQL (NeonDB)
- SQLAlchemy ORM
- JWT Authentication
- Postman (API Testing)

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

## 📌 Project Setup

### 1. Clone Repository

```bash
git clone https://github.com/notyouriiz/API_Inventory.git
cd API_Inventory
```

### 2. Install Dependencies

```bash
pip install -r requirements.txt
```

### 3. Configure Environment Variables

Copy `.env.example` to `.env`:

```bash
cp .env.example .env
```

Update the values according to your NeonDB credentials and JWT secret key.

---

## ▶️ Run Server

```bash
python app.py
```

The server will run at:

```
http://127.0.0.1:5000/
```

---

## 🔐 Authentication Endpoints

### Register

**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "name": "User",
  "email": "user@mail.com",
  "password": "12345"
}
```

**Response:**
```json
{
  "message": "User registered successfully",
  "user_id": 1
}
```

---

### Login

**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "email": "user@mail.com",
  "password": "12345"
}
```

**Response:**
```json
{
  "token": "<JWT_TOKEN>",
  "message": "Login successful"
}
```

---

### Profile (Protected)

**Endpoint:** `GET /auth/profile`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Response:**
```json
{
  "id": 1,
  "name": "User",
  "email": "user@mail.com",
  "created_at": "2025-11-21T10:30:00"
}
```

---

## 📦 Implemented Features

### 1. CRUD Product
- Create, Read, Update, Delete products
- Product association with categories

### 2. CRUD Category
- Create, Read, Update, Delete categories
- Category management for products

### 3. Authentication
- User registration
- User login with JWT token
- Protected endpoints with token validation
- User profile access

---

## 🛠️ Upcoming Features

1. Soft Delete - Mark records as deleted without removing data
2. Timestamp - Automatic `created_at` and `updated_at` fields
3. Auto Generate ID - UUID or auto-incrementing ID generation
4. Response Logging - Log all API responses for debugging and monitoring

---

## 🔒 Security Best Practices

- Always validate user input before processing
- Use HTTPS in production environment
- Implement rate limiting to prevent brute force attacks
- Regularly rotate JWT secret keys
- Hash passwords using strong algorithms (bcrypt recommended)
- Keep dependencies updated

---

## 📚 API Documentation

For detailed API documentation, use Postman or any API testing tool to interact with the endpoints. Import the collection file (if available) or manually create requests for each endpoint.

---

## 📧 Support

For questions or issues, please contact the development team or open an issue in the repository.