# PHP CRUD Project

A simple PHP application with user authentication, a dashboard, a product showcase, and admin CRUD actions for users and items.

## Features
- User login and registration
- Admin and standard user roles
- Dashboard overview
- Item listing and item detail pages
- Admin can create, update, and delete users
- Admin can create, update, and delete products with image upload
- Product fields: name, short description, detailed description, image, and price

## Default admin account
- Email: admin@demo.com
- Password: admin123

## Run locally
From the project root, run:

```bash
php -S localhost:8000 -t public
```

Then open:
- http://localhost:8000/login.php

## Project structure
- `app/` - configuration, database helpers, auth helpers
- `public/` - pages served by the web server
- `uploads/items/` - uploaded item images
- `storage/database.sqlite` - SQLite database generated automatically
# Cobot
# Cobot
