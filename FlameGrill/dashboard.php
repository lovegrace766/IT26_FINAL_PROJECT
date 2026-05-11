<?php
// ============================================================
// dashboard.php — CaterCloud Unique Dashboard
// Features: Hero Banner, Inline Image Upload, Chart.js
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
 $userAvatar = $_SESSION['user_avatar'] ?? 'https://i.pravatar.cc/150?u=default';

// ---- HANDLE IMAGE UPLOAD DIRECTLY FROM DASHBOARD ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_image'])) {
    $productId = (int)$_POST['product_id'];
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['product_image']['tmp_name'];
        $fileName = $_FILES['product_image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowedExts)) {
            $newFileName = 'product_' . $productId . '_' . time() . '.' . $fileExt;
            $destPath = 'uploads/' . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
                $stmt->bind_param("si", $destPath, $productId);
                $stmt->execute();
            }
        }
    }
    header("Location: dashboard.php");
    exit();
}

// ---- HANDLE ADD TO CART ----
if (isset($_POST['add_to_cart'])) {
    $productId = (int)$_POST['product_id'];
    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['productId'] == $product['id']) {
                $item['qty']++;
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) {
            $_SESSION['cart'][] = [
                'productId' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'qty' => 1,
                'image' => $product['image'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&h=400&fit=crop'
            ];
        }
    }
    header("Location: dashboard.php");
    exit();
}

// ---- STATS ----
 $statProducts = 0; $statOrders = 0; $statPending = 0; $statRevenue = 0;

 $res = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($res) $statProducts = (int)$res->fetch_assoc()['total'];

 $res = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($res) $statOrders = (int)$res->fetch_assoc()['total'];

 $res = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'");
if ($res) $statPending = (int)$res->fetch_assoc()['total'];

 $res = $conn->query("SELECT COALESCE(SUM(totalAmount), 0) AS total FROM orders WHERE status = 'completed'");
if ($res) $statRevenue = (float)$res->fetch_assoc()['total'];

// ---- FILTERS ----
 $categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'All';
 $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($categoryFilter !== 'All' && !empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND name LIKE ? ORDER BY name");
    $likeSearch = "%" . $searchQuery . "%";
    $stmt->bind_param("ss", $categoryFilter, $likeSearch);
} elseif ($categoryFilter !== 'All') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY name");
    $stmt->bind_param("s", $categoryFilter);
} elseif (!empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY name");
    $likeSearch = "%" . $searchQuery . "%";
    $stmt->bind_param("s", $likeSearch);
} else {
    $stmt = $conn->prepare("SELECT * FROM products ORDER BY name");
}
 $stmt->execute();
 $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

 $catResult = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
 $categories = $catResult ? $catResult->fetch_all(MYSQLI_ASSOC) : [];

 $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;

// ---- CHART DATA ----
 $pLabels = ['Burgers', 'Pizza', 'Sides', 'Drinks']; 
 $pData = [12, 19, 8, 15]; 
 $popRes = $conn->query("SELECT p.name, SUM(oi.quantity) as qty FROM order_items oi INNER JOIN products p ON oi.productId = p.id GROUP BY oi.productId ORDER BY qty DESC LIMIT 5");
if ($popRes && $popRes->num_rows > 0) {
    $pLabels = []; $pData = [];
    while($r = $popRes->fetch_assoc()) { $pLabels[] = $r['name']; $pData[] = (int)$r['qty']; }
}

 $sLabels = ['Pending', 'Processing', 'Completed']; 
 $sData = [5, 3, 10]; 
 $statRes = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
if ($statRes && $statRes->num_rows > 0) {
    $sLabels = []; $sData = [];
    while($r = $statRes->fetch_assoc()) { $sLabels[] = ucfirst($r['status']); $sData[] = (int)$r['count']; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CaterCloud — Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard.php" class="nav-item active"><i class="fas fa-house"></i><span class="nav-label">Menu</span></a>
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
            <!-- Top Bar -->
            <div class="topbar">
                <div>
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($userFirst); ?>!</p>
                </div>
                <div class="topbar-right">
                    <form class="topbar-search" method="GET" action="dashboard.php">
                        <i class="fas fa-search" style="color:var(--muted)"></i>
                        <input type="text" name="search" placeholder="Search menu..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </form>
                    <a href="cart.php" style="position:relative; font-size:20px; color:var(--text);">
                        <i class="fas fa-bag-shopping"></i>
                        <?php if ($cartCount > 0): ?>
                            <span style="position:absolute; top:-8px; right:-8px; background:var(--danger); color:#fff; border-radius:50%; width:18px; height:18px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php"><img src="<?php echo htmlspecialchars($userAvatar); ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--border);"></a>
                </div>
            </div>

            <!-- Unique Hero Banner -->
            <div class="hero-banner">
                <div class="hero-text">
                    <h2>Delicious food, delivered fast ☁️</h2>
                    <p>Order your favorites from the cloud. Fresh ingredients, amazing taste.</p>
                </div>
                <div class="hero-icon">
                    <i class="fas fa-cloud-meatball"></i>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card" style="border-left: 4px solid var(--accent);">
                    <div class="stat-icon" style="background:rgba(37,99,235,0.1); color:var(--accent);"><i class="fas fa-utensils"></i></div>
                    <div><div class="stat-value"><?php echo $statProducts; ?></div><div class="stat-label">Menu Items</div></div>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--success);">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.1); color:var(--success);"><i class="fas fa-bag-shopping"></i></div>
                    <div><div class="stat-value"><?php echo $statOrders; ?></div><div class="stat-label">Total Orders</div></div>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--warning);">
                    <div class="stat-icon" style="background:rgba(245,158,11,0.1); color:var(--warning);"><i class="fas fa-clock"></i></div>
                    <div><div class="stat-value"><?php echo $statPending; ?></div><div class="stat-label">Pending</div></div>
                </div>
                <div class="stat-card" style="border-left: 4px solid var(--info);">
                    <div class="stat-icon" style="background:rgba(14,165,233,0.1); color:var(--info);"><i class="fas fa-dollar-sign"></i></div>
                    <div><div class="stat-value">$<?php echo number_format($statRevenue, 2); ?></div><div class="stat-label">Revenue</div></div>
                </div>
            </div>

            <!-- Categories -->
            <div class="cat-pills">
                <a href="dashboard.php" class="cat-pill <?php echo $categoryFilter === 'All' ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                <a href="dashboard.php?category=<?php echo urlencode($cat['category']); ?>" class="cat-pill <?php echo $categoryFilter === $cat['category'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['category']); ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Menu Grid -->
            <div class="section-head"><h2>Menu</h2></div>
            <div class="food-grid-main">
                <?php foreach ($products as $p): 
                    $imgSrc = !empty($p['image']) ? $p['image'] : 'pizza.jpg';
                    $discount = isset($p['discount']) ? (int)$p['discount'] : 0;
                    $discountedPrice = $discount > 0 ? $p['price'] * (1 - $discount/100) : $p['price'];
                ?>
                <div class="food-card">
                    <div class="food-card-img-wrap">
                        <img class="food-card-img" src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <span class="food-card-cat"><?php echo htmlspecialchars($p['category']); ?></span>
                        <?php if ($discount > 0): ?>
                            <span class="food-card-discount">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                        
                        <!-- CHANGE IMAGE BUTTON (Visible only for Admins) -->
                        <?php if ($userRole === 'admin'): ?>
                        <div class="card-img-edit-overlay">
                            <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="update_image" value="1">
                                <input type="file" name="product_image" accept="image/*" id="img_upload_<?php echo $p['id']; ?>" style="display:none;" onchange="this.form.submit()">
                                <button type="button" class="edit-img-btn" onclick="document.getElementById('img_upload_<?php echo $p['id']; ?>').click()">
                                    <i class="fas fa-camera"></i> Change Pic
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="food-card-body">
                        <div class="food-card-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="food-card-desc"><?php echo htmlspecialchars($p['description']); ?></div>
                        <div class="food-card-footer">
                            <div>
                                <span class="food-card-price">$<?php echo number_format($discountedPrice, 2); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="food-card-old-price">$<?php echo number_format($p['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="dashboard.php">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" name="add_to_cart" class="add-cart-btn"><i class="fas fa-plus"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Chart.js Integration -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3 style="margin-bottom:16px;">Popular Items</h3>
                    <canvas id="popularChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3 style="margin-bottom:16px;">Order Status</h3>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Chart.js: Popular Items Bar Chart
    new Chart(document.getElementById('popularChart'), {
        type: 'bar',
        data: { labels: <?php echo json_encode($pLabels); ?>, datasets: [{ label:'Orders', data: <?php echo json_encode($pData); ?>, backgroundColor: '#2563eb', borderRadius: 8 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
    });

    // Chart.js: Order Status Doughnut
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: <?php echo json_encode($sLabels); ?>, datasets: [{ data: <?php echo json_encode($sData); ?>, backgroundColor: ['#f59e0b', '#0ea5e9', '#10b981'], borderWidth: 0 }] },
        options: { responsive: true, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
    });
    </script>
</body>
</html>