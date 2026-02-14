# Gas Tank Management System

A comprehensive web-based system for managing gas tank ordering, inventory, financial transactions, and administration.

## Features

### 🛒 Customer Ordering System
- Browse available gas tank products
- Real-time stock availability
- Easy-to-use order form
- Quantity selection with stock validation
- Order summary with total calculation

### 📦 Item Management
- Add, edit, and delete gas tank products
- Track inventory levels
- Monitor low stock items
- Real-time stock updates

### 💰 Financial Management
- Transaction tracking for all orders
- Revenue reporting (total and pending)
- Payment status management
- Transaction history with detailed records

### 📊 Admin Dashboard
- Overview statistics (orders, revenue, inventory)
- Low stock alerts
- Order management with status updates
- Payment tracking
- Financial reports

## Technology Stack

- **Backend**: Node.js, Express.js
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Data Storage**: In-memory (can be easily extended to use a database)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/CPCNarido/PIJP.git
cd PIJP
```

2. Install dependencies:
```bash
npm install
```

3. Start the server:
```bash
npm start
```

4. Open your browser and navigate to:
- Customer Site: `http://localhost:3000`
- Admin Dashboard: `http://localhost:3000/admin`

## Usage

### Customer Interface
1. Navigate to the home page
2. Browse available gas tanks
3. Select quantities for desired items
4. Fill in customer information
5. Submit order

### Admin Interface
1. Navigate to `/admin`
2. View dashboard statistics
3. Manage items using the "Item Management" tab
4. Track orders in the "Order Management" tab
5. View financial reports in the "Financial Reports" tab

## API Endpoints

### Items
- `GET /api/items` - Get all items
- `GET /api/items/:id` - Get specific item
- `POST /api/items` - Create new item
- `PUT /api/items/:id` - Update item
- `DELETE /api/items/:id` - Delete item

### Orders
- `GET /api/orders` - Get all orders
- `GET /api/orders/:id` - Get specific order
- `POST /api/orders` - Create new order
- `PUT /api/orders/:id` - Update order status

### Financial
- `GET /api/transactions` - Get all transactions
- `GET /api/financial/summary` - Get financial summary

### Dashboard
- `GET /api/dashboard/stats` - Get dashboard statistics

## Project Structure

```
PIJP/
├── server.js              # Express server and API routes
├── package.json           # Project dependencies
├── .gitignore            # Git ignore rules
├── README.md             # Project documentation
└── public/               # Frontend files
    ├── index.html        # Customer ordering page
    ├── admin.html        # Admin dashboard
    ├── css/
    │   └── style.css     # Stylesheet
    └── js/
        ├── main.js       # Customer page logic
        └── admin.js      # Admin page logic
```

## Features in Detail

### Order Processing
- Validates stock availability before order confirmation
- Automatically updates inventory levels
- Creates transaction records for each order
- Tracks order and payment status

### Inventory Management
- Real-time stock level tracking
- Low stock warnings (< 10 units)
- Full CRUD operations for products
- Stock validation during ordering

### Financial Tracking
- Automatic transaction creation on order placement
- Revenue calculation based on payment status
- Separate tracking for completed and pending revenue
- Complete transaction history

## Future Enhancements

Potential improvements for the system:
- Database integration (MongoDB, PostgreSQL)
- User authentication and authorization
- Email notifications for orders
- Advanced reporting and analytics
- Multi-currency support
- Invoice generation
- Customer order history
- Delivery tracking

## License

ISC

## Author

CPCNarido