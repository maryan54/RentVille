<?php
session_start();
require "db.php";

if (isset($_SESSION['user_id'])) {
    header("Location: tenant-dashboard.php"); exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm  = $_POST["confirm"];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "An account with that email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            // Public registration always creates a 'tenant' role account
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'tenant')");
            $stmt->execute([$name, $email, $hashed]);
            header("Location: login.php?registered=1"); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — RentVille</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <a href="index.php" class="brand">🏘️ RentVille</a>
    <div class="nav-links">
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
</nav>

<div class="page-narrow">
    <div class="card">
        <h2>Create an account</h2>
        <p>Register as a tenant to view your lease and rent info</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-top:16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="margin-top:24px;">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password (min. 6 characters)</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>

        <p class="text-center mt">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

</body>
</html>
