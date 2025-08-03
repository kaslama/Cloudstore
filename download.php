<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid file ID.");
}

$file_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch file details
$stmt = $pdo->prepare("SELECT original_name, file_path FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    die("File not found or access denied.");
}

// Use full relative path as stored in DB (including subfolders)
$file_path = __DIR__ . '/uploads/' . $file['file_path'];

if (!file_exists($file_path)) {
    die("File not found on server.");
}

$allowDownload = false;

// Check login and ownership
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND (user_id = ? OR (is_public = 1 AND public_token = ?)) AND is_deleted = 0");
    $stmt->execute([$file_id, $user_id, $_GET['token'] ?? '']);
    $file = $stmt->fetch();
    $allowDownload = (bool) $file;
} else if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND is_public = 1 AND public_token = ? AND is_deleted = 0");
    $stmt->execute([$file_id, $token]);
    $file = $stmt->fetch();
    $allowDownload = (bool) $file;
}

if (!$allowDownload) {
    die('Access denied.');
}


// Send file for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Content-Length: ' . filesize($file_path));
header('Pragma: public');
header('Cache-Control: must-revalidate');

readfile($file_path);
exit;
?>
