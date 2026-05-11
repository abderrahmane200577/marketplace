# 🛒 Multi-Vendor Marketplace — Backend API

> Laravel 11 · PostgreSQL · Redis · Docker · Sanctum

---

## 📁 Project Structure

```
marketplace/
├── docker-compose.yml
├── docker/
│   ├── Dockerfile
│   ├── nginx/default.conf
│   └── php/local.ini
├── .env.example
├── app/
│   ├── bootstrap/app.php              ← Middleware registration
│   ├── Http/
│   │   ├── Controllers/Auth/
│   │   │   └── AuthController.php     ← Register, Login, Logout, Verify, Reset
│   │   └── Middleware/
│   │       ├── RoleMiddleware.php      ← RBAC: role:admin,vendor,customer
│   │       └── VendorApprovedMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   └── Vendor.php
│   └── routes/
│       └── api.php
├── database/
│   ├── migrations/                    ← All 8 migration files
│   └── DatabaseSeeder.php
└── docs/
    └── marketplace-api.postman_collection.json
```

---

## 🚀 Setup (Step by Step)

### 1. Create Laravel Project

```bash
composer create-project laravel/laravel marketplace
cd marketplace
composer require laravel/sanctum
```

### 2. Copy the files from this package

Copy each file from this package into your Laravel project at the matching path shown above.

For `bootstrap/app.php`, **replace** the existing one.  
For `routes/api.php`, **replace** the existing one.

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` — the DB values are already set for Docker. If you run locally, adjust `DB_HOST=127.0.0.1`.

### 4. Start Docker

```bash
docker-compose up -d --build
```

Services started:
| Service   | URL                          |
|-----------|------------------------------|
| API       | http://localhost:8000        |
| pgAdmin   | http://localhost:5050        |
| PostgreSQL| localhost:5432               |
| Redis     | localhost:6379               |

### 5. Install dependencies & run migrations

```bash
# Enter the app container
docker exec -it marketplace_app bash

# Inside container:
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### 6. Test the API

```bash
# Health check
curl http://localhost:8000/api/health

# Login as admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@marketplace.com","password":"Admin@12345"}'
```

Import `docs/marketplace-api.postman_collection.json` into Postman for all endpoints.

---

## 🔐 Demo Accounts (after seeding)

| Role     | Email                       | Password        |
|----------|-----------------------------|-----------------|
| Admin    | admin@marketplace.com       | Admin@12345     |
| Vendor   | vendor@marketplace.com      | Vendor@12345    |
| Customer | customer@marketplace.com    | Customer@12345  |

---

## 🗄️ Database Schema

```
users ──┬── vendors ──── products ──┬── product_images
        │                           ├── product_variants
        │                           └── inventory ──── inventory_logs
        │
        ├── orders (customer) ────── order_items (vendor_id FK)
        ├── carts ──────────────── cart_items
        └── messages (sender/receiver)
```

---

## 🛣️ API Endpoints (Week 1)

### Auth (public)
| Method | Endpoint                        | Description              |
|--------|---------------------------------|--------------------------|
| POST   | `/api/auth/register`            | Register customer/vendor |
| POST   | `/api/auth/login`               | Login → returns token    |
| GET    | `/api/auth/verify-email/{token}`| Email verification       |
| POST   | `/api/auth/forgot-password`     | Send reset email         |
| POST   | `/api/auth/reset-password`      | Reset with token         |

### Auth (protected)
| Method | Endpoint                        | Description              |
|--------|---------------------------------|--------------------------|
| GET    | `/api/auth/me`                  | Current user profile     |
| POST   | `/api/auth/logout`              | Invalidate token         |
| POST   | `/api/auth/resend-verification` | Resend verify email      |

### Middleware aliases
```php
'role:admin'            // Admin only
'role:vendor'           // Vendor only
'role:customer'         // Customer only
'role:admin,vendor'     // Admin or Vendor
'vendor.approved'       // Vendor must be approved
```

---

## 📅 Roadmap

- [x] **Week 1** — Docker · Auth · DB Schema ← *you are here*
- [ ] **Week 2** — Vendor & Product Management
- [ ] **Week 3** — Cart · Checkout · Orders
- [ ] **Week 4** — Inventory · CRM Messaging
- [ ] **Week 5** — Admin Dashboard · Analytics
- [ ] **Week 6** — Stripe · Notifications · Reviews
