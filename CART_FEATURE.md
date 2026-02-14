# Shopping Cart Feature

## Overview
The shopping cart feature allows users to add multiple products before checking out. Users can:
- Add products to cart from product listings
- View cart with all items
- Update quantities or remove items
- Checkout all items at once with a single delivery address

## Installation

### 1. Run the Database Migration

**Option A: Using the Migration Runner (Easiest)**
1. Visit: `http://localhost/run_cart_migration.php`
2. Follow the on-screen confirmation
3. Delete `run_cart_migration.php` after successful migration

**Option B: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select your database (`lpg_system`)
3. Go to SQL tab
4. Copy contents of `migrations/add_cart.sql`
5. Execute the SQL

**Option C: Using HeidiSQL**
1. Open HeidiSQL and connect to your database
2. Open `migrations/add_cart.sql`
3. Execute the SQL

**Option D: Using MySQL command line**
```bash
mysql -u root -p lpg_system < migrations/add_cart.sql
```

### 2. Production Deployment (Aiven)
For production on Aiven MySQL:
```bash
mysql -h <your-aiven-host> -u <username> -p <database> < migrations/add_cart.sql
```

## Features

### For Users
- **Add to Cart Button**: On product cards in both homepage and user dashboard
- **Cart Badge**: Shows item count in header navigation
- **Cart Page** (`/user/cart.php`):
  - View all cart items with images
  - Update quantities with +/- buttons
  - Remove individual items
  - Clear entire cart
  - Single checkout for all items
  - Stock validation before checkout

### API Endpoints (`/api/cart.php`)
- `GET ?action=count` - Get total item count
- `GET ?action=get` - Get all cart items
- `POST ?action=add` - Add item to cart
  - `tank_id`: Product ID
  - `qty`: Quantity (default: 1)
- `POST ?action=update` - Update item quantity
  - `cart_id`: Cart item ID
  - `qty`: New quantity
- `POST ?action=remove` - Remove item from cart
  - `cart_id`: Cart item ID
- `POST ?action=clear` - Clear entire cart

### Database Schema
```sql
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gas_tank_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (gas_tank_id) REFERENCES gas_tanks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tank (user_id, gas_tank_id)
);
```

## Files Added/Modified

### New Files
- `migrations/add_cart.sql` - Database migration
- `api/cart.php` - Cart API endpoints
- `user/cart.php` - Cart view and checkout page
- `run_cart_migration.php` - Migration runner script

### Modified Files
- `partials/header.php` - Added cart icon with badge
- `user/index.php` - Added "Add to Cart" buttons
- `index.php` - Added "Add to Cart" for logged-in users
- `assets/app.js` - Added global cart count updater
- `assets/styles.css` - Added cart styles

## Usage

### Adding Items to Cart
```javascript
// From product card
<button onclick="addToCart(<?php echo $tank['id']; ?>)">
    Add to Cart
</button>
```

### Updating Cart Count
```javascript
// Automatically updates on page load
// Manual update after adding item:
if (window.updateCartCount) {
    window.updateCartCount();
}
```

### Checkout Process
1. User adds items to cart
2. Navigates to Cart page
3. Reviews items and adjusts quantities
4. Enters delivery address
5. Clicks "Proceed to Checkout"
6. System validates stock for all items
7. Creates order with all items
8. Updates stock quantities
9. Clears cart
10. Redirects to orders page

## Stock Management
- Items cannot be added if out of stock
- Quantities are validated against available stock
- Stock is locked during checkout (FOR UPDATE)
- If any item has insufficient stock, entire checkout fails
- Cart displays stock warnings

## Styling
All cart styles are in `assets/styles.css`:
- `.cart-link` - Cart navigation link
- `.cart-badge` - Item count badge
- `.cart-container` - Main cart layout
- `.cart-item` - Individual cart item card
- `.qty-control` - Quantity adjustment controls
- `.cart-summary` - Checkout summary sidebar

Responsive design with mobile breakpoints at 968px.

## Notes
- Cart is user-specific (per user_id)
- Cart persists across sessions
- Unique constraint prevents duplicate items (updates quantity instead)
- Items auto-removed if product is deleted
- Cart cleared after successful checkout
- Address autocomplete works if Google Maps API key configured
