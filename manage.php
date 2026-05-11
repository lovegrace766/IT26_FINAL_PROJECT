<?php
session_start(); // CRITICAL FIX
require_once 'db_connection.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header("Location: dashboard.php"); exit(); }

 $message = '';

// SAVE PRODUCT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name = trim($_POST['prod_name']); $desc = trim($_POST['prod_desc']);
    $price = (float)$_POST['prod_price']; $category = trim($_POST['prod_category']);
    $imgSeed = trim($_POST['prod_imgseed']) ?: 'food'.time();
    $imagePath = '';

    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['prod_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $imagePath = 'uploads/product_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['prod_image']['tmp_name'], $imagePath);
        }
    }

    if ($id > 0) {
        $sql = "UPDATE products SET name=?, description=?, price=?, category=?, imgSeed=?";
        $types = "ssdss"; $params = [&$name, &$desc, &$price, &$category, &$imgSeed];
        if (!empty($imagePath)) { $sql .= ", image=?"; $types .= "s"; $params[] = &$imagePath; }
        $sql .= " WHERE id=?"; $types .= "i"; $params[] = &$id;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $message = 'Product updated.';
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, imgSeed, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsss", $name, $desc, $price, $category, $imgSeed, $imagePath);
        $stmt->execute();
        $message = 'Product added.';
    }
}

// DELETES (Keep your existing delete logic here, just ensure session_start is at top)
if (isset($_GET['delete_product'])) { $id=(int)$_GET['delete_product']; $conn->query("DELETE FROM products WHERE id=$id"); header("Location: manage.php"); exit(); }
if (isset($_GET['delete_user'])) { $id=(int)$_GET['delete_user']; if($id!=$_SESSION['user_id']) $conn->query("DELETE FROM users WHERE id=$id"); header("Location: manage.php"); exit(); }
if (isset($_GET['toggle_role'])) { $id=(int)$_GET['toggle_role']; $conn->query("UPDATE users SET role = IF(role='admin','customer','admin') WHERE id=$id"); header("Location: manage.php"); exit(); }
if (isset($_GET['advance_order'])) { $id=(int)$_GET['advance_order']; $conn->query("UPDATE orders SET status = CASE WHEN status='pending' THEN 'processing' WHEN status='processing' THEN 'completed' ELSE status END WHERE id=$id"); header("Location: manage.php"); exit(); }
if (isset($_GET['delete_order'])) { $id=(int)$_GET['delete_order']; $conn->query("DELETE FROM orders WHERE id=$id"); header("Location: manage.php"); exit(); }

 $products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
 $users = $conn->query("SELECT * FROM users ORDER BY id")->fetch_all(MYSQLI_ASSOC);
 $ordersResult = $conn->query("SELECT o.id, o.totalAmount, o.status, o.createdAt, u.firstName, u.lastName FROM orders o INNER JOIN users u ON o.userId = u.id ORDER BY o.createdAt DESC");
 $orders = $ordersResult->fetch_all(MYSQLI_ASSOC);

 $editProduct = null;
if (isset($_GET['edit_product'])) { $eid=(int)$_GET['edit_product']; $stmt=$conn->prepare("SELECT * FROM products WHERE id=?"); $stmt->bind_param("i",$eid); $stmt->execute(); $editProduct=$stmt->get_result()->fetch_assoc(); }
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>FlameGrill — Manage</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body><div class="dash-layout">
    <nav class="sidebar"><!-- PASTE YOUR SIDEBAR HTML HERE OR USE INCLUDE --></nav>
    <main class="main-content">
        <div class="topbar"><div class="topbar-left"><h1>Manage System</h1><p>CRUD & Image Upload</p></div></div>
        <?php if(!empty($message)) echo "<div class='auth-success'>$message</div>"; ?>
        
        <div class="profile-section" style="margin-bottom:24px;">
            <h3><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
            <form method="POST" action="manage.php" enctype="multipart/form-data">
                <input type="hidden" name="save_product" value="1">
                <input type="hidden" name="product_id" value="<?php echo $editProduct ? $editProduct['id'] : ''; ?>">
                <div class="form-row">
                    <div class="form-group"><label>Product Name</label><input type="text" class="form-input" name="prod_name" value="<?php echo $editProduct ? htmlspecialchars($editProduct['name']) : ''; ?>" required></div>
                    <div class="form-group"><label>Category</label><input type="text" class="form-input" name="prod_category" value="<?php echo $editProduct ? htmlspecialchars($editProduct['category']) : ''; ?>" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Price ($)</label><input type="number" class="form-input" name="prod_price" step="0.01" value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>" required></div>
                    <div class="form-group"><label>Product Image</label><input type="file" class="form-input" name="prod_image" accept="image/*"></div>
                </div>
                <div class="form-group"><label>Description</label><input type="text" class="form-input" name="prod_desc" value="<?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?>"></div>
                <button type="submit" class="btn-primary"><?php echo $editProduct ? 'Update' : 'Add'; ?> Product</button>
            </form>
        </div>

        <div class="data-table-wrap">
            <div class="data-table-header"><h3>Products</h3></div>
            <table class="data-table">
                <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($products as $p): $img=!empty($p['image'])?$p['image']:"https://picsum.photos/seed/".$p['imgSeed']."/60/60.jpg"; ?>
                    <tr>
                        <td><img src="<?php echo $img; ?>" alt=""></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                        <td>$<?php echo number_format($p['price'],2); ?></td>
                        <td>
                            <a href="manage.php?edit_product=<?php echo $p['id']; ?>" class="btn-sm" style="background:rgba(232,93,4,0.1);color:var(--accent);"><i class="fas fa-pen"></i></a>
                            <a href="manage.php?delete_product=<?php echo $p['id']; ?>" class="btn-sm" style="background:rgba(220,47,2,0.1);color:var(--danger);margin-left:4px;" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div></body></html>