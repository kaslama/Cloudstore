<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['file_id'])) {
    header('Location: mydrive.php');  // Or wherever your drive page is
    exit();
}

$user_id = $_SESSION['user_id'];
$file_id = (int)$_POST['file_id'];

// Validate file ownership and existence
$stmt = $pdo->prepare("SELECT file_path FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    $_SESSION['error'] = "File not found or already deleted.";
    header('Location: mydrive.php');
    exit();
}

// Soft delete: mark is_deleted = 1
$stmt = $pdo->prepare("UPDATE files SET is_deleted = 1 WHERE id = ? AND user_id = ?");
if ($stmt->execute([$file_id, $user_id])) {
    $_SESSION['message'] = "File deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete file.";
}

header('Location: dashboard.php');
exit();
?>
