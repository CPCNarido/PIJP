<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $size = (float) ($_POST['size_kg'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $qty = (int) ($_POST['available_qty'] ?? 0);

        if ($name === '' || $size <= 0 || $price <= 0) {
            set_flash('error', 'Provide valid tank details.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO gas_tanks (name, size_kg, price, available_qty) VALUES (:name, :size, :price, :qty)');
            $stmt->execute(['name' => $name, 'size' => $size, 'price' => $price, 'qty' => $qty]);
            set_flash('success', 'Tank added.');
            redirect('/admin/stock.php');
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

$tanks = $pdo->query('SELECT id, name, size_kg, price, available_qty, active FROM gas_tanks ORDER BY name')->fetchAll();
?>

<h2 class="section-title">Manage Gas Tank Stock</h2>

<div class="card">
    <form class="form" method="post">
        <input type="hidden" name="action" value="add">
        <label for="name">Tank name</label>
        <input class="input" id="name" name="name" required>

        <label for="size_kg">Size (kg)</label>
        <input class="input" type="number" step="0.1" id="size_kg" name="size_kg" required>

        <label for="price">Price (PHP)</label>
        <input class="input" type="number" step="0.01" id="price" name="price" required>

        <label for="available_qty">Available quantity</label>
        <input class="input" type="number" id="available_qty" name="available_qty" min="0" required>

        <button class="button" type="submit">Add tank</button>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Name</th>
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
