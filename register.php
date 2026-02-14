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

<section class="hero">
    <div class="hero-card">
        <h2 class="section-title">Create your account</h2>
        <form class="form" method="post">
            <label for="name">Full name</label>
            <input class="input" id="name" name="name" required>

            <label for="email">Email address</label>
            <input class="input" type="email" id="email" name="email" required>

            <label for="password">Password</label>
            <input class="input" type="password" id="password" name="password" required>

            <button class="button" type="submit">Register</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
