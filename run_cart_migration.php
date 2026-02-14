<?php
/**
 * Cart Migration Runner
 * Run this file once by visiting: http://localhost/run_cart_migration.php
 */

require_once __DIR__ . '/db.php';

try {
    $pdo = db();
    
    // Read and execute migration
    $sql = file_get_contents(__DIR__ . '/migrations/add_cart.sql');
    $pdo->exec($sql);
    
    echo "✓ Cart table created successfully!<br>";
    echo "<br>You can now:";
    echo "<ul>";
    echo "<li>Delete this file (run_cart_migration.php)</li>";
    echo "<li>Start using the cart feature</li>";
    echo "</ul>";
    echo "<br><a href='/user/index.php'>Go to Dashboard</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br><br>You may need to run the SQL manually using phpMyAdmin or HeidiSQL.";
    echo "<br>SQL file location: migrations/add_cart.sql";
}
