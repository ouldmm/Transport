<?php
// update-status.php
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$id     = (int)($_GET['id'] ?? 0);
$status = sanitize($_GET['status'] ?? '');
$back   = sanitize($_GET['back'] ?? 'dashboard');
$allowed = ['confirmed','cancelled','completed','pending'];

if ($id && in_array($status, $allowed)) {
    try {
        $db = getDB();
        $db->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$status,$id]);
    } catch (Exception $e) { /* demo */ }
}

header("Location: " . ($back === 'bookings' ? 'bookings.php' : 'dashboard.php') . "?updated=1");
exit;
?>
