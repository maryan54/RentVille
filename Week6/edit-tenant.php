<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$error     = "";
$id        = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    header("Location: dashboard.php"); exit;
}

// Fetch existing tenant (must belong to this user)
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$tenant = $stmt->fetch();

if (!$tenant) {
    header("Location: dashboard.php"); exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name        = trim($_POST["name"]);
    $phone       = trim($_POST["phone"]);
    $email       = trim($_POST["email"]);
    $property    = trim($_POST["property"]);
    $rent        = (float)$_POST["rent_amount"];
    $lease_start = $_POST["lease_start"];
    $lease_end   = !empty($_POST["lease_end"]) ? $_POST["lease_end"] : null;
    $notes       = trim($_POST["notes"]);
    $status      = $_POST["status"];

    if (empty($name) || empty($phone) || empty($property) || $rent < 1 || empty($lease_start)) {
        $error = "Name, phone, property, rent and lease start are required.";
    } elseif (!in_array($status, ['Active','Inactive'])) {
        $error = "Invalid status.";
    } else {
        $stmt = $pdo->prepare(
            "UPDATE tenants
             SET name=?, phone=?, email=?, property=?, rent_amount=?,
                 lease_start=?, lease_end=?, notes=?, status=?
             WHERE id=? AND user_id=?"
        );
        $stmt->execute([
            $name, $phone, $email, $property, $rent,
            $lease_start, $lease_end, $notes, $status,
            $id, $user_id
        ]);
        header("Location: dashboard.php"); exit;
    }

    $tenant = array_merge($tenant, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tenant — RentVille</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-brand">🏘️ RentVille</a>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add-tenant.php">Add Tenant</a></li>
    </ul>
    <div class="sidebar-user">
        <strong><?= htmlspecialchars($user_name) ?></strong>
        <a href="logout.php">Sign out</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div>
            <h1>Edit Tenant</h1>
            <p>Updating record for <strong><?= htmlspecialchars($tenant['name']) ?></strong></p>
        </div>
        <a href="dashboard.php" class="btn btn-soft">Back</a>
    </div>

    <div class="card" style="max-width:560px;">
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?= htmlspecialchars($tenant['name']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" name="phone" required
                           value="<?= htmlspecialchars($tenant['phone']) ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($tenant['email']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Property *</label>
                <input type="text" name="property" required maxlength="100"
                       value="<?= htmlspecialchars($tenant['property']) ?>">
            </div>
            <div class="form-group">
                <label>Monthly Rent (KSh) *</label>
                <input type="number" name="rent_amount" required min="1"
                       value="<?= htmlspecialchars($tenant['rent_amount']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Lease Start *</label>
                    <input type="date" name="lease_start" required
                           value="<?= htmlspecialchars($tenant['lease_start']) ?>">
                </div>
                <div class="form-group">
                    <label>Lease End</label>
                    <input type="date" name="lease_end"
                           value="<?= htmlspecialchars($tenant['lease_end'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                    <option value="Active"   <?= $tenant['status'] === 'Active'   ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $tenant['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" maxlength="500" rows="3"><?= htmlspecialchars($tenant['notes'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <a href="dashboard.php" class="btn btn-soft" style="flex:1;text-align:center;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="flex:2;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
