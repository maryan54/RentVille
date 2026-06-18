<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php"); exit;
}

$user_name = $_SESSION['user_name'];
$success   = "";
$error     = "";

// Create new user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    $allowed_roles = ['superadmin', 'landlord', 'tenant'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (!in_array($role, $allowed_roles)) {
        $error = "Invalid role selected.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "An account with that email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role]);
            $success = "User '{$name}' created successfully as " . ucfirst($role) . ".";
        }
    }
}

// Delete user
if (isset($_GET['delete_user'])) {
    $del_id = (int)$_GET['delete_user'];
    if ($del_id === $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
        header("Location: superadmin-dashboard.php?deleted=1"); exit;
    }
}

if (isset($_GET['deleted'])) $success = "User deleted successfully.";

// Fetch all users
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();

// Fetch all tenants system-wide
$tenants = $pdo->query(
    "SELECT t.*, u.name AS landlord_name
     FROM tenants t
     JOIN users u ON t.user_id = u.id
     ORDER BY t.created_at DESC"
)->fetchAll();

$total_users   = count($users);
$total_tenants = count($tenants);
$active        = count(array_filter($tenants, fn($t) => $t['status'] === 'Active'));
$total_rent    = array_sum(array_map(fn($t) => $t['status'] === 'Active' ? $t['rent_amount'] : 0, $tenants));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — RentVille</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <a href="superadmin-dashboard.php" class="brand">🏘️ RentVille</a>
    <div class="nav-links">
        <span style="color:rgba(255,255,255,0.85);font-size:13px;margin-right:8px;">
            Super Admin: <?= htmlspecialchars($user_name) ?>
        </span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page">

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h1>Admin Dashboard</h1>
        <p>Full system control — manage users and monitor all properties.</p>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="num"><?= $total_users ?></div>
            <div class="label">Total Users</div>
        </div>
        <div class="stat">
            <div class="num"><?= $total_tenants ?></div>
            <div class="label">Total Tenants</div>
        </div>
        <div class="stat">
            <div class="num"><?= $active ?></div>
            <div class="label">Active Leases</div>
        </div>
        <div class="stat" style="border-top-color:#F59E0B;">
            <div class="num" style="color:#F59E0B;">KSh <?= number_format($total_rent) ?></div>
            <div class="label">Total Monthly Rent</div>
        </div>
    </div>

    <!-- Create User -->
    <div class="card">
        <h2>Create Account</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_user">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required maxlength="100"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6"
                           placeholder="Min. 6 characters">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="superadmin">Super Admin</option>
                        <option value="landlord">Landlord</option>
                        <option value="tenant">Tenant</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
    </div>

    <!-- Users List -->
    <div class="card">
        <h2>All Users</h2>
        <?php if (empty($users)): ?>
            <p style="color:#666;text-align:center;padding:16px 0;">No users found.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?>
                            <?php if ($u['id'] === $_SESSION['user_id']): ?>
                                <span class="badge badge-active" style="font-size:10px;">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td style="font-size:13px;color:#6B7280;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <a href="superadmin-dashboard.php?delete_user=<?= $u['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?')">
                                    Delete
                                </a>
                            <?php else: ?>
                                <span style="font-size:12px;color:#9CA3AF;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- All Tenants -->
    <div class="card">
        <h2>All Tenants (System-wide)</h2>
        <?php if (empty($tenants)): ?>
            <p style="color:#666;text-align:center;padding:24px 0;">No tenant records yet.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Tenant</th><th>Property</th><th>Rent</th><th>Landlord</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['name']) ?></td>
                        <td><?= htmlspecialchars($t['property']) ?></td>
                        <td>KSh <?= number_format($t['rent_amount']) ?></td>
                        <td style="font-size:13px;"><?= htmlspecialchars($t['landlord_name']) ?></td>
                        <td>
                            <span class="badge <?= $t['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $t['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
