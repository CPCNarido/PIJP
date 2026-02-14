<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

// Check authentication for API
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = db();
$action = $_GET['action'] ?? '';

// Get cart count
if ($action === 'count') {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(qty), 0) as count FROM cart WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $result = $stmt->fetch();
    echo json_encode(['success' => true, 'count' => (int) $result['count']]);
    exit;
}

// Get cart items
if ($action === 'get') {
    $stmt = $pdo->prepare('
        SELECT c.id, c.gas_tank_id, c.qty, g.name, g.category, g.image_path, g.size_kg, g.price, g.available_qty
        FROM cart c
        JOIN gas_tanks g ON c.gas_tank_id = g.id
        WHERE c.user_id = :user_id AND g.active = 1
        ORDER BY c.created_at DESC
    ');
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $items = $stmt->fetchAll();
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

// POST requests only beyond this point
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Add to cart
if ($action === 'add') {
    $tank_id = (int) ($_POST['tank_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 1);

    if ($tank_id <= 0 || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit;
    }

    try {
        // Check if product exists and is available
        $stmt = $pdo->prepare('SELECT id, available_qty FROM gas_tanks WHERE id = :id AND active = 1');
        $stmt->execute(['id' => $tank_id]);
        $tank = $stmt->fetch();

        if (!$tank) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        if ((int) $tank['available_qty'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
            exit;
        }

        // Check if item already in cart
        $stmt = $pdo->prepare('SELECT id, qty FROM cart WHERE user_id = :user_id AND gas_tank_id = :tank_id');
        $stmt->execute(['user_id' => $_SESSION['user_id'], 'tank_id' => $tank_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $new_qty = (int) $existing['qty'] + $qty;
            if ($new_qty > (int) $tank['available_qty']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
                exit;
            }
            $stmt = $pdo->prepare('UPDATE cart SET qty = :qty WHERE id = :id');
            $stmt->execute(['qty' => $new_qty, 'id' => $existing['id']]);
        } else {
            if ($qty > (int) $tank['available_qty']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
                exit;
            }
            $stmt = $pdo->prepare('INSERT INTO cart (user_id, gas_tank_id, qty) VALUES (:user_id, :tank_id, :qty)');
            $stmt->execute(['user_id' => $_SESSION['user_id'], 'tank_id' => $tank_id, 'qty' => $qty]);
        }

        echo json_encode(['success' => true, 'message' => 'Added to cart']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
    }
    exit;
}

// Update cart item quantity
if ($action === 'update') {
    $cart_id = (int) ($_POST['cart_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 0);

    if ($cart_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
        exit;
    }

    if ($qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
        exit;
    }

    try {
        // Verify cart item belongs to user and check stock
        $stmt = $pdo->prepare('
            SELECT c.id, g.available_qty
            FROM cart c
            JOIN gas_tanks g ON c.gas_tank_id = g.id
            WHERE c.id = :cart_id AND c.user_id = :user_id AND g.active = 1
        ');
        $stmt->execute(['cart_id' => $cart_id, 'user_id' => $_SESSION['user_id']]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit;
        }

        if ($qty > (int) $item['available_qty']) {
            echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE cart SET qty = :qty WHERE id = :id');
        $stmt->execute(['qty' => $qty, 'id' => $cart_id]);

        echo json_encode(['success' => true, 'message' => 'Cart updated']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating cart']);
    }
    exit;
}

// Remove from cart
if ($action === 'remove') {
    $cart_id = (int) ($_POST['cart_id'] ?? 0);

    if ($cart_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM cart WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $cart_id, 'user_id' => $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'Removed from cart']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error removing from cart']);
    }
    exit;
}

// Clear entire cart
if ($action === 'clear') {
    try {
        $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error clearing cart']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
