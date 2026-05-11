<?php
// ============================================================
// login.php — CaterCloud Login
// ============================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'db_connection.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

 $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_first'] = $user['firstName'] ?? ($user['username'] ?? 'User');
                $_SESSION['user_last'] = $user['lastName'] ?? '';
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'customer';
                $_SESSION['user_avatar'] = $user['avatar'] ?? '';

                $stmt->close();
                header("Location: dashboard.php"); // CONNECTS TO DASHBOARD
                exit();
            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No account found with that email.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CaterCloud — Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="logo-mark">
                <i class="fas fa-cloud"></i>
            </div>
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Sign in to your CaterCloud account</p>

            <?php if (!empty($error)): ?>
                <div class="auth-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-input" id="email" name="email"
                           placeholder="you@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-input" id="password" name="password"
                           placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-primary">Sign In</button>
            </form>

            <p class="auth-link">
                Don't have an account?
                <a href="register.php">Register</a>
            </p>
        </div>
    </div>
</body>
</html>