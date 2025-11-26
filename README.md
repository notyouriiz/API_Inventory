# Inventory API (Products & Categories) — Flask Version

A lightweight **Inventory Management API** built with **Flask**, providing full CRUD functionality for both **Products** and **Categories**, along with several **implicit protocols** such as fast-fail validation, auto-ID generation, timestamping, and default pagination.

---

## 🚀 Features

### ✅ Explicit Protocols (API Features)
The API provides full CRUD functionality for:
- **Products**  
- **Categories**

### 🟦 Implicit Protocols (Automatic API Behaviors)
These rules happen internally, without client requests:
1. **ID Auto-Generation** — Server generates unique IDs (UUID/integer).
2. **Automatic Timestamping** — `created_at` and `updated_at` managed by server.
3. **Fast-Fail Validation** — stops validation at the first field error.
4. **Default Pagination** — lists default to `page=1`, `limit=20`.
5. **Consistent Error Format** — unified JSON error response.
6. **Local Development Auto-Seeding** *(optional)* — DB seeds sample data when empty.

---

## 🛠 Requirements

Install Python 3.10+  
Then install dependencies:

```bash
pip install -r requirements.txt
```