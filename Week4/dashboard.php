<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM tenants WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['delete'], $user_id]);
    header("Location: dashboard.php"); exit;
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM tenants WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $t = $stmt->fetch();
    if ($t) {
        $new = $t['status'] === 'Active' ? 'Inactive' : 'Active';
        $pdo->prepare("UPDATE tenants SET status = ? WHERE id = ?")->execute([$new, $id]);
    }
    header("Location: dashboard.php"); exit;
}

// Fetch tenants
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$tenants = $stmt->fetchAll();

$total   = count($tenants);
$active  = count(array_filter($tenants, fn($t) => $t['status'] === 'Active'));
$total_rent = array_sum(array_map(fn($t) => $t['status'] === 'Active' ? $t['rent_amount'] : 0, $tenants));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — RentVille</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <a href="dashboard.php" class="brand">🏘️ RentVille</a>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="add-tenant.php">+ Add Tenant</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page">

    <div class="card">
        <h1>Hey, <?= htmlspecialchars($user_name) ?>!</h1>
        <p style="color:#666;margin-top:4px;">Your tenant dashboard.</p>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="num"><?= $total ?></div>
            <div class="label">Total Tenants</div>
        </div>
        <div class="stat">
            <div class="num"><?= $active ?></div>
            <div class="label">Active</div>
        </div>
        <div class="stat">
            <div class="num">KSh <?= number_format($total_rent) ?></div>
            <div class="label">Monthly Rent</div>
        </div>
        <div class="stat" style="border-top-color:#F59E0B;">
            <div class="num" style="color:#F59E0B;">
                <?= $total > 0 ? round(($active / $total) * 100) : 0 ?>%
            </div>
            <div class="label">Occupancy Rate</div>
        </div>
    </div>

    <div class="card">
        <div class="flex-between">
            <h2 style="margin:0;">Tenants</h2>
            <a href="add-tenant.php" class="btn btn-primary">+ Add Tenant</a>
        </div>

        <?php if (empty($tenants)): ?>
            <p style="color:#666;text-align:center;padding:24px 0;">
                No tenants yet. <a href="add-tenant.php">Add your first one!</a>
            </p>
        <?php else: ?>
            <?php foreach ($tenants as $t): ?>
            <div class="tenant-row">
                <div class="tenant-info">
                    <h3><?= htmlspecialchars($t['name']) ?>
                        <span class="badge <?= $t['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $t['status'] ?>
                        </span>
                    </h3>
                    <p>
                        <?= htmlspecialchars($t['phone']) ?>
                        &nbsp;·&nbsp; <?= htmlspecialchars($t['property']) ?>
                        &nbsp;·&nbsp; KSh <?= number_format($t['rent_amount']) ?>/mo
                    </p>
                    <p style="font-size:13px;color:#6B7280;">
                        Lease: <?= $t['lease_start'] ?>
                        <?php if ($t['lease_end']): ?> → <?= $t['lease_end'] ?><?php endif; ?>
                    </p>
                    <?php if ($t['notes']): ?>
                        <p style="font-size:13px;color:#888;margin-top:4px;font-style:italic;">
                            <?= htmlspecialchars($t['notes']) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="tenant-actions">
                    <a href="dashboard.php?toggle=<?= $t['id'] ?>"
                       class="btn btn-outline btn-sm">
                        <?= $t['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>
                    </a>
                    <a href="dashboard.php?delete=<?= $t['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Remove <?= htmlspecialchars($t['name'], ENT_QUOTES) ?>?')">
                        Delete
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
