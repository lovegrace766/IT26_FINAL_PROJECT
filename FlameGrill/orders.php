<?php
// ============================================================
// orders.php — User Orders Page (CaterCloud Version)
// Features: Connected to Login, Cart, and shows Food Images
// ============================================================

// MUST HAVE THIS TO KNOW WHO IS LOGGED IN!
session_start(); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

 $userId = $_SESSION['user_id'];
 $userRole = $_SESSION['user_role'] ?? 'customer';
 $userFirst = $_SESSION['user_first'] ?? 'User';
 $userAvatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=150&h=150&fit=crop';

// Status filter
 $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
 $allowedStatuses = ['pending', 'processing', 'completed'];
if (!in_array($statusFilter, $allowedStatuses) && $statusFilter !== 'all') {
    $statusFilter = 'all';
}

// Build query based on filter and user role
// SQL JOIN: Retrieves order data alongside user data
if ($userRole === 'admin') {
    if ($statusFilter === 'all') {
        $stmt = $conn->prepare("SELECT o.id, o.totalAmount, o.status, o.createdAt, 
                                        u.firstName, u.lastName
                                 FROM orders o 
                                 INNER JOIN users u ON o.userId = u.id
                                 ORDER BY o.createdAt DESC");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT o.id, o.totalAmount, o.status, o.createdAt,
                                        u.firstName, u.lastName
                                 FROM orders o 
                                 INNER JOIN users u ON o.userId = u.id
                                 WHERE o.status = ? 
                                 ORDER BY o.createdAt DESC");
        $stmt->bind_param("s", $statusFilter);
        $stmt->execute();
    }
} else {
    if ($statusFilter === 'all') {
        $stmt = $conn->prepare("SELECT o.id, o.totalAmount, o.status, o.createdAt,
                                        u.firstName, u.lastName
                                 FROM orders o 
                                 INNER JOIN users u ON o.userId = u.id
                                 WHERE o.userId = ? 
                                 ORDER BY o.createdAt DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT o.id, o.totalAmount, o.status, o.createdAt,
                                        u.firstName, u.lastName
                                 FROM orders o 
                                 INNER JOIN users u ON o.userId = u.id
                                 WHERE o.userId = ? AND o.status = ? 
                                 ORDER BY o.createdAt DESC");
        $stmt->bind_param("is", $userId, $statusFilter);
        $stmt->execute();
    }
}
 $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get order items for each order (JOIN to get product names AND images)
 $orderData = [];
foreach ($orders as $order) {
    $stmt2 = $conn->prepare("SELECT oi.quantity, oi.price, p.name, p.image 
                              FROM order_items oi 
                              INNER JOIN products p ON oi.productId = p.id 
                              WHERE oi.orderId = ?");
    $stmt2->bind_param("i", $order['id']);
    $stmt2->execute();
    $items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $orderData[] = ['order' => $order, 'items' => $items];
}

// Connects to Cart Session for the topbar badge
 $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CaterCloud — Orders</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-layout">
        <nav class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon"><i class="fas fa-cloud"></i></div>
                <span class="sidebar-logo-text">CaterCloud</span>
            </div>
            <div class="nav-items">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-house"></i><span class="nav-label">Menu</span></a>
                <a href="orders.php" class="nav-item active"><i class="fas fa-receipt"></i><span class="nav-label">Orders</span></a>
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

        <main class="main-content">
            <div class="topbar">
                <div>
                    <h1>My Orders</h1>
                    <p>Track your current and past orders</p>
                </div>
                <div class="topbar-right">
                    <!-- Cart Button connected to Session -->
                    <a href="cart.php" style="position:relative; font-size:20px; color:var(--text);">
                        <i class="fas fa-bag-shopping"></i>
                        <?php if ($cartCount > 0): ?>
                            <span style="position:absolute; top:-8px; right:-8px; background:var(--danger); color:#fff; border-radius:50%; width:18px; height:18px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <!-- Avatar connected to Session -->
                    <a href="profile.php"><img src="<?php echo htmlspecialchars($userAvatar); ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--border);"></a>
                </div>
            </div>

            <!-- Status Filters -->
            <div class="cat-pills" style="margin-bottom: 24px;">
                <a href="orders.php?status=all" class="cat-pill <?php echo $statusFilter==='all'?'active':''; ?>">All</a>
                <a href="orders.php?status=pending" class="cat-pill <?php echo $statusFilter==='pending'?'active':''; ?>">Pending</a>
                <a href="orders.php?status=processing" class="cat-pill <?php echo $statusFilter==='processing'?'active':''; ?>">Processing</a>
                <a href="orders.php?status=completed" class="cat-pill <?php echo $statusFilter==='completed'?'active':''; ?>">Completed</a>
            </div>

            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <?php if ($userRole === 'admin'): ?><th>Customer</th><?php endif; ?>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orderData)): ?>
                            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:40px;">No orders found. <a href="dashboard.php" style="color:var(--accent);">Order something!</a></td></tr>
                        <?php else: ?>
                            <?php foreach ($orderData as $data):
                                $o = $data['order'];
                                $items = $data['items'];
                                $statusClass = $o['status']==='pending' ? 'status-pending' : ($o['status']==='processing' ? 'status-processing' : 'status-completed');
                            ?>
                            <tr>
                                <td style="font-weight:700;">#<?php echo $o['id']; ?></td>
                                <?php if ($userRole === 'admin'): ?>
                                    <td><?php echo htmlspecialchars($o['firstName'] . ' ' . $o['lastName']); ?></td>
                                <?php endif; ?>
                                
                                <!-- Items Column with Images connected to Dashboard Menu -->
                                <td style="max-width:300px;">
                                    <?php foreach ($items as $item): 
                                        $itemImg = !empty($item['image']) ? $item['image'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&h=100&fit=crop';
                                    ?>
                                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                                        <img src="<?php echo htmlspecialchars($itemImg); ?>" style="width:35px; height:35px; border-radius:8px; object-fit:cover;" alt="">
                                        <span style="font-size:13px; color:var(--text);"><?php echo htmlspecialchars($item['name']); ?> <span style="color:var(--muted);">x<?php echo $item['quantity']; ?></span></span>
                                    </div>
                                    <?php endforeach; ?>
                                </td>

                                <td style="font-weight:700;color:var(--accent);">$<?php echo number_format($o['totalAmount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($o['status']); ?>
                                    </span>
                                </td>
                                <td style="color:var(--muted);">
                                    <?php echo date('M j, g:i A', strtotime($o['createdAt'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>