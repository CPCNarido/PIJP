<?php
require_once __DIR__ . '/../partials/header.php';
require_login();
require_once __DIR__ . '/../db.php';

$config = require __DIR__ . '/../config.php';
$mapsKey = trim($config['google_maps_key'] ?? '');

$pdo = db();

// Handle checkout from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $delivery_address = trim($_POST['delivery_address'] ?? '');

    if ($delivery_address !== '') {
        $delivery_address = preg_replace('/\s+/', ' ', $delivery_address);
    }

    if ($delivery_address === '') {
        set_flash('error', 'Please provide a delivery address.');
    } else if (mb_strlen($delivery_address) > 500) {
        set_flash('error', 'Delivery address is too long.');
    } else {
        $pdo->beginTransaction();
        try {
            // Get cart items with stock lock
            $stmt = $pdo->prepare('
                SELECT c.id as cart_id, c.gas_tank_id, c.qty, g.name, g.price, g.available_qty
                FROM cart c
                JOIN gas_tanks g ON c.gas_tank_id = g.id
                WHERE c.user_id = :user_id AND g.active = 1
                FOR UPDATE
            ');
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $cart_items = $stmt->fetchAll();

            if (empty($cart_items)) {
                throw new RuntimeException('Your cart is empty.');
            }

            // Validate stock for all items
            foreach ($cart_items as $item) {
                if ((int) $item['available_qty'] <= 0) {
                    throw new RuntimeException($item['name'] . ' is out of stock.');
                }
                if ((int) $item['available_qty'] < (int) $item['qty']) {
                    throw new RuntimeException('Insufficient stock for ' . $item['name'] . '. Only ' . $item['available_qty'] . ' available.');
                }
            }

            // Create order
            $stmt = $pdo->prepare('INSERT INTO orders (user_id, delivery_address, status, source) VALUES (:user_id, :address, :status, :source)');
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'address' => $delivery_address,
                'status' => 'pending',
                'source' => 'web',
            ]);
            $order_id = (int) $pdo->lastInsertId();

            // Add order items and update stock
            $stmt_item = $pdo->prepare('INSERT INTO order_items (order_id, gas_tank_id, qty, unit_price) VALUES (:order_id, :tank_id, :qty, :price)');
            $stmt_stock = $pdo->prepare('UPDATE gas_tanks SET available_qty = available_qty - :qty WHERE id = :id');

            foreach ($cart_items as $item) {
                $stmt_item->execute([
                    'order_id' => $order_id,
                    'tank_id' => $item['gas_tank_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ]);

                $stmt_stock->execute([
                    'qty' => $item['qty'],
                    'id' => $item['gas_tank_id']
                ]);
            }

            // Add order event
            $stmt = $pdo->prepare('INSERT INTO order_events (order_id, status, note) VALUES (:order_id, :status, :note)');
            $stmt->execute([
                'order_id' => $order_id,
                'status' => 'pending',
                'note' => 'Order placed from cart on website.',
            ]);

            // Clear cart
            $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $_SESSION['user_id']]);

            $pdo->commit();
            set_flash('success', 'Order placed successfully! Track it in My Orders.');
            redirect('/user/orders.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            set_flash('error', $e->getMessage());
        }
    }
}

// Get current cart items
$stmt = $pdo->prepare('
    SELECT c.id, c.gas_tank_id, c.qty, g.name, g.category, g.image_path, g.size_kg, g.price, g.available_qty
    FROM cart c
    JOIN gas_tanks g ON c.gas_tank_id = g.id
    WHERE c.user_id = :user_id AND g.active = 1
    ORDER BY c.created_at DESC
');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += (float) $item['price'] * (int) $item['qty'];
}
?>

<h2 class="section-title">🛒 Shopping Cart</h2>

<?php if (empty($cart_items)): ?>
    <div class="hero">
        <div class="hero-card" style="text-align: center;">
            <p style="font-size: 48px; margin-bottom: 16px;">🛒</p>
            <h3>Your cart is empty</h3>
            <p class="hero-copy" style="margin-bottom: 24px;">Add some products to get started!</p>
            <a href="/user/index.php" class="button">Browse Products</a>
        </div>
    </div>
<?php else: ?>
    <div class="cart-container">
        <div class="cart-items">
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" data-cart-id="<?php echo e((string) $item['id']); ?>">
                    <div class="cart-item-image">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>">
                        <?php else: ?>
                            <div class="no-image">No image</div>
                        <?php endif; ?>
                    </div>

                    <div class="cart-item-details">
                        <h3><?php echo e($item['name']); ?></h3>
                        <span class="product-category small"><?php echo e(ucfirst($item['category'])); ?></span>
                        <p class="hero-copy">Size: <?php echo e((string) $item['size_kg']); ?> kg</p>
                        <p class="hero-copy"><strong>PHP <?php echo e((string) number_format((float) $item['price'], 2)); ?></strong></p>
                        <?php if ($item['available_qty'] <= 0): ?>
                            <p class="badge" style="background: var(--danger); color: white;">Out of Stock</p>
                        <?php elseif ($item['qty'] > $item['available_qty']): ?>
                            <p class="badge" style="background: var(--warning); color: white;">Only <?php echo e((string) $item['available_qty']); ?> available</p>
                        <?php else: ?>
                            <p class="badge">In Stock: <?php echo e((string) $item['available_qty']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="cart-item-actions">
                        <div class="qty-control">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo e((string) $item['id']); ?>, <?php echo e((string) ($item['qty'] - 1)); ?>)">−</button>
                            <input type="number" class="qty-input" value="<?php echo e((string) $item['qty']); ?>" min="1" max="<?php echo e((string) $item['available_qty']); ?>" onchange="updateQuantity(<?php echo e((string) $item['id']); ?>, this.value)">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo e((string) $item['id']); ?>, <?php echo e((string) ($item['qty'] + 1)); ?>)">+</button>
                        </div>
                        <p class="cart-item-subtotal">PHP <?php echo e((string) number_format((float) $item['price'] * (int) $item['qty'], 2)); ?></p>
                        <button class="button-danger small" onclick="removeFromCart(<?php echo e((string) $item['id']); ?>)">Remove</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <div class="hero-card">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="cart-total">PHP <?php echo e((string) number_format($total, 2)); ?></span>
                </div>
                <div class="summary-row" style="border-top: 2px solid var(--border); padding-top: 12px; margin-top: 12px; font-size: 18px; font-weight: 700;">
                    <span>Total</span>
                    <span class="cart-total" style="color: var(--primary);">PHP <?php echo e((string) number_format($total, 2)); ?></span>
                </div>

                <form method="post" style="margin-top: 24px;">
                    <div>
                        <label for="delivery_address">Delivery Address</label>
                        <input class="input" type="text" id="delivery_address" name="delivery_address" required maxlength="500" placeholder="Enter your delivery address">
                        <small style="color: var(--muted-light); margin-top: 6px; display: block;">Start typing for suggestions</small>
                    </div>
                    <button class="button" type="submit" name="checkout" style="width: 100%; margin-top: 16px;">Proceed to Checkout</button>
                </form>

                <button class="button-secondary" onclick="clearCart()" style="width: 100%; margin-top: 12px;">Clear Cart</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
async function updateQuantity(cartId, qty) {
    qty = parseInt(qty);
    if (qty < 1) {
        if (confirm('Remove this item from cart?')) {
            removeFromCart(cartId);
        }
        return;
    }

    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('qty', qty);

    try {
        const response = await fetch('/api/cart.php?action=update', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating cart');
        }
    } catch (error) {
        alert('Error updating cart');
    }
}

async function removeFromCart(cartId) {
    if (!confirm('Remove this item from cart?')) return;

    const formData = new FormData();
    formData.append('cart_id', cartId);

    try {
        const response = await fetch('/api/cart.php?action=remove', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error removing item');
        }
    } catch (error) {
        alert('Error removing item');
    }
}

async function clearCart() {
    if (!confirm('Clear all items from cart?')) return;

    try {
        const response = await fetch('/api/cart.php?action=clear', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error clearing cart');
        }
    } catch (error) {
        alert('Error clearing cart');
    }
}
</script>

<?php if ($mapsKey !== ''): ?>
    <script defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo e($mapsKey); ?>&libraries=places&callback=initAddressAutocomplete"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
