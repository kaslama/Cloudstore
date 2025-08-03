<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// First, get all file paths for cleanup (optional if you want to delete from disk too)
$stmt = $pdo->prepare("SELECT file_path FROM files WHERE user_id = ? AND is_deleted = 1");
$stmt->execute([$userId]);
$files = $stmt->fetchAll();

foreach ($files as $file) {
    if (file_exists($file['file_path'])) {
        unlink($file['file_path']); // delete from disk
    }
}

// Delete from DB
$stmt = $pdo->prepare("DELETE FROM files WHERE user_id = ? AND is_deleted = 1");
$stmt->execute([$userId]);

header('Location: pages/trash.php?deleted=1');
exit();
