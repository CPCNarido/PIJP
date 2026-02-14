<?php
require_once __DIR__ . '/../partials/header.php';
require_login();
require_once __DIR__ . '/../db.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tank_id = (int) ($_POST['gas_tank_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);

    if ($tank_id <= 0 || $qty <= 0) {
        set_flash('error', 'Please select a tank and quantity.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, price, available_qty FROM gas_tanks WHERE id = :id AND active = 1 FOR UPDATE');
            $stmt->execute(['id' => $tank_id]);
            $tank = $stmt->fetch();

            if (!$tank || (int) $tank['available_qty'] < $qty) {
                throw new RuntimeException('Insufficient stock for this tank.');
            }

            $stmt = $pdo->prepare('INSERT INTO orders (user_id, status, source) VALUES (:user_id, :status, :source)');
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'status' => 'pending',
                'source' => 'web',
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
                'status' => 'pending',
                'note' => 'Order placed on website.',
            ]);

            $pdo->commit();
            set_flash('success', 'Order placed. Track it in My Orders.');
            redirect('/user/orders.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            set_flash('error', $e->getMessage());
        }
    }
}

$tanks = $pdo->query('SELECT id, name, size_kg, price, available_qty FROM gas_tanks WHERE active = 1 ORDER BY name')->fetchAll();
?>

<h2 class="section-title">Available Gas Tanks</h2>
<div class="grid">
    <?php foreach ($tanks as $tank): ?>
        <div class="card">
            <h3><?php echo e($tank['name']); ?></h3>
            <p class="hero-copy">Size: <?php echo e((string) $tank['size_kg']); ?> kg</p>
            <p class="hero-copy">Price: PHP <?php echo e((string) number_format((float) $tank['price'], 2)); ?></p>
            <p class="badge">Stock: <?php echo e((string) $tank['available_qty']); ?></p>
        </div>
    <?php endforeach; ?>
</div>

<section class="hero">
    <div class="hero-card">
        <h2 class="section-title">Place an order</h2>
        <form class="form" method="post">
            <label for="gas_tank_id">Select tank</label>
            <select class="select" id="gas_tank_id" name="gas_tank_id" required>
                <option value="">Choose a tank</option>
                <?php foreach ($tanks as $tank): ?>
                    <option value="<?php echo e((string) $tank['id']); ?>">
                        <?php echo e($tank['name']); ?> - <?php echo e((string) $tank['size_kg']); ?> kg
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="qty">Quantity</label>
            <input class="input" type="number" id="qty" name="qty" min="1" required>

            <button class="button" type="submit">Submit order</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
