// API Base URL
const API_BASE = window.location.origin;

// State
let products = [];
let cart = {};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    setupOrderForm();
});

// Load products from API
async function loadProducts() {
    try {
        const response = await fetch(`${API_BASE}/api/items`);
        products = await response.json();
        displayProducts();
        initializeOrderItems();
    } catch (error) {
        console.error('Error loading products:', error);
        showNotification('Failed to load products', 'error');
    }
}

// Display products
function displayProducts() {
    const container = document.getElementById('products-container');
    container.innerHTML = products.map(product => `
        <div class="product-card">
            <h3>${product.name}</h3>
            <p class="price">$${product.price.toFixed(2)}</p>
            <p>${product.description}</p>
            <p class="stock ${product.stock < 10 ? 'low' : ''}">
                Stock: ${product.stock} units
            </p>
        </div>
    `).join('');
}

// Initialize order items section
function initializeOrderItems() {
    const container = document.getElementById('order-items');
    container.innerHTML = products.map(product => {
        cart[product.id] = 0;
        return `
            <div class="order-item">
                <div class="order-item-info">
                    <h4>${product.name}</h4>
                    <p>$${product.price.toFixed(2)} each</p>
                </div>
                <div class="order-item-controls">
                    <div class="quantity-control">
                        <button type="button" class="quantity-btn" onclick="updateQuantity(${product.id}, -1)">-</button>
                        <span class="quantity-display" id="qty-${product.id}">0</span>
                        <button type="button" class="quantity-btn" onclick="updateQuantity(${product.id}, 1)">+</button>
                    </div>
                    <span class="item-total" id="total-${product.id}">$0.00</span>
                </div>
            </div>
        `;
    }).join('');
}

// Update quantity
function updateQuantity(productId, change) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const newQuantity = cart[productId] + change;
    
    if (newQuantity < 0) return;
    if (newQuantity > product.stock) {
        showNotification(`Only ${product.stock} units available`, 'warning');
        return;
    }

    cart[productId] = newQuantity;
    updateDisplay(productId);
    updateOrderSummary();
}

// Update display
function updateDisplay(productId) {
    const product = products.find(p => p.id === productId);
    const quantity = cart[productId];
    const total = quantity * product.price;

    document.getElementById(`qty-${productId}`).textContent = quantity;
    document.getElementById(`total-${productId}`).textContent = `$${total.toFixed(2)}`;
}

// Update order summary
function updateOrderSummary() {
    let totalItems = 0;
    let totalAmount = 0;

    for (const [productId, quantity] of Object.entries(cart)) {
        totalItems += quantity;
        const product = products.find(p => p.id === parseInt(productId));
        if (product) {
            totalAmount += quantity * product.price;
        }
    }

    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-amount').textContent = `$${totalAmount.toFixed(2)}`;
}

// Setup order form
function setupOrderForm() {
    const form = document.getElementById('order-form');
    form.addEventListener('submit', handleOrderSubmit);
}

// Handle order submission
async function handleOrderSubmit(e) {
    e.preventDefault();

    const customerName = document.getElementById('customer-name').value;
    const customerEmail = document.getElementById('customer-email').value;
    const customerPhone = document.getElementById('customer-phone').value;

    // Prepare order items
    const orderItems = [];
    let totalAmount = 0;

    for (const [productId, quantity] of Object.entries(cart)) {
        if (quantity > 0) {
            const product = products.find(p => p.id === parseInt(productId));
            if (product) {
                orderItems.push({
                    itemId: product.id,
                    name: product.name,
                    quantity: quantity,
                    price: product.price,
                    total: quantity * product.price
                });
                totalAmount += quantity * product.price;
            }
        }
    }

    if (orderItems.length === 0) {
        showNotification('Please select at least one item', 'warning');
        return;
    }

    const orderData = {
        customerName,
        customerEmail,
        customerPhone,
        items: orderItems,
        totalAmount
    };

    try {
        const response = await fetch(`${API_BASE}/api/orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });

        if (response.ok) {
            const order = await response.json();
            showNotification(`Order #${order.id} placed successfully!`, 'success');
            
            // Reset form and cart
            document.getElementById('order-form').reset();
            cart = {};
            loadProducts(); // Reload to get updated stock
        } else {
            const error = await response.json();
            showNotification(error.error || 'Failed to place order', 'error');
        }
    } catch (error) {
        console.error('Error placing order:', error);
        showNotification('Failed to place order', 'error');
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
