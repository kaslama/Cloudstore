<?php
session_start();
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');

if ($query === '') {
    echo json_encode(['success' => true, 'folders' => [], 'files' => []]);
    exit();
}

$likeQuery = '%' . $query . '%';

// Search folders
$stmt = $pdo->prepare("SELECT id, name FROM folders WHERE user_id = ? AND name LIKE ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id, $likeQuery]);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Search files with folder info
$stmt = $pdo->prepare("SELECT f.id, f.original_name, f.folder_id, f.uploaded_at, fo.name AS folder_name
                       FROM files f
                       LEFT JOIN folders fo ON f.folder_id = fo.id
                       WHERE f.user_id = ? AND f.is_deleted = 0 AND f.original_name LIKE ?
                       ORDER BY f.uploaded_at DESC LIMIT 5");
$stmt->execute([$user_id, $likeQuery]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'folders' => $folders, 'files' => $files]);
