# Selene-Shop

Selene Shop is a PHP/MySQL e-commerce website focused on accessories and jewelry shopping. It provides customer authentication, dynamic product browsing, cart and checkout workflows, payment processing with address capture, an admin dashboard for product management, and a contact channel for user support.

This project was developed as a university group project.

## Group Members

- Wesam Sanbo
- Reem AlTheeb
- Layan Alzahrani
- Shahad Algharyafe
- Amjad Alobiad
- Sarah Alsubaie
- Shahad Alqarni

## What Selene-Shop Does

- User authentication: customer signup, login, logout, and session-based account state.
- Product catalog: loads products from MySQL and shows product details by ID.
- Shopping cart: add items, update quantities, remove items, clear cart, and stock-aware validation.
- Checkout and payment: delivery address form, payment method selection, order creation, and stock deduction.
- Purchase history helper: recent purchased items are stored in a cookie per logged-in customer.
- Contact support: users can submit messages that are saved in the database.
- Admin panel: admin login and full product management (add, edit, delete, search, image upload).
- Feedback: users can submit a star rating and optional comment after placing an order.

## Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP (session-based flow)
- Database: MySQL / MariaDB (PDO)
- Local development: XAMPP or any PHP + MySQL stack

## Project Structure

```
Web20/
	Web20/
		admin.php
		cart_add.php
		checkout.php
		contact.php
		db.php
		login.php
		logout.php
		payment.php
		product.php
		shop.php
		signup.php
		style.css
		selene-web.sql
		README.md
```

## Quick Start

### 1) Database setup

1. Create a MySQL database (example name: `selene-web`).
2. Import `selene-web.sql` into your MySQL server using phpMyAdmin or MySQL CLI.
3. Ensure MySQL is running on the same port used in `db.php` (current code uses `3307`).

### 2) Configure database connection

Update `db.php` values if needed:

- Host: `localhost`
- Port: `3307`
- Database: `db_selene(1)` (as currently configured in code)
- User: `root`
- Password: empty by default in local setup

Important: the SQL file comment references `selene-web`, while code currently points to `db_selene(1)`. Make sure your imported database name matches what you set in `db.php`.

### 3) Run the project

Use one of these options:

- Option A (XAMPP/Apache): place the project in your web root and open `shop.php` in the browser.
- Option B (PHP built-in server): run from the project folder:

```bash
php -S localhost:8000
```

Then open:

- Shop: `http://localhost:8000/shop.php`
- Admin: `http://localhost:8000/admin.php`

## Main Pages and Flows

- `signup.php`: create a customer account with password hashing.
- `login.php`: authenticate customer and create session.
- `shop.php`: list products from DB and quick-add to cart.
- `product.php`: single product details and quantity selection.
- `cart_add.php`: secure cart insert with stock checks.
- `checkout.php`: cart review, quantity updates, remove, empty cart.
- `payment.php`: address + payment flow, order creation, stock update, rating.
- `contact.php`: contact form and message storage.
- `admin.php`: admin auth and product CRUD dashboard.

## Database Entities (High Level)

- `customer`, `admin`
- `product`
- `orders`, `order_items`
- `payment`
- `address`
- `feedback`
- `contact_messages`

## Notes

- Cart and login state are stored in PHP sessions.
- Product stock is validated during cart updates and again before final order creation.
- Admin credentials in the current seeded SQL are plain text values for demo purposes.
- Uploaded product images from admin are saved into the project folder.
- A per-customer cookie is used to display a short "past purchases" summary on the shop page.

## Project Status

Current implementation includes:

- Customer registration and login flows
- Dynamic product listing and detailed product view
- Session-based shopping cart with quantity and stock control
- Checkout, payment submission, and order persistence
- Post-purchase rating/feedback flow
- Contact message storage
- Admin dashboard for product management
