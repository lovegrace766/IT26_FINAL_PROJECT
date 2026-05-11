<?php
// ============================================================
// place_order.php — Processes the cart and saves to Database
// ============================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connection.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

 $userId = $_SESSION['user_id'];
 $cart = $_SESSION['cart'] ?? [];

// If cart is empty, go back to menu
if (empty($cart)) {
    header("Location: dashboard.php");
    exit();
}

// Calculate Total
 $totalAmount = 0;
foreach ($cart as $item) {
    $totalAmount += $item['price'] * $item['qty'];
}

// Insert into ORDERS table
 $stmt = $conn->prepare("INSERT INTO orders (userId, totalAmount, status) VALUES (?, ?, 'pending')");
 $stmt->bind_param("id", $userId, $totalAmount);
 $stmt->execute();
 $orderId = $stmt->insert_id; // Get the new order ID

// Insert into ORDER_ITEMS table
 $stmt = $conn->prepare("INSERT INTO order_items (orderId, productId, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($cart as $item) {
    $stmt->bind_param("iiid", $orderId, $item['productId'], $item['qty'], $item['price']);
    $stmt->execute();
}

// Clear the cart
unset($_SESSION['cart']);

// Redirect to Orders page with a success message
 $_SESSION['order_success'] = "Order #$orderId placed successfully!";
header("Location: orders.php");
exit();
?>