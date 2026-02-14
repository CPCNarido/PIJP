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
            <div class="brand-mark">PIJP</div>
            <div>
                <div class="brand-title"><?php echo e($config['app_name']); ?></div>
                <div class="brand-subtitle">Track cylinders. Control supply.</div>
            </div>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
        <nav class="nav" id="mainNav">
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
                    <a href="/user/cart.php" class="cart-link">
                        🛒 Cart
                        <span class="cart-badge" style="display: none;">0</span>
                    </a>
                <?php endif; ?>
                <a href="/logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if ($flash): ?>
        <div class="flash <?php echo e($flash['type']); ?>">
            <span>✓</span>
            <span><?php echo e($flash['message']); ?></span>
        </div>
    <?php endif; ?>

    <main class="page">
