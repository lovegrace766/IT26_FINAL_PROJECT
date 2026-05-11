<?php
// ============================================================
// profile.php — User Profile, Avatar Selection & Name Change
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
 $userFirst = $_SESSION['user_first'] ?? 'User';
 $userLast = $_SESSION['user_last'] ?? '';
 $userAvatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=150&h=150&fit=crop';
 $message = '';

// ---- Handle Avatar Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_avatar'])) {
    $newAvatar = trim($_POST['new_avatar']);
    
    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->bind_param("si", $newAvatar, $userId);
    if ($stmt->execute()) {
        $_SESSION['user_avatar'] = $newAvatar;
        $userAvatar = $newAvatar;
        $message = "Avatar updated successfully!";
    }
}

// ---- Handle Name/Username Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newFirst = trim($_POST['firstName'] ?? '');
    $newLast = trim($_POST['lastName'] ?? '');
    
    if (!empty($newFirst)) {
        $stmt = $conn->prepare("UPDATE users SET firstName = ?, lastName = ? WHERE id = ?");
        $stmt->bind_param("ssi", $newFirst, $newLast, $userId);
        if ($stmt->execute()) {
            // Update the session variables immediately so the dashboard changes
            $_SESSION['user_first'] = $newFirst;
            $_SESSION['user_last'] = $newLast;
            $userFirst = $newFirst;
            $userLast = $newLast;
            $message = "Profile name updated successfully!";
        }
    } else {
        $message = "First name cannot be empty.";
    }
}

// Predefined Cute Food Avatars
 $predefinedAvatars = [
    'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=150&h=150&fit=crop', // Donut
    'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=150&h=150&fit=crop', // Pancakes
    'https://images.unsplash.com/photo-1550547660-d9450f859349?w=150&h=150&fit=crop', // Burger
    'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=150&h=150&fit=crop', // Pizza
    'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=150&h=150&fit=crop', // Ice cream
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CaterCloud — Profile</title>
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
                <a href="profile.php" class="nav-item active"><i class="fas fa-user"></i><span class="nav-label">Profile</span></a>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
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
                <div><h1>My Profile</h1><p>Manage your account settings</p></div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="auth-success" style="margin-bottom:20px; display:block; text-align:center;">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div style="max-width: 600px; margin: 0 auto;">
                
                <!-- Avatar Section -->
                <div class="profile-section" style="text-align: center;">
                    <img src="<?php echo htmlspecialchars($userAvatar); ?>" style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:4px solid var(--accent); margin-bottom:16px; box-shadow: var(--shadow-lg);">
                    <h2><?php echo htmlspecialchars($userFirst . ' ' . $userLast); ?></h2>
                    <p style="color:var(--muted); margin-bottom:32px;">Select a cute food avatar below</p>

                    <form method="POST" action="profile.php" id="avatarForm">
                        <input type="hidden" name="new_avatar" id="avatarInput" value="<?php echo htmlspecialchars($userAvatar); ?>">
                        <div class="avatar-grid">
                            <?php foreach ($predefinedAvatars as $av): ?>
                                <img src="<?php echo $av; ?>" 
                                     class="avatar-option <?php echo ($av === $userAvatar) ? 'selected' : ''; ?>" 
                                     onclick="selectAvatar('<?php echo $av; ?>', this)">
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top: 32px; width: 100%;">
                            Save Avatar
                        </button>
                    </form>
                </div>

                <!-- Edit Name Section -->
                <div class="profile-section" style="margin-top: 24px;">
                    <h3 style="margin-bottom: 16px; font-family: 'Outfit', sans-serif;"><i class="fas fa-pen-to-square" style="margin-right:8px; color:var(--accent);"></i>Edit Profile Details</h3>
                    <form method="POST" action="profile.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" class="form-input" name="firstName" value="<?php echo htmlspecialchars($userFirst); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" class="form-input" name="lastName" value="<?php echo htmlspecialchars($userLast); ?>">
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn-secondary" style="width: 100%; margin-top:8px; cursor:pointer;">
                            Update Name
                        </button>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
    // JavaScript to handle avatar selection UI
    function selectAvatar(url, element) {
        document.getElementById('avatarInput').value = url;
        // Remove selected class from all
        document.querySelectorAll('.avatar-option').forEach(el => el.classList.remove('selected'));
        // Add to clicked
        element.classList.add('selected');
    }
    </script>
</body>
</html>