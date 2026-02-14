<?php
require_once __DIR__ . '/../partials/header.php';
require_login();
require_once __DIR__ . '/../db.php';

$pdo = db();

$stmt = $pdo->prepare(
    'SELECT o.id, o.status, o.delivery_address, o.created_at, i.qty, i.unit_price, g.name, s.name as staff_name, s.phone
     FROM orders o
     JOIN order_items i ON i.order_id = o.id
     JOIN gas_tanks g ON g.id = i.gas_tank_id
     LEFT JOIN staff s ON s.id = o.staff_id
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
                <p class="hero-copy"><strong><?php echo e($order['name']); ?> x <?php echo e((string) $order['qty']); ?></strong></p>
                <p class="hero-copy">Total: <strong>PHP <?php echo e((string) number_format($order['qty'] * $order['unit_price'], 2)); ?></strong></p>
                
                <?php if ($order['delivery_address']): ?>
                    <p class="hero-copy" style="font-size: 13px; margin: 12px 0 0;">
                        <strong>Delivery to:</strong><br>
                        <?php echo e($order['delivery_address']); ?>
                    </p>
                <?php endif; ?>

                <div style="margin: 16px 0 12px;">
                    <span class="status <?php echo e($order['status']); ?>"><?php echo e(str_replace('_', ' ', strtoupper($order['status']))); ?></span>
                </div>

                <?php if ($order['status'] !== 'pending' && $order['staff_name']): ?>
                    <div style="background: rgba(37, 99, 235, 0.05); border-left: 4px solid var(--primary); padding: 12px; border-radius: 8px; margin: 12px 0;">
                        <p style="margin: 0; font-size: 12px; color: var(--muted); text-transform: uppercase; font-weight: 600;">Your Delivery Rider</p>
                        <p style="margin: 6px 0 0; font-weight: 600; font-size: 16px;"><?php echo e($order['staff_name']); ?></p>
                        <p style="margin: 4px 0 0; color: var(--primary); font-weight: 600;">📞 <?php echo e($order['phone']); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($events[$order['id']])): ?>
                    <div class="timeline">
                        <?php foreach ($events[$order['id']] as $event): ?>
                            <div class="timeline-item">
                                <strong><?php echo e(str_replace('_', ' ', strtoupper($event['status']))); ?></strong>
                                <div><?php echo e($event['note'] ?? ''); ?></div>
                                <small><?php echo e(date('M d, Y H:i', strtotime($event['created_at']))); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
