<?php
session_start();
require_once __DIR__ . '/../config/db.php'; // Adjust path if needed

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_GET['file_id']) || !is_numeric($_GET['file_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit();
}

$fileId = (int)$_GET['file_id'];
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$fileId, $userId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit();
}

// Prepare data to send
$data = [
    'success' => true,
    'file' => [
        'original_name' => $file['original_name'],
        'file_size' => $file['file_size'],
        'uploaded_at' => date('M d, Y H:i', strtotime($file['uploaded_at'])),
        'folder' => $file['folder'],
        'file_hash' => $file['file_hash'],
        'is_public' => (bool)$file['is_public'],
    ]
];

echo json_encode($data);
exit();
