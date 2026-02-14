# Product Category Migration

This migration adds a `category` field to the `gas_tanks` table to support different product types:
- **Gas** - LPG cylinders
- **Accessories** - Gas-related accessories
- **Stove** - Cooking stoves

## Apply Migration

### Local (Laragon)
```powershell
# Navigate to your Laragon bin directory (adjust path to your MySQL version)
cd C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin

# Run the migration
.\mysql.exe -u root -p pijp < "C:\laragon\www\PIJP\migrations\add_product_category.sql"
```

### Production (Aiven MySQL)
```powershell
# From your PIJP directory
cd C:\laragon\www\PIJP

# Run using the mysql command (with your Aiven credentials)
mysql --host=<your-aiven-host> --port=<port> --user=<username> --password --ssl-mode=REQUIRED <database_name> < migrations/add_product_category.sql
```

## What Changed

1. **Database Schema**: Added `category` ENUM field to `gas_tanks` table
2. **Admin Stock Page**: Added category dropdown when adding new products
3. **Homepage**: Added search bar, category filters, and sorting options
4. **User Dashboard**: Added search bar, category filters, and sorting options
5. **Product Display**: Now shows category badge on all product cards

## After Migration

1. All existing products will have category = 'gas' by default
2. You can add new product types (Accessories, Stove) from Admin → Manage Product Stock
3. Users can filter products by category on the homepage and dashboard
4. Search functionality allows finding products by name
5. Sorting options: Newest, Name, Price (Low to High), Price (High to Low)

## Features Added

### Search Bar
- Available on homepage and user dashboard
- Searches product names
- Works in combination with category filters

### Category Filters
- **All** - Shows all products
- **Gas** - LPG cylinders only
- **Accessories** - Gas accessories only
- **Stove** - Cooking stoves only

### Sorting Options
- **Newest** - Most recently added products first (homepage)
- **Name** - Alphabetical order
- **Price: Low to High** - Cheapest first
- **Price: High to Low** - Most expensive first

### UI Improvements
- Modern search and filter bar design
- Category badges on product cards
- Responsive layout for mobile devices
- Smooth transitions and hover effects
