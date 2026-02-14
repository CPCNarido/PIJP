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

    set_flash('error', 'Invalid email or password.');
}
?>

<div class="hero">
    <div style="max-width: 400px; margin: 0 auto; width: 100%;">
        <div class="hero-card">
            <h1 class="hero-title" style="font-size: 32px; text-align: center;">Welcome Back</h1>
            <p class="hero-copy" style="text-align: center; margin-bottom: 32px;">Log in to manage your orders</p>

            <form class="form" method="post">
                <div>
                    <label for="email">Email address</label>
                    <input class="input" type="email" id="email" name="email" required placeholder="admin@pijp.local">
                </div>

                <div>
                    <label for="password">Password</label>
                    <input class="input" type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <button class="button" type="submit" style="width: 100%;">Login</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p class="hero-copy">Don't have an account? <a href="/register.php" style="font-weight: 600;">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
