<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/db.php';

$metrics = [
    'tanks' => 0,
    'orders' => 0,
    'users' => 0,
];
$products = [];

// Get filter and search parameters
$category = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

try {
    $pdo = db();
    $metrics['tanks'] = (int) $pdo->query('SELECT COUNT(*) FROM gas_tanks WHERE active = 1')->fetchColumn();
    $metrics['orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('approved','delivered')")->fetchColumn();
    $metrics['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    
    // Build query with filters
    $query = 'SELECT id, name, category, image_path, size_kg, price FROM gas_tanks WHERE active = 1';
    $params = [];
    
    if ($category !== 'all' && in_array($category, ['gas', 'accessories', 'stove'])) {
        $query .= ' AND category = :category';
        $params['category'] = $category;
    }
    
    if ($search !== '') {
        $query .= ' AND name LIKE :search';
        $params['search'] = '%' . $search . '%';
    }
    
    // Add sorting
    switch ($sort) {
        case 'price_low':
            $query .= ' ORDER BY price ASC';
            break;
        case 'price_high':
            $query .= ' ORDER BY price DESC';
            break;
        case 'name':
            $query .= ' ORDER BY name ASC';
            break;
        default:
            $query .= ' ORDER BY created_at DESC';
    }
    
    $query .= ' LIMIT 12';
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    // Database not configured yet.
}
?>

<section class="hero hero--landing supergas-hero">
    <div class="hero-card hero-primary">
        <span class="eyebrow">Reliable LPG for home kitchens</span>
        <h1 class="hero-title">Hassle-free cylinder booking with real-time delivery.</h1>
        <p class="hero-copy">
            PIJP keeps every order visible: live stock, instant approvals, and assigned riders so
            customers always know when their cylinders arrive.
        </p>
        <div class="cta-group">
            <a class="button" href="/register.php">Book your cylinder</a>
            <a class="button secondary" href="/login.php">Customer login</a>
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
    <div class="hero-media">
        <div class="hero-image">
            <div class="hero-image__badge">Safe SR-grade LPG</div>
            <div class="hero-image__card">
                <span>Delivery ETA</span>
                <strong>45 mins</strong>
            </div>
            <div class="hero-image__card">
                <span>Live Riders</span>
                <strong>12 on duty</strong>
            </div>
        </div>
        <div class="hero-panel">
            <h3>Smart cooking solutions</h3>
            <p class="hero-copy">Transparent pricing, doorstep delivery, and verified riders.</p>
            <ul class="feature-list">
                <li>Easy online booking</li>
                <li>Safety-first handling</li>
                <li>Trusted service teams</li>
            </ul>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="section-head">
        <h2 class="section-title">I am looking for</h2>
        <p class="hero-copy">Pick a quick path and get your cylinder delivered without delays.</p>
    </div>
    <div class="grid">
        <div class="card">
            <h3>New gas connection</h3>
            <p class="hero-copy">Create an account, verify details, and place your first order.</p>
            <a class="link-arrow" href="/register.php">Start now</a>
        </div>
        <div class="card">
            <h3>Refill order</h3>
            <p class="hero-copy">Choose a tank size and schedule same-day delivery.</p>
            <a class="link-arrow" href="/login.php">Book refill</a>
        </div>
        <div class="card">
            <h3>Track my delivery</h3>
            <p class="hero-copy">See status updates and assigned riders in real time.</p>
            <a class="link-arrow" href="/user/orders.php">View orders</a>
        </div>
    </div>
</section>

<section class="section-block">
    <div class="section-head">
        <h2 class="section-title">Our products</h2>
        <p class="hero-copy">Reliable cylinders for every kitchen size and cooking demand.</p>
    </div>
    
    <div class="search-filter-bar">
        <form method="get" action="/index.php" class="search-form">
            <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?php echo e($search); ?>">
            <button type="submit" class="search-button">Search</button>
        </form>
        
        <div class="filter-group">
            <a href="?category=all&search=<?php echo urlencode($search); ?>&sort=<?php echo e($sort); ?>" class="filter-btn <?php echo $category === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?category=gas&search=<?php echo urlencode($search); ?>&sort=<?php echo e($sort); ?>" class="filter-btn <?php echo $category === 'gas' ? 'active' : ''; ?>">Gas</a>
            <a href="?category=accessories&search=<?php echo urlencode($search); ?>&sort=<?php echo e($sort); ?>" class="filter-btn <?php echo $category === 'accessories' ? 'active' : ''; ?>">Accessories</a>
            <a href="?category=stove&search=<?php echo urlencode($search); ?>&sort=<?php echo e($sort); ?>" class="filter-btn <?php echo $category === 'stove' ? 'active' : ''; ?>">Stove</a>
        </div>
        
        <div class="sort-group">
            <label for="sort" style="font-size: 14px; font-weight: 600; color: var(--muted);">Sort by:</label>
            <select id="sort" class="sort-select" onchange="window.location.href='?category=<?php echo e($category); ?>&search=<?php echo urlencode($search); ?>&sort=' + this.value">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
            </select>
        </div>
    </div>
    <div class="grid product-grid">
        <?php if (!$products): ?>
            <div class="card">No products available yet. Please check back soon.</div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image_path'])): ?>
                            <img src="<?php echo e($product['image_path']); ?>" alt="<?php echo e($product['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <span class="product-category"><?php echo e(ucfirst($product['category'])); ?></span>
                    <h3><?php echo e($product['name']); ?></h3>
                    <p class="hero-copy"><?php echo e((string) $product['size_kg']); ?> kg · PHP <?php echo e((string) number_format((float) $product['price'], 2)); ?></p>
                    <span class="badge">Available</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="section-block support-strip">
    <div>
        <h2 class="section-title">Always-on support</h2>
        <p class="hero-copy">Need help? Our teams assist with delivery, safety tips, and order tracking.</p>
    </div>
    <div class="support-actions">
        <div class="support-card">
            <h3>Order assistance</h3>
            <p class="hero-copy">Get help with booking, status, or delivery changes.</p>
        </div>
        <div class="support-card">
            <h3>Safety guidance</h3>
            <p class="hero-copy">Tips on safe storage and efficient cooking.</p>
        </div>
    </div>
</section>


<?php require_once __DIR__ . '/partials/footer.php'; ?>
