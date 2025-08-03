<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

if (!isset($_GET['file_id']) || !is_numeric($_GET['file_id'])) {
    die('Invalid file ID.');
}

$file_id = (int)$_GET['file_id'];
$user_id = $_SESSION['user_id'];

// Get file info to delete physical file
$stmt = $pdo->prepare("SELECT file_path FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if ($file) {
    $filePath = __DIR__ . '/uploads/' . $file['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    // Delete DB record
    $delStmt = $pdo->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
    $delStmt->execute([$file_id, $user_id]);
}

header("Location: pages/trash.php");

exit();
