<?php
require_once __DIR__ . '/../partials/header.php';
require_admin();
require_once __DIR__ . '/../db.php';

$pdo = db();

$phonePattern = '/^[0-9+\-\s()]{7,20}$/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '' || $phone === '') {
            set_flash('error', 'Name and phone are required.');
        } else if (mb_strlen($name) > 120) {
            set_flash('error', 'Name is too long.');
        } else if (!preg_match($phonePattern, $phone)) {
            set_flash('error', 'Provide a valid phone number.');
        } else if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Provide a valid email address.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO staff (name, phone, email) VALUES (:name, :phone, :email)');
            $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email !== '' ? $email : null]);
            set_flash('success', 'Delivery staff added.');
            redirect('/admin/staff.php');
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        if ($id <= 0 || $name === '' || $phone === '') {
            set_flash('error', 'Provide valid staff details.');
        } else if (mb_strlen($name) > 120) {
            set_flash('error', 'Name is too long.');
        } else if (!preg_match($phonePattern, $phone)) {
            set_flash('error', 'Provide a valid phone number.');
        } else if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Provide a valid email address.');
        } else {
            $stmt = $pdo->prepare('UPDATE staff SET name = :name, phone = :phone, email = :email, active = :active WHERE id = :id');
            $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email !== '' ? $email : null, 'active' => $active, 'id' => $id]);
            set_flash('success', 'Staff updated.');
            redirect('/admin/staff.php');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE staff SET active = 0 WHERE id = :id');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'Staff removed.');
            redirect('/admin/staff.php');
        } else {
            set_flash('error', 'Invalid staff selection.');
        }
    }
}

$staff = $pdo->query('SELECT id, name, phone, email, active FROM staff ORDER BY name')->fetchAll();
?>

<h2 class="section-title">Manage Delivery Staff</h2>

<div class="card">
    <h3>Add New Delivery Staff</h3>
    <form class="form" method="post">
        <input type="hidden" name="action" value="add">
        <div>
            <label for="name">Full name</label>
            <input class="input" id="name" name="name" required maxlength="120">
        </div>

        <div>
            <label for="phone">Phone number</label>
            <input class="input" id="phone" name="phone" required maxlength="20" pattern="[0-9+\-\s()]{7,20}" placeholder="+63 9xx xxx xxxx">
        </div>

        <div>
            <label for="email">Email (optional)</label>
            <input class="input" type="email" id="email" name="email" maxlength="160">
        </div>

        <button class="button" type="submit">Add Staff</button>
    </form>
</div>

<h3 style="margin-top: 32px;">Current Staff</h3>
<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($staff as $member): ?>
            <tr>
                <td><?php echo e($member['name']); ?></td>
                <td><?php echo e($member['phone']); ?></td>
                <td><?php echo e($member['email'] ?? '—'); ?></td>
                <td><?php echo $member['active'] ? '<span class="badge">Active</span>' : '<span style="color: var(--muted);">Inactive</span>'; ?></td>
                <td>
                    <form method="post" style="display: flex; gap: 8px;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo e((string) $member['id']); ?>">
                        <input class="input" type="text" name="name" value="<?php echo e($member['name']); ?>" required maxlength="120">
                        <input class="input" type="text" name="phone" value="<?php echo e($member['phone']); ?>" required maxlength="20" pattern="[0-9+\-\s()]{7,20}">
                        <input class="input" type="email" name="email" value="<?php echo e($member['email'] ?? ''); ?>" maxlength="160">
                        <label style="display: flex; align-items: center; gap: 6px;">
                            <input type="checkbox" name="active" <?php echo $member['active'] ? 'checked' : ''; ?>> Active
                        </label>
                        <button class="button" type="submit">Save</button>
                    </form>
                    <form method="post" style="margin-top: 8px;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo e((string) $member['id']); ?>">
                        <button class="button danger" data-confirm="Remove this staff member?" type="submit">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
