// API Base URL
const API_BASE = window.location.origin;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadDashboardStats();
    loadItems();
    loadOrders();
    loadFinancialData();
    setupTabs();
    setupForms();
});

// Setup tabs
function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.dataset.tab;
            switchTab(tabName);
        });
    });
}

// Switch tabs
function switchTab(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    // Add active class to selected tab
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(`${tabName}-tab`).classList.add('active');
}

// Load dashboard statistics
async function loadDashboardStats() {
    try {
        const response = await fetch(`${API_BASE}/api/dashboard/stats`);
        const stats = await response.json();

        document.getElementById('stat-total-orders').textContent = stats.totalOrders;
        document.getElementById('stat-pending-orders').textContent = stats.pendingOrders;
        document.getElementById('stat-total-revenue').textContent = `$${stats.totalRevenue.toFixed(2)}`;
        document.getElementById('stat-total-items').textContent = stats.totalItems;
        document.getElementById('stat-low-stock').textContent = stats.lowStockItems;
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

// Load items
async function loadItems() {
    try {
        const response = await fetch(`${API_BASE}/api/items`);
        const items = await response.json();
        displayItems(items);
    } catch (error) {
        console.error('Error loading items:', error);
        showNotification('Failed to load items', 'error');
    }
}

// Display items
function displayItems(items) {
    const tbody = document.getElementById('items-table-body');
    tbody.innerHTML = items.map(item => `
        <tr>
            <td>${item.id}</td>
            <td>${item.name}</td>
            <td>$${item.price.toFixed(2)}</td>
            <td>${item.stock}</td>
            <td>${item.description}</td>
            <td>
                <button class="btn btn-edit" onclick="editItem(${item.id})">Edit</button>
                <button class="btn btn-danger" onclick="deleteItem(${item.id})">Delete</button>
            </td>
        </tr>
    `).join('');
}

// Load orders
async function loadOrders() {
    try {
        const response = await fetch(`${API_BASE}/api/orders`);
        const orders = await response.json();
        displayOrders(orders);
    } catch (error) {
        console.error('Error loading orders:', error);
        showNotification('Failed to load orders', 'error');
    }
}

// Display orders
function displayOrders(orders) {
    const tbody = document.getElementById('orders-table-body');
    tbody.innerHTML = orders.map(order => `
        <tr>
            <td>#${order.id}</td>
            <td>${order.customerName}</td>
            <td>${order.customerEmail}</td>
            <td>${order.customerPhone}</td>
            <td>$${order.totalAmount.toFixed(2)}</td>
            <td>
                <select class="status-select" onchange="updateOrderStatus(${order.id}, 'status', this.value)">
                    <option value="Pending" ${order.status === 'Pending' ? 'selected' : ''}>Pending</option>
                    <option value="Processing" ${order.status === 'Processing' ? 'selected' : ''}>Processing</option>
                    <option value="Completed" ${order.status === 'Completed' ? 'selected' : ''}>Completed</option>
                    <option value="Cancelled" ${order.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
            </td>
            <td>
                <select class="status-select" onchange="updateOrderStatus(${order.id}, 'payment', this.value)">
                    <option value="Unpaid" ${order.paymentStatus === 'Unpaid' ? 'selected' : ''}>Unpaid</option>
                    <option value="Paid" ${order.paymentStatus === 'Paid' ? 'selected' : ''}>Paid</option>
                </select>
            </td>
            <td>${new Date(order.orderDate).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-edit" onclick="viewOrderDetails(${order.id})">View</button>
            </td>
        </tr>
    `).join('');
}

// Load financial data
async function loadFinancialData() {
    try {
        // Load summary
        const summaryResponse = await fetch(`${API_BASE}/api/financial/summary`);
        const summary = await summaryResponse.json();

        document.getElementById('fin-total-revenue').textContent = `$${summary.totalRevenue.toFixed(2)}`;
        document.getElementById('fin-pending-revenue').textContent = `$${summary.pendingRevenue.toFixed(2)}`;
        document.getElementById('fin-completed-transactions').textContent = summary.completedTransactions;
        document.getElementById('fin-total-transactions').textContent = summary.totalTransactions;

        // Load transactions
        const transactionsResponse = await fetch(`${API_BASE}/api/transactions`);
        const transactions = await transactionsResponse.json();
        displayTransactions(transactions);
    } catch (error) {
        console.error('Error loading financial data:', error);
        showNotification('Failed to load financial data', 'error');
    }
}

// Display transactions
function displayTransactions(transactions) {
    const tbody = document.getElementById('transactions-table-body');
    tbody.innerHTML = transactions.map(transaction => `
        <tr>
            <td>#${transaction.id}</td>
            <td>#${transaction.orderId}</td>
            <td>$${transaction.amount.toFixed(2)}</td>
            <td>${transaction.type}</td>
            <td><span class="status-badge status-${transaction.status.toLowerCase()}">${transaction.status}</span></td>
            <td>${new Date(transaction.date).toLocaleDateString()}</td>
        </tr>
    `).join('');
}

// Setup forms
function setupForms() {
    document.getElementById('item-form').addEventListener('submit', handleAddItem);
    document.getElementById('edit-item-form').addEventListener('submit', handleEditItem);
}

// Handle add item
async function handleAddItem(e) {
    e.preventDefault();

    const itemData = {
        name: document.getElementById('item-name').value,
        price: document.getElementById('item-price').value,
        stock: document.getElementById('item-stock').value,
        description: document.getElementById('item-description').value
    };

    try {
        const response = await fetch(`${API_BASE}/api/items`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(itemData)
        });

        if (response.ok) {
            showNotification('Item added successfully', 'success');
            hideAddItemForm();
            document.getElementById('item-form').reset();
            loadItems();
            loadDashboardStats();
        } else {
            showNotification('Failed to add item', 'error');
        }
    } catch (error) {
        console.error('Error adding item:', error);
        showNotification('Failed to add item', 'error');
    }
}

// Show/Hide add item form
function showAddItemForm() {
    document.getElementById('add-item-form').style.display = 'block';
}

function hideAddItemForm() {
    document.getElementById('add-item-form').style.display = 'none';
}

// Edit item
async function editItem(id) {
    try {
        const response = await fetch(`${API_BASE}/api/items/${id}`);
        const item = await response.json();

        document.getElementById('edit-item-id').value = item.id;
        document.getElementById('edit-item-name').value = item.name;
        document.getElementById('edit-item-price').value = item.price;
        document.getElementById('edit-item-stock').value = item.stock;
        document.getElementById('edit-item-description').value = item.description;

        document.getElementById('edit-item-modal').style.display = 'block';
    } catch (error) {
        console.error('Error loading item:', error);
        showNotification('Failed to load item', 'error');
    }
}

// Handle edit item
async function handleEditItem(e) {
    e.preventDefault();

    const id = document.getElementById('edit-item-id').value;
    const itemData = {
        name: document.getElementById('edit-item-name').value,
        price: document.getElementById('edit-item-price').value,
        stock: document.getElementById('edit-item-stock').value,
        description: document.getElementById('edit-item-description').value
    };

    try {
        const response = await fetch(`${API_BASE}/api/items/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(itemData)
        });

        if (response.ok) {
            showNotification('Item updated successfully', 'success');
            closeEditModal();
            loadItems();
            loadDashboardStats();
        } else {
            showNotification('Failed to update item', 'error');
        }
    } catch (error) {
        console.error('Error updating item:', error);
        showNotification('Failed to update item', 'error');
    }
}

// Close edit modal
function closeEditModal() {
    document.getElementById('edit-item-modal').style.display = 'none';
}

// Delete item
async function deleteItem(id) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/api/items/${id}`, {
            method: 'DELETE'
        });

        if (response.ok) {
            showNotification('Item deleted successfully', 'success');
            loadItems();
            loadDashboardStats();
        } else {
            showNotification('Failed to delete item', 'error');
        }
    } catch (error) {
        console.error('Error deleting item:', error);
        showNotification('Failed to delete item', 'error');
    }
}

// Update order status
async function updateOrderStatus(orderId, type, value) {
    const updateData = {};
    if (type === 'status') {
        updateData.status = value;
    } else if (type === 'payment') {
        updateData.paymentStatus = value;
    }

    try {
        const response = await fetch(`${API_BASE}/api/orders/${orderId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updateData)
        });

        if (response.ok) {
            showNotification('Order updated successfully', 'success');
            loadOrders();
            loadFinancialData();
            loadDashboardStats();
        } else {
            showNotification('Failed to update order', 'error');
        }
    } catch (error) {
        console.error('Error updating order:', error);
        showNotification('Failed to update order', 'error');
    }
}

// View order details
async function viewOrderDetails(orderId) {
    try {
        const response = await fetch(`${API_BASE}/api/orders/${orderId}`);
        const order = await response.json();

        const itemsList = order.items.map(item => 
            `${item.name} - Qty: ${item.quantity} - $${item.total.toFixed(2)}`
        ).join('\n');

        alert(`Order #${order.id} Details:\n\n` +
              `Customer: ${order.customerName}\n` +
              `Email: ${order.customerEmail}\n` +
              `Phone: ${order.customerPhone}\n\n` +
              `Items:\n${itemsList}\n\n` +
              `Total: $${order.totalAmount.toFixed(2)}\n` +
              `Status: ${order.status}\n` +
              `Payment: ${order.paymentStatus}\n` +
              `Date: ${new Date(order.orderDate).toLocaleString()}`);
    } catch (error) {
        console.error('Error loading order details:', error);
        showNotification('Failed to load order details', 'error');
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';

    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('edit-item-modal');
    if (event.target === modal) {
        closeEditModal();
    }
}
