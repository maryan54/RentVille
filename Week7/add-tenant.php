<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['superadmin', 'landlord'])) {
    header("Location: login.php"); exit;
}

$user_id = $_SESSION['user_id'];
$error   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name       = trim($_POST["name"]);
    $phone      = trim($_POST["phone"]);
    $email      = trim($_POST["email"]);
    $property   = trim($_POST["property"]);
    $rent       = (float)$_POST["rent_amount"];
    $lease_start = $_POST["lease_start"];
    $lease_end  = !empty($_POST["lease_end"]) ? $_POST["lease_end"] : null;
    $notes      = trim($_POST["notes"]);

    if (empty($name) || empty($phone) || empty($property) || $rent < 1 || empty($lease_start)) {
        $error = "Name, phone, property, rent and lease start are required.";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tenants (user_id, name, phone, email, property, rent_amount, lease_start, lease_end, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $name, $phone, $email, $property, $rent, $lease_start, $lease_end, $notes]);
        header("Location: " . ($_SESSION["role"] === "superadmin" ? "superadmin-dashboard.php" : "landlord-dashboard.php")); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tenant — RentVille</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <a href="landlord-dashboard.php" class="brand">🏘️ RentVille</a>
    <div class="nav-links">
        <a href="landlord-dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page" style="max-width:540px;">
    <div class="card">
        <h2>Add a Tenant</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div style="display:flex;gap:12px;">
                <div class="form-group" style="flex:1;">
                    <label>Phone *</label>
                    <input type="text" name="phone" required
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                           placeholder="+254 7XX XXX XXX">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Email</label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Property *</label>
                <input type="text" name="property" required maxlength="100"
                       value="<?= htmlspecialchars($_POST['property'] ?? '') ?>"
                       placeholder="e.g. Sunset Apartment A1">
            </div>
            <div class="form-group">
                <label>Monthly Rent (KSh) *</label>
                <input type="number" name="rent_amount" required min="1"
                       value="<?= htmlspecialchars($_POST['rent_amount'] ?? '') ?>"
                       placeholder="15000">
            </div>
            <div style="display:flex;gap:12px;">
                <div class="form-group" style="flex:1;">
                    <label>Lease Start *</label>
                    <input type="date" name="lease_start" required
                           value="<?= htmlspecialchars($_POST['lease_start'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Lease End</label>
                    <input type="date" name="lease_end"
                           value="<?= htmlspecialchars($_POST['lease_end'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" maxlength="500" rows="3"
                          placeholder="Emergency contact, special terms..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px;">
                <a href="landlord-dashboard.php" class="btn btn-outline" style="flex:1;text-align:center;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="flex:2;">Add Tenant</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
