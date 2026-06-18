<?php
session_start();
require "db.php";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Please enter your email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"]   = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            header("Location: dashboard.php"); exit;
        } else {
            $error = "Incorrect email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — RentVille</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <a href="index.php" class="auth-brand">🏘️ RentVille</a>
        <h2>Welcome back</h2>
        <p>Login to manage your tenants</p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if (isset($_GET['registered'])): ?><div class="alert alert-success">Account created! You can now log in.</div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p class="text-center mt">No account? <a href="register.php" style="color:#D97706;font-weight:600;">Register</a></p>
    </div>
</div>
</body>
</html>
