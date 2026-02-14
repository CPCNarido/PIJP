<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/db.php';

$metrics = [
    'tanks' => 0,
    'orders' => 0,
    'users' => 0,
];

try {
    $pdo = db();
    $metrics['tanks'] = (int) $pdo->query('SELECT COUNT(*) FROM gas_tanks WHERE active = 1')->fetchColumn();
    $metrics['orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('approved','delivered')")->fetchColumn();
    $metrics['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (Throwable $e) {
    // Database not configured yet.
}
?>

<section class="hero">
    <div class="hero-card">
        <span class="badge">Live inventory & approval flow</span>
        <h1 class="hero-title">Order LPG cylinders with full visibility.</h1>
        <p class="hero-copy">
            PIJP connects customers and admins in a single system: stock tracking, order approval,
            and delivery progress in real time.
        </p>
        <div class="metrics">
            <div class="metric">
                <h4>Active Tanks</h4>
                <p><?php echo e((string) $metrics['tanks']); ?></p>
            </div>
            <div class="metric">
                <h4>Approved Orders</h4>
                <p><?php echo e((string) $metrics['orders']); ?></p>
            </div>
            <div class="metric">
                <h4>Registered Users</h4>
                <p><?php echo e((string) $metrics['users']); ?></p>
            </div>
        </div>
    </div>
    <div class="hero-card">
        <h2 class="section-title">What you can do</h2>
        <div class="grid">
            <div class="card">
                <h3>Customers</h3>
                <p class="hero-copy">Browse available tanks, place orders, and monitor delivery progress.</p>
            </div>
            <div class="card">
                <h3>Admins</h3>
                <p class="hero-copy">Manage stock, approve orders, log offline sales, and review analytics.</p>
            </div>
            <div class="card">
                <h3>Operations</h3>
                <p class="hero-copy">Every order is tracked with status updates for complete transparency.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
