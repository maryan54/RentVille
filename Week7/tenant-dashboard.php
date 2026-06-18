<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header("Location: login.php"); exit;
}

$user_name  = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'] ?? '';

// Re-fetch email if not in session (older sessions)
if (empty($user_email)) {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $user_email = $row['email'] ?? '';
}

// Find tenant record(s) matching this account's email
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE email = ? ORDER BY created_at DESC");
$stmt->execute([$user_email]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Lease — RentVille</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <a href="tenant-dashboard.php" class="brand">🏘️ RentVille</a>
    <div class="nav-links">
        <span style="color:rgba(255,255,255,0.85);font-size:13px;margin-right:8px;">
            <?= htmlspecialchars($user_name) ?>
        </span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page">

    <div class="card">
        <h1>Hello, <?= htmlspecialchars($user_name) ?>!</h1>
        <p>Here's your current lease and rent information.</p>
    </div>

    <div class="card">
        <h2>My Lease Details</h2>

        <?php if (empty($records)): ?>
            <p style="color:#666;text-align:center;padding:24px 0;">
                No lease record found linked to your email yet.
                Ask your landlord to add you using the email <strong><?= htmlspecialchars($user_email) ?></strong>.
            </p>
        <?php else: ?>
            <?php foreach ($records as $t): ?>
            <div class="tenant-row">
                <div class="tenant-info">
                    <h3><?= htmlspecialchars($t['property']) ?>
                        <span class="badge <?= $t['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $t['status'] ?>
                        </span>
                    </h3>
                    <p>
                        Monthly Rent: KSh <?= number_format($t['rent_amount']) ?>
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
                <!-- View-only, no actions -->
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
