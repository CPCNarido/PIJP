# PIJP Gas Ordering System

End-to-end gas ordering website with customer ordering and admin management.

## Features

User
- View available gas tanks and stock
- Place orders online with delivery address
- Track order status and updates
- See assigned delivery rider details after approval

Admin
- Add, update, or retire gas tanks
- Manage delivery staff (add riders with name & phone)
- Approve orders and assign delivery staff
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
- Database: Aiven MySQL (use the Aiven connection string values in `DB_URL`)
- Backend: Render (use Docker with the provided `Dockerfile`)
- Frontend: GitHub Pages can host a static marketing page, but the PHP app should be served from Render.

### Google Maps Integration
To enable address autocomplete in the order form:
1. Get a Google Maps API key from [Google Cloud Console](https://cloud.google.com/console).
2. Replace `AIzaSyDummy` in [user/index.php](user/index.php) with your actual API key.
3. Redeploy to Render.

Without the API key, users can still type addresses manually.

### Render Quick Fix (PHP not found)
If your service is deploying with Node and shows `php: command not found`, switch the service to Docker:
1. Add the `Dockerfile` from this repo.
2. In Render, change Runtime to **Docker** (or create a new Docker web service).
3. Leave Build Command empty.
4. Start Command is not required (Docker CMD handles it).