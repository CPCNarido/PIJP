<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $staff_id = (int) ($_POST['staff_id'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $note = $note !== '' ? preg_replace('/\s+/', ' ', $note) : '';

    if ($order_id <= 0 || !in_array($status, ['approved', 'in_delivery', 'delivered', 'cancelled'], true)) {
        set_flash('error', 'Select a valid order update.');
    } else if (mb_strlen($note) > 255) {
        set_flash('error', 'Note is too long.');
    } else if (in_array($status, ['in_delivery', 'delivered'], true) && $staff_id <= 0) {
        set_flash('error', 'Assign a delivery staff member before marking in delivery or delivered.');
    } else {
        if ($staff_id > 0) {
            $staffStmt = $pdo->prepare('SELECT id FROM staff WHERE id = :id AND active = 1');
            $staffStmt->execute(['id' => $staff_id]);
            if (!$staffStmt->fetch()) {
                set_flash('error', 'Selected staff member is not available.');
                redirect('/admin/orders.php');
            }
        }

        $stmt = $pdo->prepare('UPDATE orders SET status = :status, staff_id = :staff_id WHERE id = :id');
        $stmt->execute(['status' => $status, 'staff_id' => $staff_id > 0 ? $staff_id : null, 'id' => $order_id]);

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
    "SELECT o.id, o.status, o.delivery_address, o.created_at, o.source, o.staff_id, u.name AS user_name, o.customer_name,
            i.qty, i.unit_price, g.name AS tank_name, s.name AS staff_name, s.phone
     FROM orders o
     JOIN order_items i ON i.order_id = o.id
     JOIN gas_tanks g ON g.id = i.gas_tank_id
     LEFT JOIN users u ON u.id = o.user_id
     LEFT JOIN staff s ON s.id = o.staff_id
     ORDER BY o.created_at DESC"
)->fetchAll();

$staff = $pdo->query('SELECT id, name, phone FROM staff WHERE active = 1 ORDER BY name')->fetchAll();
?>

<h2 class="section-title">Approve and Track Orders</h2>

<table class="table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Address</th>
            <th>Tank</th>
            <th>Total</th>
            <th>Status</th>
            <th>Delivery Staff</th>
            <th>Assign & Update</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?php echo e((string) $order['id']); ?> <span class="badge"><?php echo e($order['source']); ?></span></td>
                <td><?php echo e($order['user_name'] ?? $order['customer_name'] ?? 'Walk-in'); ?></td>
                <td><?php echo e($order['delivery_address'] ? substr($order['delivery_address'], 0, 30) . '...' : '—'); ?></td>
                <td><?php echo e($order['tank_name']); ?> x <?php echo e((string) $order['qty']); ?></td>
                <td>PHP <?php echo e((string) number_format($order['qty'] * $order['unit_price'], 2)); ?></td>
                <td><span class="status <?php echo e($order['status']); ?>"><?php echo e(str_replace('_', ' ', $order['status'])); ?></span></td>
                <td>
                    <?php if ($order['staff_name']): ?>
                        <div>
                            <strong><?php echo e($order['staff_name']); ?></strong><br>
                            <small><?php echo e($order['phone']); ?></small>
                        </div>
                    <?php else: ?>
                        <span style="color: var(--muted);">Not assigned</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form class="form" method="post" style="gap: 8px;">
                        <input type="hidden" name="order_id" value="<?php echo e((string) $order['id']); ?>">
                        <select class="select" name="status" required style="font-size: 12px;">
                            <option value="">Update status</option>
                            <option value="approved">Approve</option>
                            <option value="in_delivery">In Delivery</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancel</option>
                        </select>
                        <select class="select" name="staff_id" style="font-size: 12px;">
                            <option value="">Assign staff</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?php echo e((string) $s['id']); ?>" <?php echo $s['id'] == ($order['staff_id'] ?? '') ? 'selected' : ''; ?>>
                                    <?php echo e($s['name']); ?> (<?php echo e($s['phone']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input class="input" name="note" placeholder="Note..." style="font-size: 12px;">
                        <button class="button" type="submit" style="font-size: 12px;">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
