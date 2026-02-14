<?php
require_once __DIR__ . '/../partials/header.php';
require_login();
require_once __DIR__ . '/../db.php';

$pdo = db();

$stmt = $pdo->prepare(
    'SELECT o.id, o.status, o.created_at, i.qty, i.unit_price, g.name
     FROM orders o
     JOIN order_items i ON i.order_id = o.id
     JOIN gas_tanks g ON g.id = i.gas_tank_id
     WHERE o.user_id = :user_id
     ORDER BY o.created_at DESC'
);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$events = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT order_id, status, note, created_at FROM order_events WHERE order_id IN ($in) ORDER BY created_at ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $event) {
        $events[$event['order_id']][] = $event;
    }
}
?>

<h2 class="section-title">My Orders</h2>

<?php if (!$orders): ?>
    <div class="card">No orders yet. Place one from your dashboard.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($orders as $order): ?>
            <div class="card">
                <h3>Order #<?php echo e((string) $order['id']); ?></h3>
                <p class="hero-copy"><?php echo e($order['name']); ?> x <?php echo e((string) $order['qty']); ?></p>
                <p class="hero-copy">Total: PHP <?php echo e((string) number_format($order['qty'] * $order['unit_price'], 2)); ?></p>
                <span class="status <?php echo e($order['status']); ?>"><?php echo e($order['status']); ?></span>

                <?php if (!empty($events[$order['id']])): ?>
                    <div class="timeline">
                        <?php foreach ($events[$order['id']] as $event): ?>
                            <div class="timeline-item">
                                <strong><?php echo e(strtoupper($event['status'])); ?></strong>
                                <div><?php echo e($event['note'] ?? ''); ?></div>
                                <small><?php echo e($event['created_at']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
