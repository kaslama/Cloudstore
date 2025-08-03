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

$stmt = $pdo->prepare("UPDATE files SET is_deleted = 0 WHERE user_id = ? AND is_deleted = 1");
$stmt->execute([$userId]);

header('Location: pages/trash.php?restored=1');
exit();
