<?php
session_start();
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Only accept POST uploads
    header('Location: dashboard.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$messages = [];

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $messages[] = "❌ No file uploaded or upload error.";
} else {
    $file = $_FILES['file'];

    $originalName = $file['name'];
    $tmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $folder_id = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : null;

    $fileHash = md5_file($tmpName);

    // Check for duplicates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE user_id = ? AND file_hash = ? AND is_deleted = 0");
    $stmt->execute([$user_id, $fileHash]);
    $duplicateCount = $stmt->fetchColumn();

    if ($duplicateCount > 0) {
        $messages[] = "⚠️ Duplicate file detected. This file already exists.";
    } else {
        $safeName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $originalName);
        $uploadDir = __DIR__ . '/uploads/user_' . $user_id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $baseName = pathinfo($safeName, PATHINFO_FILENAME);
        $extension = pathinfo($safeName, PATHINFO_EXTENSION);

        $version = 0;
        do {
            $newFileName = time() . '_' . $baseName . ($version > 0 ? "_v$version" : '') . '.' . $extension;
            $destPath = $uploadDir . $newFileName;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE user_id = ? AND file_path = ?");
            $stmt->execute([$user_id, "user_$user_id/$newFileName"]);
            $count = $stmt->fetchColumn();

            $version++;
        } while ($count > 0);

        if (move_uploaded_file($tmpName, $destPath)) {
            $relativePath = "user_$user_id/$newFileName";

            $stmt = $pdo->prepare("INSERT INTO files (user_id, original_name, file_path, file_size, uploaded_at, version, is_deleted, folder_id, file_hash) VALUES (?, ?, ?, ?, NOW(), ?, 0, ?, ?)");
            $stmt->execute([$user_id, $originalName, $relativePath, $fileSize, 1, $folder_id, $fileHash]);

            $messages[] = "✅ File uploaded successfully!";
        } else {
            $messages[] = "❌ Failed to move uploaded file.";
        }
    }
}

$_SESSION['message'] = implode("<br>", array_filter($messages, fn($m) => str_starts_with($m, "✅")));
$_SESSION['error'] = implode("<br>", array_filter($messages, fn($m) => str_starts_with($m, "❌") || str_starts_with($m, "⚠️")));

header('Location:dashboard.php');
exit();
