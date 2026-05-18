# Multi-Vendor Marketplace

Laravel API with a Next.js frontend for a multi-vendor e-commerce marketplace.

## Stack

- Backend: Laravel 11 API
- Auth: Laravel Sanctum
- Frontend: Next.js
- Database: PostgreSQL
- Cache: Redis
- DevOps: Docker

## Setup

```bash
cd C:\Users\dell\marketplace
docker-compose up -d --build
```

Inside the app container:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Frontend:

```bash
cd C:\Users\dell\marketplace\marketplace-frontend
npm install
npm run dev
```

## Demo Accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@marketplace.com | Admin@12345 |
| Vendor | vendor@marketplace.com | Vendor@12345 |
| Customer | customer@marketplace.com | Customer@12345 |

## Week 1 Features

- Docker project setup.
- Database schema for users, vendors, categories, products, inventory, carts, orders, and messages.
- Register and login with Sanctum tokens.
- Email verification and password reset endpoints.
- Role-based middleware for admin, vendor, and customer.

## Week 2 Features

- Admin can list, view, approve, reject, suspend, and reactivate vendors.
- Vendor can view dashboard stats.
- Vendor can view and update store profile.
- Vendor can create, list, view, update, and delete products.
- Product management supports categories, images, variants, SKU, price, status, and stock.
- Public users can browse active products.
- Basic frontend UI includes product cards, login panel, vendor dashboard, product form, and admin vendor panel.
- Postman collection includes Week 2 endpoints.

## API Endpoints

### Public

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/health` | API health check |
| POST | `/api/auth/register` | Register customer or vendor |
| POST | `/api/auth/login` | Login and get token |
| GET | `/api/categories` | List categories |
| GET | `/api/categories/{id}` | Show category |
| GET | `/api/products` | List active products |
| GET | `/api/products/{id}` | Show active product |

### Vendor

Use a vendor Bearer token.

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/vendor/dashboard` | Vendor dashboard |
| GET | `/api/vendor/profile` | Store profile |
| PUT | `/api/vendor/profile` | Update store profile |
| GET | `/api/vendor/products` | List vendor products |
| POST | `/api/vendor/products` | Create product |
| GET | `/api/vendor/products/{id}` | Show product |
| PUT | `/api/vendor/products/{id}` | Update product |
| DELETE | `/api/vendor/products/{id}` | Delete product |
| POST | `/api/vendor/products/{id}/images` | Add product images |
| DELETE | `/api/vendor/products/{id}/images/{imageId}` | Delete product image |

### Admin

Use an admin Bearer token.

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/admin/dashboard` | Admin dashboard |
| GET | `/api/admin/users` | List users |
| GET | `/api/admin/vendors` | List vendors |
| GET | `/api/admin/vendors/{id}` | Show vendor |
| PATCH | `/api/admin/vendors/{id}/approve` | Approve vendor |
| PATCH | `/api/admin/vendors/{id}/reject` | Reject vendor |
| PATCH | `/api/admin/vendors/{id}/suspend` | Suspend vendor |
| PATCH | `/api/admin/vendors/{id}/reactivate` | Reactivate vendor |
| POST | `/api/admin/categories` | Create category |
| PUT | `/api/admin/categories/{id}` | Update category |
| DELETE | `/api/admin/categories/{id}` | Delete category |

## Roadmap

- [x] Week 1 - Docker, Auth, DB Schema
- [x] Week 2 - Vendor and Product Management + Basic Frontend UI
- [ ] Week 3 - Cart, Checkout, Orders
- [ ] Week 4 - Inventory, CRM Messaging
- [ ] Week 5 - Admin Dashboard, Analytics
- [ ] Week 6 - Stripe, Notifications, Reviews
