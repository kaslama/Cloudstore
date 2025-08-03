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

$stmt = $pdo->prepare("UPDATE files SET is_deleted = 0 WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);

header('Location: pages/trash.php');
exit();
