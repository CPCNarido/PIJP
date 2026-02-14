<?php
require_once __DIR__ . '/../partials/header.php';
require_login();
require_once __DIR__ . '/../db.php';

$config = require __DIR__ . '/../config.php';
$mapsKey = trim($config['google_maps_key'] ?? '');

$pdo = db();

// Get filter and search parameters
$category = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'name';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tank_id = (int) ($_POST['gas_tank_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);
    $delivery_address = trim($_POST['delivery_address'] ?? '');

    if ($delivery_address !== '') {
        $delivery_address = preg_replace('/\s+/', ' ', $delivery_address);
    }

    if ($tank_id <= 0 || $qty <= 0) {
        set_flash('error', 'Please select a tank and quantity.');
    } else if ($delivery_address === '') {
        set_flash('error', 'Please provide a delivery address.');
    } else if (mb_strlen($delivery_address) > 500) {
        set_flash('error', 'Delivery address is too long.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, price, available_qty FROM gas_tanks WHERE id = :id AND active = 1 FOR UPDATE');
            $stmt->execute(['id' => $tank_id]);
            $tank = $stmt->fetch();

            if (!$tank || (int) $tank['available_qty'] < $qty) {
                throw new RuntimeException('Insufficient stock for this tank.');
            }

            $stmt = $pdo->prepare('INSERT INTO orders (user_id, delivery_address, status, source) VALUES (:user_id, :address, :status, :source)');
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'address' => $delivery_address,
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

// Build query with filters
$query = 'SELECT id, name, category, image_path, size_kg, price, available_qty FROM gas_tanks WHERE active = 1';
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
        $query .= ' ORDER BY name ASC';
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tanks = $stmt->fetchAll();
?>

<h2 class="section-title">Available Products</h2>

<div class="search-filter-bar">
    <form method="get" action="/user/index.php" class="search-form">
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
            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
        </select>
    </div>
</div>

<div class="grid">
    <?php foreach ($tanks as $tank): ?>
        <div class="card">
            <?php if (!empty($tank['image_path'])): ?>
                <img class="tank-image" src="<?php echo e($tank['image_path']); ?>" alt="<?php echo e($tank['name']); ?>">
            <?php endif; ?>
            <span class="product-category"><?php echo e(ucfirst($tank['category'])); ?></span>
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
            <div>
                <label for="gas_tank_id">Select product</label>
                <select class="select" id="gas_tank_id" name="gas_tank_id" required>
                    <option value="">Choose a product</option>
                    <?php foreach ($tanks as $tank): ?>
                        <option value="<?php echo e((string) $tank['id']); ?>">
                            [<?php echo e(ucfirst($tank['category'])); ?>] <?php echo e($tank['name']); ?> - <?php echo e((string) $tank['size_kg']); ?> kg (PHP <?php echo e((string) number_format((float) $tank['price'], 2)); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="qty">Quantity</label>
                <input class="input" type="number" id="qty" name="qty" min="1" required placeholder="How many cylinders?">
            </div>

            <div>
                <label for="delivery_address">Delivery Address</label>
                <input class="input" type="text" id="delivery_address" name="delivery_address" required maxlength="500" placeholder="Enter your delivery address">
                <small style="color: var(--muted-light); margin-top: 6px; display: block;">Start typing your address for suggestions</small>
                <?php if ($mapsKey === ''): ?>
                    <small style="color: var(--muted-light); margin-top: 6px; display: block;">Maps autocomplete is disabled. Add GOOGLE_MAPS_KEY to enable it.</small>
                <?php endif; ?>
            </div>

            <button class="button" type="submit" style="width: 100%;">Submit Order</button>
        </form>
    </div>
</section>

<?php if ($mapsKey !== ''): ?>
    <script defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo e($mapsKey); ?>&libraries=places&callback=initAddressAutocomplete"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
