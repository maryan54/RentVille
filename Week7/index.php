<?php
session_start();

function roleRedirect($role) {
    if ($role === 'superadmin') return 'superadmin-dashboard.php';
    if ($role === 'landlord')   return 'landlord-dashboard.php';
    return 'tenant-dashboard.php';
}

if (isset($_SESSION['user_id'])) {
    header("Location: " . roleRedirect($_SESSION['role']));
} else {
    header("Location: login.php");
}
exit;
