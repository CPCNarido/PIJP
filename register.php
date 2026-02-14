<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        set_flash('error', 'All fields are required.');
    } else {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            set_flash('error', 'Email already registered.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)');
            $stmt->execute(['name' => $name, 'email' => $email, 'hash' => $hash]);
            set_flash('success', 'Registration complete. Please log in.');
            redirect('/login.php');
        }
    }
}
?>

<div class="hero">
    <div style="max-width: 400px; margin: 0 auto; width: 100%;">
        <div class="hero-card">
            <h1 class="hero-title" style="font-size: 32px; text-align: center;">Create Account</h1>
            <p class="hero-copy" style="text-align: center; margin-bottom: 32px;">Join PIJP to order gas</p>

            <form class="form" method="post">
                <div>
                    <label for="name">Full name</label>
                    <input class="input" id="name" name="name" required placeholder="Your name">
                </div>

                <div>
                    <label for="email">Email address</label>
                    <input class="input" type="email" id="email" name="email" required placeholder="your@email.com">
                </div>

                <div>
                    <label for="password">Password</label>
                    <input class="input" type="password" id="password" name="password" required placeholder="At least 8 characters">
                </div>

                <button class="button" type="submit" style="width: 100%;">Create Account</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p class="hero-copy">Already have an account? <a href="/login.php" style="font-weight: 600;">Log in here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
