<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');
    $tank_id = (int) ($_POST['gas_tank_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);

    if ($address !== '') {
        $address = preg_replace('/\s+/', ' ', $address);
    }

    if ($name === '' || $tank_id <= 0 || $qty <= 0 || $address === '') {
        set_flash('error', 'Provide valid offline order details including address.');
    } else if (mb_strlen($name) > 120) {
        set_flash('error', 'Customer name is too long.');
    } else if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Provide a valid email address.');
    } else if (mb_strlen($address) > 500) {
        set_flash('error', 'Delivery address is too long.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, price, available_qty FROM gas_tanks WHERE id = :id AND active = 1 FOR UPDATE');
            $stmt->execute(['id' => $tank_id]);
            $tank = $stmt->fetch();

            if (!$tank) {
                throw new RuntimeException('Product not found or no longer available.');
            }

            if ((int) $tank['available_qty'] <= 0) {
                throw new RuntimeException('This product is out of stock.');
            }

            if ((int) $tank['available_qty'] < $qty) {
                throw new RuntimeException('Insufficient stock. Only ' . $tank['available_qty'] . ' available.');
            }

            $stmt = $pdo->prepare('INSERT INTO orders (customer_name, customer_email, delivery_address, status, source) VALUES (:name, :email, :address, :status, :source)');
            $stmt->execute([
                'name' => $name,
                'email' => $email ?: null,
                'address' => $address,
                'status' => 'approved',
                'source' => 'offline',
            ]);
            $order_id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, gas_tank_id, qty, unit_price) VALUES (:order_id, :tank_id, :qty, :price)');
            $stmt->execute([
                'order_id' => $order_id,
                'tank_id' => $tank_id,
                'qty' => $qty,
                'price' => $tank['price'],
            ]);

            $stmt = $pdo->prepare('UPDATE gas_tanks SET available_qty = available_qty - :qty WHERE id = :id');
            $stmt->execute(['qty' => $qty, 'id' => $tank_id]);

            $stmt = $pdo->prepare('INSERT INTO order_events (order_id, status, note) VALUES (:order_id, :status, :note)');
            $stmt->execute([
                'order_id' => $order_id,
                'status' => 'approved',
                'note' => 'Offline order logged by admin.',
            ]);

            $pdo->commit();
            set_flash('success', 'Offline order saved.');
            redirect('/admin/offline_order.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            set_flash('error', $e->getMessage());
        }
    }
}

$tanks = $pdo->query('SELECT id, name, category, size_kg, price, available_qty FROM gas_tanks WHERE active = 1 ORDER BY category, name')->fetchAll();
?>

<h2 class="section-title">Log Offline Order</h2>

<div class="card">
    <form class="form" method="post">
        <label for="customer_name">Customer name</label>
        <input class="input" id="customer_name" name="customer_name" required maxlength="120">

        <label for="customer_email">Customer email (optional)</label>
        <input class="input" type="email" id="customer_email" name="customer_email" maxlength="160">

        <label for="delivery_address">Delivery address</label>
        <input class="input" id="delivery_address" name="delivery_address" required maxlength="500" placeholder="Customer's delivery address">

        <label for="gas_tank_id">Product</label>
        <select class="select" id="gas_tank_id" name="gas_tank_id" required>
            <option value="">Choose a product</option>
            <?php foreach ($tanks as $tank): ?>
                <option value="<?php echo e((string) $tank['id']); ?>">
                    [<?php echo e(ucfirst($tank['category'])); ?>] <?php echo e($tank['name']); ?> - <?php echo e((string) $tank['size_kg']); ?> kg
                </option>
            <?php endforeach; ?>
        </select>

        <label for="qty">Quantity</label>
        <input class="input" type="number" id="qty" name="qty" min="1" required>

        <button class="button" type="submit">Save order</button>
    </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
