<?php
// ============================================================
// analytics.php — Analytics Dashboard with Chart.js
// Demonstrates SQL JOINs (INNER, LEFT, RIGHT, FULL OUTER)
// ============================================================

require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

 $userId = $_SESSION['user_id'];
 $userRole = $_SESSION['user_role'];
 $userFirst = $_SESSION['user_first'];
 $userAvatar = $_SESSION['user_avatar'] ?: 'https://picsum.photos/seed/avatar42/80/80.jpg';

// Only admin can access analytics
if ($userRole !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

 $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;

// ============================================================
// SQL JOIN #1: INNER JOIN
// Purpose: Show orders that have BOTH a valid user AND valid products
// Only matching records from all three tables appear
// ============================================================
 $innerJoinSQL = "
    SELECT o.id AS orderId,
           CONCAT(u.firstName, ' ', u.lastName) AS customer,
           p.name AS productName,
           oi.quantity,
           o.totalAmount,
           o.status
    FROM orders o
    INNER JOIN users u ON o.userId = u.id
    INNER JOIN order_items oi ON o.id = oi.orderId
    INNER JOIN products p ON oi.productId = p.id
    ORDER BY o.id DESC
";
 $innerJoinResult = $conn->query($innerJoinSQL);

// ============================================================
// SQL JOIN #2: LEFT JOIN
// Purpose: Show ALL users, even those without any orders
// NULL appears in order columns for users who never ordered
// ============================================================
 $leftJoinSQL = "
    SELECT u.firstName, u.lastName, o.id AS orderId, o.totalAmount
    FROM users u
    LEFT JOIN orders o ON u.id = o.userId
    ORDER BY u.id
";
 $leftJoinResult = $conn->query($leftJoinSQL);

// ============================================================
// SQL JOIN #3: RIGHT JOIN
// Purpose: Show ALL products, even those never ordered
// NULL appears in order columns for products with no orders
// ============================================================
 $rightJoinSQL = "
    SELECT p.name, p.price, oi.orderId
    FROM order_items oi
    RIGHT JOIN products p ON oi.productId = p.id
    ORDER BY p.id
";
 $rightJoinResult = $conn->query($rightJoinSQL);

// ============================================================
// SQL JOIN #4: FULL OUTER JOIN (simulated via UNION)
// MySQL doesn't support FULL OUTER JOIN natively.
// Simulated by combining LEFT JOIN and RIGHT JOIN with UNION.
// Purpose: Show ALL users and ALL orders, including unmatched
// records from both sides
// ============================================================
 $fullOuterJoinSQL = "
    SELECT u.firstName, u.lastName, o.id AS orderId, o.totalAmount, 'from_users' AS source
    FROM users u
    LEFT JOIN orders o ON u.id = o.userId

    UNION

    SELECT u.firstName, u.lastName, o.id AS orderId, o.totalAmount, 'from_orders' AS source
    FROM users u
    RIGHT JOIN orders o ON u.id = o.userId
    WHERE u.id IS NULL
    ORDER BY orderId
";
 $fullOuterJoinResult = $conn->query($fullOuterJoinSQL);

// ---- Chart Data: Popular Items (Bar) ----
 $barChartSQL = "
    SELECT p.name, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    INNER JOIN products p ON oi.productId = p.id
    GROUP BY oi.productId
    ORDER BY total_qty DESC LIMIT 8
";
 $barChartResult = $conn->query($barChartSQL);
 $barLabels = []; $barData = [];
while ($row = $barChartResult->fetch_assoc()) {
    $barLabels[] = strlen($row['name']) > 16 ? substr($row['name'],0,16).'...' : $row['name'];
    $barData[] = (int)$row['total_qty'];
}

// ---- Chart Data: Order Status (Doughnut) ----
 $doughnutSQL = "
    SELECT status, COUNT(*) AS count FROM orders GROUP BY status
";
 $doughnutResult = $conn->query($doughnutSQL);
 $statusLabels = []; $statusData = [];
while ($row = $doughnutResult->fetch_assoc()) {
    $statusLabels[] = ucfirst($row['status']);
    $statusData[] = (int)$row['count'];
}

// ---- Chart Data: 30-Day Trend (Line) ----
 $trendSQL = "
    SELECT DATE(createdAt) AS day, COUNT(*) AS count
    FROM orders
    WHERE createdAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(createdAt)
    ORDER BY day
";
 $trendResult = $conn->query($trendSQL);
 $trendLabels = []; $trendData = [];
for ($i = 29; $i >= 0; $i--) {
    $trendLabels[] = date('M j', strtotime("-$i days"));
    $trendData[] = 0;
}
while ($row = $trendResult->fetch_assoc()) {
    for ($d = 29; $d >= 0; $d--) {
        if (date('Y-m-d', strtotime("-$d days")) === $row['day']) {
            $trendData[29 - $d] = (int)$row['count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlameGrill — Analytics</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dash-layout">
        <nav class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon"><i class="fas fa-fire-flame-curved"></i></div>
                <span class="sidebar-logo-text">FlameGrill</span>
            </div>
            <div class="nav-items">
                <a href="dashboard.php" class="nav-item" style="text-decoration:none;color:inherit;">
                    <i class="fas fa-house"></i><span class="nav-label">Home</span>
                </a>
                <a href="orders.php" class="nav-item" style="text-decoration:none;color:inherit;">
                    <i class="fas fa-receipt"></i><span class="nav-label">My Orders</span>
                </a>
                <a href="profile.php" class="nav-item" style="text-decoration:none;color:inherit;">
                    <i class="fas fa-user"></i><span class="nav-label">Profile</span>
                </a>
                <div class="nav-item active">
                    <i class="fas fa-chart-line"></i><span class="nav-label">Analytics</span>
                </div>
                <a href="manage.php" class="nav-item" style="text-decoration:none;color:inherit;">
                    <i class="fas fa-gear"></i><span class="nav-label">Manage</span>
                </a>
            </div>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item" style="text-decoration:none;color:inherit;">
                    <i class="fas fa-right-from-bracket"></i><span class="nav-label">Logout</span>
                </a>
            </div>
        </nav>

        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h1>Analytics</h1>
                    <p>Data insights</p>
                </div>
                <div class="topbar-right">
                    <a href="cart.php" class="cart-btn">
                        <i class="fas fa-bag-shopping"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-count"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php">
                        <img class="user-avatar" src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Avatar">
                    </a>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>Popular Items (Bar Chart)</h3>
                    <canvas id="chartBar"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Order Status (Doughnut)</h3>
                    <canvas id="chartDoughnut"></canvas>
                </div>
            </div>
            <div class="charts-grid" style="margin-top:0;">
                <div class="chart-card" style="grid-column:1/-1;">
                    <h3>Order Trends (Last 30 Days)</h3>
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>

            <!-- SQL JOIN Demonstrations -->
            <div class="section-head" style="margin-top:24px;">
                <h2>SQL Join Demonstrations</h2>
            </div>

            <!-- INNER JOIN -->
            <div class="profile-section" style="margin-bottom:16px;">
                <h3>INNER JOIN — Orders with Users and Products</h3>
                <p style="color:var(--muted);font-size:13px;margin-bottom:12px;">
                    Shows only orders that have matching user and product records. Orphaned records are excluded.
                </p>
                <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:12px;font-family:monospace;font-size:12px;color:var(--accent);overflow-x:auto;">
                    SELECT o.id, u.firstName, u.lastName, p.name, oi.quantity, o.totalAmount, o.status<br>
                    FROM orders o<br>
                    INNER JOIN users u ON o.userId = u.id<br>
                    INNER JOIN order_items oi ON o.id = oi.orderId<br>
                    INNER JOIN products p ON oi.productId = p.id
                </div>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Order</th><th>Customer</th><th>Product</th><th>Qty</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php while ($row = $innerJoinResult->fetch_assoc()):
                                $sc = $row['status']==='pending'?'status-pending':($row['status']==='processing'?'status-processing':'status-completed');
                            ?>
                            <tr>
                                <td>#<?php echo $row['orderId']; ?></td>
                                <td><?php echo htmlspecialchars($row['customer']); ?></td>
                                <td><?php echo htmlspecialchars($row['productName']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td style="color:var(--accent);font-weight:600;">$<?php echo number_format($row['totalAmount'],2); ?></td>
                                <td><span class="status-badge <?php echo $sc; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LEFT JOIN -->
            <div class="profile-section" style="margin-bottom:16px;">
                <h3>LEFT JOIN — All Users with Their Orders</h3>
                <p style="color:var(--muted);font-size:13px;margin-bottom:12px;">
                    Shows all users, even those without any orders (NULL for order fields).
                </p>
                <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:12px;font-family:monospace;font-size:12px;color:var(--accent);overflow-x:auto;">
                    SELECT u.firstName, u.lastName, o.id AS orderId, o.totalAmount<br>
                    FROM users u<br>
                    LEFT JOIN orders o ON u.id = o.userId
                </div>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Customer</th><th>Order ID</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php while ($row = $leftJoinResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                                <td><?php echo $row['orderId'] ? '#'.$row['orderId'] : '<em style="color:var(--muted);">No orders</em>'; ?></td>
                                <td><?php echo $row['totalAmount'] ? '$'.number_format($row['totalAmount'],2) : '—'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RIGHT JOIN -->
            <div class="profile-section" style="margin-bottom:16px;">
                <h3>RIGHT JOIN — All Products Even If Not Ordered</h3>
                <p style="color:var(--muted);font-size:13px;margin-bottom:12px;">
                    Shows all products; NULL order info for products never ordered.
                </p>
                <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:12px;font-family:monospace;font-size:12px;color:var(--accent);overflow-x:auto;">
                    SELECT p.name, p.price, oi.orderId<br>
                    FROM order_items oi<br>
                    RIGHT JOIN products p ON oi.productId = p.id
                </div>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Product</th><th>Price</th><th>Order ID</th></tr></thead>
                        <tbody>
                            <?php while ($row = $rightJoinResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td>$<?php echo number_format($row['price'],2); ?></td>
                                <td><?php echo $row['orderId'] ? '#'.$row['orderId'] : '<em style="color:var(--muted);">Never ordered</em>'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FULL OUTER JOIN (Simulated) -->
            <div class="profile-section" style="margin-bottom:16px;">
                <h3>FULL OUTER JOIN — Simulated via UNION (MySQL)</h3>
                <p style="color:var(--muted);font-size:13px;margin-bottom:12px;">
                    MySQL doesn't natively support FULL OUTER JOIN. Simulated by combining LEFT JOIN + RIGHT JOIN with UNION.
                    Shows ALL users and ALL orders, including unmatched from both sides.
                </p>
                <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:12px;font-family:monospace;font-size:12px;color:var(--accent);overflow-x:auto;">
                    SELECT u.firstName, u.lastName, o.id, o.totalAmount FROM users u LEFT JOIN orders o ON u.id = o.userId<br>
                    UNION<br>
                    SELECT u.firstName, u.lastName, o.id, o.totalAmount FROM users u RIGHT JOIN orders o ON u.id = o.userId WHERE u.id IS NULL
                </div>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Customer</th><th>Order ID</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php while ($row = $fullOuterJoinResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['firstName'] ? htmlspecialchars($row['firstName'].' '.$row['lastName']) : '<em style="color:var(--muted);">Deleted user</em>'; ?></td>
                                <td><?php echo $row['orderId'] ? '#'.$row['orderId'] : '<em style="color:var(--muted);">No orders</em>'; ?></td>
                                <td><?php echo $row['totalAmount'] ? '$'.number_format($row['totalAmount'],2) : '—'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Charts -->
    <script>
    new Chart(document.getElementById('chartBar').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($barLabels); ?>,
            datasets: [{ label:'Qty Ordered', data:<?php echo json_encode($barData); ?>, backgroundColor:['#e85d04','#dc2f02','#f48c06','#e76f51','#40916c','#457b9d','#e9c46a','#9b2226'], borderRadius:6, borderSkipped:false }]
        },
        options: { responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#6b6560'}}, x:{grid:{display:false},ticks:{color:'#6b6560',maxRotation:45}} } }
    });

    new Chart(document.getElementById('chartDoughnut').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($statusLabels); ?>,
            datasets: [{ data:<?php echo json_encode($statusData); ?>, backgroundColor:['#e9c46a','#457b9d','#40916c'], borderWidth:0, hoverOffset:8 }]
        },
        options: { responsive:true, cutout:'65%', plugins:{legend:{position:'bottom',labels:{color:'#c9c5bd',padding:16}}} }
    });

    new Chart(document.getElementById('chartTrend').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trendLabels); ?>,
            datasets: [{ label:'Orders', data:<?php echo json_encode($trendData); ?>, borderColor:'#e85d04', backgroundColor:'rgba(232,93,4,0.08)', fill:true, tension:0.4, pointRadius:2, pointHoverRadius:6, pointBackgroundColor:'#e85d04' }]
        },
        options: { responsive:true, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true,grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#6b6560',stepSize:1}}, x:{grid:{display:false},ticks:{color:'#6b6560',maxTicksLimit:10}} } }
    });
    </script>
</body>
</html>