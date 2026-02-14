<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if ($order_id > 0 && in_array($status, ['approved', 'delivered', 'cancelled'], true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $order_id]);

        $stmt = $pdo->prepare('INSERT INTO order_events (order_id, status, note) VALUES (:order_id, :status, :note)');
        $stmt->execute([
            'order_id' => $order_id,
            'status' => $status,
            'note' => $note !== '' ? $note : 'Status updated by admin.',
        ]);

        set_flash('success', 'Order updated.');
        redirect('/admin/orders.php');
    }
}

$orders = $pdo->query(
    "SELECT o.id, o.status, o.created_at, o.source, u.name AS user_name, o.customer_name,
            i.qty, i.unit_price, g.name AS tank_name
     FROM orders o
     JOIN order_items i ON i.order_id = o.id
     JOIN gas_tanks g ON g.id = i.gas_tank_id
     LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC"
)->fetchAll();
?>

<h2 class="section-title">Approve and Track Orders</h2>

<table class="table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Tank</th>
            <th>Total</th>
            <th>Status</th>
            <th>Update</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?php echo e((string) $order['id']); ?> <span class="badge"><?php echo e($order['source']); ?></span></td>
                <td><?php echo e($order['user_name'] ?? $order['customer_name'] ?? 'Walk-in'); ?></td>
                <td><?php echo e($order['tank_name']); ?> x <?php echo e((string) $order['qty']); ?></td>
                <td>PHP <?php echo e((string) number_format($order['qty'] * $order['unit_price'], 2)); ?></td>
                <td><span class="status <?php echo e($order['status']); ?>"><?php echo e($order['status']); ?></span></td>
                <td>
                    <form class="form" method="post">
                        <input type="hidden" name="order_id" value="<?php echo e((string) $order['id']); ?>">
                        <select class="select" name="status" required>
                            <option value="approved">Approve</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancel</option>
                        </select>
                        <input class="input" name="note" placeholder="Update note (optional)">
                        <button class="button" type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
