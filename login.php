<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user((int) $user['id']);
        if ($user['role'] === 'admin') {
            redirect('/admin/index.php');
        }
        redirect('/user/index.php');
    }

    set_flash('error', 'Invalid credentials.');
}
?>

<section class="hero">
    <div class="hero-card">
        <h2 class="section-title">Log in</h2>
        <form class="form" method="post">
            <label for="email">Email address</label>
            <input class="input" type="email" id="email" name="email" required>

            <label for="password">Password</label>
            <input class="input" type="password" id="password" name="password" required>

            <button class="button" type="submit">Login</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
