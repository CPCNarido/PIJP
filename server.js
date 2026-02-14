const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static('public'));

// In-memory database (for simplicity)
const database = {
    items: [
        { id: 1, name: 'Small Gas Tank (5kg)', price: 15.00, stock: 50, description: 'Portable 5kg gas tank' },
        { id: 2, name: 'Medium Gas Tank (11kg)', price: 25.00, stock: 30, description: 'Standard 11kg gas tank' },
        { id: 3, name: 'Large Gas Tank (20kg)', price: 40.00, stock: 20, description: 'Industrial 20kg gas tank' }
    ],
    orders: [],
    transactions: [],
    nextItemId: 4,
    nextOrderId: 1,
    nextTransactionId: 1
};

// API Routes

// Items Management
app.get('/api/items', (req, res) => {
    res.json(database.items);
});

app.get('/api/items/:id', (req, res) => {
    const item = database.items.find(i => i.id === parseInt(req.params.id));
    if (item) {
        res.json(item);
    } else {
        res.status(404).json({ error: 'Item not found' });
    }
});

app.post('/api/items', (req, res) => {
    const { name, price, stock, description } = req.body;
    const newItem = {
        id: database.nextItemId++,
        name,
        price: parseFloat(price),
        stock: parseInt(stock),
        description
    };
    database.items.push(newItem);
    res.status(201).json(newItem);
});

app.put('/api/items/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const itemIndex = database.items.findIndex(i => i.id === id);
    
    if (itemIndex !== -1) {
        const { name, price, stock, description } = req.body;
        database.items[itemIndex] = {
            id,
            name,
            price: parseFloat(price),
            stock: parseInt(stock),
            description
        };
        res.json(database.items[itemIndex]);
    } else {
        res.status(404).json({ error: 'Item not found' });
    }
});

app.delete('/api/items/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const itemIndex = database.items.findIndex(i => i.id === id);
    
    if (itemIndex !== -1) {
        database.items.splice(itemIndex, 1);
        res.json({ message: 'Item deleted successfully' });
    } else {
        res.status(404).json({ error: 'Item not found' });
    }
});

// Orders Management
app.get('/api/orders', (req, res) => {
    res.json(database.orders);
});

app.get('/api/orders/:id', (req, res) => {
    const order = database.orders.find(o => o.id === parseInt(req.params.id));
    if (order) {
        res.json(order);
    } else {
        res.status(404).json({ error: 'Order not found' });
    }
});

app.post('/api/orders', (req, res) => {
    const { customerName, customerEmail, customerPhone, items, totalAmount } = req.body;
    
    // Validate stock availability
    for (const orderItem of items) {
        const item = database.items.find(i => i.id === orderItem.itemId);
        if (!item || item.stock < orderItem.quantity) {
            return res.status(400).json({ 
                error: `Insufficient stock for item: ${orderItem.name}` 
            });
        }
    }
    
    const newOrder = {
        id: database.nextOrderId++,
        customerName,
        customerEmail,
        customerPhone,
        items,
        totalAmount: parseFloat(totalAmount),
        status: 'Pending',
        paymentStatus: 'Unpaid',
        orderDate: new Date().toISOString()
    };
    
    // Update stock levels
    for (const orderItem of items) {
        const item = database.items.find(i => i.id === orderItem.itemId);
        if (item) {
            item.stock -= orderItem.quantity;
        }
    }
    
    // Create transaction record
    const transaction = {
        id: database.nextTransactionId++,
        orderId: newOrder.id,
        amount: parseFloat(totalAmount),
        type: 'Sale',
        date: new Date().toISOString(),
        status: 'Pending'
    };
    database.transactions.push(transaction);
    
    database.orders.push(newOrder);
    res.status(201).json(newOrder);
});

app.put('/api/orders/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const orderIndex = database.orders.findIndex(o => o.id === id);
    
    if (orderIndex !== -1) {
        const { status, paymentStatus } = req.body;
        database.orders[orderIndex].status = status || database.orders[orderIndex].status;
        database.orders[orderIndex].paymentStatus = paymentStatus || database.orders[orderIndex].paymentStatus;
        
        // Update transaction status if payment status changed
        if (paymentStatus) {
            const transaction = database.transactions.find(t => t.orderId === id);
            if (transaction) {
                transaction.status = paymentStatus === 'Paid' ? 'Completed' : 'Pending';
            }
        }
        
        res.json(database.orders[orderIndex]);
    } else {
        res.status(404).json({ error: 'Order not found' });
    }
});

// Financial/Transactions
app.get('/api/transactions', (req, res) => {
    res.json(database.transactions);
});

app.get('/api/financial/summary', (req, res) => {
    const totalRevenue = database.transactions
        .filter(t => t.status === 'Completed')
        .reduce((sum, t) => sum + t.amount, 0);
    
    const pendingRevenue = database.transactions
        .filter(t => t.status === 'Pending')
        .reduce((sum, t) => sum + t.amount, 0);
    
    res.json({
        totalRevenue,
        pendingRevenue,
        totalTransactions: database.transactions.length,
        completedTransactions: database.transactions.filter(t => t.status === 'Completed').length
    });
});

// Dashboard Statistics
app.get('/api/dashboard/stats', (req, res) => {
    const totalOrders = database.orders.length;
    const pendingOrders = database.orders.filter(o => o.status === 'Pending').length;
    const completedOrders = database.orders.filter(o => o.status === 'Completed').length;
    const totalRevenue = database.transactions
        .filter(t => t.status === 'Completed')
        .reduce((sum, t) => sum + t.amount, 0);
    const totalItems = database.items.length;
    const lowStockItems = database.items.filter(i => i.stock < 10).length;
    
    res.json({
        totalOrders,
        pendingOrders,
        completedOrders,
        totalRevenue,
        totalItems,
        lowStockItems
    });
});

// Serve HTML pages
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/admin', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

// Start server
app.listen(PORT, () => {
    console.log(`Gas Tank Management System running on http://localhost:${PORT}`);
});
