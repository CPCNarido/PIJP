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

<section class="hero hero--landing">
    <div class="hero-card hero-primary">
        <span class="eyebrow">Fast, verified LPG delivery</span>
        <h1 class="hero-title">Modern LPG ordering with trusted delivery teams.</h1>
        <p class="hero-copy">
            PIJP brings real-time stock, guided approvals, and rider assignment into one platform so
            every order is tracked from request to doorstep.
        </p>
        <div class="cta-group">
            <a class="button" href="/register.php">Create account</a>
            <a class="button secondary" href="/login.php">Sign in</a>
        </div>
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
    <div class="hero-visual">
        <div class="hero-orb"></div>
        <div class="hero-panel">
            <h3>Service highlights</h3>
            <ul class="feature-list">
                <li>Fast approval workflows for admins</li>
                <li>Live delivery status for customers</li>
                <li>Verified rider assignment and contact</li>
            </ul>
        </div>
        <div class="hero-panel">
            <h3>Operations view</h3>
            <p class="hero-copy">Track sales, stock movement, and delivery timelines in one dashboard.</p>
            <span class="badge">Unified control center</span>
        </div>
    </div>
</section>

<section>
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
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
