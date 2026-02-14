<?php
require_once __DIR__ . '/../auth.php';
$user = current_user();
$flash = get_flash();
$config = require __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($config['app_name']); ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script defer src="/assets/app.js"></script>
</head>
<body>
    <header class="site-header">
        <div class="brand">
            <span class="brand-mark">PIJP</span>
            <div class="brand-text">
                <div class="brand-title">PIJP Gas Ordering</div>
                <div class="brand-subtitle">Track cylinders. Control supply.</div>
            </div>
        </div>
        <nav class="nav">
            <a href="/">Home</a>
            <?php if (!$user): ?>
                <a href="/register.php">Register</a>
                <a href="/login.php" class="cta">Login</a>
            <?php else: ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="/admin/index.php">Admin</a>
                <?php else: ?>
                    <a href="/user/index.php">Dashboard</a>
                    <a href="/user/orders.php">My Orders</a>
                <?php endif; ?>
                <a href="/logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($flash): ?>
        <div class="flash <?php echo e($flash['type']); ?>">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <main class="page">
