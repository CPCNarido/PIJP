<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';

$pdo = db();
$metrics = [
    'tanks' => (int) $pdo->query('SELECT COUNT(*) FROM gas_tanks WHERE active = 1')->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'sales' => (float) $pdo->query("SELECT COALESCE(SUM(oi.qty * oi.unit_price), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ('approved','delivered')")->fetchColumn(),
];
?>

<section class="hero">
    <div class="hero-card">
        <h2 class="section-title">Admin Command Center</h2>
        <div class="metrics">
            <div class="metric">
                <h4>Active Tanks</h4>
                <p><?php echo e((string) $metrics['tanks']); ?></p>
            </div>
            <div class="metric">
                <h4>Pending Orders</h4>
                <p><?php echo e((string) $metrics['pending']); ?></p>
            </div>
            <div class="metric">
                <h4>Registered Users</h4>
                <p><?php echo e((string) $metrics['users']); ?></p>
            </div>
            <div class="metric">
                <h4>Total Sales</h4>
                <p>PHP <?php echo e((string) number_format($metrics['sales'], 2)); ?></p>
            </div>
        </div>
    </div>
    <div class="hero-card">
        <h2 class="section-title">Quick actions</h2>
        <div class="action-grid">
            <a class="action-card" href="/admin/stock.php">
                <div class="action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">📦</div>
                <h3>Manage Stock</h3>
                <p class="hero-copy">Add, edit, and retire products.</p>
            </a>
            <a class="action-card" href="/admin/orders.php">
                <div class="action-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">✓</div>
                <h3>Approve Orders</h3>
                <p class="hero-copy">Review and update order status.</p>
            </a>
            <a class="action-card" href="/admin/staff.php">
                <div class="action-icon" style="background: linear-gradient(135deg, #10b981, #059669);">👥</div>
                <h3>Delivery Staff</h3>
                <p class="hero-copy">Manage delivery riders.</p>
            </a>
            <a class="action-card" href="/admin/offline_order.php">
                <div class="action-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">📝</div>
                <h3>Offline Order</h3>
                <p class="hero-copy">Log walk-in or phone orders.</p>
            </a>
            <a class="action-card" href="/admin/users.php">
                <div class="action-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);">👤</div>
                <h3>Users</h3>
                <p class="hero-copy">View registered customers.</p>
            </a>
            <a class="action-card" href="/admin/sales.php">
                <div class="action-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">📊</div>
                <h3>Sales Report</h3>
                <p class="hero-copy">Monitor revenue and volume.</p>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
