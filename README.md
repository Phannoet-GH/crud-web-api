# Topic 02 - CRUD Web API

A PHP CRUD web API scaffold for Topic 02.

## Structure

- `public/index.php` — request router and entry point
- `app/config/database.php` — database configuration
- `app/controllers/ProductController.php` — API controller
- `app/models/Product.php` — product model
- `app/services/ProductService.php` — business logic layer
- `app/helpers/Response.php` — JSON response helper
- `app/middleware/AuthMiddleware.php` — optional API key guard
- `app/routes/api.php` — route definitions
- `database/crud_api.sql` — initial schema file
- `.env` — environment variables

## Installation

1. Install dependencies:

```bash
composer install
```

2. Configure your database settings in `.env`. The project now uses MySQL by default, so update the following values to match your DBForge MySQL connection:

```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sv1112_db
DB_USERNAME=phannoet
DB_PASSWORD=3126
DB_CHARSET=utf8mb4
API_KEY=
```

3. Start the built-in PHP server using the public router:

```bash
php -S localhost:8000 public/router.php
```

4. Open the UI in a browser:

- `http://localhost:8000/`

5. Use the API endpoints:

- `GET /api/products`
- `GET /api/products/{id}`
- `POST /api/products`
- `PUT /api/products/{id}`
- `DELETE /api/products/{id}`

## Notes

- The API expects JSON request bodies.
- The default database driver is MySQL.
- The database name is `sv1112_db` by default.
- To manage the database with a GUI, use DBForge MySQL or any MySQL client.
- To enable API key authentication, set `API_KEY` in `.env`.
