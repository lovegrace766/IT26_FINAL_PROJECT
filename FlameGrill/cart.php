<?php
// ============================================================
// cart.php — Shopping Cart (CaterCloud Version)
// Displays items from session, handles quantity changes
// ============================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

 $userId = $_SESSION['user_id'];
 $userRole = $_SESSION['user_role'] ?? 'customer';
 $userFirst = $_SESSION['user_first'] ?? 'User';
 $userAvatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=150&h=150&fit=crop';
 $message = '';

// ---- Handle quantity changes and removal ----
if (isset($_GET['increase'])) {
    $idx = (int)$_GET['increase'];
    if (isset($_SESSION['cart'][$idx])) {
        $_SESSION['cart'][$idx]['qty']++;
    }
    header("Location: cart.php");
    exit();
}

if (isset($_GET['decrease'])) {
    $idx = (int)$_GET['decrease'];
    if (isset($_SESSION['cart'][$idx])) {
        $_SESSION['cart'][$idx]['qty']--;
        if ($_SESSION['cart'][$idx]['qty'] <= 0) {
            array_splice($_SESSION['cart'], $idx, 1);
        }
    }
    header("Location: cart.php");
    exit();
}

if (isset($_GET['remove'])) {
    $idx = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$idx])) {
        array_splice($_SESSION['cart'], $idx, 1);
    }
    header("Location: cart.php");
    exit();
}

// ---- Fetch Cart Data ----
 $cart = $_SESSION['cart'] ?? [];
 $cartTotal = 0;
foreach ($cart as $item) {
    $cartTotal += $item['price'] * $item['qty'];
}
 $cartCount = array_sum(array_column($cart, 'qty'));

// Success message from place_order.php
if (isset($_SESSION['order_success'])) {
    $message = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CaterCloud — Cart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon"><i class="fas fa-cloud"></i></div>
                <span class="sidebar-logo-text">CaterCloud</span>
            </div>
            <div class="nav-items">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-house"></i><span class="nav-label">Menu</span></a>
                <a href="orders.php" class="nav-item"><i class="fas fa-receipt"></i><span class="nav-label">Orders</span></a>
                <a href="analytics.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Analytics</span></a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user"></i><span class="nav-label">Profile</span></a>
                <?php if ($userRole === 'admin'): ?>
                <a href="manage.php" class="nav-item"><i class="fas fa-gear"></i><span class="nav-label">Manage</span></a>
                <?php endif; ?>
            </div>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item"><i class="fas fa-right-from-bracket"></i><span class="nav-label">Logout</span></a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <div>
                    <h1>Your Cart</h1>
                    <p><?php echo $cartCount; ?> item(s) in your bag</p>
                </div>
                <div class="topbar-right">
                    <a href="dashboard.php" class="btn-secondary" style="text-decoration:none; display:inline-block;">
                        <i class="fas fa-arrow-left" style="margin-right:6px;"></i>Back to Menu
                    </a>
                    <a href="profile.php"><img src="<?php echo htmlspecialchars($userAvatar); ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--border);"></a>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="auth-success" style="margin-bottom:20px; display:block; text-align:center;">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart)): ?>
                <div style="text-align:center; padding:80px 20px;">
                    <i class="fas fa-bag-shopping" style="font-size:64px; color:var(--border); margin-bottom:20px; display:block;"></i>
                    <h2 style="color:var(--muted); margin-bottom:12px;">Your cart is empty</h2>
                    <p style="color:var(--muted); margin-bottom:24px;">Browse the menu and add some items!</p>
                    <a href="dashboard.php" class="btn-primary" style="display:inline-block; width:auto; padding:12px 32px; text-decoration:none;">
                        Browse Menu
                    </a>
                </div>
            <?php else: ?>
                <div style="max-width:800px; margin:0 auto;">
                    <?php foreach ($cart as $idx => $item): 
                        $itemImg = !empty($item['image']) ? $item['image'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop';
                    ?>
                    <div style="display:flex; align-items:center; gap:20px; background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:16px; margin-bottom:16px; box-shadow:var(--shadow);">
                        <img src="<?php echo htmlspecialchars($itemImg); ?>" style="width:90px; height:90px; border-radius:12px; object-fit:cover;">
                        
                        <div style="flex:1;">
                            <div style="font-family:'Outfit',sans-serif; font-size:18px; font-weight:700;"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div style="color:var(--accent); font-weight:700; margin-top:4px;">$<?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                        </div>

                        <div style="display:flex; align-items:center; gap:12px;">
                            <a href="cart.php?decrease=<?php echo $idx; ?>" style="width:32px; height:32px; background:var(--surface2); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--text); text-decoration:none; font-weight:700;">
                                <i class="fas fa-minus" style="font-size:12px;"></i>
                            </a>
                            <span style="font-weight:700; font-size:16px; min-width:20px; text-align:center;"><?php echo $item['qty']; ?></span>
                            <a href="cart.php?increase=<?php echo $idx; ?>" style="width:32px; height:32px; background:var(--surface2); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--text); text-decoration:none; font-weight:700;">
                                <i class="fas fa-plus" style="font-size:12px;"></i>
                            </a>
                            
                            <a href="cart.php?remove=<?php echo $idx; ?>" style="margin-left:12px; color:var(--danger); font-size:18px;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Total and Checkout -->
                    <div style="background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:24px; margin-top:20px; box-shadow:var(--shadow-lg);">
                        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                            <span style="font-size:18px; font-weight:500; color:var(--muted);">Total Amount</span>
                            <span style="font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--accent);">$<?php echo number_format($cartTotal, 2); ?></span>
                        </div>
                        
                        <form method="POST" action="place_order.php">
                            <button type="submit" class="btn-primary" style="width:100%; padding:16px; font-size:16px; border-radius:12px;">
                                <i class="fas fa-lock" style="margin-right:8px;"></i> Place Order
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>