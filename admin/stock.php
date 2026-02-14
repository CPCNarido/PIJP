<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/cloudinary.php';

$pdo = db();
$config = require __DIR__ . '/../config.php';

// Initialize Cloudinary
$cloudinary = null;
if (!empty($config['cloudinary']['cloud_name']) && 
    !empty($config['cloudinary']['api_key']) && 
    !empty($config['cloudinary']['api_secret'])) {
    $cloudinary = new Cloudinary(
        $config['cloudinary']['cloud_name'],
        $config['cloudinary']['api_key'],
        $config['cloudinary']['api_secret']
    );
}

$allowedImageTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$maxImageSize = 2 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? 'gas';
        $size = (float) ($_POST['size_kg'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $qty = (int) ($_POST['available_qty'] ?? 0);
        $image = $_FILES['image'] ?? null;

        if (!in_array($category, ['gas', 'accessories', 'stove'])) {
            $category = 'gas';
        }

        if ($name === '' || $size <= 0 || $price <= 0) {
            set_flash('error', 'Provide valid tank details.');
        } else if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'Upload a product image.');
        } else if ($image['size'] > $maxImageSize) {
            set_flash('error', 'Image must be 2MB or smaller.');
        } else {
            $imageInfo = @getimagesize($image['tmp_name']);
            $mimeType = $imageInfo['mime'] ?? '';
            if (!array_key_exists($mimeType, $allowedImageTypes)) {
                set_flash('error', 'Upload a JPG, PNG, or WEBP image.');
            } else {
                try {
                    if ($cloudinary) {
                        // Upload to Cloudinary
                        $result = $cloudinary->uploadImage($image['tmp_name'], [
                            'folder' => 'pijp/tanks',
                            'public_id' => 'tank_' . bin2hex(random_bytes(8)),
                        ]);
                        $imagePath = $result['secure_url'];
                    } else {
                        // Fallback to local upload if Cloudinary not configured
                        $uploadDir = __DIR__ . '/../uploads/tanks';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        $filename = bin2hex(random_bytes(12)) . '.' . $allowedImageTypes[$mimeType];
                        $targetPath = $uploadDir . '/' . $filename;
                        if (!move_uploaded_file($image['tmp_name'], $targetPath)) {
                            throw new Exception('Failed to save the image.');
                        }
                        $imagePath = '/uploads/tanks/' . $filename;
                    }

                    $stmt = $pdo->prepare('INSERT INTO gas_tanks (name, category, image_path, size_kg, price, available_qty) VALUES (:name, :category, :image, :size, :price, :qty)');
                    $stmt->execute(['name' => $name, 'category' => $category, 'image' => $imagePath, 'size' => $size, 'price' => $price, 'qty' => $qty]);
                    set_flash('success', 'Tank added.');
                    redirect('/admin/stock.php');
                } catch (Exception $e) {
                    set_flash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $qty = (int) ($_POST['available_qty'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        $stmt = $pdo->prepare('UPDATE gas_tanks SET price = :price, available_qty = :qty, active = :active WHERE id = :id');
        $stmt->execute(['price' => $price, 'qty' => $qty, 'active' => $active, 'id' => $id]);
        set_flash('success', 'Tank updated.');
        redirect('/admin/stock.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE gas_tanks SET active = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        set_flash('success', 'Tank retired.');
        redirect('/admin/stock.php');
    }
}

$tanks = $pdo->query('SELECT id, name, category, image_path, size_kg, price, available_qty, active FROM gas_tanks ORDER BY name')->fetchAll();
?>

<h2 class="section-title">Manage Product Stock</h2>

<div class="card">
    <form class="form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <label for="name">Product name</label>
        <input class="input" id="name" name="name" required>

        <label for="category">Category</label>
        <select class="select" id="category" name="category" required>
            <option value="gas">Gas</option>
            <option value="accessories">Accessories</option>
            <option value="stove">Stove</option>
        </select>

        <label for="size_kg">Size (kg)</label>
        <input class="input" type="number" step="0.1" id="size_kg" name="size_kg" required>

        <label for="price">Price (PHP)</label>
        <input class="input" type="number" step="0.01" id="price" name="price" required>

        <label for="available_qty">Available quantity</label>
        <input class="input" type="number" id="available_qty" name="available_qty" min="0" required>

        <label for="image">Product image</label>
        <input class="input" type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" required>

        <button class="button" type="submit">Add tank</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Image</th>
            <th>Size (kg)</th>
            <th>Price</th>
            <th>Available</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tanks as $tank): ?>
            <tr>
                <td><?php echo e($tank['name']); ?></td>
                <td><span class="badge"><?php echo e(ucfirst($tank['category'])); ?></span></td>
                <td>
                    <?php if (!empty($tank['image_path'])): ?>
                        <img class="tank-thumb" src="<?php echo e($tank['image_path']); ?>" alt="<?php echo e($tank['name']); ?>">
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td><?php echo e((string) $tank['size_kg']); ?></td>
                <td>PHP <?php echo e((string) number_format((float) $tank['price'], 2)); ?></td>
                <td><?php echo e((string) $tank['available_qty']); ?></td>
                <td><?php echo $tank['active'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <form method="post" class="form" style="gap:6px">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo e((string) $tank['id']); ?>">
                        <input class="input" type="number" step="0.01" name="price" value="<?php echo e((string) $tank['price']); ?>" required>
                        <input class="input" type="number" name="available_qty" value="<?php echo e((string) $tank['available_qty']); ?>" required>
                        <label>
                            <input type="checkbox" name="active" <?php echo $tank['active'] ? 'checked' : ''; ?>> Active
                        </label>
                        <button class="button" type="submit">Save</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo e((string) $tank['id']); ?>">
                        <button class="button danger" data-confirm="Retire this tank?" type="submit">Retire</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
