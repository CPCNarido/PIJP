<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';

$pdo = db();

$total = (float) $pdo->query("SELECT COALESCE(SUM(oi.qty * oi.unit_price), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ('approved','delivered')")->fetchColumn();
$count = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('approved','delivered')")->fetchColumn();
$volume = (int) $pdo->query("SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status IN ('approved','delivered')")->fetchColumn();
?>

<section class="hero">
    <div class="hero-card">
        <h2 class="section-title">Overall Sales</h2>
        <div class="metrics">
            <div class="metric">
                <h4>Total Revenue</h4>
                <p>PHP <?php echo e((string) number_format($total, 2)); ?></p>
            </div>
            <div class="metric">
                <h4>Approved Orders</h4>
                <p><?php echo e((string) $count); ?></p>
            </div>
            <div class="metric">
                <h4>Cylinders Sold</h4>
                <p><?php echo e((string) $volume); ?></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
