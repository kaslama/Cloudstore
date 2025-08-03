<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folder_id'])) {
    $folderId = (int)$_POST['folder_id'];
    $userId = $_SESSION['user_id'];

    // Check ownership
    $stmt = $pdo->prepare("SELECT * FROM folders WHERE id = ? AND user_id = ?");
    $stmt->execute([$folderId, $userId]);
    $folder = $stmt->fetch();

    if ($folder) {
        // Optional: Delete files in folder
        $stmt = $pdo->prepare("DELETE FROM files WHERE folder_id = ? AND user_id = ?");
        $stmt->execute([$folderId, $userId]);

        // Delete folder
        $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ? AND user_id = ?");
        $stmt->execute([$folderId, $userId]);

        header('Location: my_drive.php?msg=folder_deleted');
        exit();
    } else {
        echo "❌ Folder not found or unauthorized.";
    }
} else {
    echo "❌ Invalid request.";
}
header("Location: my_drive.php?msg=folder_deleted");
exit;
