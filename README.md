# PIJP Gas Ordering System

End-to-end gas ordering website with customer ordering and admin management.

## Features

User
- View available gas tanks and stock
- Place orders online
- Track order status and updates

Admin
- Add, update, or retire gas tanks
- Approve and update orders
- View registered users
- Review overall sales
- Log offline orders (walk-in or phone)

## Tech Stack
- HTML, CSS, JavaScript
- PHP (server-side)
- MySQL (Aiven hosted)

## Local Setup (Laragon)
1. Create a MySQL database (local or Aiven) named `pijp`.
2. Run the schema in [migrations/schema.sql](migrations/schema.sql).
3. Configure DB settings via environment variables or edit [config.php](config.php):
	- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Start Laragon and open the project in the browser.

## First Admin Account
Create the first admin directly in the database:

```sql
INSERT INTO users (name, email, password_hash, role)
VALUES ('Admin', 'admin@pijp.local', '$2y$10$replace_with_real_hash', 'admin');
```

You can generate a password hash with PHP:

```php
<?php echo password_hash('your-password', PASSWORD_DEFAULT); ?>
```

## Hosting Notes
- Database: Aiven MySQL (use the Aiven connection string values in `DB_*`)
- Backend: Render (deploy the PHP app; set environment variables in Render)
- Frontend: GitHub Pages can host a static marketing page, but the PHP app should be served from Render.

If you want a separate static landing page for GitHub Pages, we can add one.